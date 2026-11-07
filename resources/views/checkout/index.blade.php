@extends('layouts.app')

@section('title', 'Checkout – Nasaari Store')

@section('content')
@php
    $domesticCountry = config('shipping.domestic_country', 'GH');
    $isDomestic = $country === $domesticCountry;
    $converted = $displayCurrency->isConverted();
@endphp
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 lg:py-14">
    <div class="flex flex-col gap-4 mb-8">
        <div class="inline-flex w-fit items-center gap-2 px-4 py-2 rounded-full bg-cream-200 text-brand-700 text-sm font-semibold shadow-sm shadow-brand-500/10">
            <i class="fas fa-lock text-xs"></i> Secure checkout powered by Paystack
        </div>
        <div>
            <h1 class="section-heading">Complete Your Order</h1>
            <p class="section-subheading max-w-2xl">Review your delivery details, confirm your items, and finish checkout with confidence.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-[1.6fr_1fr] gap-8">
        <div class="card-panel p-6 sm:p-8">
            <div class="flex flex-col gap-2 mb-8">
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-gray-400">Delivery address</p>
                <h2 class="text-2xl font-semibold text-gray-900">Where should we deliver your order?</h2>
            </div>

            <form action="{{ route('checkout.store') }}" method="POST" class="space-y-6" id="checkout-form"
                  data-checkout data-quote-url="{{ route('checkout.shipping-quote') }}" data-domestic="{{ $domesticCountry }}">
                @csrf

                <div class="space-y-3">
                    <label for="email" class="text-sm font-semibold text-gray-700">Email Address</label>
                    <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" placeholder="you@example.com" required autocomplete="email" class="input-field @error('email') border-red-500 ring-red-100 @enderror">
                    <p class="text-xs text-gray-500">We'll send your receipt and order updates here.</p>
                    @error('email')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="space-y-3">
                    <label for="country" class="text-sm font-semibold text-gray-700">Country</label>
                    <select id="country" name="country" required autocomplete="country" class="input-field @error('country') border-red-500 ring-red-100 @enderror" data-quote-field>
                        @foreach($countries as $code => $name)
                            <option value="{{ $code }}" @selected($country === $code)>{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('country')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="space-y-3">
                    <label for="shipping_address" class="text-sm font-semibold text-gray-700">Street Address</label>
                    <input id="shipping_address" type="text" name="shipping_address" value="{{ old('shipping_address', $user->address) }}" placeholder="House number, street, area" required autocomplete="street-address" class="input-field @error('shipping_address') border-red-500 ring-red-100 @enderror">
                    @error('shipping_address')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="space-y-3">
                        <label for="city" class="text-sm font-semibold text-gray-700">City</label>
                        <input id="city" type="text" name="city" value="{{ old('city', $user->city) }}" placeholder="City" required autocomplete="address-level2" class="input-field @error('city') border-red-500 ring-red-100 @enderror" data-quote-field>
                        @error('city')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div class="space-y-3">
                        <label for="postal_code" class="text-sm font-semibold text-gray-700">
                            Postal Code <span class="font-normal text-gray-400" data-postal-optional @if(! $isDomestic) hidden @endif>(optional in Ghana)</span>
                        </label>
                        <input id="postal_code" type="text" name="postal_code" value="{{ old('postal_code', $user->postal_code) }}" placeholder="Postal code / GhanaPost GPS" autocomplete="postal-code" class="input-field @error('postal_code') border-red-500 ring-red-100 @enderror" data-quote-field>
                        @error('postal_code')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="space-y-3">
                    <label for="phone" class="text-sm font-semibold text-gray-700">Phone Number</label>
                    <input id="phone" type="tel" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="+233 XX XXX XXXX" required autocomplete="tel" class="input-field @error('phone') border-red-500 ring-red-100 @enderror">
                    @error('phone')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>

                {{-- Shipping: Ghana pays on delivery; elsewhere a live DHL estimate --}}
                <section class="rounded-[1.75rem] border border-cream-300 bg-cream-200/70 p-5 sm:p-6 space-y-4" aria-labelledby="shipping-heading" aria-live="polite">
                    <h3 id="shipping-heading" class="flex items-center gap-3 font-semibold text-brand-800">
                        <span class="inline-flex w-9 h-9 items-center justify-center rounded-2xl bg-accent-100 text-accent-700"><i class="fas fa-truck"></i></span>
                        Shipping
                    </h3>

                    <div data-shipping-pod @if(! $isDomestic) hidden @endif>
                        <x-shop.shipping-notice type="pod" />
                    </div>

                    <div data-shipping-dhl @if($isDomestic) hidden @endif class="space-y-4">
                        <div class="rounded-2xl bg-white p-4 shadow-sm border border-gray-100">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-gray-800">DHL Express estimate</p>
                                    <p class="text-sm text-gray-600" data-quote-status>
                                        @if($quote)
                                            {{ $quote['service'] }}@if($quote['estimated_delivery']) · arrives around {{ \Carbon\Carbon::parse($quote['estimated_delivery'])->format('D j M') }}@endif
                                        @else
                                            Enter your country, city and postal code to see the DHL rate.
                                        @endif
                                    </p>
                                </div>
                                <p class="text-xl font-bold text-brand-700" data-quote-amount>{{ $quote ? \App\Support\Money::show($quote['amount']) : '' }}</p>
                            </div>
                            <button type="button" class="btn-secondary btn-sm mt-3" data-quote-button>
                                <i class="fas fa-calculator text-xs" aria-hidden="true"></i> <span>{{ $quote ? 'Recalculate' : 'Calculate shipping' }}</span>
                            </button>
                            <p class="mt-3 hidden rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700" role="alert" data-quote-error></p>
                        </div>
                        <x-shop.shipping-notice type="dhl" />
                    </div>
                </section>

                <button type="submit" class="btn-brand w-full text-center" data-checkout-submit>
                    <i class="fas fa-lock text-xs" aria-hidden="true"></i> Continue to Payment
                </button>
                <p class="text-center text-xs text-gray-500 hidden" data-submit-hint>Calculate the DHL shipping estimate before continuing.</p>
            </form>
        </div>

        <aside class="space-y-6">
            <div class="card-panel p-6 sm:p-7 xl:sticky xl:top-24">
                <div class="flex items-start justify-between gap-4 mb-6">
                    <div>
                        <p class="text-sm text-gray-400 uppercase tracking-[0.2em]">Order summary</p>
                        <h2 class="text-2xl font-semibold text-gray-900">Your cart</h2>
                    </div>
                    <span class="status-chip status-chip-pending">Review</span>
                </div>

                <div class="space-y-4 mb-6">
                    @foreach($cart as $item)
                        <div class="flex items-center gap-4 rounded-3xl border border-gray-100 bg-white p-4">
                            <div class="w-16 h-16 flex-shrink-0 rounded-3xl bg-gray-100 overflow-hidden flex items-center justify-center">
                                <img src="{{ asset('storage/' . $item['image']) }}" alt="{{ $item['name'] }}" class="w-full h-full object-cover">
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-900 truncate">{{ $item['name'] }}</p>
                                <p class="text-xs text-gray-500">@if(!empty($item['color_name'])){{ $item['color_name'] }} · @endif Qty {{ $item['quantity'] }}</p>
                            </div>
                            <p class="ml-auto text-sm font-semibold text-gray-900 whitespace-nowrap">@money($item['price'] * $item['quantity'])</p>
                        </div>
                    @endforeach
                </div>

                <dl class="space-y-3 text-sm text-gray-600 border-t border-gray-100 pt-5">
                    <div class="flex justify-between">
                        <dt>Subtotal</dt>
                        <dd class="font-semibold text-gray-900" data-total="subtotal">@money($totals['subtotal'])</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt>Shipping</dt>
                        <dd class="font-semibold text-right" data-total="shipping"
                            data-pod-label="Pay on delivery" data-pending-label="Calculated by DHL">
                            @if($isDomestic)
                                <span class="text-emerald-700">Pay on delivery</span>
                            @elseif($quote)
                                <span class="text-gray-900">@money($quote['amount'])</span>
                            @else
                                <span class="text-gray-500">Calculated by DHL</span>
                            @endif
                        </dd>
                    </div>
                    @if($totals['tax'] > 0)
                        <div class="flex justify-between">
                            <dt>Tax ({{ rtrim(rtrim(number_format($totals['tax_rate'] * 100, 2), '0'), '.') }}%)</dt>
                            <dd class="font-semibold text-gray-900" data-total="tax">@money($totals['tax'])</dd>
                        </div>
                    @endif
                </dl>

                <div class="flex justify-between items-center pt-5 mt-5 border-t border-gray-100">
                    <span class="text-base font-semibold text-gray-900">Total</span>
                    <span class="text-2xl font-bold text-brand-600" data-total="total">@money($totals['total'])</span>
                </div>
                <p class="mt-1 text-right text-xs text-gray-500 @unless($converted) hidden @endunless" data-total="total_ghs">
                    @if($converted) You'll be charged @ghs($totals['total']) by Paystack. @endif
                </p>

                <div class="mt-6 space-y-3">
                    <a href="{{ route('cart.index') }}" class="btn-secondary w-full">Back to Cart</a>
                    <p class="text-sm text-gray-500">Need to update items? Return to cart before you continue.</p>
                </div>
            </div>

            <div class="card-panel p-6">
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-gray-400 mb-4">Trusted by shoppers</p>
                <div class="space-y-3 text-sm text-gray-600">
                    <p class="flex items-center gap-3"><i class="fas fa-shield-halved text-accent-500"></i>Secure payments and verified payouts.</p>
                    <p class="flex items-center gap-3"><i class="fas fa-truck-fast text-accent-500"></i>Fast delivery with premium packaging.</p>
                    <p class="flex items-center gap-3"><i class="fas fa-headset text-accent-500"></i>Dedicated support for every order.</p>
                </div>
            </div>
        </aside>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Checkout shipping: Ghana shows the pay-on-delivery note; other countries ask the server
    // for a DHL estimate (calculated from the cart on the server, never from this page).
    (function () {
        const form = document.querySelector('[data-checkout]');
        if (!form) return;

        const $ = (sel) => form.closest('.grid').querySelector(sel);
        const country = form.querySelector('#country');
        const pod = form.querySelector('[data-shipping-pod]');
        const dhl = form.querySelector('[data-shipping-dhl]');
        const button = form.querySelector('[data-quote-button]');
        const status = form.querySelector('[data-quote-status]');
        const amount = form.querySelector('[data-quote-amount]');
        const error = form.querySelector('[data-quote-error]');
        const submit = form.querySelector('[data-checkout-submit]');
        const hint = form.querySelector('[data-submit-hint]');
        const optional = form.querySelector('[data-postal-optional]');
        const shippingCell = $('[data-total="shipping"]');
        let quoted = {{ ($isDomestic || $quote) ? 'true' : 'false' }};
        let timer;

        const isDomestic = () => country.value === form.dataset.domestic;
        const setTotal = (key, text) => { const el = $(`[data-total="${key}"]`); if (el && text != null) el.textContent = text; };
        const setShippingCell = (html) => { shippingCell.innerHTML = html; };
        const escape = (t) => String(t).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

        function refreshMode() {
            const domestic = isDomestic();
            pod.hidden = !domestic;
            dhl.hidden = domestic;
            if (optional) optional.hidden = !domestic;
            form.querySelector('#postal_code').required = !domestic;
            if (domestic) {
                quoted = true;
                setShippingCell('<span class="text-emerald-700">' + shippingCell.dataset.podLabel + '</span>');
            } else if (!quoted) {
                setShippingCell('<span class="text-gray-500">' + shippingCell.dataset.pendingLabel + '</span>');
            }
            submit.disabled = !quoted;
            hint.classList.toggle('hidden', quoted);
        }

        async function requestQuote() {
            const data = new FormData(form);
            error.classList.add('hidden');
            if (!isDomestic() && (!data.get('city') || !data.get('postal_code'))) {
                status.textContent = 'Enter your city and postal code to see the DHL rate.';
                return;
            }

            button.disabled = true;
            button.querySelector('span').textContent = 'Asking DHL…';
            status.textContent = 'Getting the latest DHL rate for your parcel…';
            form.closest('.card-panel').setAttribute('aria-busy', 'true');

            try {
                const response = await fetch(form.dataset.quoteUrl, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    body: data,
                });
                const json = await response.json();
                if (!response.ok) throw new Error(json.message || Object.values(json.errors || {})[0]?.[0] || 'We could not calculate shipping.');

                setTotal('subtotal', json.formatted.subtotal);
                setTotal('tax', json.formatted.tax);
                setTotal('total', json.formatted.total);
                const ghs = $('[data-total="total_ghs"]');
                if (ghs && window.NasaariCurrency && window.NasaariCurrency.code !== window.NasaariCurrency.base) {
                    ghs.textContent = "You'll be charged " + json.formatted.total_ghs + ' by Paystack.';
                    ghs.classList.remove('hidden');
                }

                if (json.quote) {
                    amount.textContent = json.formatted.shipping;
                    status.textContent = json.quote.service + (json.quote.estimated_delivery ? ' · arrives around ' + json.quote.estimated_delivery : '')
                        + ' · ' + json.quote.chargeable_weight + ' kg chargeable';
                    setShippingCell('<span class="text-gray-900">' + escape(json.formatted.shipping) + '</span>');
                }
                quoted = true;
            } catch (e) {
                quoted = false;
                amount.textContent = '';
                status.textContent = 'No estimate yet.';
                error.textContent = e.message;
                error.classList.remove('hidden');
            } finally {
                button.disabled = false;
                button.querySelector('span').textContent = quoted ? 'Recalculate' : 'Try again';
                form.closest('.card-panel').removeAttribute('aria-busy');
                refreshMode();
            }
        }

        button.addEventListener('click', requestQuote);
        form.querySelectorAll('[data-quote-field]').forEach((field) => {
            field.addEventListener('change', () => {
                if (!isDomestic()) quoted = false;
                refreshMode();
                clearTimeout(timer);
                if (!isDomestic()) timer = setTimeout(requestQuote, 400);
            });
        });
        form.addEventListener('submit', () => { submit.disabled = true; submit.innerHTML = 'Placing your order…'; });

        refreshMode();
    })();
</script>
@endpush
