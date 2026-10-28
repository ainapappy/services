<?php

namespace App\Domain\Webhooks\Models;

use App\Domain\Webhooks\WebhookEvent;
use Database\Factories\Domain\Webhooks\Models\WebhookEndpointFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * An outgoing webhook endpoint: a URL, a signing secret and the list of
 * canonical events it subscribed to.
 *
 * The secret is stored encrypted — never hashed — because every delivery
 * must be signed with it: it has to stay retrievable. It is hidden from
 * every serialization (Inertia props, JSON, logs) and leaves the
 * application exactly once, flashed after creation or regeneration.
 *
 * @property int $id
 * @property string $url
 * @property string $secret
 * @property list<string> $events
 * @property bool $active
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class WebhookEndpoint extends Model
{
    /** @use HasFactory<WebhookEndpointFactory> */
    use HasFactory;

    /**
     * The number of random bytes (hex-encoded) of the secret: 64
     * hexadecimal characters.
     */
    final public const SECRET_BYTES = 32;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'url',
        'secret',
        'events',
        'active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * The secret is hidden, so no serialization path can ever carry it —
     * not even encrypted.
     *
     * @var list<string>
     */
    protected $hidden = [
        'secret',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            // The single encryption point of the module: assigning the
            // plaintext secret encrypts it (Crypt, APP_KEY); reading
            // decrypts it for signing only.
            'secret' => 'encrypted',
            'events' => 'array',
            'active' => 'boolean',
        ];
    }

    /**
     * Generate a fresh high-entropy plaintext secret (64 hex characters).
     */
    public static function generateSecret(): string
    {
        return bin2hex(random_bytes(self::SECRET_BYTES));
    }

    /**
     * The deliveries pushed to this endpoint, newest first when listed.
     *
     * @return HasMany<WebhookDelivery, $this>
     */
    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class)->orderByDesc('created_at');
    }

    /**
     * Determine whether the endpoint subscribed to the given event.
     */
    public function subscribesTo(WebhookEvent $event): bool
    {
        return in_array($event->value, $this->events ?? [], true);
    }
}
