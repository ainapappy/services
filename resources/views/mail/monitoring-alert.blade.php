<x-mail::layout :subject="$subject" icon="alert" eyebrow="Alerte monitoring" :heading="$monitorName">
    <p style="margin: 0;">Le moniteur <strong>{{ $monitorName }}</strong> ({{ $monitorTypeLabel }}) a changé d'état lors du dernier contrôle.</p>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 28px 0 0; border-top: 1px solid #e8e8e4; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 14px; line-height: 22px;">
        <tr>
            <td width="40%" style="padding: 12px 0; border-bottom: 1px solid #e8e8e4; color: #66666f;">Statut</td>
            <td style="padding: 12px 0; border-bottom: 1px solid #e8e8e4; color: #17171c; font-weight: 600;">{{ $statusLabel }}</td>
        </tr>
        <tr>
            <td style="padding: 12px 0; border-bottom: 1px solid #e8e8e4; color: #66666f;">Niveau</td>
            <td style="padding: 12px 0; border-bottom: 1px solid #e8e8e4; color: #17171c;">{{ $levelLabel }}</td>
        </tr>
        <tr>
            <td style="padding: 12px 0; border-bottom: 1px solid #e8e8e4; color: #66666f;">Latence</td>
            <td style="padding: 12px 0; border-bottom: 1px solid #e8e8e4; color: #17171c;">{{ $latencyLabel }}</td>
        </tr>
        <tr>
            <td style="padding: 12px 0; border-bottom: 1px solid #e8e8e4; color: #66666f;">Message</td>
            <td style="padding: 12px 0; border-bottom: 1px solid #e8e8e4; color: #17171c;">{{ $message }}</td>
        </tr>
        <tr>
            <td style="padding: 12px 0; border-bottom: 1px solid #e8e8e4; color: #66666f;">Contrôlé le</td>
            <td style="padding: 12px 0; border-bottom: 1px solid #e8e8e4; color: #17171c;">{{ $occurredAt }}</td>
        </tr>
    </table>
    <div style="text-align: center; margin: 32px 0;">
        <x-mail::button :href="$actionUrl" label="Ouvrir le tableau de bord"/>
    </div>
    <x-mail::divider/>
    <p style="margin: 0; font-size: 14px; line-height: 22px; color: #66666f;">La résolution est automatique : l'alerte se referme dès que le moniteur retrouve un état sain, et le retour à la normale n'est pas notifié par email.</p>
</x-mail::layout>
