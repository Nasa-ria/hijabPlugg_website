{{--
    Order confirmation + status update email (App\Mail\OrderStatusMail).
    Table layout and inline styles so it renders well in Gmail, Outlook and phone mail apps.
    Colours: dark plum #3a1234 · bright rose #e0708a · porcelain #faf4ef
--}}
@php
    $plum = '#3a1234'; $rose = '#e0708a'; $roseDark = '#c2506b'; $porcelain = '#faf4ef'; $line = '#e8d8cf';
    $serif = "'Bodoni Moda', Georgia, 'Times New Roman', serif";
    $sans = "Montserrat, 'Helvetica Neue', Helvetica, Arial, sans-serif";
    $flow = \App\Models\Order::FLOW;
    $step = array_search($status, $flow, true);
    $logo = file_exists(public_path('images/logo.png')) ? asset('images/logo.png') : null;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>{{ $copy['heading'] }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Bodoni+Moda:wght@500&family=Montserrat:wght@400;600&family=Parisienne&display=swap" rel="stylesheet">
    <style>
        @media (max-width: 600px) {
            .container { width: 100% !important; }
            .pad { padding-left: 20px !important; padding-right: 20px !important; }
            .item-img { width: 64px !important; height: 80px !important; }
        }
    </style>
</head>
<body style="margin:0; padding:0; background:{{ $porcelain }}; font-family:{{ $sans }}; color:{{ $plum }};">
    <div style="display:none; max-height:0; overflow:hidden;">{{ $copy['body'] }}</div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:{{ $porcelain }};">
        <tr><td align="center" style="padding:32px 12px;">
            <table role="presentation" class="container" width="600" cellpadding="0" cellspacing="0" style="width:600px; max-width:600px; background:#ffffff; border-radius:16px; overflow:hidden;">

                {{-- Brand --}}
                <tr><td align="center" style="background:{{ $plum }}; padding:28px 24px;">
                    @if($logo)
                        <img src="{{ $logo }}" alt="Nasaari Store" width="140" style="display:block; width:140px; height:auto; border:0;">
                    @else
                        <span style="font-family:Parisienne, 'Brush Script MT', cursive; font-size:40px; line-height:1; color:{{ $porcelain }};">Nasaari</span>
                        <span style="font-family:{{ $sans }}; font-size:11px; letter-spacing:3px; text-transform:uppercase; color:{{ $rose }}; padding-left:6px;">Store</span>
                    @endif
                </td></tr>

                {{-- Message --}}
                <tr><td class="pad" style="padding:36px 40px 8px;">
                    <p style="margin:0; font-size:11px; letter-spacing:3px; text-transform:uppercase; color:{{ $roseDark }}; font-weight:600;">Order {{ $order->order_number }}</p>
                    <h1 style="margin:10px 0 0; font-family:{{ $serif }}; font-weight:500; font-size:28px; line-height:1.25; color:{{ $plum }};">{{ $copy['heading'] }}</h1>
                    <p style="margin:14px 0 0; font-size:15px; line-height:1.7; color:#4b5563;">
                        Hi {{ $order->customer_name ?: 'there' }}, {{ $copy['body'] }}
                    </p>
                </td></tr>

                {{-- Status --}}
                <tr><td class="pad" style="padding:24px 40px 8px;">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:{{ $porcelain }}; border-radius:12px;">
                        <tr><td style="padding:18px 20px;">
                            <p style="margin:0; font-size:12px; color:#6b7280;">Current status</p>
                            <p style="margin:4px 0 0; font-size:17px; font-weight:600; color:{{ $status === 'cancelled' ? '#6b7280' : $plum }};">{{ $statusLabel }}</p>
                            @if($status !== 'cancelled' && $step !== false)
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:14px;">
                                    <tr>
                                        @foreach($flow as $i => $s)
                                            <td style="padding-right:4px;">
                                                <div style="height:4px; border-radius:4px; background:{{ $i <= $step ? $rose : $line }};"></div>
                                                <p style="margin:6px 0 0; font-size:10px; color:{{ $i <= $step ? $plum : '#9ca3af' }};">{{ \App\Models\Order::STATUSES[$s]['label'] }}</p>
                                            </td>
                                        @endforeach
                                    </tr>
                                </table>
                            @endif
                        </td></tr>
                    </table>
                </td></tr>

                {{-- Items --}}
                <tr><td class="pad" style="padding:24px 40px 0;">
                    <p style="margin:0 0 8px; font-family:{{ $serif }}; font-size:18px; color:{{ $plum }};">Your order</p>
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                        @foreach($order->items as $item)
                            @php
                                $path = $item->variant?->imagePath() ?? $item->product?->main_image;
                            @endphp
                            <tr>
                                <td width="80" style="padding:12px 0; border-bottom:1px solid {{ $line }}; vertical-align:top;">
                                    @if($path)
                                        <img class="item-img" src="{{ asset('storage/' . $path) }}" alt="{{ $item->product?->name }}" width="72" height="90" style="display:block; width:72px; height:90px; object-fit:cover; border-radius:8px; border:0;">
                                    @endif
                                </td>
                                <td style="padding:12px 12px; border-bottom:1px solid {{ $line }}; vertical-align:top;">
                                    <p style="margin:0; font-size:14px; font-weight:600; color:{{ $plum }};">{{ $item->product?->name ?? 'Product' }}</p>
                                    @if($item->color_name)
                                        <p style="margin:4px 0 0; font-size:13px; color:#6b7280;">Colour: {{ $item->color_name }}</p>
                                    @endif
                                    <p style="margin:4px 0 0; font-size:13px; color:#6b7280;">Qty {{ $item->quantity }} × ₵{{ number_format($item->price, 2) }}</p>
                                </td>
                                <td align="right" style="padding:12px 0; border-bottom:1px solid {{ $line }}; vertical-align:top; font-size:14px; font-weight:600; white-space:nowrap;">
                                    ₵{{ number_format($item->subtotal, 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </table>

                    {{-- Totals --}}
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:12px; font-size:13px; color:#4b5563;">
                        <tr><td style="padding:3px 0;">Subtotal</td><td align="right">₵{{ number_format($order->subtotal, 2) }}</td></tr>
                        <tr><td style="padding:3px 0;">{{ $order->shippingLabel() }}</td><td align="right">{{ $order->isPayOnDelivery() ? 'Paid on delivery' : '₵' . number_format($order->shipping_cost, 2) }}</td></tr>
                        @if((float) $order->tax > 0)
                            <tr><td style="padding:3px 0;">Tax</td><td align="right">₵{{ number_format($order->tax, 2) }}</td></tr>
                        @endif
                        <tr><td style="padding:10px 0 0; font-size:16px; font-weight:600; color:{{ $plum }};">Total</td>
                            <td align="right" style="padding:10px 0 0; font-size:16px; font-weight:600; color:{{ $plum }};">₵{{ number_format($order->total_amount, 2) }}</td></tr>
                    </table>
                </td></tr>

                {{-- Delivery --}}
                <tr><td class="pad" style="padding:24px 40px 0;">
                    <p style="margin:0 0 6px; font-family:{{ $serif }}; font-size:18px; color:{{ $plum }};">Delivering to</p>
                    <p style="margin:0; font-size:14px; line-height:1.6; color:#4b5563;">
                        {{ $order->customer_name }}<br>
                        {{ $order->shipping_address }}
                        @if($order->customer_phone)<br>{{ $order->customer_phone }}@endif
                    </p>
                </td></tr>

                {{-- Track --}}
                <tr><td align="center" class="pad" style="padding:32px 40px 36px;">
                    <a href="{{ $trackUrl }}" style="display:inline-block; background:{{ $rose }}; color:#ffffff; text-decoration:none; font-weight:600; font-size:14px; padding:14px 28px; border-radius:12px;">Track your order</a>
                    <p style="margin:14px 0 0; font-size:12px; color:#9ca3af;">Use your order number and this email address.</p>
                </td></tr>

                {{-- Footer --}}
                <tr><td align="center" style="background:{{ $porcelain }}; padding:20px 24px; font-size:12px; color:#6b7280; line-height:1.6;">
                    Questions? Just reply to this email.<br>
                    Nasaari Store · Hijabs | modesty &amp; more
                </td></tr>
            </table>
        </td></tr>
    </table>
</body>
</html>
