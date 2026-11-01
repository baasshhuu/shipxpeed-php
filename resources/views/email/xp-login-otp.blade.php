<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>Your Shipxpeed login code</title></head>
<body style="margin:0;padding:0;background:#f4f6f8;font-family:Arial,Helvetica,sans-serif;color:#1b2230;">
<table width="100%" cellpadding="0" cellspacing="0" style="padding:32px 12px;">
    <tr><td align="center">
        <table width="100%" cellpadding="0" cellspacing="0" style="max-width:520px;background:#ffffff;border-radius:10px;">
            <tr><td style="padding:28px 32px 8px;font-size:20px;font-weight:bold;">Shipxpeed</td></tr>
            <tr><td style="padding:8px 32px;font-size:15px;line-height:1.6;color:#38404f;">
                Hello {{ $name }},<br>Use this code to log in to your Shipxpeed seller account:
            </td></tr>
            <tr><td align="center" style="padding:18px 32px;">
                <div style="display:inline-block;background:#eef3ff;border:1px solid #c9d8ff;border-radius:8px;padding:14px 26px;font-size:30px;font-weight:bold;letter-spacing:8px;color:#1a3c8f;">{{ $code }}</div>
            </td></tr>
            <tr><td style="padding:8px 32px 28px;font-size:13px;line-height:1.6;color:#6b7487;">
                The code works for {{ $minutes }} minutes and can be used once.<br>
                If you didn't try to log in, you can ignore this e-mail. Nobody can log in without this code. Never share it with anyone, including Shipxpeed staff.
            </td></tr>
        </table>
    </td></tr>
</table>
</body>
</html>
