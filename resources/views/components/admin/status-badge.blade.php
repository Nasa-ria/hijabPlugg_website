{{-- Coloured pill for an order status (colours defined in App\Models\Order::STATUSES). --}}
@props(['status'])

@php
    $config = \App\Models\Order::STATUSES[$status] ?? ['label' => ucfirst(str_replace('_', ' ', $status)), 'badge' => 'bg-gray-100 text-gray-600 ring-gray-300'];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset whitespace-nowrap ' . $config['badge']]) }}>
    <span class="h-1.5 w-1.5 rounded-full bg-current opacity-70" aria-hidden="true"></span>{{ $config['label'] }}
</span>
