<?php

namespace App\Domain\Webhooks\Models;

use App\Domain\Webhooks\WebhookEvent;
use Database\Factories\Domain\Webhooks\Models\WebhookDeliveryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One push attempt campaign of one event envelope to one endpoint. The
 * payload is the exact envelope sent on the wire, snapshotted when the
 * domain event fired.
 *
 * @property int $id
 * @property int $webhook_endpoint_id
 * @property string $event
 * @property array{event: string, occurred_at: string, data: array<string, mixed>} $payload
 * @property string $status
 * @property int $attempts
 * @property int|null $response_code
 * @property string|null $error
 * @property int|null $duration_ms
 * @property Carbon|null $delivered_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class WebhookDelivery extends Model
{
    /** @use HasFactory<WebhookDeliveryFactory> */
    use HasFactory;

    /**
     * The delivery waits for its first attempt or a scheduled retry.
     */
    final public const STATUS_PENDING = 'pending';

    /**
     * The endpoint answered with a 2xx status.
     */
    final public const STATUS_SUCCESS = 'success';

    /**
     * The retry campaign was exhausted without a 2xx answer.
     */
    final public const STATUS_FAILED = 'failed';

    /**
     * Every possible delivery statuses.
     *
     * @var list<string>
     */
    final public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_SUCCESS,
        self::STATUS_FAILED,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'webhook_endpoint_id',
        'event',
        'payload',
        'status',
        'attempts',
        'response_code',
        'error',
        'duration_ms',
        'delivered_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'attempts' => 'integer',
            'response_code' => 'integer',
            'duration_ms' => 'integer',
            'delivered_at' => 'datetime',
        ];
    }

    /**
     * The endpoint the delivery was pushed to.
     *
     * @return BelongsTo<WebhookEndpoint, $this>
     */
    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(WebhookEndpoint::class, 'webhook_endpoint_id');
    }

    /**
     * Determine whether the retry campaign is still running or waiting.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Determine whether the delivery reached its endpoint.
     */
    public function isSuccessful(): bool
    {
        return $this->status === self::STATUS_SUCCESS;
    }

    /**
     * Determine whether the campaign was exhausted without success.
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * The canonical event this delivery carries, or null when the stored
     * name is no longer part of the canonical list.
     */
    public function webhookEvent(): ?WebhookEvent
    {
        return WebhookEvent::tryFrom($this->event);
    }
}
