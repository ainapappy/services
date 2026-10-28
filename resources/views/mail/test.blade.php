<x-mail::layout :subject="$subject" eyebrow="Essai du squelette" heading="Email de test">
    <p style="margin: 0;">Bonjour,</p>
    <p style="margin: 16px 0 0;">Cet email met en scène le squelette des emails de <strong>{{ config('app.name') }}</strong> : en-tête avec icône, titres, textes, bouton, séparateur et tableau de détails. Il n'appelle aucune action.</p>
    <div style="text-align: center; margin: 32px 0;">
        <x-mail::button :href="$actionUrl" label="Bouton d'exemple"/>
    </div>
    <x-mail::divider/>
    <p style="margin: 0; font-weight: 600;">Les icônes prédéfinies</p>
    <p style="margin: 16px 0 0;">Cinq pictogrammes monochromes, au trait lucide, prêts à être placés dans l'en-tête d'un email.</p>
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin: 20px 0 0;">
        <tr>
            <td style="padding: 0 18px 0 0;"><x-mail::icon name="mail" size="22"/></td>
            <td style="padding: 0 18px 0 0;"><x-mail::icon name="check" size="22"/></td>
            <td style="padding: 0 18px 0 0;"><x-mail::icon name="alert" size="22"/></td>
            <td style="padding: 0 18px 0 0;"><x-mail::icon name="key" size="22"/></td>
            <td style="padding: 0;"><x-mail::icon name="send" size="22"/></td>
        </tr>
    </table>
    <x-mail::divider/>
    <p style="margin: 0; font-weight: 600;">Un tableau de détails</p>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 16px 0 0; border-top: 1px solid #e8e8e4; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 14px; line-height: 22px;">
        <tr>
            <td width="40%" style="padding: 12px 0; border-bottom: 1px solid #e8e8e4; color: #66666f;">Envoi</td>
            <td style="padding: 12px 0; border-bottom: 1px solid #e8e8e4; color: #17171c;">{{ $sentAt }}</td>
        </tr>
        <tr>
            <td style="padding: 12px 0; border-bottom: 1px solid #e8e8e4; color: #66666f;">Composants</td>
            <td style="padding: 12px 0; border-bottom: 1px solid #e8e8e4; color: #17171c;">layout, button, divider, icon</td>
        </tr>
        <tr>
            <td style="padding: 12px 0; color: #66666f;">Contenu</td>
            <td style="padding: 12px 0; color: #17171c;">HTML en tables imbriquées, styles inline, aucune image distante</td>
        </tr>
    </table>
</x-mail::layout>
