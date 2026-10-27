#!/usr/bin/env bash
# Seller dashboard: live "Calculate your shipping rates" card + "How to create an order" video.
# Copies the new files, adds one route and one @include. Safe to run twice.
# Backs up first and restores automatically if any check fails.
set -uo pipefail
cd /var/www/shipxpeed || exit 1
REF=origin/main
STAMP=$(date +%Y%m%d-%H%M%S)
BK=/root/pre-dashboard-tools-$STAMP.tgz

NEW_FILES="
app/Http/Controllers/selleradmin/QuickRateController.php
resources/views/sellerdashboard/partials/quick-tools.blade.php
public/videos/how-to-create-order.mp4
public/videos/how-to-create-order.jpg
"
ROUTES=routes/web.php

git fetch -q origin || { echo "FAIL: git fetch"; exit 1; }

# The live dashboard view is the one with the KPI tiles
VIEW=$(grep -rl --include='*.blade.php' 'sxd-kpis' resources/views | head -1)
[ -n "$VIEW" ] || { echo "FAIL: could not find the dashboard view (sxd-kpis)"; exit 1; }
echo "Dashboard view: $VIEW"

EXIST=""; for f in $NEW_FILES $ROUTES $VIEW; do [ -e "$f" ] && EXIST="$EXIST $f"; done
tar czf "$BK" $EXIST && echo "Backup: $BK"
restore() {
  echo "!! restoring backup"; tar xzf "$BK"
  for f in $NEW_FILES; do tar tzf "$BK" | grep -qx "$f" || rm -f "$f"; done
  php artisan view:clear >/dev/null 2>&1; php artisan route:clear >/dev/null 2>&1
  echo "RESTORED - nothing changed"; exit 1
}

for f in $NEW_FILES; do mkdir -p "$(dirname "$f")"; git show "$REF:$f" > "$f" || restore; done
echo "Copied dashboard card, rate endpoint and video"

VIEW="$VIEW" php <<'PHP' || restore
<?php
// route: POST /seller/quick-rate, inside the seller group next to the dashboard route
$r = 'routes/web.php';
$s = file_get_contents($r);
if (strpos($s, 'QuickRateController') !== false) {
    echo "already there: quick-rate route\n";
} else {
    if (!preg_match("/^.*Route::get\('\/dashboard',\s*\[Dashboard::class,\s*'index'\]\).*$/m", $s, $m)) {
        fwrite(STDERR, "dashboard route anchor not found\n"); exit(1);
    }
    $add = $m[0] . "\n    Route::post('/quick-rate', \\App\\Http\\Controllers\\selleradmin\\QuickRateController::class)->name('seller.quick.rate');";
    file_put_contents($r, str_replace($m[0], $add, $s));
    echo "patched: $r\n";
}

// include the card in the dashboard view
$v = getenv('VIEW');
$s = file_get_contents($v);
$inc = "@include('sellerdashboard.partials.quick-tools')";
if (strpos($s, $inc) !== false) {
    echo "already there: include\n";
} else {
    $k = strrpos($s, '@endsection');
    if ($k === false) { $k = strripos($s, '</body>'); }
    if ($k === false) { fwrite(STDERR, "no @endsection or </body> in $v\n"); exit(1); }
    file_put_contents($v, substr($s, 0, $k) . "\n" . $inc . "\n" . substr($s, $k));
    echo "patched: $v\n";
}
PHP

php -l app/Http/Controllers/selleradmin/QuickRateController.php >/dev/null || restore
php artisan route:clear >/dev/null; php artisan view:clear >/dev/null
php artisan route:list 2>/dev/null | grep -q "seller/quick-rate" || { echo "route not registered"; restore; }

OUT=$(php artisan tinker --execute='
$s = App\Models\SellerList::where("status", 1)->first() ?? App\Models\SellerList::first();
Auth::guard("seller")->setUser($s);
$r = app(Illuminate\Contracts\Http\Kernel::class)->handle(Illuminate\Http\Request::create("https://shipxpeed.tech/seller/dashboard"));
$c = (string) $r->getContent();
echo "CHK dashboard " . $r->getStatusCode() . ($r->getStatusCode() >= 500 ? " ERR " . substr(strip_tags($c), 0, 300) : "") . PHP_EOL;
echo "CHK card " . (str_contains($c, "qtRow") ? "yes" : "no") . PHP_EOL;
$req = Illuminate\Http\Request::create("/seller/quick-rate", "POST", ["origin" => "110001", "destination" => "500034", "weight" => 500, "payment_type" => "prepaid"]);
$j = app(App\Http\Controllers\selleradmin\QuickRateController::class)($req)->getData(true);
echo "CHK quickrate " . (($j["ok"] ?? false) ? "ok" : "fail") . " zone " . ($j["zone"] ?? "-") . " couriers " . count($j["rates"] ?? []) . PHP_EOL;
foreach (array_slice($j["rates"] ?? [], 0, 5) as $q) echo "RATE " . $q["name"] . " = Rs " . $q["total"] . PHP_EOL;
' 2>&1 | grep -E "^(CHK|RATE)|Error|Exception")
echo "$OUT"
echo "$OUT" | grep -q "^CHK dashboard 200" || { echo "dashboard check failed"; restore; }
echo "$OUT" | grep -q "^CHK card yes" || { echo "card not on dashboard"; restore; }
echo "$OUT" | grep -q "^CHK quickrate ok" || { echo "rate endpoint check failed"; restore; }

chown -R www-data:www-data storage bootstrap/cache public/videos 2>/dev/null || true
echo "DONE. Backup kept at $BK"
