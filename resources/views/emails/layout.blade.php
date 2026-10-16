<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $appName }}</title>
</head>
<body style="margin:0;padding:0;background:#f8fafc;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;">
                    <tr>
                        <td style="padding:28px 28px 8px;">
                            <table role="presentation" cellpadding="0" cellspacing="0">
                                <tr>
                                    @if(!empty($logoUrl))
                                        <td style="padding-right:12px;vertical-align:middle;">
                                            <img src="{{ $logoUrl }}" alt="{{ $appName }}" width="48" height="48" style="display:block;width:48px;height:48px;border-radius:12px;object-fit:cover;">
                                        </td>
                                    @endif
                                    <td style="vertical-align:middle;">
                                        <div style="font-size:18px;font-weight:700;line-height:1.2;">{{ $appName }}</div>
                                        <div style="font-size:12px;color:#64748b;margin-top:2px;">Competitive ELO League</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:8px 28px 28px;font-size:15px;line-height:1.6;">
                            @yield('body')
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
