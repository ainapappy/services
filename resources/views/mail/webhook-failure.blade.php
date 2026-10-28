<x-mail::layout :subject="$subject" icon="send" eyebrow="Webhooks" heading="Livraison définitivement en échec">
    <p style="margin: 0;">Une livraison webhook a épuisé toutes ses tentatives sans jamais recevoir de réponse valide. La campagne est terminée, aucun nouvel essai n'est programmé.</p>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 28px 0 0; border-top: 1px solid #e8e8e4; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 14px; line-height: 22px;">
        <tr>
            <td width="40%" style="padding: 12px 0; border-bottom: 1px solid #e8e8e4; color: #66666f;">Endpoint</td>
            <td style="padding: 12px 0; border-bottom: 1px solid #e8e8e4; color: #17171c; word-break: break-all;">{{ $endpointUrl }}</td>
        </tr>
        <tr>
            <td style="padding: 12px 0; border-bottom: 1px solid #e8e8e4; color: #66666f;">Événement</td>
            <td style="padding: 12px 0; border-bottom: 1px solid #e8e8e4; color: #17171c;">{{ $eventName }}</td>
        </tr>
        <tr>
            <td style="padding: 12px 0; border-bottom: 1px solid #e8e8e4; color: #66666f;">Tentatives</td>
            <td style="padding: 12px 0; border-bottom: 1px solid #e8e8e4; color: #17171c;">{{ $attempts }} sur {{ $maxAttempts }}</td>
        </tr>
        <tr>
            <td style="padding: 12px 0; border-bottom: 1px solid #e8e8e4; color: #66666f;">Dernier code de réponse</td>
            <td style="padding: 12px 0; border-bottom: 1px solid #e8e8e4; color: #17171c;">{{ $responseCodeLabel }}</td>
        </tr>
        <tr>
            <td style="padding: 12px 0; border-bottom: 1px solid #e8e8e4; color: #66666f;">Dernière erreur</td>
            <td style="padding: 12px 0; border-bottom: 1px solid #e8e8e4; color: #17171c;">{{ $errorLabel }}</td>
        </tr>
        <tr>
            <td style="padding: 12px 0; border-bottom: 1px solid #e8e8e4; color: #66666f;">Échec constaté le</td>
            <td style="padding: 12px 0; border-bottom: 1px solid #e8e8e4; color: #17171c;">{{ $occurredAt }}</td>
        </tr>
    </table>
    <div style="text-align: center; margin: 32px 0;">
        <x-mail::button :href="$actionUrl" label="Voir la livraison dans le journal"/>
    </div>
    <x-mail::divider/>
    <p style="margin: 0; font-size: 14px; line-height: 22px; color: #66666f;">Une nouvelle campagne peut être relancée à tout moment depuis le journal des livraisons du back-office, une fois l'endpoint de nouveau joignable.</p>
</x-mail::layout>
