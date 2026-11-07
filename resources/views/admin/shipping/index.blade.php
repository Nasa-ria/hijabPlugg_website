@extends('layouts.admin')

@section('page_title', 'Shipping')

@section('content')
<div class="grid grid-cols-1 xl:grid-cols-[1.2fr_1fr] gap-6">
    <div class="space-y-6">
        {{-- Ghana --}}
        <section class="admin-card space-y-3">
            <h2 class="font-serif text-2xl text-brand-900"><i class="fas fa-truck text-accent-500 text-xl mr-2" aria-hidden="true"></i>{{ \App\Support\Countries::name($domestic) }}: pay on delivery</h2>
            <p class="text-sm text-gray-600">Customers delivering to {{ \App\Support\Countries::name($domestic) }} pay only for their products at checkout. The delivery fee is paid directly to the courier, so it is never added to the order total.</p>
        </section>

        {{-- DHL --}}
        <section class="admin-card space-y-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="font-serif text-2xl text-brand-900"><i class="fas fa-plane-departure text-accent-500 text-xl mr-2" aria-hidden="true"></i>International: DHL Express</h2>
                    <p class="mt-1 text-sm text-gray-600">Live rates from the MyDHL API, based on each order's weight, dimensions and destination.</p>
                </div>
                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $configured ? 'bg-emerald-50 text-emerald-800' : 'bg-red-50 text-red-700' }}">
                    {{ $configured ? 'Connected (' . $environment . ')' : 'Not configured' }}
                </span>
            </div>

            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                @foreach($checks as $key => $ok)
                    <div class="flex items-center justify-between rounded-xl bg-cream-50 px-4 py-2.5">
                        <dt class="font-mono text-xs text-gray-600">{{ $key }}</dt>
                        <dd class="font-semibold {{ $ok ? 'text-emerald-700' : 'text-red-700' }}">{{ $ok ? 'Set' : 'Missing' }}</dd>
                    </div>
                @endforeach
                <div class="flex items-center justify-between rounded-xl bg-cream-50 px-4 py-2.5">
                    <dt class="text-gray-600">Environment</dt>
                    <dd class="font-semibold text-brand-900">{{ ucfirst($environment) }}</dd>
                </div>
                <div class="flex items-center justify-between rounded-xl bg-cream-50 px-4 py-2.5">
                    <dt class="text-gray-600">DHL product</dt>
                    <dd class="font-semibold text-brand-900">{{ $productCode ?: 'Cheapest available' }}</dd>
                </div>
                <div class="sm:col-span-2 flex items-center justify-between rounded-xl bg-cream-50 px-4 py-2.5">
                    <dt class="text-gray-600">Ships from</dt>
                    <dd class="font-semibold text-brand-900">{{ collect([$origin['address'], $origin['city'], $origin['postal_code'], \App\Support\Countries::name($origin['country'])])->filter()->implode(', ') }}</dd>
                </div>
            </dl>

            <details class="rounded-xl border border-cream-300 p-4 text-sm text-gray-600">
                <summary class="cursor-pointer font-semibold text-brand-900">How to connect or change DHL</summary>
                <p class="mt-3">For security, DHL credentials are kept in the server's <code>.env</code> file and are never stored in the database or shown here. Ask your developer or host to set:</p>
                <pre class="mt-3 overflow-x-auto rounded-xl bg-brand-dark p-4 text-xs text-cream-100">DHL_API_KEY=your-key
DHL_API_SECRET=your-secret
DHL_ACCOUNT_NUMBER=your-dhl-account
DHL_ENVIRONMENT=production   # or "test"
SHIPPING_ORIGIN_CITY=Accra
SHIPPING_ORIGIN_POSTAL_CODE=
SHIPPING_ORIGIN_ADDRESS=</pre>
                <p class="mt-3">Then run <code>php artisan config:clear</code>. API keys come from developer.dhl.com (MyDHL API).</p>
            </details>

            <div class="rounded-xl border border-cream-300 p-4 text-sm text-gray-600 space-y-1">
                <p class="font-semibold text-brand-900">Parcel rules</p>
                <p>Units are stacked in a box: length and width of the largest item, height of the stack, plus {{ $parcel['packaging_padding_cm'] }} cm padding and {{ $parcel['packaging_weight_kg'] }} kg packaging. A new box starts above {{ $parcel['max_package_height_cm'] }} cm or {{ $parcel['max_package_weight_kg'] }} kg.</p>
                <p>Products without measurements use {{ $parcel['default_item_weight_kg'] }} kg and {{ implode(' × ', $parcel['default_item_dimensions_cm']) }} cm.</p>
                @if($missingShippingData)
                    <p class="text-amber-700"><i class="fas fa-triangle-exclamation" aria-hidden="true"></i> {{ $missingShippingData }} listed {{ Str::plural('product', $missingShippingData) }} have no measurements yet; add them on each product's page for exact rates.</p>
                @endif
            </div>
        </section>
    </div>

    {{-- Test quote --}}
    <section class="admin-card space-y-5 self-start">
        <div>
            <h2 class="font-serif text-2xl text-brand-900">Test a DHL quote</h2>
            <p class="mt-1 text-sm text-gray-600">Checks the connection and shows what a customer would pay. Nothing is booked.</p>
        </div>
        <form method="POST" action="{{ route('admin.shipping.test') }}" class="space-y-4">
            @csrf
            <div>
                <label class="admin-label" for="t-country">Destination country</label>
                <select id="t-country" name="country" class="input-field" required>
                    @foreach($countries as $code => $name)
                        @continue($code === $domestic)
                        <option value="{{ $code }}" @selected(old('country', 'US') === $code)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="admin-label" for="t-city">City</label>
                    <input id="t-city" type="text" name="city" value="{{ old('city', 'New York') }}" required class="input-field">
                </div>
                <div>
                    <label class="admin-label" for="t-postal">Postal code</label>
                    <input id="t-postal" type="text" name="postal_code" value="{{ old('postal_code', '10001') }}" class="input-field">
                </div>
            </div>
            <div class="grid grid-cols-[1fr_100px] gap-4">
                <div>
                    <label class="admin-label" for="t-product">Product</label>
                    <select id="t-product" name="product_id" class="input-field" required>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}" @selected((int) old('product_id') === $product->id)>{{ $product->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="admin-label" for="t-qty">Qty</label>
                    <input id="t-qty" type="number" name="quantity" min="1" max="50" value="{{ old('quantity', 1) }}" required class="input-field">
                </div>
            </div>
            <button type="submit" class="btn-brand w-full" @disabled($products->isEmpty())>Get quote</button>
        </form>

        @if($result)
            @if($result['ok'])
                @php $q = $result['quote']; @endphp
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900 space-y-1">
                    <p class="text-lg font-semibold">₵{{ number_format($q['amount'], 2) }} <span class="text-sm font-normal">({{ $q['service'] }})</span></p>
                    @if($q['provider_currency'] !== 'GHS')
                        <p>DHL rate: {{ $q['provider_currency'] }} {{ number_format($q['provider_amount'], 2) }}, converted to GHS.</p>
                    @endif
                    <p>{{ count($q['packages']) }} {{ Str::plural('box', count($q['packages'])) }} · actual {{ $q['actual_weight'] }} kg · volumetric {{ $q['volumetric_weight'] }} kg · charged on {{ $q['chargeable_weight'] }} kg</p>
                    @if($q['estimated_delivery'])<p>Estimated delivery: {{ \Carbon\Carbon::parse($q['estimated_delivery'])->format('D j M Y') }}</p>@endif
                </div>
            @else
                <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700" role="alert">{{ $result['message'] }}</div>
            @endif
        @endif
    </section>
</div>
@endsection
