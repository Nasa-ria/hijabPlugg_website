{{--
    "New order" email for the store (App\Mail\NewOrderAdminMail). Same branded look as the customer emails.
--}}
@php
    $plum = '#3a1234'; $rose = '#e0708a'; $porcelain = '#faf4ef'; $line = '#e8d8cf';
    $serif = "'Bodoni Moda', Georgia, 'Times New Roman', serif";
    $sans = "Montserrat, 'Helvetica Neue', Helvetica, Arial, sans-serif";
    $paid = $order->payment_status === 'paid';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>New order {{ $order->order_number }}</title>
</head>
<body style="margin:0; padding:0; background:{{ $porcelain }}; font-family:{{ $sans }}; color:{{ $plum }};">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:{{ $porcelain }};">
        <tr><td align="center" style="padding:28px 12px;">
            <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:600px; max-width:100%; background:#ffffff; border-radius:16px; overflow:hidden;">
                <tr><td style="background:{{ $plum }}; padding:22px 32px;">
                    <span style="font-size:11px; letter-spacing:3px; text-transform:uppercase; color:{{ $rose }};">New order</span>
                    <p style="margin:6px 0 0; font-family:{{ $serif }}; font-size:24px; color:{{ $porcelain }};">#{{ $order->order_number }} · ₵{{ number_format($order->total_amount, 2) }}</p>
                    <p style="margin:6px 0 0; font-size:13px; color:#d9c0b3;">{{ $order->created_at->format('j M Y, g:i a') }}</p>
                </td></tr>

                {{-- Payment + customer --}}
                <tr><td style="padding:24px 32px 0;">
                    <p style="margin:0; display:inline-block; padding:4px 12px; border-radius:999px; font-size:12px; font-weight:600; background:{{ $paid ? '#ecfdf5' : '#fffbeb' }}; color:{{ $paid ? '#065f46' : '#92400e' }};">
                        Payment: {{ $paid ? 'Paid' : ucfirst($order->payment_status) }}
                    </p>
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:18px; font-size:14px; line-height:1.7; color:#4b5563;">
                        <tr><td style="width:110px; color:#6b7280; vertical-align:top;">Customer</td><td style="color:{{ $plum }}; font-weight:600;">{{ $order->customer_name ?? $order->user?->name }}</td></tr>
                        <tr><td style="color:#6b7280; vertical-align:top;">Email</td><td><a href="mailto:{{ $order->contactEmail() }}" style="color:{{ $plum }};">{{ $order->contactEmail() }}</a></td></tr>
                        <tr><td style="color:#6b7280; vertical-align:top;">Phone</td><td>{{ $order->customer_phone ?: 'Not given' }}</td></tr>
                        <tr><td style="color:#6b7280; vertical-align:top;">Deliver to</td><td>{{ $order->shipping_address }}</td></tr>
                    </table>
                </td></tr>

                {{-- Items --}}
                <tr><td style="padding:20px 32px 0;">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                        @foreach($order->items as $item)
                            @php $path = $item->variant?->imagePath() ?? $item->product?->main_image; @endphp
                            <tr>
                                <td width="64" style="padding:10px 0; border-top:1px solid {{ $line }}; vertical-align:top;">
                                    @if($path)<img src="{{ asset('storage/' . $path) }}" alt="" width="56" height="70" style="display:block; width:56px; height:70px; object-fit:cover; border-radius:8px; border:0;">@endif
                                </td>
                                <td style="padding:10px 10px; border-top:1px solid {{ $line }}; vertical-align:top; font-size:13px; color:#4b5563;">
                                    <strong style="color:{{ $plum }}; font-size:14px;">{{ $item->product?->name ?? 'Product' }}</strong><br>
                                    @if($item->color_name)Colour: {{ $item->color_name }}<br>@endif
                                    Qty {{ $item->quantity }} × ₵{{ number_format($item->price, 2) }}
                                </td>
                                <td align="right" style="padding:10px 0; border-top:1px solid {{ $line }}; vertical-align:top; font-size:14px; font-weight:600; white-space:nowrap;">₵{{ number_format($item->subtotal, 2) }}</td>
                            </tr>
                        @endforeach
                        <tr><td colspan="2" style="padding:12px 0 0; border-top:1px solid {{ $line }}; font-size:15px; font-weight:600;">Total</td>
                            <td align="right" style="padding:12px 0 0; border-top:1px solid {{ $line }}; font-size:15px; font-weight:600;">₵{{ number_format($order->total_amount, 2) }}</td></tr>
                    </table>
                </td></tr>

                <tr><td align="center" style="padding:28px 32px 32px;">
                    <a href="{{ $adminUrl }}" style="display:inline-block; background:{{ $rose }}; color:#ffffff; text-decoration:none; font-weight:600; font-size:14px; padding:13px 26px; border-radius:12px;">Open order in admin</a>
                    <p style="margin:12px 0 0; font-size:12px; color:#9ca3af;">Reply to this email to contact the customer.</p>
                </td></tr>
            </table>
        </td></tr>
    </table>
</body>
</html>
