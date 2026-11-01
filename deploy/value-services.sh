#!/usr/bin/env bash
# Value Added Services hub + Weight disputes v2 (auto-fetch from courier, courier photos, shared links).
#  1. copies the new files, adds routes, the sidebar link and the hourly job
#  2. runs deploy/weight-disputes.sh (seller accept / reject flow, admin review, upload limits)
#  3. runs the v2 migration and checks every new page loads
# Backs up every touched file first and restores automatically if any step fails. Safe to run twice.
set -uo pipefail
APP=${APP:-/var/www/shipxpeed}
cd "$APP" || { echo "FAIL: $APP not found"; exit 1; }
REF=origin/main
STAMP=$(date +%Y%m%d-%H%M%S)
BK=/root/pre-value-services-$STAMP.tgz
MIG=database/migrations/2026_11_01_000001_weight_dispute_v2.php

NEW_FILES="
$MIG
app/Support/WeightAutoFetch.php
app/Console/Commands/WeightAutoFetchCommand.php
app/Providers/XpeedServiceProvider.php
app/Http/Controllers/selleradmin/ValueServicesController.php
routes/vas_web.php
resources/views/partials/vas-menu-link.blade.php
resources/views/sellerdashboard/vas/_layout.blade.php
resources/views/sellerdashboard/vas/_nav.blade.php
resources/views/sellerdashboard/vas/_style.blade.php
resources/views/sellerdashboard/vas/rto-score.blade.php
resources/views/sellerdashboard/vas/early-cod.blade.php
resources/views/sellerdashboard/vas/secure.blade.php
resources/views/sellerdashboard/vas/delivery-boost.blade.php
resources/views/sellerdashboard/vas/brand-boost.blade.php
resources/views/sellerdashboard/notify/index.blade.php
"
WEIGHT_FILES="
app/Models/WeightDisputes.php
app/Support/WeightDisputeService.php
app/Exports/WeightDisputeTemplateExport.php
app/Exports/WeightDiscrepancyExport.php
app/Http/Controllers/admin/WeightDispatchingController.php
app/Http/Controllers/selleradmin/WeightDispatchingController.php
resources/views/WeightDispatching/index.blade.php
resources/views/sellerdashboard/WeightDispatching/index.blade.php
"
LAYOUT=$(grep -rlE 'sxSide|sx-side' resources/views/layouts 2>/dev/null | head -1)
[ -n "$LAYOUT" ] || LAYOUT=resources/views/layouts/sellerdash.blade.php
SHARED="routes/web.php routes/admin.php $LAYOUT public/.user.ini public/.htaccess"

echo "Shipxpeed value services + weight disputes v2 installer"
git fetch -q origin || { echo "FAIL: git fetch"; exit 1; }
git cat-file -e "$REF:deploy/value-services.sh" || { echo "FAIL: latest code not found on $REF"; exit 1; }
[ -f app/Providers/XpeedServiceProvider.php ] || { echo "FAIL: Control Suite not installed on this server (XpeedServiceProvider missing)"; exit 1; }
for f in routes/web.php routes/admin.php; do
  { [ -s "$f" ] && head -c 5 "$f" | grep -q '<?php' && [ "$(wc -l < "$f")" -gt 100 ]; } || { echo "FAIL: $f is missing or damaged - run deploy/recover.sh first"; exit 1; }
done

EXIST=""; for f in $NEW_FILES $WEIGHT_FILES $SHARED; do [ -e "$f" ] && EXIST="$EXIST $f"; done
PRE=" $EXIST " 
tar czf "$BK" $EXIST 2>/dev/null && echo "Backup: $BK"
restore() {
  echo "!! restoring backup"; tar xzf "$BK"
  for f in $NEW_FILES; do case "$PRE" in *" $f "*) ;; *) rm -f "$f" ;; esac; done
  php artisan optimize:clear >/dev/null 2>&1
  echo "RESTORED - code is back as it was (new database columns/tables, if added, are harmless)"; exit 1
}

# 1. new files
for f in $NEW_FILES; do mkdir -p "$(dirname "$f")"; git show "$REF:$f" > "$f" || restore; done
echo "Copied new files"

# 2. routes + sidebar link
LAYOUT="$LAYOUT" php <<'PHP' || restore
<?php
$r = 'routes/web.php';
$s = file_get_contents($r);
if (strpos($s, 'vas_web.php') === false) {
    file_put_contents($r, rtrim($s) . "\n\n// Seller › Value Added Services hub\nrequire __DIR__ . '/vas_web.php';\n");
    echo "patched: $r\n";
} else { echo "already there: vas routes\n"; }

$a = 'routes/admin.php';
$s = file_get_contents($a);
if (strpos($s, 'weight.dispute.autofetch') === false) {
    if (!preg_match("/^.*->name\('weight\.dispatching\.download'\).*$/m", $s, $m)) { fwrite(STDERR, "admin weight routes not found\n"); exit(1); }
    $add = $m[0] . "\n        Route::post('weight-dispute/auto-fetch', 'autoFetch')->name('weight.dispute.autofetch')->middleware('isAllow:104,can_view');"
         . "\n        Route::post('weight-dispute/{id}/proof-links', 'proofLinks')->name('weight.dispute.links')->middleware('isAllow:104,can_view');";
    file_put_contents($a, str_replace($m[0], $add, $s));
    echo "patched: $a\n";
} else { echo "already there: auto-fetch routes\n"; }

$l = getenv('LAYOUT');
$s = file_get_contents($l);
if (strpos($s, 'vas-menu-link') === false) {
    $k = strripos($s, '</body>');
    if ($k === false) { fwrite(STDERR, "no </body> in $l\n"); exit(1); }
    file_put_contents($l, substr($s, 0, $k) . "@includeIf('partials.vas-menu-link')\n" . substr($s, $k));
    echo "patched: $l (sidebar link)\n";
} else { echo "already there: sidebar link\n"; }
PHP

for f in $NEW_FILES routes/web.php routes/admin.php; do
  case "$f" in *.blade.php) ;; *.php) php -l "$f" >/dev/null 2>&1 || { echo "LINT FAIL: $f"; php -l "$f"; restore; } ;; esac
done
echo "Lint OK"

# 3. weight dispute flow (copies its files from origin/main, adds its routes, raises upload limits, checks its pages)
git show "$REF:deploy/weight-disputes.sh" > /root/weight-disputes.sh || restore
bash /root/weight-disputes.sh || { echo "weight dispute installer failed"; restore; }

# 4. v2 migration + caches
chown -R www-data:www-data app config routes resources database storage bootstrap/cache 2>/dev/null
composer dump-autoload -q 2>/dev/null || true
sudo -u www-data php artisan migrate --path=$MIG --force || restore
sudo -u www-data php artisan optimize:clear >/dev/null

# 5. hourly job needs the Laravel scheduler in cron
if ! (crontab -l 2>/dev/null; crontab -u www-data -l 2>/dev/null) | grep -q "schedule:run"; then
  (crontab -u www-data -l 2>/dev/null; echo "* * * * * cd $APP && php artisan schedule:run >> /dev/null 2>&1") | crontab -u www-data -
  echo "Added Laravel scheduler to www-data crontab"
else
  echo "Scheduler cron already present"
fi

# 6. checks: routes + every new page renders for a real seller / admin
OUT=$(sudo -u www-data php -r '
require "vendor/autoload.php"; $a = require "bootstrap/app.php";
$k = $a->make(Illuminate\Contracts\Http\Kernel::class); $k->bootstrap();
$r = app("router")->getRoutes(); $r->refreshNameLookups();
foreach (["vas.show", "weight.dispute.autofetch", "weight.dispute.links", "seller.weight.dispute.raise"] as $n) echo "ROUTE $n " . ($r->getByName($n) ? "ok" : "missing") . PHP_EOL;
echo "TABLE " . (Schema::hasTable("weight_fetch_checks") && Schema::hasColumn("weight_disputes", "courier_proofs") ? "ok" : "missing") . PHP_EOL;
$s = App\Models\SellerList::where("status", 1)->first() ?: App\Models\SellerList::first();
if ($s) {
  Auth::guard("seller")->setUser($s);
  foreach (["rto-score", "early-cod", "secure", "delivery-boost", "brand-boost"] as $t) {
    $res = $k->handle(Illuminate\Http\Request::create("/seller/services/" . $t));
    $c = $res->getStatusCode();
    echo "CHK $t $c" . ($c >= 500 ? " ERR " . substr(strip_tags((string) $res->getContent()), 0, 300) : "") . PHP_EOL;
  }
  $res = $k->handle(Illuminate\Http\Request::create("/notifications"));
  echo "CHK notify " . $res->getStatusCode() . PHP_EOL;
}
echo "AUTO " . json_encode(App\Support\WeightAutoFetch::stats()["ready"] ?? false) . PHP_EOL;
' 2>&1)
echo "$OUT" | grep -E "^(ROUTE|TABLE|CHK|AUTO)"
echo "$OUT" | grep -q "missing" && { echo "$OUT" | tail -15; restore; }
echo "$OUT" | grep -qE "^CHK .* 5[0-9][0-9]" && { echo "a page returned a server error"; restore; }
echo "$OUT" | grep -q "^CHK rto-score" || { echo "page check did not run:"; echo "$OUT" | tail -15; restore; }

echo "VALUE_SERVICES_DONE backup=$BK"
echo "Weight auto-fetch: ON (Shipclues), runs hourly at :23. Admin > Weight Disputes > Auto-fetch to turn off or Fetch now."
