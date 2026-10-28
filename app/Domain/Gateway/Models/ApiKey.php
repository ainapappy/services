<?php

namespace App\Domain\Gateway\Models;

use Database\Factories\Domain\Gateway\Models\ApiKeyFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * An API key of the gateway. Only the sha256 hash of the secret is
 * persisted: the plaintext secret crosses the application once, at
 * creation, and can never be recovered afterwards.
 *
 * @property int $id
 * @property string $name
 * @property string $prefix
 * @property string $key_hash
 * @property list<string> $abilities
 * @property int|null $rate_limit
 * @property Carbon|null $last_used_at
 * @property Carbon|null $revoked_at
 * @property Carbon|null $expires_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class ApiKey extends Model
{
    /** @use HasFactory<ApiKeyFactory> */
    use HasFactory;

    /**
     * The prefix every plaintext secret starts with.
     */
    final public const SECRET_PREFIX = 'sk_';

    /**
     * The number of random bytes (hex-encoded) after the secret prefix.
     */
    final public const SECRET_BYTES = 20;

    /**
     * The number of plaintext characters kept visible in the prefix.
     */
    final public const PREFIX_LENGTH = 8;

    /**
     * The rate limit applied when a key carries no per-key override.
     */
    final public const DEFAULT_RATE_LIMIT = 60;

    /**
     * Ability granting read access to the enabled services.
     */
    final public const ABILITY_SERVICES_READ = 'services:read';

    /**
     * Ability granting read access to the published landing pages.
     */
    final public const ABILITY_LANDINGS_READ = 'landings:read';

    /**
     * Ability granting read access to the configured connectors.
     */
    final public const ABILITY_CONNECTORS_READ = 'connectors:read';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'prefix',
        'key_hash',
        'abilities',
        'rate_limit',
        'last_used_at',
        'revoked_at',
        'expires_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'key_hash',
    ];

    /**
     * Generate a fresh high-entropy plaintext secret ("sk_" + 40 hex chars).
     */
    public static function generateSecret(): string
    {
        return self::SECRET_PREFIX.bin2hex(random_bytes(self::SECRET_BYTES));
    }

    /**
     * Hash a plaintext secret the way keys are stored.
     */
    public static function hashSecret(string $secret): string
    {
        return hash('sha256', $secret);
    }

    /**
     * Extract the displayable prefix of a plaintext secret.
     */
    public static function prefixFor(string $secret): string
    {
        return substr($secret, 0, self::PREFIX_LENGTH);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'abilities' => 'array',
            'rate_limit' => 'integer',
            'last_used_at' => 'datetime',
            'revoked_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Determine whether the key has been revoked.
     */
    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    /**
     * Determine whether the key has passed its expiry date.
     */
    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Determine whether the key is usable: neither revoked nor expired.
     */
    public function isActive(): bool
    {
        return ! $this->isRevoked() && ! $this->isExpired();
    }

    /**
     * Determine whether the key carries the given ability.
     */
    public function hasAbility(string $ability): bool
    {
        return in_array($ability, $this->abilities ?? [], true);
    }

    /**
     * The rate limit in requests per minute, honoring the per-key override.
     */
    public function rateLimit(): int
    {
        return $this->rate_limit ?? self::DEFAULT_RATE_LIMIT;
    }

    /**
     * Scope a query to only include active keys.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->whereNull('revoked_at')
            ->where(fn (Builder $query): Builder => $query
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>', now()));
    }
}
