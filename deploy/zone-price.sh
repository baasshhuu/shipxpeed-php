#!/usr/bin/env bash
# Puts the new Zone Price Setting page live (Couriers / Rates / Bulk upload tabs) and lets
# Shipclues couriers use the zone rate card + per-seller on/off.
# Backs up every touched file first and restores automatically if any check fails.
set -uo pipefail
cd /var/www/shipxpeed || exit 1
REF=origin/main
STAMP=$(date +%Y%m%d-%H%M%S)
BK=/root/pre-zone-price-$STAMP.tgz

COPY_FILES="
app/Support/RateCard.php
app/Exports/ZoneRateTemplateExport.php
app/Imports/ZoneRateSheetImport.php
app/Http/Controllers/admin/ZonePriceSettingController.php
resources/views/admin/zonepricesetting/manager.blade.php
"
SHIPCLUES=app/Services/ShipcluesService.php
PATCHED="routes/admin.php resources/views/partial/sidebar.blade.php"

git fetch -q origin || { echo "FAIL: git fetch"; exit 1; }

EXIST=""; for f in $COPY_FILES $SHIPCLUES $PATCHED; do [ -e "$f" ] && EXIST="$EXIST $f"; done
tar czf "$BK" $EXIST && echo "Backup: $BK"
restore() {
  echo "!! restoring backup"; tar xzf "$BK"
  for f in $COPY_FILES; do tar tzf "$BK" | grep -qx "$f" || rm -f "$f"; done
  php artisan view:clear >/dev/null 2>&1; php artisan route:clear >/dev/null 2>&1
  echo "RESTORED - nothing changed"; exit 1
}

for f in $COPY_FILES; do mkdir -p "$(dirname "$f")"; git show "$REF:$f" > "$f" || restore; done
echo "Copied zone price files"

# Shipclues: only replace the file if the server copy is a known GitHub version (no local edits to lose)
SC_NOW=$(sha1sum "$SHIPCLUES" 2>/dev/null | cut -d' ' -f1)
SC_OK=0
for c in e9e6a1b fd791b8; do
  [ "$SC_NOW" = "$(git show "$c:$SHIPCLUES" 2>/dev/null | sha1sum | cut -d' ' -f1)" ] && SC_OK=1
done
if [ "$SC_OK" = 1 ]; then
  git show "$REF:$SHIPCLUES" > "$SHIPCLUES" || restore
  echo "Updated Shipclues service (rate card + per-seller on/off)"
else
  echo "NOTE: Shipclues service on this server has local changes - left as is (Shipclues keeps markup pricing)"
fi

php <<'PHP' || restore
<?php
function patch($file, $find, $add, $marker) {
    $s = file_get_contents($file);
    if (strpos($s, $marker) !== false) { echo "already patched: $file\n"; return; }
    if (strpos($s, $find) === false) { fwrite(STDERR, "anchor not found in $file\n"); exit(1); }
    file_put_contents($file, str_replace($find, $add, $s));
    echo "patched: $file\n";
}
$s = file_get_contents('routes/admin.php');
if (!preg_match("/^.*Route::post\('zone-pricesetting-update\/\{id\}'.*$/m", $s, $m)) { fwrite(STDERR, "zone route anchor not found\n"); exit(1); }
patch('routes/admin.php', $m[0], $m[0] . "\n" .
"            Route::post('zone-price/courier-toggle', 'toggleCourier')->name('zone.price.courier.toggle')->middleware('isAllow:104,can_add');\n" .
"            Route::post('zone-price/save-grid', 'saveGrid')->name('zone.price.save.grid')->middleware('isAllow:104,can_add');\n" .
"            Route::get('zone-price/rate-template', 'downloadRateTemplate')->name('zone.price.rate.template')->middleware('isAllow:104,can_add');\n" .
"            Route::post('zone-price/rate-upload', 'uploadRates')->name('zone.price.rate.upload')->middleware('isAllow:104,can_add');",
"zone.price.courier.toggle");

// Sidebar: link straight to the page (best effort, never blocks)
$sb = 'resources/views/partial/sidebar.blade.php';
$t = file_get_contents($sb);
$t2 = str_replace(
    ["@routeis('zone.price.index') active @endrouteis\">\n            <a href=\"{{ route('zone.price.index') }}\""],
    ["@routeis('zone.price.index,zone.pricesetting.add') active @endrouteis\">\n            <a href=\"{{ route('zone.pricesetting.add') }}\""],
    $t);
if ($t2 !== $t) { file_put_contents($sb, $t2); echo "patched: $sb\n"; } else { echo "sidebar left as is\n"; }
PHP

for f in $COPY_FILES $SHIPCLUES routes/admin.php; do
  case "$f" in *.php) php -l "$f" >/dev/null 2>&1 || { echo "LINT FAIL: $f"; restore; } ;; esac
done

php artisan view:clear >/dev/null
php artisan route:clear >/dev/null
php artisan cache:forget ratecard_keys_shipclues >/dev/null 2>&1 || true

# Render the page and the template download as an admin, inside the app
OUT=$(php artisan tinker --execute='
$k = app(Illuminate\Contracts\Http\Kernel::class);
Auth::guard("web")->setUser(App\Models\User::first());
$s = App\Models\SellerList::first();
foreach (["/zone-price-add", "/zone-price-add?tab=rates&seller=" . ($s->id ?? 1), "/zone-price/rate-template?seller=default&couriers=live", "/zone-price-index"] as $u) {
  $r = $k->handle(Illuminate\Http\Request::create("https://shipxpeed.tech" . $u));
  $c = $r->getStatusCode();
  echo "CHK " . $u . " " . $c . ($c >= 500 ? " ERR " . substr(strip_tags((string) $r->getContent()), 0, 300) : "") . PHP_EOL;
}
echo "COURIERS " . count(App\Support\RateCard::couriers()) . " LIVE " . collect(App\Support\RateCard::couriers())->where("live", true)->pluck("label")->implode(", ") . PHP_EOL;
' 2>&1 | grep -E "^(CHK|COURIERS)|Error|Exception" )
echo "$OUT"
echo "$OUT" | grep -q "^CHK /zone-price-add 200" || { echo "page check failed"; restore; }
echo "$OUT" | grep -qE "^CHK .* 5[0-9][0-9]" && { echo "a page returned a server error"; restore; }

chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
echo "DONE. Backup kept at $BK"
