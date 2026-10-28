<?php

namespace App\Domain\Monitoring\Models;

use App\Domain\Connectors\Models\Connector;
use Database\Factories\Domain\Monitoring\Models\MonitorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

/**
 * A health monitor watching one platform target: a connector, the API
 * gateway health endpoint, or the recent failed webhook deliveries.
 *
 * The current state is never stored on the row: it is the latest
 * monitor_check entry, loaded efficiently through the eager-loadable
 * "latestCheck" relation.
 *
 * @property int $id
 * @property string $name
 * @property string $type
 * @property array<string, mixed> $target
 * @property int|null $latency_threshold_ms
 * @property bool $active
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Monitor extends Model
{
    /** @use HasFactory<MonitorFactory> */
    use HasFactory;

    /**
     * The monitor watches a connector row named in "target".
     */
    final public const TYPE_CONNECTOR = 'connector';

    /**
     * The monitor probes the internal /api/v1/health endpoint.
     */
    final public const TYPE_GATEWAY = 'gateway';

    /**
     * The monitor watches the recent failed webhook deliveries.
     */
    final public const TYPE_WEBHOOK = 'webhook';

    /**
     * Every supported monitor types.
     *
     * @var list<string>
     */
    final public const TYPES = [
        self::TYPE_CONNECTOR,
        self::TYPE_GATEWAY,
        self::TYPE_WEBHOOK,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'type',
        'target',
        'latency_threshold_ms',
        'active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'target' => 'array',
            'latency_threshold_ms' => 'integer',
            'active' => 'boolean',
        ];
    }

    /**
     * Normalize a monitor target by type: only a connector monitor
     * carries a connector id; the gateway and webhook types watch fixed
     * platform targets and always store an empty target. The routed
     * monitor keeps its previous connector when an update omits the id.
     *
     * @param  array<array-key, mixed>|null  $target
     * @return array<string, int>
     */
    public static function normalizeTarget(string $type, ?array $target, ?self $existing = null): array
    {
        if ($type !== self::TYPE_CONNECTOR) {
            return [];
        }

        $connectorId = $target['connectorId']
            ?? $existing?->target['connectorId']
            ?? 0;

        return ['connectorId' => is_numeric($connectorId) ? (int) $connectorId : 0];
    }

    /**
     * The journal of the checks, newest first when listed.
     *
     * @return HasMany<MonitorCheck, $this>
     */
    public function checks(): HasMany
    {
        return $this->hasMany(MonitorCheck::class)->orderByDesc('checked_at')->orderByDesc('id');
    }

    /**
     * The current state of the monitor: its most recent check, resolved
     * by checked_at (ties broken by id). Eager-loadable per monitor.
     *
     * @return HasOne<MonitorCheck, $this>
     */
    public function latestCheck(): HasOne
    {
        return $this->hasOne(MonitorCheck::class)->latestOfMany('checked_at');
    }

    /**
     * The alerts of the monitor, newest first when listed.
     *
     * @return HasMany<MonitorAlert, $this>
     */
    public function alerts(): HasMany
    {
        return $this->hasMany(MonitorAlert::class)->orderByDesc('created_at');
    }

    /**
     * The alerts not resolved yet.
     *
     * @return HasMany<MonitorAlert, $this>
     */
    public function openAlerts(): HasMany
    {
        return $this->alerts()->whereNull('resolved_at');
    }

    /**
     * The connector targeted by the monitor, or null when the target is
     * missing, points to nothing, or the type is not "connector".
     */
    public function targetConnector(): ?Connector
    {
        if ($this->type !== self::TYPE_CONNECTOR) {
            return null;
        }

        $connectorId = $this->target['connectorId'] ?? null;

        return is_numeric($connectorId)
            ? Connector::query()->find((int) $connectorId)
            : null;
    }

    /**
     * A short human summary of the watched target, for the cards and
     * the monitors list.
     */
    public function targetSummary(): string
    {
        return match ($this->type) {
            self::TYPE_CONNECTOR => $this->targetConnector()->name ?? 'Missing connector',
            self::TYPE_GATEWAY => 'Gateway health endpoint',
            self::TYPE_WEBHOOK => 'Failed webhook deliveries, last 15 minutes',
            default => throw new InvalidArgumentException("Unsupported monitor type [{$this->type}]."),
        };
    }
}
