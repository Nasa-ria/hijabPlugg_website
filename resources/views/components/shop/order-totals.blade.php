{{--
    Totals of a placed order. Amounts are stored in GHS; they are shown in the visitor's
    display currency (using the rate saved with the order), with the GHS amount charged.
--}}
@props(['order', 'totalClass' => 'text-2xl font-bold text-brand-600'])

@php
    $currencyService = app(\App\Services\CurrencyService::class);
    $converted = $currencyService->isConverted();
    $fmt = fn ($v) => $currencyService->formatForOrder($order, $v);
@endphp

<dl {{ $attributes->merge(['class' => 'space-y-3 text-sm']) }}>
    <div class="flex justify-between gap-4">
        <dt class="text-gray-600">Subtotal</dt>
        <dd class="font-semibold text-gray-900">{{ $fmt($order->subtotal) }}</dd>
    </div>
    <div class="flex justify-between gap-4">
        <dt class="text-gray-600">
            {{ $order->shippingLabel() }}
            @if($order->isDhl() && ! $order->shipping_confirmed_at)
                <span class="block text-xs text-gray-400">Estimate by DHL; you won't be charged again</span>
            @endif
        </dt>
        <dd class="font-semibold text-gray-900 text-right">
            @if($order->isPayOnDelivery())
                <span class="text-emerald-700">Pay on delivery</span>
            @else
                {{ $fmt($order->shipping_cost) }}
            @endif
        </dd>
    </div>
    @if((float) $order->tax > 0)
        <div class="flex justify-between gap-4">
            <dt class="text-gray-600">Tax</dt>
            <dd class="font-semibold text-gray-900">{{ $fmt($order->tax) }}</dd>
        </div>
    @endif
    <div class="flex justify-between items-baseline gap-4 border-t border-gray-200 pt-3">
        <dt class="text-base font-bold text-gray-900">Total</dt>
        <dd class="{{ $totalClass }}">{{ $fmt($order->total_amount) }}</dd>
    </div>
    @if($converted)
        <div class="flex justify-between gap-4 text-xs text-gray-500">
            <dt>Charged in Ghana Cedis</dt>
            <dd class="font-semibold">{{ $currencyService->formatBase($order->total_amount) }}</dd>
        </div>
    @endif
</dl>

@if($order->isPayOnDelivery())
    <x-shop.shipping-notice type="pod" class="mt-4" />
@elseif($order->isDhl())
    <x-shop.shipping-notice type="dhl" class="mt-4" />
@endif
