<?php

use App\Models\User;
use App\Notifications\Auth\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::emailVerification());
});

test('the user model sends the custom verification notification', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    $user->sendEmailVerificationNotification();

    Notification::assertSentTo($user, VerifyEmail::class, 1);
    Notification::assertNotSentTo($user, \Illuminate\Auth\Notifications\VerifyEmail::class);
});

test('the verification mail renders the skeleton with the signed link', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    $user->sendEmailVerificationNotification();

    Notification::assertSentTo(
        $user,
        VerifyEmail::class,
        function (VerifyEmail $notification) use ($user): bool {
            $mail = $notification->toMail($user);

            expect($mail->subject)->toBe('Vérifiez votre adresse email')
                ->and($mail->view)->toBe('mail.verify-email')
                ->and(view($mail->view, $mail->viewData)->render())
                ->toContain('/email/verify/')
                ->toContain('expires=')
                ->toContain('signature=')
                ->toContain('Vérifier mon adresse email')
                ->toContain('valable 60 minutes')
                ->toContain('linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%)')
                ->toContain('<svg')
                ->toBeEmojiFree();

            return true;
        },
    );
});

test('the fortify flow sends the custom verification notification', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->post(route('verification.send'))
        ->assertRedirect(route('home'));

    Notification::assertSentTo($user, VerifyEmail::class, 1);
});
