{{-- Collection tile used to fill the product row while the catalogue grows. --}}
@props(['title', 'subtitle' => null, 'image', 'href', 'index' => 0])

<a href="{{ $href }}" class="story-card group relative block overflow-hidden rounded-[1.75rem] bg-cream-200 aspect-[4/5]"
   data-reveal style="--i: {{ $index }}">
    <span class="story-zoom absolute inset-0 block">
        <img src="{{ $image }}" alt="" width="600" height="750" loading="lazy" decoding="async" class="h-full w-full object-cover">
    </span>
    <span class="absolute inset-0 bg-gradient-to-t from-brand-dark/75 via-brand-dark/10 to-transparent" aria-hidden="true"></span>
    <span class="absolute bottom-0 left-0 right-0 p-6 text-cream-100">
        @if($subtitle)
            <span class="block text-xs uppercase tracking-[0.28em] text-brand-ivory/80">{{ $subtitle }}</span>
        @endif
        <span class="mt-2 block text-xl font-semibold">{{ $title }}</span>
        <span class="story-link mt-3 text-sm font-semibold text-brand-ivory">Shop now <i class="fas fa-arrow-right text-xs" aria-hidden="true"></i></span>
    </span>
</a>
