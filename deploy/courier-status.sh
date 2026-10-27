#!/usr/bin/env bash
# Puts the new Courier Status page live (Admin > Price Setting > Courier Status):
#  - shows only integrated couriers (Delhivery Surface/Air, Shipclues couriers, Fship couriers),
#  - "All Sellers (default)" sets each courier's default ON/OFF for every seller (incl. new ones),
#  - picking a seller sets that seller's own switch, with "Use default" to undo,
#  - Delhivery, Shipclues and Fship rate listing obey these switches.
# Backs up every touched file first and restores automatically if any check fails.
set -uo pipefail
cd /var/www/shipxpeed || exit 1
REF=origin/main
STAMP=$(date +%Y%m%d-%H%M%S)
BK=/root/pre-courier-status-$STAMP.tgz

COPY_FILES="
app/Support/CourierAccess.php
app/Support/RateCard.php
app/Http/Controllers/admin/ActiveslebsController.php
resources/views/admin/Activeslebs/index.blade.php
database/migrations/2026_10_27_000001_create_courier_defaults_table.php
"
PATCHED="routes/admin.php app/Services/DelhiveryB2CService.php app/Services/DelhiveryB2CServiceExpress.php app/Services/FshipService.php"

git fetch -q origin || { echo "FAIL: git fetch"; exit 1; }

EXIST=""; for f in $COPY_FILES $PATCHED; do [ -e "$f" ] && EXIST="$EXIST $f"; done
tar czf "$BK" $EXIST && echo "Backup: $BK"
restore() {
  echo "!! restoring backup"; tar xzf "$BK"
  for f in $COPY_FILES; do tar tzf "$BK" | grep -qx "$f" || rm -f "$f"; done
  php artisan view:clear >/dev/null 2>&1; php artisan route:clear >/dev/null 2>&1
  echo "RESTORED - nothing changed"; exit 1
}

for f in $COPY_FILES; do mkdir -p "$(dirname "$f")"; git show "$REF:$f" > "$f" || restore; done
echo "Copied files"

php <<'PHP' || restore
<?php
function patchStr($file, $find, $add, $marker) {
    if (!file_exists($file)) { echo "skip (missing): $file\n"; return; }
    $s = file_get_contents($file);
    if (strpos($s, $marker) !== false) { echo "already patched: $file\n"; return; }
    if (strpos($s, $find) === false) { fwrite(STDERR, "anchor not found in $file\n"); exit(1); }
    file_put_contents($file, str_replace($find, $add, $s)); echo "patched: $file\n";
}
function patchRe($file, $re, $to, $marker) {
    if (!file_exists($file)) { echo "skip (missing): $file\n"; return; }
    $s = file_get_contents($file);
    if (strpos($s, $marker) !== false) { echo "already patched: $file\n"; return; }
    $n = 0; $s2 = preg_replace($re, $to, $s, -1, $n);
    if (!$n) { fwrite(STDERR, "pattern not found in $file\n"); exit(1); }
    file_put_contents($file, $s2); echo "patched ($n): $file\n";
}

// 1) reset route
$a = "Route::post('pricesetting/status-update', 'updateStatus')->name('pricesetting.courier.update')->middleware('isAllow:104,can_add');";
patchStr('routes/admin.php', $a,
    $a . "\n        Route::post('pricesetting/status-reset', 'resetStatus')->name('pricesetting.courier.reset')->middleware('isAllow:104,can_add');",
    'pricesetting.courier.reset');

// 2) Delhivery Surface / Air follow the switches (own switch, else default)
foreach (['app/Services/DelhiveryB2CService.php' => 'Delhivery', 'app/Services/DelhiveryB2CServiceExpress.php' => 'Delhivery_Air'] as $f => $k) {
    patchRe($f,
        "/ActicvSleb::where\\(\\[\\s*'seller_id' => \\\$order->seller_id,\\s*'LogisticProvider' => '" . $k . "',\\s*'status' => 1\\s*\\]\\)->exists\\(\\)/",
        "\\App\\Support\\CourierAccess::isOn((int) \$order->seller_id, '" . $k . "')",
        'CourierAccess::isOn');
}

// 3) Fship couriers follow the switches
$f = 'app/Services/FshipService.php';
if (file_exists($f) && strpos(file_get_contents($f), 'CourierAccess::isOn') === false) {
    $s = file_get_contents($f);
    $i = strpos($s, 'private function hiddenForSeller(int $sellerId, string $rateKey): bool');
    $j = $i === false ? false : strpos($s, '    private function zoneFor(', $i);
    if ($i === false || $j === false) { fwrite(STDERR, "Fship anchor not found\n"); exit(1); }
    $s = substr($s, 0, $i) . "private function hiddenForSeller(int \$sellerId, string \$rateKey): bool\n    {\n        return !\\App\\Support\\CourierAccess::isOn(\$sellerId, \$rateKey, 'Fship');\n    }\n\n" . substr($s, $j);
    file_put_contents($f, $s); echo "patched: $f\n";
}
PHP

for f in $COPY_FILES $PATCHED; do
  case "$f" in *.php) [ -e "$f" ] && { php -l "$f" >/dev/null 2>&1 || { echo "LINT FAIL: $f"; restore; }; } ;; esac
done

php artisan migrate --force --path=database/migrations/2026_10_27_000001_create_courier_defaults_table.php || restore
php artisan view:clear >/dev/null
php artisan route:clear >/dev/null
php artisan cache:forget courier_defaults_map >/dev/null 2>&1 || true
php artisan cache:forget ratecard_keys_shipclues >/dev/null 2>&1 || true
php artisan cache:forget ratecard_keys_fship >/dev/null 2>&1 || true

# Render the page as an admin, inside the app, and list what it shows
OUT=$(php artisan tinker --execute='
$k = app(Illuminate\Contracts\Http\Kernel::class);
Auth::guard("web")->setUser(App\Models\User::first());
$s = App\Models\SellerList::first();
foreach (["/active-slebs-add", "/active-slebs-add?seller_id=" . ($s->id ?? 1)] as $u) {
  $r = $k->handle(Illuminate\Http\Request::create("https://shipxpeed.tech" . $u));
  $c = $r->getStatusCode();
  echo "CHK " . $u . " " . $c . ($c >= 500 ? " ERR " . substr(strip_tags((string) $r->getContent()), 0, 300) : "") . PHP_EOL;
}
foreach (App\Support\CourierAccess::groups() as $code => $g) {
  echo "GROUP " . $code . ": " . collect($g["couriers"])->map(fn($c, $key) => $c["label"] . ($c["default"] ? " [ON]" : " [OFF]"))->implode(", ") . PHP_EOL;
}
' 2>&1 | grep -E "^(CHK|GROUP)|Error|Exception" )
echo "$OUT"
echo "$OUT" | grep -q "^CHK /active-slebs-add 200" || { echo "page check failed"; restore; }
echo "$OUT" | grep -qE "^CHK .* 5[0-9][0-9]" && { echo "a page returned a server error"; restore; }

chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
echo "DONE. Backup kept at $BK"
