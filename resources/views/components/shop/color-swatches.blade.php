{{--
    ColorSwatches: a product's colours, each shown as a small round crop of
    that colour's photo (falls back to the swatch colour if there is no photo).
      mode="dots"   small photo circles under a grid item; choosing one switches the item's photo
      mode="select" selectable swatches inside the product popup (a radio group; sold-out colours are disabled)
--}}
@props(['product', 'mode' => 'dots', 'max' => 6])

@php
    $variants = $product->variants;
    $swatchStyle = fn ($v) => 'background-color: ' . $v->color_hex . ';'
        . ($v->imagePath() ? " background-image: url('" . \App\Support\Img::url($v->imagePath(), 160) . "');" : '');
@endphp

@if($variants->isNotEmpty())
    @if($mode === 'dots')
        <div {{ $attributes->merge(['class' => 'shop-item__dots']) }} role="group" aria-label="{{ $product->name }} colours">
            @foreach($variants->take($max) as $variant)
                <button type="button" class="shop-dot {{ $variant->inStock() ? '' : 'is-sold-out' }}" style="{{ $swatchStyle($variant) }}"
                        data-dot
                        data-variant-id="{{ $variant->id }}"
                        data-color-name="{{ $variant->color_name }}"
                        data-image="{{ \App\Support\Img::url($variant->imagePath(), 480) }}"
                        data-srcset="{{ \App\Support\Img::srcset($variant->imagePath()) }}"
                        aria-pressed="{{ $loop->first ? 'true' : 'false' }}"
                        aria-label="Show {{ $variant->color_name }}{{ $variant->inStock() ? '' : ' (sold out)' }}"
                        title="{{ $variant->color_name }}{{ $variant->inStock() ? '' : ' · sold out' }}"></button>
            @endforeach
            @if($variants->count() > $max)
                <span class="text-[11px] text-gray-500">+{{ $variants->count() - $max }}</span>
            @endif
        </div>
    @else
        <div {{ $attributes->merge(['class' => 'flex flex-wrap gap-3']) }} role="radiogroup" aria-labelledby="swatch-label-{{ $product->id }}">
            @foreach($variants as $variant)
                @php
                    $price = $variant->effectivePrice();
                    $compare = $variant->price === null && $product->sale_price ? (float) $product->price : null;
                @endphp
                <button type="button" class="shop-swatch" role="radio" aria-checked="false" tabindex="{{ $loop->first ? 0 : -1 }}"
                        style="{{ $swatchStyle($variant) }}"
                        aria-label="{{ $variant->color_name }}{{ $variant->inStock() ? '' : ' (sold out)' }}"
                        title="{{ $variant->color_name }}{{ $variant->inStock() ? '' : ' · sold out' }}"
                        data-swatch
                        data-variant-id="{{ $variant->id }}"
                        data-color-name="{{ $variant->color_name }}"
                        data-stock="{{ $variant->stock_quantity }}"
                        data-price="@money($price)"
                        data-compare="{{ $compare ? \App\Support\Money::show($compare) : '' }}"
                        @disabled(! $variant->inStock())></button>
            @endforeach
        </div>
    @endif
@endif
