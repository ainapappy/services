<?php

use App\Models\User;
use App\Notifications\Auth\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::resetPasswords());
});

test('the user model sends the custom reset password notification', function () {
    Notification::fake();

    $user = User::factory()->create();

    $user->sendPasswordResetNotification('token-123');

    Notification::assertSentTo($user, ResetPassword::class, 1);
    Notification::assertNotSentTo($user, \Illuminate\Auth\Notifications\ResetPassword::class);
});

test('the reset password mail renders the skeleton with the one-time link', function () {
    Notification::fake();

    $user = User::factory()->create();

    $user->sendPasswordResetNotification('token-123');

    Notification::assertSentTo(
        $user,
        ResetPassword::class,
        function (ResetPassword $notification) use ($user): bool {
            $mail = $notification->toMail($user);

            expect($mail->subject)->toBe('Réinitialisation de votre mot de passe')
                ->and($mail->view)->toBe('mail.reset-password')
                ->and(view($mail->view, $mail->viewData)->render())
                ->toContain('/reset-password/token-123')
                ->toContain('Choisir un nouveau mot de passe')
                ->toContain('qu\'une seule fois')
                ->toContain('linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%)')
                ->toContain('<svg')
                ->toBeEmojiFree();

            return true;
        },
    );
});
