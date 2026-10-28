<x-mail::layout :subject="$subject" icon="key" eyebrow="Sécurité" heading="Réinitialisez votre mot de passe">
    <p style="margin: 0;">Bonjour,</p>
    <p style="margin: 16px 0 0;">Vous avez demandé la réinitialisation du mot de passe de votre compte <strong>{{ config('app.name') }}</strong>.</p>
    <p style="margin: 16px 0 0;">Le lien ci-dessous est valable {{ $expiresIn }} minutes et ne peut être utilisé qu'une seule fois.</p>
    <div style="text-align: center; margin: 32px 0;">
        <x-mail::button :href="$url" label="Choisir un nouveau mot de passe"/>
    </div>
    <x-mail::divider/>
    <p style="margin: 0; font-size: 14px; line-height: 22px; color: #66666f;">Vous n'avez rien demandé ? Aucune inquiétude : ignorez ce message, votre mot de passe actuel reste inchangé tant que le lien n'est pas utilisé.</p>
    <p style="margin: 16px 0 0; font-size: 14px; line-height: 22px; color: #66666f;">Si le bouton ne fonctionne pas, copiez-collez ce lien dans votre navigateur :</p>
    <p style="margin: 8px 0 0; font-size: 14px; line-height: 22px; color: #66666f; word-break: break-all;">{{ $url }}</p>
</x-mail::layout>
