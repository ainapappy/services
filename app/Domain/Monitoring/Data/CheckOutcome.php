<?php

namespace App\Domain\Monitoring\Data;

/**
 * The raw outcome of one check, before the latency threshold is applied.
 *
 * The message is written to be stored in the journal, carried by the
 * alerts and printed in the notification mail: it is safe by
 * construction — no credential, no payload, no stack trace, only a
 * short explanation of the outcome.
 */
final readonly class CheckOutcome
{
    /**
     * Create the outcome.
     */
    public function __construct(
        public string $status,
        public ?int $latencyMs,
        public ?string $message,
    ) {}
}
