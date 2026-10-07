

<head>
 <title>{{ $site_settings['application_name'] }}</title>
  <!-- [Meta] -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="description" content="Mantis is made using Bootstrap 5 design framework. Download the free admin template & use it for your project.">
  <meta name="keywords" content="Mantis, Dashboard UI Kit, Bootstrap 5, Admin Template, Admin Dashboard, CRM, CMS, Bootstrap Admin Template">
  <meta name="author" content="CodedThemes">
  <meta name="csrf-token" content="{{ csrf_token() }}">
   <!-- [Favicon] icon -->

	<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('storage/' . $site_settings['favicon']) }}" />
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('storage/' . $site_settings['favicon']) }}" />
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('storage/' . $site_settings['favicon']) }}" />
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('storage/' . $site_settings['favicon']) }}" />


   <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&amp;display=swap" id="main-font-link">
   <!-- [Tabler Icons] https://tablericons.com -->
    <link href="{{ asset('/dashboard-asset/fonts/tabler-icons.min.css') }}" rel="stylesheet" type="text/css" />
   <!-- [Feather Icons] https://feathericons.com -->
    <link href="{{ asset('/dashboard-asset/fonts/feather.css') }}" rel="stylesheet" type="text/css" />
   <!-- [Font Awesome Icons] https://fontawesome.com/icons -->
   <link href="{{ asset('assets/plugins/fontawesome-pro/css/all.min.css') }}" rel="stylesheet" />

   <!-- [Material Icons] https://fonts.google.com/icons -->
    <link href="{{ asset('/dashboard-asset/fonts/material.css') }}" rel="stylesheet" />

   <link href="{{ asset('/dashboard-asset/css/style.css') }}" rel="stylesheet" />
   <link href="{{ asset('/dashboard-asset/css/style-preset.css') }}" rel="stylesheet" />

   <script type="text/javascript" src="https://cdn.jsdelivr.net/jquery/latest/jquery.min.js"></script>
   <script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
   <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
   <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
<style>
    .kyc-profile {
    width: 50%;
    margin: 0 auto;
    margin-top: 20px;
    margin-bottom: 27px;
}

</style>
</head>

