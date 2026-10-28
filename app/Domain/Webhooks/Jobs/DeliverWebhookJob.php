<?php

namespace App\Domain\Webhooks\Jobs;

use App\Domain\Webhooks\Events\WebhookDeliveryFailed;
use App\Domain\Webhooks\Models\WebhookDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

/**
 * Push one delivery envelope to its endpoint, HMAC-signed, and journal
 * the outcome of the attempt on the delivery row.
 *
 * The attempts counter lives on the delivery row and is persisted BEFORE
 * the risky call: a crashed process never under-reports a real attempt,
 * and the count is read fresh from the database at every execution, so a
 * concurrent run cannot share the same number. A campaign is bounded to
 * five attempts with the backoff [30 s, 5 min, 30 min]: throwing on a
 * retryable failure hands the scheduling over to the queue.
 */
final class DeliverWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The maximum number of attempts of one delivery campaign.
     */
    final public const MAX_ATTEMPTS = 5;

    /**
     * The delays between retries, in seconds: 30 s, 5 min, then 30 min
     * for every further retry.
     */
    final public const BACKOFF_SECONDS = [30, 300, 1800];

    /**
     * The timeout of a single attempt, in seconds.
     */
    final public const TIMEOUT_SECONDS = 10;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = self::MAX_ATTEMPTS;

    /**
     * The delays between retries, consumed in order by the queue.
     *
     * @var list<int>
     */
    public $backoff = self::BACKOFF_SECONDS;

    /**
     * Create the job.
     */
    public function __construct(
        public readonly int $deliveryId,
    ) {}

    /**
     * Dispatch the job without ever breaking the caller.
     *
     * With the "sync" queue driver the first attempt runs inside this
     * call: its outcome (attempt count, response code, error) is already
     * persisted on the delivery row before the job rethrows. A failing
     * endpoint must never break the business action that fired the
     * webhook, so the rethrow is absorbed and reported here.
     */
    public static function dispatchSafely(WebhookDelivery $delivery): void
    {
        try {
            self::dispatch($delivery->getKey());
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        /** @var WebhookDelivery $delivery */
        $delivery = WebhookDelivery::query()->whereKey($this->deliveryId)->firstOrFail();
        $endpoint = $delivery->endpoint;

        // The counter is persisted before the attempt, not after: a
        // crashed process cannot lose a try, and the value re-read from
        // the database keeps concurrent runs from sharing a number.
        $delivery->forceFill(['attempts' => $delivery->attempts + 1])->save();

        // The stored envelope is re-encoded byte-exactly: the signature
        // covers what is actually sent.
        $body = (string) json_encode($delivery->payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $signature = hash_hmac('sha256', $body, $endpoint->secret);

        $startedAt = hrtime(true);

        try {
            $response = Http::timeout(self::TIMEOUT_SECONDS)
                ->withHeaders([
                    'X-Signature' => $signature,
                    'X-Event' => $delivery->event,
                    'X-Timestamp' => (string) time(),
                ])
                ->withBody($body, 'application/json')
                ->post($endpoint->url);
        } catch (ConnectionException) {
            // No HTTP answer at all: no response code, and the exception
            // wording may carry host details, so a generic safe message.
            $delivery->forceFill([
                'duration_ms' => $this->durationMs($startedAt),
                'error' => 'Host unreachable.',
            ])->save();

            $this->afterFailure($delivery);

            return;
        }

        $delivery->forceFill([
            'response_code' => $response->status(),
            'duration_ms' => $this->durationMs($startedAt),
            'error' => $response->successful() ? null : 'HTTP '.$response->status().' response.',
        ])->save();

        if ($response->successful()) {
            $delivery->forceFill([
                'status' => WebhookDelivery::STATUS_SUCCESS,
                'delivered_at' => now(),
            ])->save();

            return;
        }

        $this->afterFailure($delivery);
    }

    /**
     * Journal the failure of the current attempt, then hand the retry
     * over to the queue — or declare the delivery failed.
     */
    private function afterFailure(WebhookDelivery $delivery): void
    {
        if ($delivery->attempts >= self::MAX_ATTEMPTS) {
            // Campaign exhausted: the terminal state lives on the row and
            // the domain event lets monitoring react (phase 08). The job
            // itself ends quietly — nothing is gained by also failing the
            // queue job.
            $delivery->forceFill(['status' => WebhookDelivery::STATUS_FAILED])->save();

            WebhookDeliveryFailed::dispatch($delivery);

            return;
        }

        // Throwing schedules the next attempt with the configured backoff.
        throw new RuntimeException(sprintf(
            'Webhook delivery #%d failed on attempt %d; retry scheduled.',
            $delivery->id,
            $delivery->attempts,
        ));
    }

    /**
     * The elapsed milliseconds since the given hrtime tick.
     */
    private function durationMs(int|float $startedAt): int
    {
        return (int) round((hrtime(true) - $startedAt) / 1e6);
    }
}
