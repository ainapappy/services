<?php

namespace App\Domain\Connectors\Drivers;

use App\Domain\Connectors\Contracts\Connector;
use App\Domain\Connectors\Data\ConnectorResult;
use App\Domain\Connectors\Models\Connector as ConnectorModel;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Shared mechanics of the connection test: a HEAD request on the health
 * endpoint, with a GET fallback when the host refuses HEAD (405) or when
 * the HEAD attempt cannot complete at the transport level. Success is a
 * 2xx answer.
 *
 * The messages are safe by construction: they carry the HTTP status or a
 * generic wording — never the response body, a payload or a stack trace.
 */
abstract class BaseConnector implements Connector
{
    /**
     * The timeout of a single attempt, in seconds.
     */
    final public const TIMEOUT_SECONDS = 5;

    /**
     * pings the health endpoint of the connector: a HEAD attempt, a GET
     * fallback when the host refuses HEAD, and a safe, latency-measured
     * result. Success is a 2xx answer.
     */
    public function test(ConnectorModel $connector): ConnectorResult
    {
        $target = $this->target($connector->base_url);
        $startedAt = hrtime(true);

        try {
            $response = $this->attempt('head', $target);

            if ($response->status() === 405) {
                // Some servers answer "method not allowed" to HEAD: retry
                // with GET before declaring the host down.
                $response = $this->attempt('get', $target);
            }
        } catch (ConnectionException) {
            try {
                $response = $this->attempt('get', $target);
            } catch (ConnectionException) {
                // The exception wording may carry host resolution details:
                // replace it with a generic, safe message.
                return new ConnectorResult(
                    false,
                    ConnectorModel::STATUS_ERROR,
                    $this->latencyMs($startedAt),
                    'Host unreachable.',
                );
            }
        }

        $latencyMs = $this->latencyMs($startedAt);

        if ($response->successful()) {
            return new ConnectorResult(true, ConnectorModel::STATUS_CONNECTED, $latencyMs, null);
        }

        return new ConnectorResult(
            false,
            ConnectorModel::STATUS_ERROR,
            $latencyMs,
            'HTTP '.$response->status().' response.',
        );
    }

    /**
     * The endpoint of the health check: a type-specific path appended to
     * the connector base URL.
     */
    protected function target(string $baseUrl): string
    {
        return rtrim($baseUrl, '/').$this->healthPath();
    }

    /**
     * The path appended to the base URL for the health check.
     */
    abstract protected function healthPath(): string;

    /**
     * Run one HTTP attempt against the target.
     *
     * @param  'head'|'get'  $method
     */
    private function attempt(string $method, string $target): Response
    {
        $request = Http::timeout(self::TIMEOUT_SECONDS);

        return $method === 'head' ? $request->head($target) : $request->get($target);
    }

    /**
     * The elapsed milliseconds since the given hrtime tick.
     */
    private function latencyMs(int|float $startedAt): int
    {
        return (int) round((hrtime(true) - $startedAt) / 1e6);
    }
}
