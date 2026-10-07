<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shipxpeed</title>
    <!-- ===============================================-->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('storage/' .($site_settings['favicon'] ?? 'default-logo.png')) }}" />
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('storage/' .($site_settings['favicon'] ?? 'default-logo.png')) }}" />
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('storage/' .($site_settings['favicon'] ?? 'default-logo.png')) }}" />
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('storage/' .($site_settings['favicon'] ?? 'default-logo.png')) }}" />
    <link href="{{ asset('assets/plugins/bootstrap/bootstrap.min.css') }}" rel="stylesheet" type="text/css" />
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html,
        body {
            height: 100%;
            font-family: Arial, sans-serif;
            color: #4a4a4a;
            /* Gray-600 text color */
        }

        .container-fluid {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
        }

        .row {
            width: 100%;
            height: 100vh;
            display: flex;
            margin: 0;
        }

        .left-section {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, #124265, #12426594, #12426552, #12426521);
            text-align: center;
            padding: 50px;
        }

        .left-section p {
            font-size: 1.2rem;
            color: #6c757d;
            max-width: 400px;
        }

        .right-section {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            position: relative;
            overflow-y: auto;
            padding-top: 100px;
        }

        .register-form {
            width: 100%;
            padding: 70px;
            background: white;
            overflow: auto;
            color: #4a4a4a;
            /* Gray-600 text color */
        }

        .form-control {
            background: white;
            border: 1px solid rgba(0, 0, 0, 0.3);
            color: #4a4a4a;
            /* Gray-600 */
        }

        .form-control::placeholder {
            color: rgba(0, 0, 0, 0.5);
        }

        .form-control:focus {
            box-shadow: none;
            border-color: rgba(0, 0, 0, 0.6);
        }

        label {
            color: #4a4a4a;
            /* Gray-600 */
            font-weight: semibold;
            font-size: 13px;
        }

        .btn-dark {
            background: black;
            color: white;
            font-weight: bold;
        }

        .btn-dark:hover {
            background: rgba(0, 0, 0, 0.8);
        }

        .left-section p {
            font-size: 1.2rem;
            color: #ffff;
            max-width: 400px;
        }

        .form-group input::placeholder {
            font-size: 13px;

        }

        .form-control,
        option {
            font-size: 13px;

        }

        .left-section {
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            background-color: #467b9e;
            text-align: center;
            padding: 40px;
        }
    </style>
</head>

<body>
    @yield('content')
</body>

</html>
