<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;

/**
 * Serves the static Shipxpeed marketing site built into public/site/.
 * Pages are pre-rendered HTML; this controller only maps clean URLs to them.
 * Source and build script: shipxpeed-site (python3 build.py -> dist/site).
 */
class MarketingSiteController extends Controller
{
    private const PAGES = [
        'domestic-shipping', 'international-shipping', 'b2b-shipping', 'hyperlocal',
        'solutions/d2c', 'solutions/shopify', 'solutions/woocommerce',
        'shipping-api', 'about', 'contact', 'faq', 'blog', 'privacy', 'terms',
    ];

    public function home()
    {
        return $this->serve('index.html');
    }

    public function page(string $page)
    {
        abort_unless(in_array($page, self::PAGES, true), 404);

        return $this->serve($page . '/index.html');
    }

    private function serve(string $relative)
    {
        $path = public_path('site/' . $relative);
        abort_unless(is_file($path), 404);

        return response()->file($path, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Cache-Control' => 'public, max-age=300',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
        ]);
    }
}
