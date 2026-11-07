{{-- A titled row of product cards (recommendations, related products). Renders nothing when empty. --}}
@props(['title', 'products', 'subtitle' => null, 'icon' => null, 'link' => null, 'linkLabel' => 'View all'])

@if($products->isNotEmpty())
    <section {{ $attributes->merge(['class' => 'shop-row']) }}>
        <div class="shop-row__header">
            <div>
                <h2 class="shop-row__title">
                    @if($icon)<i class="fas {{ $icon }} text-accent-500 text-base mr-2" aria-hidden="true"></i>@endif{{ $title }}
                </h2>
                @if($subtitle)<p class="shop-row__subtitle">{{ $subtitle }}</p>@endif
            </div>
            @if($link)
                <a href="{{ $link }}" class="shop-row__link">{{ $linkLabel }} <i class="fas fa-arrow-right text-[10px]" aria-hidden="true"></i></a>
            @endif
        </div>
        <ul class="shop-row__list" role="list">
            @foreach($products as $product)
                <li><x-shop.product-card :product="$product" :index="$loop->index" /></li>
            @endforeach
        </ul>
    </section>
@endif
