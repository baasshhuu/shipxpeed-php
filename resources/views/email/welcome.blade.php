<!doctype html>
<html lang="en-US">

<head>
    <meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
    <title>WelCome To {{ $site_settings['application_name'] }} </title>
    <meta name="description" content="Reset Password Email.">
    <style type="text/css">
        @import url(https://fonts.googleapis.com/css?family=Rubik:300,400,500,700|Open+Sans:300,400,600,700);

        a:hover {
            text-decoration: underline !important;
        }

        #users {
            font-family: 'Rubik',sans-serif, Arial, Helvetica, sans-serif;
            border-collapse: collapse;
            width: 100%;
            margin: 20px auto 10px auto;
            border-radius: 10px;
        }

        #users td,
        #users th {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: start;
        }

        #users tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        #users tr:hover {
            background-color: #ddd;
        }

        #users th {
            padding-top: 8px;
            padding-bottom: 8px;
            font-weight: normal;
            text-align: left;
            background-color: #4361ee;
            color: white;
        }
    </style>
</head>

<body marginheight="0" topmargin="0" marginwidth="0" style="margin: 0px; background-color: #f2f3f8;" leftmargin="0">
    <!--100% body table-->
    <table cellspacing="0" border="0" cellpadding="0" width="100%" bgcolor="#f2f3f8"
        style="font-family: 'Open Sans', sans-serif;">
        <tr>
            <td>
                <table style="background-color: #f2f3f8; max-width:670px;  margin:0 auto;" width="100%" border="0"
                    align="center" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="height:80px;">&nbsp;</td>
                    </tr>
                    <tr>

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
                                        <h3
                                            style="color:#1e1e2d; font-weight:600; margin:0;font-size:25px;font-family:'Rubik',sans-serif;">
                                            Welcome to </h3>
                                        <h1
                                            style="color: #4361ee; font-weight:600; margin:0;font-size:32px;font-family:'Rubik',sans-serif;">
                                            {{ $site_settings['application_name'] }}</h1>

                                        <span
                                            style="display:inline-block; vertical-align:middle; margin:29px 0 26px; border-bottom:1px solid #cecece; width:100px;"></span>

                                        <p style="text-align: start; font-weight:bold; font-family:'Rubik',sans-serif;">
                                            Hello</p>
                                        <p
                                            style="text-align: start; color:#455056; font-size:15px;line-height:24px; margin:0;">
                                            Thank you for joining {{ $site_settings['application_name'] }}. <br>
                                            We'd like to confirm that your account was created successfully. To access
                                            {{ $site_settings['application_name'] }} click the link below.
                                        </p>

                                        <a href="{{ route('seller.login') }}" target="__blank"
                                            style="background-color: #4361ee; box-shadow: 0 10px 20px -8px #4361ee;text-decoration:none !important; font-weight:500; margin-top:25px; margin-bottom:25px; color:#fff;text-transform:uppercase; font-size:14px;padding:10px 24px;display:inline-block;border-radius:50px;">
                                            Login to Your New Account
                                        </a>
                                        <p
                                            style="text-align: start; color:#455056; font-size:15px;line-height:24px; margin:0;">
                                            If you experience any issues logging into your account, reach out to us at
                                            <span style="color: #4361ee;"></span>.
                                        </p>
                                        <br>
                                        <p
                                            style="text-align: start; color:#455056; font-size:15px;line-height:24px; margin:0;">
                                            Best, </p>
                                        <p
                                            style="text-align: start; color:#455056; font-size:15px;line-height:24px; margin:0;">
                                            The {{ $site_settings['application_name'] }} Team</p>
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
    <!--/100% body table-->
</body>

</html>
