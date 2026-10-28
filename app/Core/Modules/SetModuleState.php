<?php

namespace App\Core\Modules;

use App\Models\Module;

/**
 * Persist the activation state of a module.
 */
class SetModuleState
{
    /**
     * Create the action.
     */
    public function __construct(private readonly ModuleRegistry $registry) {}

    /**
     * Enable or disable the module with the given slug.
     *
     * Returns false when the slug is not declared in the registry.
     */
    public function handle(string $slug, bool $enabled): bool
    {
        if (! $this->registry->has($slug)) {
            return false;
        }

        Module::query()->updateOrCreate(
            ['slug' => $slug],
            ['enabled' => $enabled],
        );

        return true;
    }
}
