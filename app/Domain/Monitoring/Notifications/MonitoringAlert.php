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
 * The mail renders the project mail skeleton (mail.monitoring-alert):
 * status, monitor, latency, message and date, with the alert icon —
 * French copy, no emoji, no remote image.
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
     * Build the mail message from the project mail skeleton.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->subject())
            ->view('mail.monitoring-alert', $this->templateData());
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
        return sprintf('[Monitoring] %s : %s', $this->statusLabel(), $this->monitor->name);
    }

    /**
     * The data handed to the Blade view.
     *
     * @return array<string, int|string>
     */
    private function templateData(): array
    {
        return [
            'subject' => $this->subject(),
            'monitorName' => $this->monitor->name,
            'monitorTypeLabel' => $this->monitorTypeLabel(),
            'statusLabel' => $this->statusLabel(),
            'levelLabel' => $this->levelLabel(),
            'latencyLabel' => $this->latencyLabel(),
            'message' => $this->check->message ?? 'Aucun détail retourné par le contrôle.',
            'occurredAt' => $this->check->checked_at->format('d/m/Y à H:i').' UTC',
            'actionUrl' => route('admin.monitoring.dashboard'),
        ];
    }

    /**
     * The French label of the check status.
     */
    private function statusLabel(): string
    {
        return match ($this->check->status) {
            MonitorCheck::STATUS_DOWN => 'En panne',
            MonitorCheck::STATUS_DEGRADED => 'Dégradé',
            default => 'Opérationnel',
        };
    }

    /**
     * The French label of the alert level.
     */
    private function levelLabel(): string
    {
        return match ($this->alert->level) {
            MonitorAlert::LEVEL_WARNING => 'Avertissement',
            MonitorAlert::LEVEL_CRITICAL => 'Critique',
            default => $this->alert->level,
        };
    }

    /**
     * The French label of the monitor type.
     */
    private function monitorTypeLabel(): string
    {
        return match ($this->monitor->type) {
            Monitor::TYPE_CONNECTOR => 'connecteur',
            Monitor::TYPE_GATEWAY => 'passerelle',
            default => 'webhook',
        };
    }

    /**
     * The measured latency, or its explicit absence.
     */
    private function latencyLabel(): string
    {
        return $this->check->latency_ms === null
            ? 'Non mesurée'
            : $this->check->latency_ms.' ms';
    }
}
