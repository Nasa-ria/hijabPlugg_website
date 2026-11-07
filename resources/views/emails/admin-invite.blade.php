{{--
    Admin invitation email (App\Mail\AdminInviteMail). Same branded look as the order emails.
--}}
@php
    $plum = '#3a1234'; $rose = '#e0708a'; $roseDark = '#c2506b'; $porcelain = '#faf4ef';
    $serif = "'Bodoni Moda', Georgia, 'Times New Roman', serif";
    $sans = "Montserrat, 'Helvetica Neue', Helvetica, Arial, sans-serif";
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Your Nasaari admin account</title>
    <link href="https://fonts.googleapis.com/css2?family=Bodoni+Moda:wght@500&family=Montserrat:wght@400;600&family=Parisienne&display=swap" rel="stylesheet">
</head>
<body style="margin:0; padding:0; background:{{ $porcelain }}; font-family:{{ $sans }}; color:{{ $plum }};">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:{{ $porcelain }};">
        <tr><td align="center" style="padding:32px 12px;">
            <table role="presentation" width="560" cellpadding="0" cellspacing="0" style="width:560px; max-width:100%; background:#ffffff; border-radius:16px; overflow:hidden;">
                <tr><td align="center" style="background:{{ $plum }}; padding:26px 24px;">
                    <span style="font-family:Parisienne, 'Brush Script MT', cursive; font-size:38px; line-height:1; color:{{ $porcelain }};">Nasaari</span>
                    <span style="font-size:11px; letter-spacing:3px; text-transform:uppercase; color:{{ $rose }}; padding-left:6px;">Admin</span>
                </td></tr>

                <tr><td style="padding:34px 36px 8px;">
                    <h1 style="margin:0; font-family:{{ $serif }}; font-weight:500; font-size:26px; color:{{ $plum }};">Welcome to the team, {{ $admin->name }}</h1>
                    <p style="margin:14px 0 0; font-size:15px; line-height:1.7; color:#4b5563;">
                        {{ $invitedBy ? $invitedBy->name . ' has added you' : 'You have been added' }} as an admin of Nasaari Store. You can use:
                    </p>
                    <p style="margin:10px 0 0; font-size:14px; line-height:1.8; color:{{ $plum }}; font-weight:600;">
                        {{ implode(' · ', $areas) }}
                    </p>
                </td></tr>

                <tr><td style="padding:22px 36px 0;">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:{{ $porcelain }}; border-radius:12px;">
                        <tr><td style="padding:18px 20px; font-size:14px; line-height:1.9; color:#4b5563;">
                            <strong style="color:{{ $plum }};">Sign-in email:</strong> {{ $admin->email }}<br>
                            <strong style="color:{{ $plum }};">Temporary password:</strong>
                            <span style="font-family:'Courier New', monospace; font-size:16px; letter-spacing:1px; color:{{ $plum }}; background:#ffffff; padding:2px 8px; border-radius:6px;">{{ $temporaryPassword }}</span>
                        </td></tr>
                    </table>
                    <p style="margin:14px 0 0; font-size:13px; line-height:1.6; color:{{ $roseDark }};">
                        For your security, you'll be asked to choose your own password the first time you sign in.
                    </p>
                </td></tr>

                <tr><td align="center" style="padding:30px 36px 34px;">
                    <a href="{{ $loginUrl }}" style="display:inline-block; background:{{ $rose }}; color:#ffffff; text-decoration:none; font-weight:600; font-size:14px; padding:14px 28px; border-radius:12px;">Sign in to the admin</a>
                    <p style="margin:12px 0 0; font-size:12px; color:#9ca3af;">{{ $loginUrl }}</p>
                </td></tr>

                <tr><td align="center" style="background:{{ $porcelain }}; padding:18px 24px; font-size:12px; color:#6b7280; line-height:1.6;">
                    Didn't expect this email? You can ignore it, or reply and let us know.
                </td></tr>
            </table>
        </td></tr>
    </table>
</body>
</html>
