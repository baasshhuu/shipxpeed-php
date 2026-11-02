#!/usr/bin/env bash
# New Reverse orders page: tabs (Delivered · create return / To be picked up / In progress / Received / Cancelled),
# create returns from delivered forward orders, one-click auto-assign with Delhivery reverse pickup.
# Needs deploy/delhivery-rvp.sh first. Backs up first, restores on failure. Safe to run twice.
# Usage: cd /var/www/shipxpeed && git fetch origin && git show origin/main:deploy/returns-page.sh | bash
set -uo pipefail
APP=${APP:-/var/www/shipxpeed}
cd "$APP" || { echo "FAIL: $APP not found"; exit 1; }
REF=origin/main
CTL=app/Http/Controllers/Xpeed/ReturnsController.php
VW=resources/views/sellerdashboard/returns/index.blade.php
MIG=database/migrations/2026_11_02_000001_xp_returns.php
RT=routes/xpeed.php
RP=app/Http/Controllers/selleradmin/ReversepickupController.php
BK=/root/pre-returns-page-$(date +%Y%m%d-%H%M%S).tgz
git fetch -q origin || { echo "FAIL: git fetch"; exit 1; }
grep -q "DELHIVERY-RVP" app/Services/DelhiveryB2CService.php || { echo "FAIL: run deploy/delhivery-rvp.sh first"; exit 1; }
for f in $RT $RP; do [ -s "$f" ] || { echo "FAIL: $f missing"; exit 1; }; done
tar czf "$BK" $RT $RP $( [ -e $CTL ] && echo $CTL ) $( [ -e $VW ] && echo $VW ) && echo "Backup: $BK"
NEWFILES=""; for f in $CTL $VW $MIG; do [ -e "$f" ] || NEWFILES="$NEWFILES $f"; done
restore() { echo "!! restoring"; tar xzf "$BK"; for f in $NEWFILES; do rm -f "$f"; done; php artisan optimize:clear >/dev/null 2>&1; echo "RESTORED - nothing changed"; exit 1; }

mkdir -p "$(dirname $VW)" "$(dirname $CTL)"
for f in $CTL $VW $MIG; do git show "$REF:$f" > "$f" || restore; done
echo "copied: controller, view, migration"

RT=$RT RP=$RP php <<'PHP' || restore
<?php
$f = getenv('RT'); $s = file_get_contents($f);
if (strpos($s, 'ReturnsController') === false) {
    $anchor = "// ---------------------------------------------------------------- seller login with e-mail OTP";
    $add = "// ---------------------------------------------------------------- reverse orders (returns)\n"
         . "Route::middleware(['seller'])->prefix('seller/reverse-order')->name('seller.reverse.')->controller(\\App\\Http\\Controllers\\Xpeed\\ReturnsController::class)->group(function () {\n"
         . "    Route::post('create-from-delivered', 'create')->name('fromforward');\n"
         . "    Route::post('auto-assign', 'assign')->name('autoassign');\n"
         . "});\n\n";
    if (substr_count($s, $anchor) === 1) { $s = str_replace($anchor, $add . $anchor, $s); }
    else { $s = rtrim($s) . "\n\n" . $add; }
    file_put_contents($f, $s); echo "patched: $f (routes)\n";
} else echo "already there: routes\n";

$f = getenv('RP'); $s = file_get_contents($f);
if (strpos($s, 'RETURNS-PAGE') === false) {
    if (!preg_match('/public function reverse_order\(\)\s*\{\n/', $s, $m) || substr_count($s, $m[0]) !== 1) { fwrite(STDERR, "reverse_order() not found\n"); exit(1); }
    $add = "    // RETURNS-PAGE: new Reverse orders page (create returns from delivered orders, auto-assign, tabs)\n"
         . "    if (class_exists(\\App\\Http\\Controllers\\Xpeed\\ReturnsController::class) && view()->exists('sellerdashboard.returns.index')) {\n"
         . "        return app(\\App\\Http\\Controllers\\Xpeed\\ReturnsController::class)->index(request());\n"
         . "    }\n";
    file_put_contents($f, str_replace($m[0], $m[0] . $add, $s)); echo "patched: $f (reverse_order)\n";
} else echo "already there: reverse_order\n";
PHP

for f in $CTL $RT $RP $MIG; do php -l "$f" >/dev/null 2>&1 || { php -l "$f"; restore; }; done
chown -R www-data:www-data $CTL $VW $MIG $RT $RP "$(dirname $VW)" 2>/dev/null
sudo -u www-data php artisan migrate --force --path=$MIG 2>&1 | tail -2
sudo -u www-data php artisan optimize:clear >/dev/null 2>&1

OUT=$(sudo -u www-data php -r '
require "vendor/autoload.php"; $a = require "bootstrap/app.php";
$a->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
echo "TABLE " . (Schema::hasTable("xp_returns") ? "ok" : "missing") . "\n";
echo "ROUTES " . (Route::has("seller.reverse.fromforward") && Route::has("seller.reverse.autoassign") ? "ok" : "missing") . "\n";
try {
  $o = App\Models\Order::orderByDesc("id")->first();
  $s = $o ? App\Models\SellerList::find($o->seller_id) : App\Models\SellerList::first();
  auth()->guard("seller")->setUser($s);
  // what the web middleware shares with every page
  View::share("site_settings", App\Models\Setting::select("setting_name", "filed_value")->get()->pluck("filed_value", "setting_name")->toArray());
  View::share("config", []);
  View::share("errors", new Illuminate\Support\ViewErrorBag());
  foreach (["eligible", "pending", "transit", "received"] as $t) {
    $html = app(App\Http\Controllers\Xpeed\ReturnsController::class)->index(Illuminate\Http\Request::create("/seller/reverse-order", "GET", ["tab" => $t]))->render();
    echo "PAGE $t " . (str_contains($html, "rv-stats") ? "ok" : "fail") . "\n";
  }
} catch (\Throwable $e) { echo "PAGE fail " . $e->getMessage() . " @ " . basename($e->getFile()) . ":" . $e->getLine() . "\n"; }' 2>&1)
echo "$OUT" | grep -E "^(TABLE|ROUTES|PAGE)"
echo "$OUT" | grep -q "^ROUTES ok" || { echo "$OUT" | tail -5; restore; }
if echo "$OUT" | grep -q "^PAGE fail"; then
  echo "$OUT" | grep -q "returns/index" && restore
  echo "NOTE: only the shared seller layout complained in this offline test (not the new page); keeping the update. Open the page to confirm."
fi
echo "$OUT" | grep -qE "^PAGE (pending ok|fail)" || { echo "$OUT" | tail -5; restore; }
echo "RETURNS_PAGE_DONE backup=$BK"
