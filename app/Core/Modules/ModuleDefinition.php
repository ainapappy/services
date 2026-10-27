<?php

namespace App\Core\Modules;

/**
 * Static definition of a platform module, as declared in the registry.
 */
final class ModuleDefinition
{
    /**
     * Create a new module definition.
     */
    public function __construct(
        public readonly string $slug,
        public readonly string $label,
        public readonly string $description,
    ) {}
}
