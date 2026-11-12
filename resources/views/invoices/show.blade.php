{{--
    Invoice / payment receipt. Rendered as a printable page and as a PDF (dompdf),
    so it uses tables and plain CSS only (no flex/grid). Amounts are GHS, as charged.
--}}
@php
    $ghs = fn ($v) => 'GHS ' . number_format((float) $v, 2);
    $paid = $order->payment_status === 'paid';
    $refunded = (float) $order->returns->sum('refund_amount');
    $reference = $order->transaction_id ?: $order->payment->firstWhere('status', 'success')?->external_reference;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>{{ $paid ? 'Receipt' : 'Invoice' }} {{ $order->invoiceNumber() }} – {{ $store['name'] }}</title>
    <style>
        @page { margin: 28px 34px; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'DejaVu Sans', 'Helvetica Neue', Arial, sans-serif; font-size: 12px; color: #2d2a32; background: {{ $pdf ? '#fff' : '#f3e9e2' }}; }
        .sheet { max-width: 800px; margin: {{ $pdf ? '0' : '32px auto' }}; background: #fff; padding: {{ $pdf ? '0' : '44px 48px' }}; {{ $pdf ? '' : 'border-radius: 18px; box-shadow: 0 20px 50px -30px rgba(58,18,52,.45);' }} }
        table { width: 100%; border-collapse: collapse; }
        td, th { vertical-align: top; }
        .brand { font-size: 26px; font-weight: bold; color: #3a1234; letter-spacing: .5px; }
        .muted { color: #6b7280; }
        .small { font-size: 10.5px; }
        .title { font-size: 22px; font-weight: bold; color: #3a1234; text-align: right; text-transform: uppercase; letter-spacing: 2px; }
        .badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 10px; font-weight: bold; letter-spacing: 1px; text-transform: uppercase; }
        .badge-paid { background: #d1fae5; color: #065f46; }
        .badge-unpaid { background: #fef3c7; color: #92400e; }
        .label { font-size: 9.5px; font-weight: bold; text-transform: uppercase; letter-spacing: 1.2px; color: #c2506b; margin-bottom: 5px; }
        .rule { border-top: 2px solid #3a1234; margin: 22px 0 18px; }
        .items th { background: #3a1234; color: #fff; font-size: 10px; text-transform: uppercase; letter-spacing: 1px; padding: 9px 10px; text-align: left; }
        .items td { padding: 10px; border-bottom: 1px solid #eee3dc; }
        .items .num { text-align: right; white-space: nowrap; }
        .totals td { padding: 5px 10px; }
        .totals .grand td { border-top: 2px solid #3a1234; padding-top: 10px; font-size: 15px; font-weight: bold; color: #3a1234; }
        .note { margin-top: 18px; padding: 12px 14px; border-radius: 10px; background: #fdf2f5; color: #46183b; font-size: 11px; }
        .footer { margin-top: 34px; text-align: center; font-size: 10.5px; color: #6b7280; }
        .toolbar { max-width: 800px; margin: 24px auto 0; text-align: right; font-family: Arial, sans-serif; }
        .toolbar a, .toolbar button { display: inline-block; margin-left: 8px; padding: 10px 18px; border-radius: 12px; font-size: 13px; font-weight: bold; text-decoration: none; cursor: pointer; border: 1px solid #3a1234; }
        .btn-plum { background: #3a1234; color: #fff; }
        .btn-light { background: #fff; color: #3a1234; }
        @media print {
            body { background: #fff; }
            .toolbar { display: none; }
            .sheet { margin: 0; padding: 0; box-shadow: none; border-radius: 0; max-width: none; }
        }
    </style>
</head>
<body>
    @unless($pdf)
        <div class="toolbar">
            <a href="{{ $backUrl }}" class="btn-light" style="float:left; margin-left:0;">&larr; Back to order</a>
            <button type="button" class="btn-light" onclick="window.print()">Print</button>
            <a href="{{ $downloadUrl }}" class="btn-plum">Download PDF</a>
        </div>
    @endunless

    <div class="sheet">
        {{-- Header --}}
        <table>
            <tr>
                <td style="width:55%;">
                    <div class="brand">{{ $store['name'] }}</div>
                    <div class="muted small" style="margin-top:6px; line-height:1.6;">
                        @if($store['address']){{ $store['address'] }}<br>@endif
                        @if($store['email']){{ $store['email'] }}<br>@endif
                        @if($store['phone']){{ $store['phone'] }}<br>@endif
                        @if($store['website']){{ $store['website'] }}@endif
                    </div>
                </td>
                <td style="width:45%; text-align:right;">
                    <div class="title">{{ $paid ? 'Receipt' : 'Invoice' }}</div>
                    <div style="margin-top:8px;"><span class="badge {{ $paid ? 'badge-paid' : 'badge-unpaid' }}">{{ $paid ? 'Paid' : ucfirst($order->payment_status) }}</span></div>
                    <table style="margin-top:12px;">
                        <tr><td class="muted" style="text-align:right; padding:2px 8px;">Invoice no.</td><td style="text-align:right; font-weight:bold; white-space:nowrap;">{{ $order->invoiceNumber() }}</td></tr>
                        <tr><td class="muted" style="text-align:right; padding:2px 8px;">Order no.</td><td style="text-align:right; white-space:nowrap;">{{ $order->order_number }}</td></tr>
                        <tr><td class="muted" style="text-align:right; padding:2px 8px;">Order date</td><td style="text-align:right; white-space:nowrap;">{{ $order->created_at->format('j M Y') }}</td></tr>
                        @if($order->paid_at)
                            <tr><td class="muted" style="text-align:right; padding:2px 8px;">Paid on</td><td style="text-align:right; white-space:nowrap;">{{ $order->paid_at->format('j M Y, H:i') }}</td></tr>
                        @endif
                    </table>
                </td>
            </tr>
        </table>

        <div class="rule"></div>

        {{-- Customer, delivery, payment --}}
        <table>
            <tr>
                <td style="width:36%; padding-right:14px;">
                    <div class="label">Billed to</div>
                    <strong>{{ $order->customer_name ?? $order->user?->name }}</strong><br>
                    <span class="muted">{{ $order->contactEmail() }}</span>
                    @if($order->customer_phone)<br><span class="muted">{{ $order->customer_phone }}</span>@endif
                </td>
                <td style="width:36%; padding-right:14px;">
                    <div class="label">Deliver to</div>
                    {{ $order->shipping_address }}
                </td>
                <td style="width:28%;">
                    <div class="label">Payment</div>
                    {{ $order->payment_method ? ucfirst($order->payment_method) : '—' }}
                    @if($reference)<br><span class="muted small">Ref: {{ $reference }}</span>@endif
                </td>
            </tr>
        </table>

        {{-- Items --}}
        <table class="items" style="margin-top:24px;">
            <thead>
                <tr>
                    <th style="width:50%;">Item</th>
                    <th class="num">Qty</th>
                    <th class="num">Unit price</th>
                    <th class="num">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                    <tr>
                        <td>
                            <strong>{{ $item->product?->name ?? 'Item' }}</strong>
                            @if($item->color_name)<br><span class="muted small">Colour: {{ $item->color_name }}</span>@endif
                            @if($item->variant?->sku ?? $item->product?->sku)<br><span class="muted small">SKU: {{ $item->variant?->sku ?? $item->product?->sku }}</span>@endif
                        </td>
                        <td class="num">{{ $item->quantity }}</td>
                        <td class="num">{{ $ghs($item->price) }}</td>
                        <td class="num">{{ $ghs($item->subtotal) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Totals --}}
        <table style="margin-top:14px;">
            <tr>
                <td style="width:50%;"></td>
                <td style="width:50%;">
                    <table class="totals">
                        <tr><td class="muted">Subtotal</td><td style="text-align:right;">{{ $ghs($order->subtotal) }}</td></tr>
                        <tr>
                            <td class="muted">{{ $order->isPayOnDelivery() ? 'Delivery' : $order->shippingLabel() }}</td>
                            <td style="text-align:right;">{{ $order->isPayOnDelivery() ? 'Paid on delivery' : $ghs($order->shipping_cost) }}</td>
                        </tr>
                        @if((float) $order->tax > 0)
                            <tr><td class="muted">Tax</td><td style="text-align:right;">{{ $ghs($order->tax) }}</td></tr>
                        @endif
                        <tr class="grand"><td>{{ $paid ? 'Total paid' : 'Total due' }}</td><td style="text-align:right;">{{ $ghs($order->total_amount) }}</td></tr>
                        @if($refunded > 0)
                            <tr><td class="muted">Refunded (returns)</td><td style="text-align:right;">− {{ $ghs($refunded) }}</td></tr>
                        @endif
                        @if($order->displayTotal())
                            <tr><td class="muted small" colspan="2" style="text-align:right;">Shown at checkout as {{ $order->displayTotal() }} ({{ $order->display_currency }})</td></tr>
                        @endif
                    </table>
                </td>
            </tr>
        </table>

        @if($order->isPayOnDelivery())
            <div class="note">The delivery fee is not included in this total. It is paid directly to the courier on delivery.</div>
        @elseif($order->isDhl() && ! $order->shipping_confirmed_at)
            <div class="note">International shipping is the DHL Express estimate charged at checkout. You will not be charged again for shipping.</div>
        @endif

        <div class="footer">
            Thank you for shopping with {{ $store['name'] }}.
            @if($store['email']) Questions about this order? Contact {{ $store['email'] }}. @endif
            <br>All amounts are in Ghana Cedis (GHS).
        </div>
    </div>
</body>
</html>
