<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no">

    <!-- ===============================================-->
    <!--    Document Title-->
    <!-- ===============================================-->
    <title>{{ $site_settings['application_name'] }} :: @yield('title', 'Error Page') </title>

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    <link href="{{ asset('assets/css/light/loader.css') }}" rel="stylesheet" type="text/css" />

    <!-- ===============================================-->
    <!--    Favicons-->
    <!-- ===============================================-->
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('storage/' . $site_settings['favicon']) }}" />
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('storage/' . $site_settings['favicon']) }}" />
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('storage/' . $site_settings['favicon']) }}" />
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('storage/' . $site_settings['favicon']) }}" />

    <!-- ===============================================-->
    <!--    Stylesheets-->
    <!-- ===============================================-->
    <link href="https://fonts.googleapis.com/css?family=Nunito:400,600,700" rel="stylesheet">
    <link href="{{ asset('assets/plugins/bootstrap/bootstrap.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/light/main.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/light/structure.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/light/waves.min.css') }}" rel="stylesheet" type="text/css" />
    @hasSection('style')
    @yield('style')
    @else
    <style>
        body:before {
            display: none;
        }

        body.error {
            color: #888ea8;
            height: 100%;
            font-size: 0.875rem;
            background: #fafafa;
            background-image: linear-gradient(to bottom, #a8edea 0%, #fed6e3 100%);
        }

        .min-vh-80 {
            min-height: 80vh !important;
        }

        .py-vh-10 {
            padding-top: 10vh !important;
            padding-bottom: 10vh !important;
        }

        .error .error-number {
            font-size: 120px;
            color: #3b3f5c;
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
            margin-top: 15px;
            text-shadow: 0px 5px 4px rgba(31, 45, 61, 0.1019607843);
        }

        .error .error-image {
            max-width: 429px;
            width: 323px;
        }
    </style>
    @endif
</head>

<body class="error text-center">
    @yield('content')

    @include('partial.common.footer')
</body>

</html>