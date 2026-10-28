<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * The mail:test email: stages the whole mail skeleton (header icon,
 * eyebrow, heading, paragraphs, button, divider, predefined icons,
 * details table) so the design can be validated visually in a real
 * inbox.
 */
class MailTest extends Mailable
{
    use Queueable;

    /**
     * Get the message envelope: an explicit, emoji-free subject.
     */
    public function envelope(): Envelope
    {
        $appName = config('app.name');

        return new Envelope(
            subject: 'Email de test — '.(is_string($appName) ? $appName : 'Services'),
        );
    }

    /**
     * Get the message content: the skeleton staging view.
     */
    public function content(): Content
    {
        $appName = config('app.name');

        return new Content(
            view: 'mail.test',
            with: [
                'subject' => 'Email de test — '.(is_string($appName) ? $appName : 'Services'),
                'actionUrl' => url('/'),
                'sentAt' => now()->format('d/m/Y à H:i').' UTC',
            ],
        );
    }
}
