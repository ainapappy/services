<?php

use App\Domain\Webhooks\Events\WebhookDeliveryFailed;
use App\Domain\Webhooks\Models\WebhookDelivery;
use App\Domain\Webhooks\Notifications\WebhookFailureMail;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

test('a failed delivery campaign notifies the verified administrators', function () {
    Notification::fake();

    $admin = User::factory()->admin()->create();
    $unverifiedAdmin = User::factory()->unverified()->admin()->create();
    $editor = User::factory()->create();

    $delivery = WebhookDelivery::factory()->failed()->create();

    WebhookDeliveryFailed::dispatch($delivery);

    Notification::assertSentTo($admin, WebhookFailureMail::class, 1);
    Notification::assertNotSentTo($unverifiedAdmin, WebhookFailureMail::class);
    Notification::assertNotSentTo($editor, WebhookFailureMail::class);
});

test('a delivery without a readable response code is labeled explicitly', function () {
    Notification::fake();

    $admin = User::factory()->admin()->create();

    $delivery = WebhookDelivery::factory()->failed()->create([
        'response_code' => null,
        'error' => 'Host unreachable.',
    ]);

    WebhookDeliveryFailed::dispatch($delivery);

    Notification::assertSentTo(
        $admin,
        WebhookFailureMail::class,
        function (WebhookFailureMail $notification) use ($admin): bool {
            $mail = $notification->toMail($admin);
            $html = view($mail->view, $mail->viewData)->render();

            expect($mail->subject)->toContain('[Webhooks] Échec définitif :')
                ->and($html)
                ->toContain('aucune réponse')
                ->toContain('Host unreachable.')
                ->toBeEmojiFree();

            return true;
        },
    );
});

test('the webhook failure mail carries the endpoint, event, attempts and journal link', function () {
    Notification::fake();

    $admin = User::factory()->admin()->create();

    $delivery = WebhookDelivery::factory()->failed()->create();

    WebhookDeliveryFailed::dispatch($delivery);

    Notification::assertSentTo(
        $admin,
        WebhookFailureMail::class,
        function (WebhookFailureMail $notification) use ($admin, $delivery): bool {
            $mail = $notification->toMail($admin);

            expect($mail->view)->toBe('mail.webhook-failure')
                ->and($mail->viewData['endpointUrl'])->toBe($delivery->endpoint->url)
                ->and($mail->viewData['eventName'])->toBe('landing.published')
                ->and($mail->viewData['attempts'])->toBe(5)
                ->and($mail->viewData['maxAttempts'])->toBe(5)
                ->and($mail->viewData['responseCodeLabel'])->toBe('500')
                ->and($mail->viewData['errorLabel'])->toBe('HTTP 500 response.')
                ->and(view($mail->view, $mail->viewData)->render())
                ->toContain('/admin/webhooks/deliveries/')
                ->toBeEmojiFree();

            return true;
        },
    );
});

test('no administrator means no mail and no error', function () {
    Notification::fake();

    User::factory()->create();

    $delivery = WebhookDelivery::factory()->failed()->create();

    WebhookDeliveryFailed::dispatch($delivery);

    Notification::assertNothingSent();
});
