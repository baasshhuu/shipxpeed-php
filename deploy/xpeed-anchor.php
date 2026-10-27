<?php
/**
 * Seller Control Suite: inserts its small hooks into existing files by anchor,
 * so server copies that differ from git are fine. Every hook carries a marker
 * ("xpeed") and is skipped if already present. Exit code 1 only when a CRITICAL
 * hook (config/app.php provider) cannot be placed.
 *
 * Usage: php xpeed-anchor.php <app root>
 */
$root = rtrim($argv[1] ?? getcwd(), '/');
chdir($root);
$fail = false;

function say($m) { echo $m, "\n"; }

function patch(string $file, callable $fn, string $marker, bool $critical = false): void
{
    global $fail;
    if (!is_file($file)) {
        say(($critical ? 'FAIL' : 'WARN') . " $file: not found");
        if ($critical) { $fail = true; }
        return;
    }
    $s = file_get_contents($file);
    if (strpos($s, $marker) !== false) {
        say("OK   $file: already has the hook");
        return;
    }
    $out = $fn($s);
    if ($out === null || $out === $s) {
        say(($critical ? 'FAIL' : 'WARN') . " $file: anchor not found, skipped");
        if ($critical) { $fail = true; }
        return;
    }
    file_put_contents($file, $out);
    say("DONE $file");
}

// 1. provider (critical: routes + hourly job come from it)
patch('config/app.php', function ($s) {
    $p = strpos($s, 'App\\Providers\\CourierServiceProvider::class,');
    if ($p === false) {
        $p = strpos($s, 'App\\Providers\\RouteServiceProvider::class,');
    }
    if ($p === false) { return null; }
    $eol = strpos($s, "\n", $p);
    return substr($s, 0, $eol + 1) . "        App\\Providers\\XpeedServiceProvider::class, // xpeed control suite\n" . substr($s, $eol + 1);
}, 'XpeedServiceProvider', true);

// 2. courier list: apply the seller's priority / rules / pincode blocks
patch('app/Http/Controllers/selleradmin/CourierController.php', function ($s) {
    $hooks = [
        'function index($id)' => "        \$logisticProviders = \\App\\Support\\Xpeed\\CourierPreferences::apply((int) \\Illuminate\\Support\\Facades\\Auth::guard('seller')->id(), [\\App\\Models\\Order::find(\$firstOrderId)], \$logisticProviders); // xpeed prefs\n",
        'function indexbulk(' => "        \$logisticProviders = \\App\\Support\\Xpeed\\CourierPreferences::apply((int) \\Illuminate\\Support\\Facades\\Auth::guard('seller')->id(), \\App\\Models\\Order::whereIn('id', (array) \$orderIds)->where('seller_id', \\Illuminate\\Support\\Facades\\Auth::guard('seller')->id())->get(), \$logisticProviders); // xpeed prefs\n",
    ];
    $done = 0;
    foreach ($hooks as $fnSig => $line) {
        // the live (uncommented) definition: preceded on its line only by whitespace + "public"
        if (!preg_match('/^[ \t]*public[ \t]+' . preg_quote($fnSig, '/') . '/m', $s, $m, PREG_OFFSET_CAPTURE)) { continue; }
        $from = $m[0][1];
        if (!preg_match('/^[ \t]*\/\/ Seller info[ \t]*$/m', $s, $a, PREG_OFFSET_CAPTURE, $from)) { continue; }
        $s = substr($s, 0, $a[0][1]) . $line . substr($s, $a[0][1]);
        $done++;
    }
    return $done ? $s : null;
}, 'xpeed prefs');

// 3. badge text on the top courier card
foreach (['resources/views/sellerdashboard/courier/index.blade.php', 'resources/views/sellerdashboard/courier/bulk_index.blade.php'] as $v) {
    patch($v, function ($s) {
        $n = 0;
        $s = preg_replace('/(<i class="fas fa-star"><\/i>)\s*Recommended/', "$1 {{ \$provider['xp_badge'] ?? 'Recommended' }}", $s, -1, $n);
        return $n ? $s : null;
    }, "xp_badge");
}

// 4. seller sidebar: Control Center links above "Shipment Price List" (desktop + mobile menus)
patch('resources/views/partial/sellerdash/sidebar.blade.php', function ($s) {
    $inc = "                @include('partial.sellerdash.xpeed-menu') {{-- xpeed menu --}}\n";
    $n = 0;
    $s2 = preg_replace_callback('/(\n)([ \t]*<li>\s*\n[ \t]*<a href="\{\{ route\(\'shipmentprice\'\) \}\}")/', function ($m) use ($inc, &$n) {
        $n++;
        return $m[1] . $inc . $m[2];
    }, $s);
    if ($n) { return $s2; }
    // fallback: before Help & Support
    $s2 = preg_replace_callback('/(\n)([ \t]*<li[^>]*>\s*\n[ \t]*<a href="\{\{ route\(\'seller\.help\'\) \}\}")/', function ($m) use ($inc, &$n) {
        $n++;
        return $m[1] . $inc . $m[2];
    }, $s);
    return $n ? $s2 : null;
}, 'xpeed-menu');

// 5. admin sidebar: Control Center links right after Dashboard
patch('resources/views/partial/sidebar.blade.php', function ($s) {
    $p = strpos($s, "route('dashboard')");
    if ($p === false) { return null; }
    $e = strpos($s, '</li>', $p);
    if ($e === false) { return null; }
    $e += strlen('</li>');
    return substr($s, 0, $e) . "\n        @include('partial.xpeed-admin-menu') {{-- xpeed menu --}}" . substr($s, $e);
}, 'xpeed-admin-menu');

exit($fail ? 1 : 0);
