<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AdminCreateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:create
        {name : The name of the administrator}
        {email : The email address of the administrator}
        {--password= : The password (a strong one is generated when omitted)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create an administrator account for the back-office';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = (string) $this->argument('name');
        $email = Str::lower((string) $this->argument('email'));

        if (User::query()->whereRaw('LOWER(email) = ?', [$email])->exists()) {
            $this->error("A user already exists with the email [{$email}].");

            return self::FAILURE;
        }

        try {
            $password = $this->resolvePassword();
        } catch (ValidationException $exception) {
            $this->error((string) $exception->getMessage());

            return self::FAILURE;
        }

        // Administrators are trusted CLI-created accounts: the email is
        // considered verified so they can reach the back-office immediately.
        User::forceCreate([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => User::ROLE_ADMIN,
            'email_verified_at' => now(),
        ]);

        $this->info("Administrator [{$name}] created with the email [{$email}].");

        if ($this->option('password') === null) {
            $this->line("Generated password: {$password}");
        }

        return self::SUCCESS;
    }

    /**
     * Resolve the password from the option or generate a strong one.
     *
     * @throws ValidationException
     */
    private function resolvePassword(): string
    {
        $password = $this->option('password') !== null
            ? (string) $this->option('password')
            : (string) Str::password(16, symbols: true);

        Validator::make(
            ['password' => $password],
            ['password' => ['required', 'string', $this->passwordRule()]],
        )->validate();

        return $password;
    }

    /**
     * The password rule applied to administrators.
     */
    private function passwordRule(): Password
    {
        return Password::defaults();
    }
}
