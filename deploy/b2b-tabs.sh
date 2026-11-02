#!/usr/bin/env bash
# B2B shipments: status tabs (All / Unshipped / Booked / In transit / Delivered / RTO / Cancelled) with actions
# (assign courier, cancel, label / manifest / invoice / e-way bill, tracking); Create order only saves the order
# and opens the Unshipped tab. Backs up first, restores on failure. Safe to run twice.
# Usage: cd /var/www/shipxpeed && git fetch origin && git show origin/main:deploy/b2b-tabs.sh | bash
set -uo pipefail
APP=${APP:-/var/www/shipxpeed}
cd "$APP" || { echo "FAIL: $APP not found"; exit 1; }
REF=origin/main
BASE=746d8aa   # the version these files were last deployed from
FILES="app/Http/Controllers/selleradmin/B2bOrderController.php app/Models/B2bOrder.php resources/views/sellerdashboard/b2b/index.blade.php resources/views/sellerdashboard/b2b/create.blade.php"
BK=/root/pre-b2b-tabs-$(date +%Y%m%d-%H%M%S).tgz
git fetch -q origin || { echo "FAIL: git fetch"; exit 1; }
for f in $FILES; do [ -s "$f" ] || { echo "FAIL: $f missing"; exit 1; }; done

# only replace files that are still the deployed version (or already the new one) - never overwrite local edits
for f in $FILES; do
  cur=$(git hash-object "$f"); old=$(git rev-parse "$BASE:$f" 2>/dev/null); new=$(git rev-parse "$REF:$f")
  if [ "$cur" != "$old" ] && [ "$cur" != "$new" ]; then echo "STOP: $f was changed on this server; not overwriting. Send this output to Claude."; exit 1; fi
done
tar czf "$BK" $FILES && echo "Backup: $BK"
restore() { echo "!! restoring"; tar xzf "$BK"; php artisan optimize:clear >/dev/null 2>&1; echo "RESTORED - nothing changed"; exit 1; }

for f in $FILES; do git show "$REF:$f" > "$f" || restore; echo "updated: $f"; done
for f in app/Http/Controllers/selleradmin/B2bOrderController.php app/Models/B2bOrder.php; do php -l "$f" >/dev/null 2>&1 || { php -l "$f"; restore; }; done
chown www-data:www-data $FILES 2>/dev/null
sudo -u www-data php artisan optimize:clear >/dev/null 2>&1

OUT=$(sudo -u www-data php -r '
require "vendor/autoload.php"; $a = require "bootstrap/app.php";
$a->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
try {
  $o = App\Models\B2bOrder::orderByDesc("id")->first();
  $s = $o ? App\Models\SellerList::find($o->seller_id) : App\Models\SellerList::first();
  auth()->guard("seller")->setUser($s);
  View::share("site_settings", App\Models\Setting::select("setting_name", "filed_value")->get()->pluck("filed_value", "setting_name")->toArray());
  View::share("config", []); View::share("errors", new Illuminate\Support\ViewErrorBag());
  $c = app(App\Http\Controllers\selleradmin\B2bOrderController::class);
  foreach ([null, "draft", "booked"] as $st) {
    $h = $c->index(Illuminate\Http\Request::create("/seller/b2b-orders", "GET", array_filter(["status" => $st])))->render();
    echo "LIST " . ($st ?: "all") . " " . (str_contains($h, "b2l-tabs") ? "ok" : "fail") . "\n";
  }
  $h = $c->create()->render();
  echo "CREATE " . (str_contains($h, "Save order") ? "ok" : "fail") . "\n";
} catch (\Throwable $e) { echo "LIST fail " . $e->getMessage() . " @ " . basename($e->getFile()) . ":" . $e->getLine() . "\n"; }' 2>&1)
echo "$OUT" | grep -E "^(LIST|CREATE)"
echo "$OUT" | grep -q "fail" && restore
echo "$OUT" | grep -q "^CREATE ok" || { echo "$OUT" | tail -5; restore; }
echo "B2B_TABS_DONE backup=$BK"
