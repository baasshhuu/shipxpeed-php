<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Reset Password - Shipxpeed</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f8f9fa;">

    <table align="center" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f8f9fa; padding: 40px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                    <tr>
                        <td align="center" style="padding: 30px;">
                            @if(isset($site_settings['logo']))
                                <img src="{{ asset('storage/' . $site_settings['logo']) }}" alt="Shipxpeed Logo" style="max-width: 180px; height: auto;">
                            @endif
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 0 40px;">
                            <h2 style="color: #333; font-size: 24px;">Reset Your Password</h2>
                            <p style="color: #555; font-size: 16px; line-height: 1.6;">
                                You’ve requested to reset your password. Click the button below to proceed:
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td align="center" style="padding: 20px;">
                            <a href="{{ $resetUrl }}" target="_blank" style="background-color: #0d6efd; color: #ffffff; text-decoration: none; padding: 12px 30px; border-radius: 5px; font-weight: bold; display: inline-block; font-size: 16px;">
                                Reset Password
                            </a>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 0 40px 30px 40px;">
                            <p style="color: #888; font-size: 14px;">
                                This link will expire in 60 minutes. If you did not request a password reset, please ignore this email.
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
