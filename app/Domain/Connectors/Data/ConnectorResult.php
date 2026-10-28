<?php

namespace App\Domain\Connectors\Data;

/**
 * The outcome of a connection test.
 *
 * The message is written to be stored as "last_error" and to cross the
 * wire as flash data: it is safe by construction — no credential, no
 * payload, no stack trace, only a short explanation of the failure.
 */
final readonly class ConnectorResult
{
    /**
     * Create the result.
     */
    public function __construct(
        public bool $success,
        public string $status,
        public int $latencyMs,
        public ?string $message,
    ) {}

    /**
     * The shape flashed to the back-office after a test.
     *
     * @return array{status: string, latencyMs: int, message: string|null}
     */
    public function toFlash(): array
    {
        return [
            'status' => $this->status,
            'latencyMs' => $this->latencyMs,
            'message' => $this->message,
        ];
    }
}
