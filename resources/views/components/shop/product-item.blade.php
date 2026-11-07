{{--
    ProductItem: one product in the grid. The photo and name open the product
    popup (hovering never opens it); the colour circles below switch the photo.
--}}
@props(['product', 'index' => 0])

@php
    $variants = $product->variants;
    $image = $variants->first()?->imagePath() ?? $product->main_image;
    $prices = $variants->isNotEmpty()
        ? $variants->map->effectivePrice()
        : collect([$product->currentPrice()]);
    $from = $prices->min() !== $prices->max();
@endphp

<li class="shop-item" style="--i: {{ $index }}" data-shop-item data-selected-variant="{{ $variants->first()?->id }}">
    <button type="button" class="shop-item__trigger" data-product-open="{{ $product->id }}" aria-haspopup="dialog">
        <span class="shop-item__media">
            @if($image)
                <img src="{{ asset('storage/' . $image) }}" alt="{{ $product->name }}{{ $variants->isNotEmpty() ? ' in ' . $variants->first()->color_name : '' }}" width="800" height="1000" data-item-image
                     loading="{{ $index < 5 ? 'eager' : 'lazy' }}" decoding="async">
            @else
                <span class="flex h-full w-full items-center justify-center text-accent-300" aria-hidden="true"><i class="fas fa-image text-2xl"></i></span>
            @endif
            <x-shop.badges :product="$product" class="shop-badges--overlay" />
            <span class="shop-item__quick" aria-hidden="true"><i class="fas fa-eye text-[10px]"></i> Quick view</span>
        </span>

        <span class="shop-item__info">
            <span class="shop-item__name">{{ $product->name }}</span>
            <span class="shop-item__price">
                {{ $from ? 'From ' : '' }}@money($prices->min())
                @if(! $from && $product->isOnSale() && $variants->every(fn ($v) => $v->price === null))
                    <s class="ml-1 text-gray-400">@money($product->price)</s>
                @endif
            </span>
        </span>
    </button>

    <x-shop.color-swatches :product="$product" mode="dots" />

    {{-- Popup content for this product, cloned into the dialog when opened --}}
    <x-shop.product-modal :product="$product" />
</li>
