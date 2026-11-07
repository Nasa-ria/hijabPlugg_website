{{--
    ProductGrid: airy grid of products plus the single popup (dialog) they open.
    Behaviour: resources/js/shop.js · Spacing, columns, timing: resources/css/shop.css
--}}
@props(['products'])

<div data-shop-grid>
    <ul class="shop-grid" role="list">
        @foreach($products as $product)
            <x-shop.product-item :product="$product" :index="$loop->index" />
        @endforeach
    </ul>

    <dialog id="productModal" class="shop-modal" aria-label="Product details">
        <button type="button" class="shop-modal__close" data-modal-close aria-label="Close">
            <i class="fas fa-times" aria-hidden="true"></i>
        </button>
        <div data-modal-body></div>
    </dialog>
</div>
