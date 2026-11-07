{{-- Small payment status label: paid / unpaid / failed. --}}
@props(['status'])

@php
    $styles = [
        'paid' => ['Paid', 'text-emerald-700', 'fa-circle-check'],
        'unpaid' => ['Unpaid', 'text-amber-700', 'fa-clock'],
        'failed' => ['Payment failed', 'text-red-700', 'fa-circle-xmark'],
    ];
    [$label, $colour, $icon] = $styles[$status] ?? [ucfirst($status), 'text-gray-600', 'fa-circle'];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 text-xs font-semibold whitespace-nowrap $colour"]) }}>
    <i class="fas {{ $icon }}" aria-hidden="true"></i>{{ $label }}
</span>
