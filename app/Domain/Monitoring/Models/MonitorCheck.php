<?php

namespace App\Domain\Monitoring\Models;

use Database\Factories\Domain\Monitoring\Models\MonitorCheckFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One journal entry of one monitor check. The journal is append-only:
 * no created_at/updated_at, only the moment the check ran.
 *
 * @property int $id
 * @property int $monitor_id
 * @property string $status
 * @property int|null $latency_ms
 * @property string|null $message
 * @property Carbon $checked_at
 */
class MonitorCheck extends Model
{
    /** @use HasFactory<MonitorCheckFactory> */
    use HasFactory;

    /**
     * The journal carries no timestamps: only checked_at matters.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The target answered healthy.
     */
    final public const STATUS_UP = 'up';

    /**
     * The target answered unhealthy.
     */
    final public const STATUS_DOWN = 'down';

    /**
     * The target answered healthy but slower than the threshold.
     */
    final public const STATUS_DEGRADED = 'degraded';

    /**
     * Every possible check statuses.
     *
     * @var list<string>
     */
    final public const STATUSES = [
        self::STATUS_UP,
        self::STATUS_DOWN,
        self::STATUS_DEGRADED,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'monitor_id',
        'status',
        'latency_ms',
        'message',
        'checked_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latency_ms' => 'integer',
            'checked_at' => 'datetime',
        ];
    }

    /**
     * The monitor this entry belongs to.
     *
     * @return BelongsTo<Monitor, $this>
     */
    public function monitor(): BelongsTo
    {
        return $this->belongsTo(Monitor::class);
    }
}
