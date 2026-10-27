#!/usr/bin/env bash
# Seller Control Suite go-live (re-runnable).
#  Seller > Control Center: NDR, RTO risk check, Bulk actions (labels / manifest / cancel / CSV),
#           Courier preferences (priority, rules, pincode block list, zone lookup), Reports (+ e-mail schedule),
#           Branded tracking page (/t/AWB), Shipment protection, claims and early COD.
#  Admin  > Control Center: NDR desk, Courier performance, RTO risk, Claims & early COD (+ fee settings).
# New files are copied from git; existing files only get small marked hooks (deploy/xpeed-anchor.php),
# so server copies that differ from git are fine. Backs up every touched file and restores on any failure.
# Usage:  cd /var/www/shipxpeed && git fetch origin && git show origin/main:deploy/control-suite.sh | bash
set -uo pipefail
APP=${APP:-/var/www/shipxpeed}
cd "$APP" || { echo "FAIL: $APP not found"; exit 1; }
REF=origin/main
STAMP=$(date +%Y%m%d-%H%M%S)
BK=/root/pre-control-suite-$STAMP.tgz
MIG=database/migrations/2026_10_28_000001_xpeed_control_suite.php

NEW_FILES="
app/Providers/XpeedServiceProvider.php
app/Console/Commands/XpeedServices.php
app/Support/Xpeed/Kit.php
app/Support/Xpeed/CourierPreferences.php
app/Support/Xpeed/RtoRisk.php
app/Support/Xpeed/Reports.php
app/Support/Xpeed/Tracking.php
app/Support/Xpeed/Protection.php
app/Http/Controllers/Xpeed/SellerSuiteController.php
app/Http/Controllers/Xpeed/AdminSuiteController.php
app/Http/Controllers/Xpeed/PublicTrackingController.php
routes/xpeed.php
$MIG
resources/views/sellerdashboard/xpeed/_style.blade.php
resources/views/sellerdashboard/xpeed/ndr.blade.php
resources/views/sellerdashboard/xpeed/bulk.blade.php
resources/views/sellerdashboard/xpeed/bulk-post.blade.php
resources/views/sellerdashboard/xpeed/manifest.blade.php
resources/views/sellerdashboard/xpeed/couriers.blade.php
resources/views/sellerdashboard/xpeed/reports.blade.php
resources/views/sellerdashboard/xpeed/risk.blade.php
resources/views/sellerdashboard/xpeed/tracking.blade.php
resources/views/sellerdashboard/xpeed/protection.blade.php
resources/views/frontend/xpeed/track.blade.php
resources/views/admin/xpeed/_style.blade.php
resources/views/admin/xpeed/ndr.blade.php
resources/views/admin/xpeed/performance.blade.php
resources/views/admin/xpeed/risk.blade.php
resources/views/admin/xpeed/services.blade.php
resources/views/partial/sellerdash/xpeed-menu.blade.php
resources/views/partial/xpeed-admin-menu.blade.php
"
PATCHED="
config/app.php
app/Http/Controllers/selleradmin/CourierController.php
resources/views/sellerdashboard/courier/index.blade.php
resources/views/sellerdashboard/courier/bulk_index.blade.php
resources/views/partial/sellerdash/sidebar.blade.php
resources/views/partial/sidebar.blade.php
"

echo "Shipxpeed Control Suite installer"
git fetch -q origin || { echo "FAIL: git fetch"; exit 1; }
for f in $NEW_FILES deploy/xpeed-anchor.php; do
  git cat-file -e "$REF:$f" 2>/dev/null || { echo "FAIL: $f is not on $REF"; exit 1; }
done

EXIST=""; for f in $NEW_FILES $PATCHED; do [ -e "$f" ] && EXIST="$EXIST $f"; done
tar czf "$BK" $EXIST 2>/dev/null; echo "Backup: $BK"
restore() {
  echo "!! restoring backup"; tar xzf "$BK"
  for f in $NEW_FILES; do tar tzf "$BK" | grep -qxF "$f" || rm -f "$f"; done
  sudo -u www-data php artisan optimize:clear >/dev/null 2>&1 || php artisan optimize:clear >/dev/null 2>&1
  echo "RESTORED - code is back as it was (new xp_* tables, if created, are unused and harmless)"; exit 1
}

# 1. new files
for f in $NEW_FILES; do mkdir -p "$(dirname "$f")"; git show "$REF:$f" > "$f" || restore; done
mkdir -p public/uploads/tracking-logos storage/app/xp-claims
echo "Copied $(echo $NEW_FILES | wc -w) files"

# 2. hooks in existing files
git show "$REF:deploy/xpeed-anchor.php" > /tmp/xpeed-anchor.php || restore
php /tmp/xpeed-anchor.php "$APP" || restore
for f in $NEW_FILES $PATCHED; do
  case "$f" in *.blade.php) ;; *.php) php -l "$f" >/dev/null 2>&1 || { echo "LINT FAIL: $f"; php -l "$f"; restore; };; esac
done
echo "Lint OK"

# 3. database + caches
chown -R www-data:www-data app config routes resources database storage bootstrap/cache public/uploads 2>/dev/null
sudo -u www-data php artisan migrate --path=$MIG --force || restore
sudo -u www-data php artisan optimize:clear >/dev/null || restore

# 4. boot check (no tinker: www-data has no writable HOME)
cat > /tmp/xpeed-check.php <<'PHPEOF'
<?php
chdir($argv[1]);
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
foreach (['xp.ndr', 'xp.couriers', 'xp.track.public', 'admin.xp.ndr', 'admin.xp.services'] as $r) {
    echo Illuminate\Support\Facades\Route::has($r) ? "ROUTE_OK $r\n" : "ROUTE_MISSING $r\n";
}
foreach (['xp_settings', 'xp_courier_prefs', 'xp_courier_rules', 'xp_report_schedules', 'xp_tracking_brands', 'xp_seller_services', 'xp_protection_charges', 'xp_claims', 'ndr_responses'] as $t) {
    echo Illuminate\Support\Facades\Schema::hasTable($t) ? "TABLE_OK $t\n" : "TABLE_MISSING $t\n";
}
// render the main pages for the first active seller and the first admin
$k = $app->make(Illuminate\Contracts\Http\Kernel::class);
$seller = App\Models\SellerList::where('status', 1)->orderBy('id')->first();
$admin = App\Models\User::orderBy('id')->first();
$pages = [];
if ($seller) { foreach (['/seller/xp/ndr', '/seller/xp/risk', '/seller/xp/bulk', '/seller/xp/couriers', '/seller/xp/reports', '/seller/xp/tracking', '/seller/xp/protection'] as $u) $pages[] = ['seller', $u]; }
if ($admin) { foreach (['/xp/ndr', '/xp/courier-performance', '/xp/rto-risk', '/xp/services'] as $u) $pages[] = ['web', $u]; }
foreach ($pages as [$guard, $u]) {
    Illuminate\Support\Facades\Auth::guard($guard)->setUser($guard === 'seller' ? $seller : $admin);
    $res = $k->handle(Illuminate\Http\Request::create('https://shipxpeed.tech' . $u));
    $c = $res->getStatusCode();
    $msg = $c >= 500 && isset($res->exception) ? ' ' . substr($res->exception->getMessage(), 0, 300) : '';
    echo "PAGE $c $u$msg\n";
}
echo "BOOT_OK\n";
PHPEOF
R=$(sudo -u www-data env HOME=/tmp php /tmp/xpeed-check.php "$APP" 2>&1 | tail -40)
echo "$R"
echo "$R" | grep -q BOOT_OK || { echo "FAIL: app did not boot"; tail -20 storage/logs/laravel.log; restore; }
echo "$R" | grep -qE "ROUTE_MISSING|TABLE_MISSING" && { echo "FAIL: routes/tables missing"; restore; }
echo "$R" | grep -qE "^PAGE 5" && { echo "FAIL: a Control Center page returns a server error"; restore; }

for u in https://shipxpeed.tech/seller-login https://shipxpeed.tech/t/TEST000000; do
  c=$(curl -s -o /dev/null -w '%{http_code}' -k "$u"); echo "$u -> $c"
  [ "$c" = "500" ] && { echo "FAIL: panel returns 500"; tail -20 storage/logs/laravel.log; restore; }
done
echo "DEPLOY_DONE backup=$BK"
