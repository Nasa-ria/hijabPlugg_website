{{--
    Shipping notices.
      type="pod": Ghana – the delivery fee is paid to the courier on delivery, not at checkout.
      type="dhl": International – the DHL amount is an estimate based on weight, size and destination.
--}}
@props(['type' => 'pod', 'compact' => false])

@if($type === 'pod')
    <div {{ $attributes->merge(['class' => 'shipping-notice shipping-notice--pod']) }} role="note" data-shipping-notice="pod">
        <span class="shipping-notice__icon" aria-hidden="true"><i class="fas fa-truck"></i></span>
        <div>
            <p class="font-semibold text-brand-900">Delivery fee paid on delivery</p>
            <p class="mt-0.5">Shipping fees are not included in your order total. You will pay the delivery fee directly upon delivery.</p>
        </div>
    </div>
@else
    <div {{ $attributes->merge(['class' => 'shipping-notice shipping-notice--dhl']) }} role="note" data-shipping-notice="dhl">
        <span class="shipping-notice__icon" aria-hidden="true"><i class="fas fa-plane-departure"></i></span>
        <div>
            <p class="font-semibold text-brand-900">International delivery with DHL Express</p>
            <p class="mt-0.5">Shipping costs are calculated based on the total weight and dimensions of your order, as well as the delivery destination. The displayed amount is an estimate provided by DHL and may vary depending on the final shipment measurements.</p>
        </div>
    </div>
@endif
