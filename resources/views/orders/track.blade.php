@extends('layouts.app')

@section('title', 'Track your order – Nasaari Store')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-16">
    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-accent-600">Order status</p>
    <h1 class="mt-3 font-serif text-4xl text-brand-900">Track your order</h1>
    <p class="mt-3 text-gray-600">Enter your order number and the email you used at checkout.</p>

    <form action="{{ route('orders.track.lookup') }}" method="POST" class="mt-8 grid grid-cols-1 sm:grid-cols-[1fr_1fr_auto] gap-3">
        @csrf
        <div>
            <label for="track-number" class="sr-only">Order number</label>
            <input id="track-number" type="text" name="order_number" required value="{{ old('order_number', $orderNumber) }}" placeholder="Order number, e.g. ORD-2026…" class="input-field">
        </div>
        <div>
            <label for="track-email" class="sr-only">Email</label>
            <input id="track-email" type="email" name="email" required value="{{ old('email') }}" placeholder="Email address" class="input-field" autocomplete="email">
        </div>
        <button type="submit" class="btn-brand">Track</button>
    </form>

    @isset($order)
        <section class="mt-12 rounded-[1.75rem] border border-cream-300 bg-white p-6 sm:p-8" aria-labelledby="track-result">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 id="track-result" class="font-serif text-2xl text-brand-900">{{ $order->order_number }}</h2>
                <x-admin.status-badge :status="$order->status" />
            </div>
            <p class="mt-1 text-sm text-gray-500">Placed {{ $order->created_at->format('j M Y, g:i a') }}</p>

            <x-order-progress :order="$order" class="mt-8" />

            <ul class="mt-8 divide-y divide-cream-200 border-t border-cream-200">
                @foreach($order->items as $item)
                    <li class="flex items-center gap-4 py-4">
                        @php $path = $item->variant?->imagePath() ?? $item->product?->main_image; @endphp
                        @if($path)
                            <img src="{{ \App\Support\Img::url($path, 160) }}" loading="lazy" decoding="async" alt="" class="h-16 w-14 rounded-lg object-cover">
                        @endif
                        <div class="flex-1 text-sm">
                            <p class="font-semibold text-brand-900">{{ $item->product?->name }}</p>
                            <p class="text-gray-500">@if($item->color_name){{ $item->color_name }} · @endif Qty {{ $item->quantity }}</p>
                        </div>
                        <p class="text-sm font-semibold text-brand-900">@orderMoney($item->subtotal)</p>
                    </li>
                @endforeach
            </ul>
            <p class="mt-4 flex justify-between border-t border-cream-300 pt-4 font-semibold text-brand-900">
                <span>Total</span><span>@orderMoney($order->total_amount)</span>
            </p>
        </section>
    @endisset
</div>
@endsection
