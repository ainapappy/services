<?php

namespace App\Domain\Monitoring\Models;

use Database\Factories\Domain\Monitoring\Models\MonitorAlertFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One alert of one monitor, opened at a worsening transition and
 * resolved when the monitor returns to a healthy state — or manually
 * from the dashboard. Alerts are only created on transitions: an
 * unchanged state never repeats one.
 *
 * @property int $id
 * @property int $monitor_id
 * @property string $message
 * @property string $level
 * @property Carbon|null $resolved_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class MonitorAlert extends Model
{
    /** @use HasFactory<MonitorAlertFactory> */
    use HasFactory;

    /**
     * The monitor answers but slower than its threshold.
     */
    final public const LEVEL_WARNING = 'warning';

    /**
     * The monitor is down.
     */
    final public const LEVEL_CRITICAL = 'critical';

    /**
     * Every possible alert levels.
     *
     * @var list<string>
     */
    final public const LEVELS = [
        self::LEVEL_WARNING,
        self::LEVEL_CRITICAL,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'monitor_id',
        'message',
        'level',
        'resolved_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }

    /**
     * The monitor this alert belongs to.
     *
     * @return BelongsTo<Monitor, $this>
     */
    public function monitor(): BelongsTo
    {
        return $this->belongsTo(Monitor::class);
    }

    /**
     * Determine whether the alert is still open.
     */
    public function isOpen(): bool
    {
        return $this->resolved_at === null;
    }
}
