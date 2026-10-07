<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Seller Registration OTP - Shipxpeed</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f6f8;">

    <table align="center" width="100%" cellpadding="0" cellspacing="0" style="padding: 40px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
                    <tr>
                        <td align="center" style="padding: 30px;">

                                {{-- <img src="{{ asset('assets/website/img/logo.png') }}" alt="Shipxpeed Logo" style="max-width: 180px; height: auto;"> --}}

                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 0 40px;">
                            <h2 style="color: #333; font-size: 22px;">Hello,</h2>
                            <p style="color: #555; font-size: 16px; line-height: 1.6;">
                                Thank you for registering as a seller with <strong>Shipxpeed</strong>.<br>
                                Your One-Time Password (OTP) is:
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td align="center" style="padding: 20px;">
                            <div style="background-color: #0d6efd; color: #ffffff; font-size: 28px; font-weight: bold; padding: 14px 30px; border-radius: 8px; display: inline-block;">
                                {{ $otp }}
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 0 40px 30px 40px;">
                            <p style="color: #777; font-size: 14px;">
                                This OTP is valid for a short time. Please do not share it with anyone. If you didn’t request this, please ignore this email.
                            </p>
                            <p style="color: #bbb; font-size: 12px; text-align: center; padding-top: 10px;">
                                &copy; {{ date('Y') }} Shipxpeed. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

</body>
</html>
