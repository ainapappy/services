@props([
    'name' => null,
    'size' => 24,
    'color' => '#6366f1',
    'label' => null,
])
@php($icons = [
    'mail' => '<rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>',
    'check' => '<path d="M20 6 9 17l-5-5"/>',
    'alert' => '<path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"/><path d="M12 9v4"/><path d="M12 17h.01"/>',
    'key' => '<path d="m15.5 7.5 2.3 2.3a1 1 0 0 0 1.4 0l2.1-2.1a1 1 0 0 0 0-1.4L19 4"/><path d="m21 2-9.6 9.6"/><circle cx="7.5" cy="15.5" r="5.5"/>',
    'send' => '<path d="M14.536 21.686a.5.5 0 0 0 .937-.024l6.5-19a.496.496 0 0 0-.635-.635l-19 6.5a.5.5 0 0 0-.024.937l7.93 3.18a2 2 0 0 1 1.112 1.11z"/><path d="m21.854 2.147-10.94 10.939"/>',
])
@php($labels = [
    'mail' => 'Email',
    'check' => 'Succès',
    'alert' => 'Alerte',
    'key' => 'Clé de sécurité',
    'send' => 'Envoi',
])
@php($paths = ($name !== null && array_key_exists($name, $icons)) ? $icons[$name] : (string) $slot)
<svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" stroke="{{ $color }}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" role="img" aria-label="{{ $label ?? $labels[$name] ?? 'Illustration' }}">{!! $paths !!}</svg>
