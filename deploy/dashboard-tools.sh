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

echo "Shipxpeed dashboard tools installer"
git fetch -q origin || { echo "FAIL: git fetch (check the server can reach GitHub)"; exit 1; }
git cat-file -e "$REF:deploy/dashboard-tools.sh" || { echo "FAIL: latest code not found on $REF"; exit 1; }

# Where to add the card: the live dashboard view (has the KPI tiles), else the classic
# dashboard view, else the seller layout (shown on the dashboard page only).
GUARD=0
VIEW=$(grep -rlE --include='*.php' 'sxd-kpis|Shipment pipeline' resources/views 2>/dev/null | grep -v '/partials/quick-tools' | head -1)
if [ -z "$VIEW" ]; then
  V2=$(php artisan tinker --execute='$a = app("router")->getRoutes()->getByName("seller.dashboard")?->getActionName(); echo "ACTION " . $a . PHP_EOL;' 2>/dev/null | grep '^ACTION' | sed 's/^ACTION //')
  echo "Dashboard route action: ${V2:-unknown}"
  for c in resources/views/layouts/sellerdash.blade.php resources/views/sellerdashboard/dashboard.blade.php; do
    [ -f "$c" ] && { VIEW=$c; break; }
  done
  [ "$VIEW" = "resources/views/layouts/sellerdash.blade.php" ] && GUARD=1
fi
[ -n "$VIEW" ] || { echo "FAIL: could not find the dashboard view"; ls resources/views resources/views/sellerdashboard | head -50; exit 1; }
echo "Adding card to: $VIEW"

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

VIEW="$VIEW" GUARD="$GUARD" php <<'PHP' || restore
<?php
// route: POST /seller/quick-rate, inside the seller group next to the dashboard route
$r = 'routes/web.php';
$s = file_get_contents($r);
if (strpos($s, 'QuickRateController') !== false) {
    echo "already there: quick-rate route\n";
} else {
    // first line that is not commented out
    if (!preg_match("/^(?![ \t]*(\/\/|#|\*)).*Route::get\(\s*'\/dashboard'\s*,\s*\[\s*Dashboard::class\s*,\s*'index'\s*\]\s*\).*$/m", $s, $m)) {
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
$block = getenv('GUARD') === '1'
    ? "@if(request()->routeIs('seller.dashboard'))\n" . $inc . "\n@endif"
    : $inc;
if (strpos($s, $inc) !== false) {
    echo "already there: include\n";
} else {
    $k = strrpos($s, '@endsection');
    if ($k === false) { $k = strripos($s, '</body>'); }
    if ($k === false) { fwrite(STDERR, "no @endsection or </body> in $v\n"); exit(1); }
    file_put_contents($v, substr($s, 0, $k) . "\n" . $block . "\n" . substr($s, $k));
    echo "patched: $v\n";
}
PHP

php -l app/Http/Controllers/selleradmin/QuickRateController.php >/dev/null || restore
php artisan route:clear >/dev/null; php artisan view:clear >/dev/null
RT=$(php artisan tinker --execute='
try { $r = app("router")->getRoutes()->match(Illuminate\Http\Request::create("/seller/quick-rate", "POST")); echo "ROUTE " . ($r->getName() ?: "-") . " " . $r->getActionName() . PHP_EOL; }
catch (\Throwable $e) { echo "ROUTE none " . get_class($e) . PHP_EOL; }
echo "DASH " . (app("router")->getRoutes()->getByName("seller.dashboard")?->uri() ?? "-") . PHP_EOL;
' 2>&1 | grep -E "^(ROUTE|DASH)|Error|Exception")
echo "$RT"
if ! echo "$RT" | grep -q "QuickRateController"; then
  echo "route not registered - where the line went:"
  grep -n "quick-rate\|Dashboard::class, 'index'" routes/web.php | head
  restore
fi

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
