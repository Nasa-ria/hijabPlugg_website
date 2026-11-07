{{--
    Inventory and shipping details of a product: SKU, purchase cost (kept apart from the
    selling price for profit reports), low-stock alert level and DHL parcel measurements.
--}}
@php
    $p = $product ?? null;
    $defaults = config('shipping.parcel');
@endphp

<div class="rounded-2xl border border-cream-300 bg-cream-50/60 p-6 space-y-6">
    <div>
        <p class="font-serif text-lg text-brand-900">Inventory &amp; costs</p>
        <p class="text-sm text-gray-500">The purchase cost is what one unit costs you. It is never shown to customers and is used to work out profit.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div>
            <label class="admin-label" for="sku">SKU</label>
            <input id="sku" type="text" name="sku" maxlength="64" value="{{ old('sku', $p?->sku) }}" placeholder="{{ $p ? '' : 'Created automatically' }}" class="input-field">
            @error('sku')<p class="text-red-500 text-sm">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="admin-label" for="cost_price">Purchase cost (₵ per unit)</label>
            <input id="cost_price" type="number" name="cost_price" step="0.01" min="0" value="{{ old('cost_price', $p?->cost_price) }}" placeholder="0.00" class="input-field">
            @error('cost_price')<p class="text-red-500 text-sm">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="admin-label" for="low_stock_threshold">Low-stock alert at</label>
            <input id="low_stock_threshold" type="number" name="low_stock_threshold" min="0" value="{{ old('low_stock_threshold', $p?->low_stock_threshold) }}" placeholder="Default: {{ config('shop.inventory.low_stock_threshold') }}" class="input-field">
            @error('low_stock_threshold')<p class="text-red-500 text-sm">{{ $message }}</p>@enderror
        </div>
    </div>

    <div>
        <p class="font-semibold text-brand-900">Shipping measurements <span class="font-normal text-sm text-gray-500">(one unit, folded and ready to pack)</span></p>
        <p class="text-sm text-gray-500">Used to get exact DHL rates for international orders. DHL charges by actual or volumetric weight, whichever is higher.</p>
        <div class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach([
                'weight_kg' => ['Weight (kg)', '0.001', $defaults['default_item_weight_kg']],
                'length_cm' => ['Length (cm)', '0.1', $defaults['default_item_dimensions_cm']['length']],
                'width_cm' => ['Width (cm)', '0.1', $defaults['default_item_dimensions_cm']['width']],
                'height_cm' => ['Height (cm)', '0.1', $defaults['default_item_dimensions_cm']['height']],
            ] as $field => [$label, $step, $default])
                <div>
                    <label class="admin-label" for="{{ $field }}">{{ $label }}</label>
                    <input id="{{ $field }}" type="number" name="{{ $field }}" step="{{ $step }}" min="0" value="{{ old($field, $p?->$field) }}" placeholder="Default {{ $default }}" class="input-field">
                    @error($field)<p class="text-red-500 text-sm">{{ $message }}</p>@enderror
                </div>
            @endforeach
        </div>
    </div>

    @if($p)
        <p class="text-xs text-gray-500">
            Stock changes made on this form are recorded in the stock history as adjustments.
            For deliveries, use <a href="{{ route('admin.inventory.index', ['q' => $p->sku]) }}" class="font-semibold text-accent-600 hover:underline">Inventory → Add stock</a> so they count as stock added.
        </p>
    @endif
</div>
