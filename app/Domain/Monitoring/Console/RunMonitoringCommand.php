<?php

namespace App\Domain\Monitoring\Console;

use App\Domain\Monitoring\Actions\RunMonitors;
use Illuminate\Console\Command;

final class RunMonitoringCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitoring:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the health checks of every active monitor and journal the outcomes';

    /**
     * Execute the console command.
     */
    public function handle(RunMonitors $action): int
    {
        $action->handle();

        $this->info('Monitoring checks executed.');

        return self::SUCCESS;
    }
}
