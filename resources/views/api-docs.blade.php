<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="dark">
    <title>API documentation · Shipxpeed</title>
    <meta name="description" content="Shipxpeed seller API: fetch couriers and rates, book shipments, labels, tracking, cancellation, NDR and webhooks.">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('site/assets/img/favicon-32.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <style>
        html, body { margin: 0; background: #070b16; color: #e9ecf5; font-family: Inter, system-ui, -apple-system, "Segoe UI", sans-serif; }
        .top { position: sticky; top: 0; z-index: 10; display: flex; align-items: center; justify-content: space-between; gap: 12px;
            padding: 12px 16px; background: rgba(7,11,22,.86); backdrop-filter: blur(10px); border-bottom: 1px solid rgba(196,206,235,.11); }
        .top img { height: 24px; width: auto; display: block; }
        .top nav { display: flex; gap: 8px; }
        .top a.b { color: #fff; text-decoration: none; font: 600 13px Inter, system-ui, sans-serif; padding: 8px 12px; border-radius: 9px; border: 1px solid rgba(196,206,235,.16); white-space: nowrap; }
        .top a.b.pri { background: #3b7bff; border-color: #3b7bff; }
        .wrap { max-width: 1280px; margin: 0 auto; padding: 16px; }
        .wrap .sxapi { --fs-bg: #0a0f1c; }
        @media (max-width: 520px) { .top a.b.sec { display: none; } }
    </style>
</head>
<body>
    <header class="top">
        <a href="{{ url('/') }}"><img src="{{ asset('site/assets/img/logo-white.png') }}" alt="Shipxpeed"></a>
        <nav>
            <a class="b sec" href="{{ route('register.get') }}">Create account</a>
            <a class="b pri" href="{{ route('seller.login') }}">Seller login</a>
        </nav>
    </header>
    <main class="wrap">
        @include('apidocs.content', [
            'docs' => app(\App\Services\SellerApiDocs::class),
            'baseUrl' => rtrim(url('/api'), '/'),
            'inPanel' => false,
            'apiToken' => null,
        ])
    </main>
</body>
</html>
