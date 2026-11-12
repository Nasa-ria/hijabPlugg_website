{{--
    ProductModal: the popup content for one product, kept in a <template> so
    nothing (not even images) loads until the customer opens it.
    The dialog shell that shows it lives in components/shop/product-grid.
--}}
@props(['product'])

@php
    $variants = $product->variants;
    $hasVariants = $variants->isNotEmpty();

    // Images on the stage: one per colour, or the product's own photos
    $stage = $hasVariants
        ? $variants->map(fn ($v) => ['key' => (string) $v->id, 'path' => $v->imagePath(), 'alt' => $product->name . ' in ' . $v->color_name])
        : collect([$product->main_image])->merge($product->images->pluck('image_path'))->filter()
            ->values()->map(fn ($path, $i) => ['key' => 'img-' . $i, 'path' => $path, 'alt' => $product->name]);

    $inStock = $hasVariants ? $variants->contains->inStock() : $product->stock_quantity > 0;
    $titleId = 'product-modal-title-' . $product->id;
@endphp

<template id="product-modal-{{ $product->id }}">
    <div class="shop-modal__layout" data-product-modal aria-labelledby="{{ $titleId }}">
        {{-- Gallery --}}
        <div class="shop-modal__gallery">
            <div class="shop-modal__stage">
                @forelse($stage as $i => $img)
                    <img {{ $i === 0 ? 'src' : 'data-src' }}="{{ \App\Support\Img::url($img['path'], 720) }}" {{ $i === 0 ? 'srcset' : 'data-srcset' }}="{{ \App\Support\Img::srcset($img['path'], [480, 720, 960]) }}" sizes="(min-width: 768px) 420px, 92vw" alt="{{ $img['alt'] }}" width="800" height="1000"
                         decoding="async" data-stage-image="{{ $img['key'] }}" class="{{ $i === 0 ? 'is-active' : '' }}">
                @empty
                    <span class="flex h-full w-full items-center justify-center text-accent-300" aria-hidden="true"><i class="fas fa-image text-4xl"></i></span>
                @endforelse
            </div>

            @if(! $hasVariants && $stage->count() > 1)
                <div class="mt-3 flex gap-2" aria-label="More photos">
                    @foreach($stage as $i => $img)
                        <button type="button" class="shop-thumb {{ $i === 0 ? 'is-active' : '' }}" data-thumb="{{ $img['key'] }}" aria-label="Show photo {{ $i + 1 }}">
                            <img src="{{ \App\Support\Img::url($img['path'], 160) }}" alt="" width="80" height="100" loading="lazy" decoding="async">
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Details --}}
        <div class="shop-modal__details">
            @if($product->category)
                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-accent-600">{{ $product->category->name }}</p>
            @endif
            <h2 id="{{ $titleId }}" class="mt-2 font-serif text-2xl leading-tight text-brand-900">
                {{ $product->name }}<span class="block mt-1 text-base font-sans text-gray-500" data-color-title></span>
            </h2>

            <p class="mt-4 flex items-baseline gap-2">
                <span class="text-xl font-semibold text-brand-900" data-price>@money($hasVariants ? $variants->map->effectivePrice()->min() : $product->currentPrice())</span>
                <s class="text-sm text-gray-400" data-compare>{{ $product->sale_price ? \App\Support\Money::show($product->price) : '' }}</s>
            </p>

            @if($product->short_description)
                <p class="mt-3 text-sm leading-6 text-gray-600">{{ $product->short_description }}</p>
            @endif

            @if($product->material || $product->size)
                <dl class="mt-5 grid grid-cols-2 gap-4 text-sm">
                    @if($product->material)
                        <div><dt class="text-gray-500">Fabric</dt><dd class="font-medium text-brand-900">{{ $product->material }}</dd></div>
                    @endif
                    @if($product->size)
                        <div><dt class="text-gray-500">Size</dt><dd class="font-medium text-brand-900">{{ $product->size }}</dd></div>
                    @endif
                </dl>
            @endif

            <form action="{{ route('cart.add') }}" method="POST" class="mt-6" data-add-form data-stock="{{ $product->stock_quantity }}" data-has-variants="{{ $hasVariants ? 1 : 0 }}">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <input type="hidden" name="variant_id" value="">
                <input type="hidden" name="source" value="popup">

                @if($hasVariants)
                    <p id="swatch-label-{{ $product->id }}" class="text-sm text-gray-600">
                        Colour: <span class="font-semibold text-brand-900" data-selected-color>Choose a colour</span>
                    </p>
                    <x-shop.color-swatches :product="$product" mode="select" class="mt-3" />
                @endif

                <div class="mt-6 flex items-center gap-4">
                    <div class="shop-qty" role="group" aria-label="Quantity">
                        <button type="button" data-qty-step="-1" aria-label="Decrease quantity"><i class="fas fa-minus text-[10px]" aria-hidden="true"></i></button>
                        <input type="number" name="quantity" value="1" min="1" max="100" inputmode="numeric" aria-label="Quantity" data-qty>
                        <button type="button" data-qty-step="1" aria-label="Increase quantity"><i class="fas fa-plus text-[10px]" aria-hidden="true"></i></button>
                    </div>

                    <button type="submit" class="btn-brand flex-1" data-add-button @disabled($hasVariants || ! $inStock)>
                        <span data-add-label>{{ $inStock ? 'Add to cart' : 'Out of stock' }}</span>
                    </button>
                </div>

                <p class="mt-3 min-h-[1.25rem] text-sm" data-message role="status" aria-live="polite">
                    @if($hasVariants && $inStock)<span class="text-gray-500">Choose a colour to add it to your cart.</span>@endif
                </p>

                <div class="mt-2 hidden gap-3" data-after-add>
                    <button type="button" class="btn-secondary flex-1 !py-2.5" data-modal-close>Continue shopping</button>
                    <a href="{{ route('cart.index') }}" class="btn-brand flex-1 !py-2.5">View cart</a>
                </div>
            </form>

            <a href="{{ route('products.show', $product) }}" class="story-link mt-6 text-sm font-semibold text-brand-700">
                View full details <i class="fas fa-arrow-right text-xs" aria-hidden="true"></i>
            </a>
        </div>
    </div>
</template>
