<?php

namespace App\Console\Commands;

use App\Core\Modules\SetModuleState;
use Illuminate\Console\Command;

class ModulesDisableCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'modules:disable {slug : The slug of the module to disable}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Disable a platform module';

    /**
     * Execute the console command.
     */
    public function handle(SetModuleState $action): int
    {
        $slug = (string) $this->argument('slug');

        if (! $action->handle($slug, false)) {
            $this->error("Module [{$slug}] is not defined in the registry.");

            return self::FAILURE;
        }

        $this->info("Module [{$slug}] disabled.");

        return self::SUCCESS;
    }
}
