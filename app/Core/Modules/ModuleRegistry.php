<?php

namespace App\Core\Modules;

/**
 * Canonical registry of the platform modules.
 *
 * The registry is the single source of truth for which modules exist. Their
 * activation state is persisted in the database (see App\Models\Module).
 */
class ModuleRegistry
{
    /**
     * The module definitions, indexed by slug.
     *
     * @var array<string, ModuleDefinition>
     */
    private array $modules = [];

    /**
     * Create the registry with every declared module.
     */
    public function __construct()
    {
        foreach (self::definitions() as $definition) {
            $this->modules[$definition->slug] = $definition;
        }
    }

    /**
     * Get every registered module definition.
     *
     * @return list<ModuleDefinition>
     */
    public function all(): array
    {
        return array_values($this->modules);
    }

    /**
     * Determine whether the given slug is declared in the registry.
     */
    public function has(string $slug): bool
    {
        return array_key_exists($slug, $this->modules);
    }

    /**
     * Find a module definition by slug.
     */
    public function find(string $slug): ?ModuleDefinition
    {
        return $this->modules[$slug] ?? null;
    }

    /**
     * The module definitions of the platform.
     *
     * @return list<ModuleDefinition>
     */
    private static function definitions(): array
    {
        return [
            new ModuleDefinition('landing', 'Landing', 'Public landing page.'),
            new ModuleDefinition('pricing', 'Pricing', 'Public pricing page and plans.'),
            new ModuleDefinition('gateway', 'Gateway', 'API gateway: keys, quotas and routing.'),
            new ModuleDefinition('connectors', 'Connectors', 'Third-party service connectors.'),
            new ModuleDefinition('webhooks', 'Webhooks', 'Outgoing webhook deliveries.'),
            new ModuleDefinition('monitoring', 'Monitoring', 'Uptime and availability monitoring.'),
        ];
    }
}
