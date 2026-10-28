<?php

namespace App\Console\Commands;

use App\Mail\MailTest;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class MailTestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mail:test {email : The recipient address}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a staging email that exercises the whole mail skeleton (layout, button, divider, icons)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = (string) $this->argument('email');

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $this->error("Invalid email address [{$email}].");

            return self::FAILURE;
        }

        Mail::to($email)->send(new MailTest);

        $this->info("Staging email sent to [{$email}].");

        return self::SUCCESS;
    }
}
