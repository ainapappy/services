<?php

namespace Database\Seeders;

use App\Core\Modules\ModuleRegistry;
use App\Models\Module;
use Illuminate\Database\Seeder;

class ModuleSeeder extends Seeder
{
    /**
     * Seed the registry modules, disabled by default.
     */
    public function run(): void
    {
        $registry = new ModuleRegistry;

        foreach ($registry->all() as $definition) {
            Module::query()->firstOrCreate(
                ['slug' => $definition->slug],
                ['enabled' => false],
            );
        }
    }
}
