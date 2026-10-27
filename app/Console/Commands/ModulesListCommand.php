<?php

namespace App\Console\Commands;

use App\Core\Modules\ModuleDefinition;
use App\Core\Modules\ModuleRegistry;
use App\Models\Module;
use Illuminate\Console\Command;

class ModulesListCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'modules:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List the platform modules with their activation state';

    /**
     * Execute the console command.
     */
    public function handle(ModuleRegistry $registry): int
    {
        $states = Module::query()->pluck('enabled', 'slug');

        $rows = collect($registry->all())
            ->map(fn (ModuleDefinition $definition): array => [
                $definition->slug,
                $definition->label,
                $definition->description,
                (bool) $states->get($definition->slug, false) ? 'enabled' : 'disabled',
            ])
            ->all();

        $this->table(['Slug', 'Label', 'Description', 'Status'], $rows);

        return self::SUCCESS;
    }
}
