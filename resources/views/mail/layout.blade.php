@props([
    'subject' => 'Notification',
    'eyebrow' => '',
    'heading' => '',
])
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <title>{{ $subject }}</title>
</head>
<body style="margin: 0; padding: 0; width: 100%; background-color: #f3f3f1;">
    {{-- Preheader invisible : la ligne affichée après le sujet dans la boîte de réception. --}}
    <div style="display: none; max-height: 0; overflow: hidden; mso-hide: all;">{{ $subject }}</div>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f3f3f1;">
        <tr>
            <td align="center" style="padding: 40px 16px;">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width: 600px; max-width: 600px;">
                    <tr>
                        <td style="background-color: #ffffff; border-radius: 16px; box-shadow: 0 1px 2px rgba(23, 23, 28, 0.04), 0 12px 32px rgba(23, 23, 28, 0.06);">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td align="center" style="padding: 44px 48px 0 48px;">
                                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin: 0 auto;">
                                            <tr>
                                                <td align="center" width="56" height="56" style="width: 56px; height: 56px; background-color: #6366f1; border-radius: 28px; text-align: center; vertical-align: middle;">
                                                    @if (isset($icon) && $icon->isNotEmpty())
                                                        {!! $icon !!}
                                                    @else
                                                        <x-mail::icon name="mail" :size="26" color="#ffffff" label="Email"/>
                                                    @endif
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                @if ($eyebrow !== '')
                                    <tr>
                                        <td align="center" style="padding: 24px 48px 0 48px;">
                                            <p style="margin: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 12px; line-height: 18px; font-weight: 600; letter-spacing: 0.12em; text-transform: uppercase; color: #6366f1;">{{ $eyebrow }}</p>
                                        </td>
                                    </tr>
                                @endif
                                @if ($heading !== '')
                                    <tr>
                                        <td align="center" style="padding: 10px 48px 0 48px;">
                                            <h1 style="margin: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 24px; line-height: 32px; font-weight: 700; color: #17171c;">{{ $heading }}</h1>
                                        </td>
                                    </tr>
                                @endif
                            </table>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="padding: 28px 48px 44px 48px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 26px; color: #17171c;">
                                        {{ $slot }}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding: 24px 24px 0 24px;">
                            <p style="margin: 0 0 8px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 12px; line-height: 18px; color: #66666f;">
                                {{ config('app.name') }} — message automatique, merci de ne pas répondre.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
