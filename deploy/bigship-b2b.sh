#!/usr/bin/env bash
# Puts Bigship domestic B2B orders live (seller "B2B orders" pages + Admin > Bigship B2B).
#  - copies the new files from origin/main, adds one require line to routes/web.php,
#    adds menu links (seller menu, old seller sidebar, admin Logistics page), runs one migration
#  - backs up everything first and restores automatically if any step fails. Safe to run twice.
# Usage:  bash deploy/bigship-b2b.sh
set -uo pipefail
APP=${APP:-/var/www/shipxpeed}
cd "$APP" || { echo "FAIL: $APP not found"; exit 1; }
REF=origin/main
STAMP=$(date +%Y%m%d-%H%M%S)
BK=/root/pre-bigship-b2b-$STAMP.tgz
MIG=database/migrations/2026_11_01_000003_bigship_b2b.php

NEW_FILES="
config/bigship.php
$MIG
app/Models/B2bOrder.php
app/Services/BigshipService.php
app/Http/Controllers/selleradmin/B2bOrderController.php
app/Http/Controllers/admin/BigshipController.php
routes/b2b_web.php
resources/views/sellerdashboard/b2b/_style.blade.php
resources/views/sellerdashboard/b2b/index.blade.php
resources/views/sellerdashboard/b2b/create.blade.php
resources/views/sellerdashboard/b2b/show.blade.php
resources/views/admin/bigship/index.blade.php
"
SHARED="routes/web.php resources/views/partials/vas-menu-link.blade.php resources/views/partial/sellerdash/sidebar.blade.php resources/views/logistics/index.blade.php"

echo "Shipxpeed Bigship B2B installer"
git fetch -q origin || { echo "FAIL: git fetch"; exit 1; }
git cat-file -e "$REF:deploy/bigship-b2b.sh" || { echo "FAIL: latest code not found on $REF"; exit 1; }

EXIST=""; for f in $NEW_FILES $SHARED; do [ -e "$f" ] && EXIST="$EXIST $f"; done
tar czf "$BK" $EXIST && echo "Backup: $BK"
restore() {
  echo "!! restoring backup"; tar xzf "$BK"
  for f in $NEW_FILES; do tar tzf "$BK" | grep -qxF "$f" || rm -f "$f"; done
  php artisan optimize:clear >/dev/null 2>&1
  echo "RESTORED - nothing changed"; exit 1
}

for f in $NEW_FILES; do mkdir -p "$(dirname "$f")"; git show "$REF:$f" > "$f" || restore; done
echo "Copied new files"

php <<'PHP' || restore
<?php
// 1. routes
$r = 'routes/web.php';
$s = file_get_contents($r);
if (strpos($s, 'b2b_web.php') !== false) {
    echo "already there: B2B routes\n";
} else {
    file_put_contents($r, rtrim($s) . "\n\n// Domestic B2B shipments (Bigship)\nrequire __DIR__ . '/b2b_web.php';\n");
    echo "patched: $r\n";
}

// 2. seller menu script: "B2B orders" under Orders (shown only when admin turns B2B on)
$m = 'resources/views/partials/vas-menu-link.blade.php';
if (is_file($m)) {
    $s = file_get_contents($m);
    if (strpos($s, "'@b2b'") !== false) {
        echo "already there: seller menu\n";
    } else {
        $rep = [
            "    var NDR = @json(Route::has('xp.ndr') ? route('xp.ndr') : null);" =>
            "    var NDR = @json(Route::has('xp.ndr') ? route('xp.ndr') : null);\n    var B2B = @json(Route::has('seller.b2b.orders') && class_exists(\\App\\Services\\BigshipService::class) && app(\\App\\Services\\BigshipService::class)->enabledForSellers() ? route('seller.b2b.orders') : null);",
            "            ['/seller/order', 'Orders & shipments', 'ti ti-package']," =>
            "            ['/seller/order', 'Orders & shipments', 'ti ti-package'],\n            ['@b2b', 'B2B orders', 'ti ti-truck-delivery'],",
            "        if (key === '@ndr') return here.indexOf('/seller/xp/ndr') === 0;" =>
            "        if (key === '@ndr') return here.indexOf('/seller/xp/ndr') === 0;\n        if (key === '@b2b') return here.indexOf('/seller/b2b-orders') === 0;",
            "                if (key === '@vas' || key === '@ndr') {\n                    var url = key === '@vas' ? VAS : NDR; if (!url) return;" =>
            "                if (key === '@vas' || key === '@ndr' || key === '@b2b') {\n                    var url = key === '@vas' ? VAS : (key === '@ndr' ? NDR : B2B); if (!url) return;",
        ];
        $okAll = true;
        foreach ($rep as $a => $b) { if (substr_count($s, $a) !== 1) { $okAll = false; break; } }
        if ($okAll) {
            file_put_contents($m, strtr($s, $rep));
            echo "patched: $m\n";
        } else {
            echo "skipped: seller menu (server copy differs) - open /seller/b2b-orders directly\n";
        }
    }
}

// 3. old seller sidebar: the commented-out "Add B2B Order" links
$sb = 'resources/views/partial/sellerdash/sidebar.blade.php';
if (is_file($sb)) {
    $s = file_get_contents($sb);
    $n = str_replace("{{-- route('seller.b2b.order') --}}", "{{ Route::has('seller.b2b.order') ? route('seller.b2b.order') : '#' }}", $s);
    // "Shipment History" under B2B should open the B2B orders list, not the create page
    $n = preg_replace(
        "~Route::has\('seller\.b2b\.order'\) \? route\('seller\.b2b\.order'\) : '#' \}\}(\"[^\n]*\n\s*<span class=\"ms-2\">Shipment History)~",
        "Route::has('seller.b2b.orders') ? route('seller.b2b.orders') : '#' }}$1",
        $n
    );
    // the second "Add B2B Order" link pointed at the B2C create-order page
    $n = preg_replace(
        "~<a href=\"\{\{ route\('seller\.order'\) \}\}\"(\s*)class=\"nav-link sidebar-sub-nav-link \{\{ request\(\)->routeIs\('seller\.order'\) \? 'active' : '' \}\}\">(\s*<span class=\"ms-2\">Add B2B Order)~",
        "<a href=\"{{ Route::has('seller.b2b.order') ? route('seller.b2b.order') : route('seller.order') }}\"$1class=\"nav-link sidebar-sub-nav-link {{ request()->routeIs('seller.b2b.order*') ? 'active' : '' }}\">$2",
        $n
    );
    if ($n !== $s) { file_put_contents($sb, $n); echo "patched: $sb\n"; } else echo "nothing to change: $sb\n";
}

// 4. admin Logistics Partners page: "Bigship B2B" button
$lp = 'resources/views/logistics/index.blade.php';
if (is_file($lp)) {
    $s = file_get_contents($lp);
    if (strpos($s, 'admin.bigship.index') !== false) {
        echo "already there: admin button\n";
    } else {
        $btn = "                        @if (Route::has('admin.bigship.index'))\n"
             . "                            <a href=\"{{ route('admin.bigship.index') }}\" class=\"btn btn-outline-primary me-2\">\n"
             . "                                <i class=\"fa-solid fa-truck-ramp-box me-1\"></i> Bigship B2B\n"
             . "                            </a>\n"
             . "                        @endif\n";
        $p = strpos($s, "@if (Helper::userCan(104, 'can_add'))");
        if ($p === false) $p = strpos($s, "route('logistics.add')");
        if ($p !== false) {
            $ls = strrpos(substr($s, 0, $p), "\n") + 1;
            file_put_contents($lp, substr($s, 0, $ls) . $btn . substr($s, $ls));
            echo "patched: $lp\n";
        } else {
            echo "skipped: admin button (open /bigship directly)\n";
        }
    }
}
PHP

for f in $NEW_FILES routes/web.php; do
  case "$f" in *.blade.php) ;; *.php) php -l "$f" >/dev/null 2>&1 || { echo "LINT FAIL: $f"; php -l "$f"; restore; } ;; esac
done
echo "Lint OK"

mkdir -p storage/app/b2b
chown -R www-data:www-data app config routes resources database storage bootstrap/cache 2>/dev/null
composer dump-autoload -q 2>/dev/null || true
sudo -u www-data php artisan migrate --path=$MIG --force || restore
sudo -u www-data php artisan optimize:clear >/dev/null

OUT=$(sudo -u www-data php -r 'require "vendor/autoload.php"; $a = require "bootstrap/app.php"; $a->make(Illuminate\Contracts\Http\Kernel::class)->bootstrap(); $r = app("router")->getRoutes(); $r->refreshNameLookups(); foreach (["seller.b2b.orders", "admin.bigship.index"] as $n) { $x = $r->getByName($n); echo "ROUTE $n " . ($x ? $x->getActionName() : "missing") . PHP_EOL; } echo "TABLE " . (Illuminate\Support\Facades\Schema::hasTable("b2b_orders") ? "ok" : "missing") . PHP_EOL;' 2>&1)
echo "$OUT" | grep -E '^(ROUTE|TABLE)'
echo "$OUT" | grep -q 'B2bOrderController@index' || { echo "FAIL: seller route not loaded"; echo "$OUT" | tail -15; restore; }
echo "$OUT" | grep -q 'BigshipController@index' || { echo "FAIL: admin route not loaded"; echo "$OUT" | tail -15; restore; }
echo "$OUT" | grep -q 'TABLE ok' || { echo "FAIL: table missing"; restore; }
CODE=$(curl -s -o /dev/null -w '%{http_code}' -k https://shipxpeed.tech/seller/b2b-orders)
echo "Page check (logged out, expect 302): $CODE"
[ "$CODE" = "500" ] && restore
echo "BIGSHIP_B2B_DONE backup=$BK"
echo "Next: Admin > Logistics Partners > Bigship B2B (or /bigship) - save login, run a rate test, approve couriers, switch On."
