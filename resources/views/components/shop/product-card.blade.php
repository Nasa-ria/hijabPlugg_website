{{--
    ProductCard: a linked product tile for recommendation rows and related products.
    (The main shop grid uses product-item, which opens the quick-view popup.)
--}}
@props(['product', 'index' => 0])

@php
    $variants = $product->variants;
    $image = $variants->first()?->imagePath() ?? $product->main_image;
    $prices = $variants->isNotEmpty() ? $variants->map->effectivePrice() : collect([$product->currentPrice()]);
    $from = $prices->min() !== $prices->max();
@endphp

<a href="{{ route('products.show', $product) }}" class="shop-card group" style="--i: {{ $index }}" data-track-click="shop_grid" data-product-id="{{ $product->id }}">
    <span class="shop-card__media">
        @if($image)
            <x-responsive-img :path="$image" :alt="$product->name" sizes="(min-width: 768px) 23vw, 46vw" width="600" height="750" />
        @else
            <span class="flex h-full w-full items-center justify-center bg-cream-200 text-accent-300" aria-hidden="true"><i class="fas fa-image text-2xl"></i></span>
        @endif
        <x-shop.badges :product="$product" class="shop-badges--overlay" />
    </span>
    <span class="shop-card__body">
        @if($product->category)
            <span class="shop-card__category">{{ $product->category->name }}</span>
        @endif
        <span class="shop-card__name">{{ $product->name }}</span>
        <span class="shop-card__meta">
            <span class="shop-card__price">
                {{ $from ? 'From ' : '' }}@money($prices->min())
                @if(! $from && $product->isOnSale() && $variants->every(fn ($v) => $v->price === null))
                    <s>@money($product->price)</s>
                @endif
            </span>
            @if($product->average_rating > 0)
                <span class="shop-card__rating" aria-label="Rated {{ number_format($product->average_rating, 1) }} out of 5">
                    <i class="fas fa-star" aria-hidden="true"></i>{{ number_format($product->average_rating, 1) }}
                </span>
            @endif
        </span>
        @if($variants->count() > 1)
            <span class="shop-card__colours">{{ $variants->count() }} colours</span>
        @endif
    </span>
</a>
