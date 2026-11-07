{{--
    Social media icons from config/nasaari.php (set in .env). Only the ones you've filled in are shown.
    tone="light" for dark backgrounds, "dark" for light backgrounds.
--}}
@props(['tone' => 'light', 'size' => 'md'])

@php
    $networks = [
        'instagram' => ['Instagram', 'fa-instagram'],
        'tiktok' => ['TikTok', 'fa-tiktok'],
        'facebook' => ['Facebook', 'fa-facebook-f'],
        'whatsapp' => ['WhatsApp', 'fa-whatsapp'],
        'x' => ['X', 'fa-x-twitter'],
        'snapchat' => ['Snapchat', 'fa-snapchat'],
        'pinterest' => ['Pinterest', 'fa-pinterest-p'],
        'youtube' => ['YouTube', 'fa-youtube'],
    ];
    $links = collect(config('nasaari.social'))->filter()->map(fn ($value, $key) =>
        $key === 'whatsapp' ? 'https://wa.me/' . preg_replace('/\D+/', '', $value) : $value
    );
    $box = $size === 'lg' ? 'w-11 h-11 text-base' : 'w-9 h-9 text-sm';
    $style = $tone === 'light'
        ? 'bg-white/10 text-cream-100 hover:bg-accent-500 hover:text-white'
        : 'bg-white border border-cream-300 text-brand-800 hover:bg-accent-500 hover:border-accent-500 hover:text-white';
@endphp

@if($links->isNotEmpty())
    <ul {{ $attributes->merge(['class' => 'flex flex-wrap items-center gap-3']) }} role="list">
        @foreach($links as $key => $url)
            <li>
                <a href="{{ $url }}" target="_blank" rel="noopener" aria-label="Nasaari on {{ $networks[$key][0] }}"
                   class="flex {{ $box }} items-center justify-center rounded-full transition-colors duration-200 {{ $style }}">
                    <i class="fab {{ $networks[$key][1] }}" aria-hidden="true"></i>
                </a>
            </li>
        @endforeach
    </ul>
@endif
