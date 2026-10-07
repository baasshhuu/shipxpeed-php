<!doctype html>
<html lang="en-US">

<head>
    <meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
    <title>{{ $site_settings['application_name'] }}</title>
    <meta name="description" content="{{ $site_settings['application_name'] }}.">
    <style type="text/css">
        a:hover {
            text-decoration: underline !important;
        }

        .btn-dark {
            padding: 0.4375rem 1.25rem;
            text-shadow: none;
            font-size: 14px;
            color: #fff;
            font-weight: normal;
            white-space: normal;
            word-wrap: break-word;
            transition: 0.2s ease-out;
            touch-action: manipulation;
            border-radius: 6px;
            cursor: pointer;
            background-color: #212529;
            will-change: opacity, transform;
            text-decoration: none;
            transition: all 0.3s ease-out;
            -webkit-transition: all 0.3s ease-out;
        }
    </style>
</head>

<body marginheight="0" topmargin="0" marginwidth="0" style="margin: 0px; background-color: #f2f3f8;" leftmargin="0">
    <table cellspacing="0" border="0" cellpadding="0" width="100%" bgcolor="#f2f3f8"
        style="@import url(https://fonts.googleapis.com/css?family=Rubik:300,400,500,700|Open+Sans:300,400,600,700); font-family: 'Open Sans', sans-serif;">
        <tr>
            <td>
                <table style="background-color: #f2f3f8; max-width:670px;  margin:0 auto;" width="100%" border="0"
                    align="center" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="height:80px;">&nbsp;</td>
                    </tr>
                    <tr>
                        <td style="text-align:center;">
                            <a href="{{ url('') }}" title="logo" target="_blank">
                                <img max-width="120" height="60" src="{{ asset('storage/' . $site_settings['logo']) }}"
                                    title="logo" alt="logo">
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td style="height:20px;">&nbsp;</td>
                    </tr>
                    <tr>
                        <td>
                            <table width="95%" border="0" align="center" cellpadding="0" cellspacing="0"
                                style="max-width:670px;background:#fff; border-radius:3px; text-align:center;-webkit-box-shadow:0 6px 18px 0 rgba(0,0,0,.06);-moz-box-shadow:0 6px 18px 0 rgba(0,0,0,.06);box-shadow:0 6px 18px 0 rgba(0,0,0,.06);">
                                <tr>
                                    <td style="height:40px;">&nbsp;</td>
                                </tr>
                                <tr>
                                    <td style="padding:0 35px;">
                                        <h4
                                            style="color:#1e1e2d;margin-bottom: 15px; text-align: left; font-weight:500; margin:0;font-size:18px;font-family:'Rubik',sans-serif;">
                                            Hello, {{ empty($user->name) ? 'Dear' : $user->name }}
                                        </h4>
                                        <p
                                            style="color:#1e1e2d; margin-bottom: 20px; text-align: left;font-family:'Rubik',sans-serif;">
                                            You must verify your email address. Before useing our services.</p>
                                        <a style="margin-bottom: 20px;" class="btn-dark" href="{{ $url }}">
                                            Verify Email Address
                                        </a>
                                        <p
                                            style="color:#1e1e2d; margin-bottom: 20px; text-align: left;font-family:'Rubik',sans-serif;">
                                            If you did not create an account, no further action is required.
                                            <br>
                                            Thanks,
                                            <br>
                                            {{ $site_settings['application_name'] }} Team
                                        </p>
                                        <p
                                            style="color:#455056; font-size:15px;line-height:24px; margin:0; text-align: justify;">
                                            If you’re having trouble clicking the "Verify Email Address" button, copy
                                            and paste the URL below into your web browser:
                                            <a href="{{ $url }}">{{ $url }}</a>
                                        </p>

                                    </td>
                                </tr>
                                <tr>
                                    <td style="height:40px;">&nbsp;</td>
                                </tr>
                            </table>
                        </td>
                    <tr>
                        <td style="height:20px;">&nbsp;</td>
                    </tr>
                    <tr>
                        <td style="text-align:center;">
                            <p
                                style="font-size:14px; color:rgba(69, 80, 86, 0.7411764705882353); line-height:18px; margin:0 0 0;">
                                &copy; <strong>{{ $site_settings['application_name'] }}</strong></p>
                        </td>
                    </tr>
                    <tr>
                        <td style="height:80px;">&nbsp;</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>