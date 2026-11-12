{{--
    Product card for the story page, styled like the collection tiles: the photo
    fills the card with name, price and colours over a soft fade.
    Shopping comes first, so it is never hidden or delayed by a reveal.
--}}
@props(['product'])

@php
    $product->loadMissing('variants');
    $variants = $product->variants;
    $image = $variants->first()?->imagePath() ?? $product->main_image;
    $prices = $variants->isNotEmpty() ? $variants->map->effectivePrice() : collect([$product->currentPrice()]);
    $from = $prices->min() !== $prices->max();
@endphp

<a href="{{ route('products.show', $product) }}" data-track-click="home" data-product-id="{{ $product->id }}"
   class="story-card group relative block overflow-hidden rounded-[1.75rem] bg-cream-200 aspect-[4/5]">
    <span class="story-zoom absolute inset-0 block">
        @if($image)
            <x-responsive-img :path="$image" :alt="$product->name" sizes="(min-width: 1024px) 22vw, (min-width: 640px) 45vw, 80vw" width="600" height="750" class="h-full w-full object-cover" />
        @else
            <span class="flex h-full w-full items-center justify-center"><i class="fas fa-image text-3xl text-accent-300" aria-hidden="true"></i></span>
        @endif
    </span>
    <span class="absolute inset-0 bg-gradient-to-t from-brand-dark/80 via-brand-dark/10 to-transparent" aria-hidden="true"></span>

    @if($product->sale_price)
        <span class="absolute top-4 left-4 badge-sale">Sale</span>
    @endif

    <span class="absolute bottom-0 left-0 right-0 p-6 text-cream-100">
        <span class="block text-xs uppercase tracking-[0.28em] text-brand-ivory/80">
            {{ $variants->count() > 1 ? $variants->count() . ' colours' : ($product->category->name ?? 'New in') }}
        </span>
        <span class="mt-2 block text-xl font-semibold">{{ $product->name }}</span>
        <span class="mt-1 block text-sm text-brand-ivory/90">
            {{ $from ? 'From ' : '' }}@money($prices->min())
            @if(! $from && $product->sale_price && $variants->every(fn ($v) => $v->price === null))
                <s class="ml-1 text-brand-ivory/60">@money($product->price)</s>
            @endif
        </span>
        <span class="story-link mt-3 text-sm font-semibold text-brand-ivory">Shop now <i class="fas fa-arrow-right text-xs" aria-hidden="true"></i></span>
    </span>
</a>
