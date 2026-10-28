<?php

namespace App\Notifications\Auth;

use Illuminate\Notifications\Messages\MailMessage;

/**
 * The password reset notification: keeps the token handling and the
 * createUrlUsing override points of the framework notification and
 * renders the project mail skeleton (French copy, no emoji, no remote
 * image).
 */
class ResetPassword extends \Illuminate\Auth\Notifications\ResetPassword
{
    /**
     * Build the mail message around the project mail skeleton.
     *
     * @param  string  $url  The one-time password reset URL.
     */
    protected function buildMailMessage($url): MailMessage
    {
        $subject = 'Réinitialisation de votre mot de passe';

        $passwords = config('auth.defaults.passwords');
        $expire = config('auth.passwords.'.(
            is_string($passwords) ? $passwords : 'users'
        ).'.expire');

        return (new MailMessage)
            ->subject($subject)
            ->view('mail.reset-password', [
                'subject' => $subject,
                'url' => $url,
                'expiresIn' => is_int($expire) ? $expire : 60,
            ]);
    }
}
