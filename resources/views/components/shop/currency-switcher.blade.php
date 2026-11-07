{{--
    Display-currency picker (GH₵ / US$). Detection picks one automatically; this lets the
    customer override it. Prices are always charged in Ghana Cedis.
--}}
@props(['id' => 'currency', 'compact' => false])

@php
    $currencyService = app(\App\Services\CurrencyService::class);
    $current = $currencyService->current();
    $options = config('currency.supported');
@endphp

<form method="POST" action="{{ route('currency.update') }}" {{ $attributes->merge(['class' => 'currency-switcher']) }} data-currency-switcher>
    @csrf
    <label for="{{ $id }}" class="sr-only">Show prices in</label>
    <span class="currency-switcher__icon" aria-hidden="true"><i class="fas fa-globe"></i></span>
    <select id="{{ $id }}" name="currency" class="currency-switcher__select" onchange="this.form.submit()" title="Prices shown in {{ $options[$current]['name'] ?? $current }}">
        @foreach($options as $code => $meta)
            <option value="{{ $code }}" @selected($code === $current)>{{ $compact ? $meta['symbol'] : $meta['symbol'] . ' ' . $code }}</option>
        @endforeach
    </select>
    <noscript><button type="submit" class="text-xs font-semibold">Set</button></noscript>
</form>
