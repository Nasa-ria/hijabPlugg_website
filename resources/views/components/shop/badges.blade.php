{{-- Product badges: Best Seller, On Sale, New Arrival, Limited Stock, Sold out (at most two shown) --}}
@props(['product', 'limit' => 2])

@php $badges = array_slice($product->badges(), 0, $limit, true); @endphp

@if($badges)
    <span {{ $attributes->merge(['class' => 'shop-badges']) }}>
        @foreach($badges as $key => $label)
            <span class="shop-badge shop-badge--{{ $key }}">{{ $label }}</span>
        @endforeach
    </span>
@endif
