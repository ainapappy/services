<?php

namespace App\Notifications\Auth;

use Illuminate\Notifications\Messages\MailMessage;

/**
 * The email verification notification: keeps the signed-URL mechanics
 * of the framework notification and renders the project mail skeleton
 * (French copy, no emoji, no remote image).
 */
class VerifyEmail extends \Illuminate\Auth\Notifications\VerifyEmail
{
    /**
     * Build the mail message around the project mail skeleton.
     *
     * @param  string  $url  The signed verification URL.
     */
    protected function buildMailMessage($url): MailMessage
    {
        $subject = 'Vérifiez votre adresse email';
        $expire = config('auth.verification.expire');

        return (new MailMessage)
            ->subject($subject)
            ->view('mail.verify-email', [
                'subject' => $subject,
                'url' => $url,
                'expiresIn' => is_int($expire) ? $expire : 60,
            ]);
    }
}
