<x-mail::layout :subject="$subject" icon="mail" eyebrow="Vérification" heading="Confirmez votre adresse email">
    <p style="margin: 0;">Bonjour,</p>
    <p style="margin: 16px 0 0;">Bienvenue sur <strong>{{ config('app.name') }}</strong>. Il reste une dernière étape pour activer votre compte : confirmer que cette adresse email vous appartient bien.</p>
    <p style="margin: 16px 0 0;">Ce lien de confirmation est valable {{ $expiresIn }} minutes.</p>
    <div style="text-align: center; margin: 32px 0;">
        <x-mail::button :href="$url" label="Vérifier mon adresse email"/>
    </div>
    <x-mail::divider/>
    <p style="margin: 0; font-size: 14px; line-height: 22px; color: #66666f;">Vous n'êtes pas à l'origine de cette demande ? Vous pouvez ignorer ce message en toute sécurité : aucune adresse ne sera vérifiée et aucun compte ne sera créé.</p>
    <p style="margin: 16px 0 0; font-size: 14px; line-height: 22px; color: #66666f;">Si le bouton ne fonctionne pas, copiez-collez ce lien dans votre navigateur :</p>
    <p style="margin: 8px 0 0; font-size: 14px; line-height: 22px; color: #66666f; word-break: break-all;">{{ $url }}</p>
</x-mail::layout>
