<?php

namespace App\Domain\Connectors\Models;

use Database\Factories\Domain\Connectors\Models\ConnectorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A third-party integration (WordPress, Caren, Erpen or custom service).
 *
 * The credentials live in a single encrypted payload ("encrypted:array"
 * cast): the plaintext crosses the application once, at write time, and
 * is hidden from every serialization — Inertia props, JSON, logs.
 *
 * @property int $id
 * @property string $name
 * @property string $type
 * @property string $base_url
 * @property array<string, mixed> $credentials
 * @property string $status
 * @property Carbon|null $last_tested_at
 * @property string|null $last_error
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Connector extends Model
{
    /** @use HasFactory<ConnectorFactory> */
    use HasFactory;

    /**
     * WordPress connector type.
     */
    final public const TYPE_WORDPRESS = 'wordpress';

    /**
     * Caren connector type.
     */
    final public const TYPE_CAREN = 'caren';

    /**
     * Erpen connector type.
     */
    final public const TYPE_ERPEN = 'erpen';

    /**
     * Custom connector type: free-form credentials.
     */
    final public const TYPE_CUSTOM = 'custom';

    /**
     * Every supported connector type.
     *
     * @var list<string>
     */
    final public const TYPES = [
        self::TYPE_WORDPRESS,
        self::TYPE_CAREN,
        self::TYPE_ERPEN,
        self::TYPE_CUSTOM,
    ];

    /**
     * The last connection test succeeded.
     */
    final public const STATUS_CONNECTED = 'connected';

    /**
     * The last connection test failed.
     */
    final public const STATUS_ERROR = 'error';

    /**
     * The connector has never been tested.
     */
    final public const STATUS_UNKNOWN = 'unknown';

    /**
     * Every possible connector statuses.
     *
     * @var list<string>
     */
    final public const STATUSES = [
        self::STATUS_CONNECTED,
        self::STATUS_ERROR,
        self::STATUS_UNKNOWN,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'type',
        'base_url',
        'credentials',
        'status',
        'last_tested_at',
        'last_error',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * The payload itself is hidden, so no serialization path can ever
     * carry the credentials — not even encrypted.
     *
     * @var list<string>
     */
    protected $hidden = [
        'credentials',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            // The single encryption point of the module: assigning an
            // array encrypts it (Crypt, APP_KEY); reading decrypts it.
            // The plaintext exists in memory only, never at rest.
            'credentials' => 'encrypted:array',
            'last_tested_at' => 'datetime',
        ];
    }
}
