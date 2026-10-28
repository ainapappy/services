@props([
    'href' => '#',
    'label' => null,
])
<a href="{{ $href }}" target="_blank" rel="noopener" style="display: inline-block; padding: 14px 28px; border-radius: 12px; background-color: #6366f1; background-image: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); color: #ffffff; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 15px; line-height: 20px; font-weight: 600; text-align: center; text-decoration: none; -webkit-text-size-adjust: none;">{{ $label ?? $slot }}</a>
