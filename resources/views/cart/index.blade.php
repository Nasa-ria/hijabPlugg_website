@extends('layouts.app')

@section('title', 'Shopping Cart – Nasaari Store')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-12">

    {{-- Page Header --}}
    <div class="flex items-center gap-3 mb-8 animate-fade-in">
        <span class="w-10 h-10 rounded-xl bg-accent-50 flex items-center justify-center">
            <i class="fas fa-shopping-bag text-accent-500"></i>
        </span>
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Shopping Cart</h1>
            @if(count($cart) > 0)
                <p class="text-sm text-gray-400">{{ count($cart) }} item{{ count($cart) !== 1 ? 's' : '' }} in your cart</p>
            @endif
        </div>
    </div>

    @if(count($cart) > 0)
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            {{-- ════════════════════════════════════════════
                 CART ITEMS
            ════════════════════════════════════════════ --}}
            <div class="lg:col-span-2 space-y-4">
                @foreach($cart as $key => $item)
                    <div class="bg-white rounded-2xl border border-gray-100 p-4 sm:p-5 flex gap-4 sm:gap-5 transition-all duration-200 hover:border-gray-200 stagger-item" id="cart-item-{{ $key }}">

                        {{-- Product Image --}}
                        <a href="{{ route('products.show', $item['product_id']) }}" class="flex-shrink-0 w-24 h-24 sm:w-28 sm:h-28 rounded-xl overflow-hidden bg-gray-100 img-zoom">
                            <img src="{{ \App\Support\Img::url($item['image'], 320) }}" alt="{{ $item['name'] }}" class="w-full h-full object-cover" loading="lazy" decoding="async">
                        </a>

                        {{-- Product Info --}}
                        <div class="flex-1 min-w-0 flex flex-col">
                            <a href="{{ route('products.show', $item['product_id']) }}" class="font-semibold text-gray-800 hover:text-brand-600 transition-colors text-[15px] truncate">
                                {{ $item['name'] }}
                            </a>
                            @if(!empty($item['color_name']))
                                <p class="text-xs text-gray-500 mt-0.5">Colour: {{ $item['color_name'] }}</p>
                            @endif
                            <p class="text-sm text-brand-600 font-semibold mt-1">
                                @money($item['price']) each
                            </p>

                            {{-- Quantity & Remove (mobile-friendly layout) --}}
                            <div class="mt-auto pt-3 flex items-center justify-between gap-3">
                                {{-- Quantity Selector --}}
                                <form action="{{ route('cart.update') }}" method="POST" class="flex items-center rounded-xl border border-gray-200 bg-gray-50 overflow-hidden" id="qty-form-{{ $key }}">
                                    @csrf
                                    <input type="hidden" name="product_id" value="{{ $item['product_id'] }}">
                                    <input type="hidden" name="key" value="{{ $key }}">
                                    <button type="submit" name="quantity" value="{{ $item['quantity'] - 1 }}" class="w-9 h-9 flex items-center justify-center text-gray-400 hover:text-brand-600 hover:bg-brand-50 transition-all {{ $item['quantity'] <= 1 ? 'opacity-40 pointer-events-none' : '' }}">
                                        <i class="fas fa-minus text-[10px]"></i>
                                    </button>
                                    <span class="w-10 h-9 flex items-center justify-center text-sm font-semibold border-x border-gray-200 bg-white">{{ $item['quantity'] }}</span>
                                    <button type="submit" name="quantity" value="{{ $item['quantity'] + 1 }}" class="w-9 h-9 flex items-center justify-center text-gray-400 hover:text-brand-600 hover:bg-brand-50 transition-all">
                                        <i class="fas fa-plus text-[10px]"></i>
                                    </button>
                                </form>

                                {{-- Remove --}}
                                <form action="{{ route('cart.remove') }}" method="POST" id="remove-form-{{ $key }}">
                                    @csrf
                                    <input type="hidden" name="product_id" value="{{ $item['product_id'] }}">
                                    <input type="hidden" name="key" value="{{ $key }}">
                                    <button type="submit" class="w-9 h-9 rounded-lg flex items-center justify-center text-gray-300 hover:text-red-500 hover:bg-red-50 transition-all" title="Remove item">
                                        <i class="fas fa-trash text-xs"></i>
                                    </button>
                                </form>
                            </div>
                        </div>

                        {{-- Line Total (desktop) --}}
                        <div class="hidden sm:flex flex-col items-end justify-between">
                            <span class="text-lg font-bold text-gray-800">@money($item['price'] * $item['quantity'])</span>
                        </div>
                    </div>
                @endforeach

                {{-- Clear Cart --}}
                <div class="pt-2">
                    <form action="{{ route('cart.clear') }}" method="POST" onsubmit="return confirm('Clear all items from your cart?')" id="clear-cart-form">
                        @csrf
                        <button type="submit" class="flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-medium text-gray-400 hover:text-red-500 hover:bg-red-50 transition-all">
                            <i class="fas fa-trash text-[10px]"></i>
                            Clear Cart
                        </button>
                    </form>
                </div>
            </div>

            {{-- ════════════════════════════════════════════
                 ORDER SUMMARY (sticky sidebar)
            ════════════════════════════════════════════ --}}
            <div class="lg:col-span-1">
                <div class="bg-white rounded-2xl border border-gray-100 p-6 lg:sticky lg:top-24 animate-slide-up">
                    <h2 class="text-lg font-bold text-gray-800 mb-6">Order Summary</h2>

                    <div class="space-y-3 mb-6">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Subtotal</span>
                            <span class="font-medium text-gray-700">@money($totals['subtotal'])</span>
                        </div>
                        <div class="flex justify-between gap-3 text-sm">
                            <span class="text-gray-500">Shipping</span>
                            @if($domestic)
                                <span class="font-medium text-emerald-700 text-right">Paid on delivery</span>
                            @else
                                <span class="font-medium text-gray-700 text-right">DHL rate at checkout</span>
                            @endif
                        </div>
                        @if($totals['tax'] > 0)
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Tax ({{ rtrim(rtrim(number_format($totals['tax_rate'] * 100, 2), '0'), '.') }}%)</span>
                                <span class="font-medium text-gray-700">@money($totals['tax'])</span>
                            </div>
                        @endif
                    </div>

                    <div class="border-t border-dashed border-gray-200 pt-4 mb-4">
                        <div class="flex justify-between items-baseline">
                            <span class="text-sm font-semibold text-gray-700">{{ $domestic ? 'Total' : 'Total before shipping' }}</span>
                            <span class="text-2xl font-bold text-brand-600">@money($totals['total'])</span>
                        </div>
                        @if($displayCurrency->isConverted())
                            <p class="mt-1 text-right text-xs text-gray-400">Charged in Ghana Cedis: @ghs($totals['total'])</p>
                        @endif
                    </div>

                    <x-shop.shipping-notice :type="$domestic ? 'pod' : 'dhl'" class="mb-6" />

                    @auth
                        <a href="{{ route('checkout.index') }}" class="btn-brand w-full text-center" id="checkout-btn">
                            <i class="fas fa-lock text-xs"></i>
                            Secure Checkout
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="btn-brand w-full text-center mb-3" id="login-checkout-btn">
                            Sign In to Checkout
                        </a>
                        <a href="{{ route('register') }}" class="btn-ghost w-full text-center" id="register-checkout-btn">
                            Create Account
                        </a>
                    @endauth

                    <a href="{{ route('products.index') }}" class="flex items-center justify-center gap-2 mt-4 text-sm font-medium text-gray-400 hover:text-brand-500 transition-colors" id="continue-shopping-link">
                        <i class="fas fa-arrow-left text-xs"></i>Continue Shopping
                    </a>

                    {{-- Trust signals --}}
                    <div class="mt-6 pt-5 border-t border-gray-100 space-y-2.5">
                        <div class="flex items-center gap-2.5 text-xs text-gray-400">
                            <i class="fas fa-lock text-accent-400 w-3.5 text-center"></i>
                            <span>SSL Encrypted Checkout</span>
                        </div>
                        <div class="flex items-center gap-2.5 text-xs text-gray-400">
                            <i class="fas fa-shield-halved text-accent-400 w-3.5 text-center"></i>
                            <span>100% Purchase Protection</span>
                        </div>
                        <div class="flex items-center gap-2.5 text-xs text-gray-400">
                            <i class="fas fa-rotate-left text-accent-400 w-3.5 text-center"></i>
                            <span>Hassle-Free Returns</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    @else
        {{-- Empty Cart State --}}
        <div class="flex flex-col items-center justify-center py-20 text-center animate-fade-in">
            <div class="w-28 h-28 rounded-3xl bg-accent-50 flex items-center justify-center mb-6 animate-float">
                <i class="fas fa-shopping-bag text-4xl text-accent-300"></i>
            </div>
            <h2 class="text-xl font-bold text-gray-800 mb-2">Your cart is empty</h2>
            <p class="text-sm text-gray-400 mb-8 max-w-xs">Looks like you haven't added anything yet. Explore our collection and find something you love.</p>
            <a href="{{ route('products.index') }}" class="btn-brand" id="start-shopping-btn">
                <i class="fas fa-shopping-bag text-sm"></i>
                Start Shopping
            </a>
        </div>
    @endif
</div>
@endsection
