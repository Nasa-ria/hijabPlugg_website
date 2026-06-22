<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $campaign->subject }}</title>
    <style>
        body { margin: 0; padding: 0; background-color: #f7f5f1; color: #333333; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
        .container { width: 100%; max-width: 680px; margin: 0 auto; }
        .button { display: inline-block; padding: 14px 28px; border-radius: 999px; background-color: #7f56d9; color: #ffffff !important; text-decoration: none; font-weight: 600; }
        .hero { background-color: #ffffff; border-radius: 32px; overflow: hidden; }
        .hero-image { width: 100%; height: auto; display: block; }
        .section { background-color: #ffffff; border-radius: 28px; padding: 32px; margin-top: 16px; }
        .headline { color: #111827; font-size: 32px; line-height: 40px; font-weight: 700; margin: 0 0 16px; }
        .subheadline { color: #6b7280; font-size: 16px; line-height: 26px; margin: 0 0 24px; }
        .small-text { color: #9ca3af; font-size: 12px; line-height: 18px; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 16px; }
        .product-card { border-radius: 24px; overflow: hidden; background-color: #f9f7f3; }
        .footer-link { color: #7f56d9; text-decoration: none; }
        @media only screen and (max-width: 600px) {
            .container { padding: 0 20px; }
            .section { padding: 24px; }
            .headline { font-size: 28px; line-height: 36px; }
            .button { width: 100%; text-align: center; }
        }
    </style>
</head>
<body>
    <table class="container" cellpadding="0" cellspacing="0" role="presentation">
        <tr><td style="padding: 24px 0;"></td></tr>
        <tr>
            <td>
                <table width="100%" cellpadding="0" cellspacing="0" role="presentation" class="hero">
                    <tr>
                        <td style="padding: 40px 32px 24px; text-align: center;">
                            <p class="small-text">Hijabis Plugg</p>
                            <h1 class="headline">{{ $campaign->title }}</h1>
                            <p class="subheadline">{{ $campaign->hero_text }}</p>
                            @if($campaign->cta_text && $campaign->cta_url)
                                <a href="{{ $campaign->cta_url }}" class="button">{{ $campaign->cta_text }}</a>
                            @endif
                        </td>
                    </tr>
                    @if($campaign->hero_image_path)
                        <tr>
                            <td>
                                <img src="{{ url('storage/' . $campaign->hero_image_path) }}" alt="Hero Banner" class="hero-image" style="display:block; width:100%; height:auto;">
                            </td>
                        </tr>
                    @endif
                </table>

                @foreach($campaign->content ?? [] as $section)
                    <table width="100%" cellpadding="0" cellspacing="0" role="presentation" class="section">
                        <tr>
                            <td style="padding-bottom: 18px;"><p class="small-text">{{ $section['title'] }}</p></td>
                        </tr>
                        <tr>
                            <td>
                                <h2 style="font-size: 24px; line-height: 32px; color: #111827; margin: 0 0 16px;">{{ $section['title'] }}</h2>
                                <p style="font-size: 16px; line-height: 26px; color: #4b5563; margin: 0 0 24px;">{{ $section['body'] }}</p>
                                @if(!empty($section['image_path']))
                                    <img src="{{ url('storage/' . $section['image_path']) }}" alt="Section Image" style="display:block; width:100%; height:auto; border-radius: 18px; margin-bottom: 24px;">
                                @endif
                                @if(!empty($section['button_text']) && !empty($section['button_url']))
                                    <a href="{{ $section['button_url'] }}" class="button">{{ $section['button_text'] }}</a>
                                @endif
                            </td>
                        </tr>
                    </table>
                @endforeach

                <table width="100%" cellpadding="0" cellspacing="0" role="presentation" class="section">
                    <tr>
                        <td style="text-align:center;">
                            <p style="font-size: 14px; color: #6b7280; line-height: 22px; margin: 0 0 16px;">Follow us for more inspiration</p>
                            <a href="https://www.instagram.com" class="footer-link" style="margin-right: 18px;">Instagram</a>
                            <a href="https://www.facebook.com" class="footer-link" style="margin-right: 18px;">Facebook</a>
                            <a href="https://www.twitter.com" class="footer-link">Twitter</a>
                        </td>
                    </tr>
                </table>

                <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-top: 16px; text-align: center;">
                    <tr>
                        <td style="font-size: 12px; line-height: 18px; color: #9ca3af; padding: 14px 0;">
                            <p style="margin: 0 0 8px;">Hijabis Plugg | Elegant modest fashion for every moment.</p>
                            <p style="margin: 0;">If you wish to unsubscribe, <a href="{{ $unsubscribeUrl }}" style="color: #7f56d9; text-decoration: none;">click here</a>.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
