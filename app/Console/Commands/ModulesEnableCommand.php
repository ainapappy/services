<?php

namespace App\Console\Commands;

use App\Core\Modules\SetModuleState;
use Illuminate\Console\Command;

class ModulesEnableCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'modules:enable {slug : The slug of the module to enable}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enable a platform module';

    /**
     * Execute the console command.
     */
    public function handle(SetModuleState $action): int
    {
        $slug = (string) $this->argument('slug');

        if (! $action->handle($slug, true)) {
            $this->error("Module [{$slug}] is not defined in the registry.");

            return self::FAILURE;
        }

        $this->info("Module [{$slug}] enabled.");

        return self::SUCCESS;
    }
}
