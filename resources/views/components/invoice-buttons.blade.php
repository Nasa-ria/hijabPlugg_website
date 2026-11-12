{{--
    "View / print receipt" and "Download PDF" buttons for an order.
    Customers see them once the order is paid; admins (admin="true") always.
--}}
@props(['order', 'admin' => false, 'size' => 'md'])

@php
    $prefix = $admin ? 'admin.orders.invoice' : 'orders.invoice';
    $label = $order->payment_status === 'paid' ? 'receipt' : 'invoice';
    $pad = $size === 'sm' ? '!py-2 !px-3 text-xs' : '';
@endphp

@if($admin || $order->payment_status === 'paid')
    <div {{ $attributes->merge(['class' => 'flex flex-wrap gap-2']) }}>
        <a href="{{ route($prefix, $order) }}" target="_blank" rel="noopener" class="btn-secondary {{ $pad }}">
            <i class="fas fa-print text-xs" aria-hidden="true"></i> Print {{ $label }}
        </a>
        <a href="{{ route($prefix . '.download', $order) }}" class="btn-brand {{ $pad }}">
            <i class="fas fa-file-pdf text-xs" aria-hidden="true"></i> Download PDF
        </a>
    </div>
@endif
