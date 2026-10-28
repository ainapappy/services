<?php

use App\Mail\MailTest;
use Illuminate\Support\Facades\Mail;

test('mail:test sends the skeleton staging email', function () {
    Mail::fake();

    $this->artisan('mail:test', ['email' => 'staging@services.test'])->assertSuccessful();

    Mail::assertSent(MailTest::class, 1);
    Mail::assertSent(MailTest::class, function (MailTest $mailable): bool {
        $html = $mailable->render();

        expect($mailable->subject)->toBe('Email de test — '.config('app.name'))
            ->and($html)
            ->toContain('Bouton d&#039;exemple')
            ->toContain('linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%)')
            ->toContain('layout, button, divider, icon')
            ->toContain('<svg')
            ->toBeEmojiFree();

        return true;
    });
});

test('mail:test rejects an invalid email address', function () {
    Mail::fake();

    $this->artisan('mail:test', ['email' => 'pas-un-email'])
        ->assertFailed();

    Mail::assertNothingSent();
});
