{{-- Small chapter label, e.g. "01 — Origin". Pass light for dark chapters. --}}
@props(['number' => null, 'light' => false])

<p {{ $attributes->merge(['class' => 'flex items-center gap-3 text-xs font-semibold uppercase tracking-[0.3em] ' . ($light ? 'text-brand-ivory/80' : 'text-brand-600')]) }}>
    @if($number)
        <span class="font-serif text-sm normal-case tracking-normal {{ $light ? 'text-accent-300' : 'text-accent-600' }}">{{ $number }}</span>
        <span class="h-px w-8 {{ $light ? 'bg-brand-ivory/40' : 'bg-brand-300' }}" aria-hidden="true"></span>
    @endif
    {{ $slot }}
</p>
