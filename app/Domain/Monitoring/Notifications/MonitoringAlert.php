<?php

namespace App\Domain\Monitoring\Notifications;

use App\Domain\Monitoring\Models\Monitor;
use App\Domain\Monitoring\Models\MonitorAlert;
use App\Domain\Monitoring\Models\MonitorCheck;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * The alert notification of a monitoring transition: mail is the
 * primary channel, the log channel is the local fallback.
 *
 * The Blade template is a deliberately sober placeholder (plain HTML,
 * background #f3f3f1, white card, #6366f1 accent, text only, no emoji,
 * no image); the definitive mail design lands in phase 09.
 */
class MonitoringAlert extends Notification
{
    /**
     * Create the notification.
     */
    public function __construct(
        public readonly Monitor $monitor,
        public readonly MonitorCheck $check,
        public readonly MonitorAlert $alert,
    ) {}

    /**
     * Get the delivery channels.
     *
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'log'];
    }

    /**
     * Build the mail message from the provisional alert template.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->subject())
            ->view('monitoring::alert', $this->templateData());
    }

    /**
     * Get the array representation (log channel fallback).
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'monitor_id' => $this->monitor->id,
            'monitor_name' => $this->monitor->name,
            'monitor_type' => $this->monitor->type,
            'alert_id' => $this->alert->id,
            'level' => $this->alert->level,
            'status' => $this->check->status,
            'latency_ms' => $this->check->latency_ms,
            'message' => $this->check->message,
            'checked_at' => $this->check->checked_at->toISOString(),
        ];
    }

    /**
     * The explicit, emoji-free subject of the alert.
     */
    private function subject(): string
    {
        $state = $this->check->status === MonitorCheck::STATUS_DOWN ? 'Down' : 'Degraded';

        return sprintf('[Monitoring] %s: %s', $state, $this->monitor->name);
    }

    /**
     * The data handed to the Blade template.
     *
     * @return array<string, int|string|null>
     */
    private function templateData(): array
    {
        return [
            'subject' => $this->subject(),
            'monitorName' => $this->monitor->name,
            'monitorType' => $this->monitor->type,
            'level' => $this->alert->level,
            'status' => $this->check->status,
            'latencyMs' => $this->check->latency_ms,
            'message' => $this->check->message ?? 'No detail returned by the check.',
            'occurredAt' => $this->check->checked_at->toISOString(),
        ];
    }
}
