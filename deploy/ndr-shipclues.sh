#!/usr/bin/env bash
# NDR page actions for Shipclues shipments (re-attempt / new address / new phone / RTO by API).
# Inserts ShipcluesService::ndrAction() into the server copy (never replaces the file) and updates the
# NDR dispatcher. Backs up first, restores on any failure. Safe to run twice.
set -uo pipefail
APP=${APP:-/var/www/shipxpeed}
cd "$APP" || { echo "FAIL: $APP not found"; exit 1; }
REF=origin/main
BK=/root/pre-ndr-shipclues-$(date +%Y%m%d-%H%M%S).tgz
SC=app/Services/ShipcluesService.php
DP=app/Services/WhatsApp/NdrActionDispatcher.php

git fetch -q origin || { echo "FAIL: git fetch"; exit 1; }
for f in $SC $DP; do [ -s "$f" ] || { echo "FAIL: $f missing on this server"; exit 1; }; done
tar czf "$BK" $SC $DP && echo "Backup: $BK"
restore() { echo "!! restoring"; tar xzf "$BK"; php artisan optimize:clear >/dev/null 2>&1; echo "RESTORED - nothing changed"; exit 1; }

git show "$REF:$SC" > /tmp/sc-origin.php || restore
SC="$SC" php <<'PHP' || restore
<?php
$f = getenv('SC'); $s = file_get_contents($f);
if (strpos($s, 'function ndrAction(') !== false) { echo "already there: ShipcluesService::ndrAction\n"; exit(0); }
$o = file_get_contents('/tmp/sc-origin.php');
$a = strpos($o, "    /**\n     * NDR action on a Shipclues shipment.");
$b = strpos($o, "    public function trackOrder(Order \$order): ?string");
if ($a === false || $b === false || $b < $a) { fwrite(STDERR, "method not found in repo copy\n"); exit(1); }
$method = substr($o, $a, $b - $a);
$anchor = "    public function trackOrder(Order \$order): ?string";
if (substr_count($s, $anchor) !== 1) { fwrite(STDERR, "trackOrder anchor not found on server copy\n"); exit(1); }
foreach (['use Illuminate\\Support\\Facades\\Http;', 'use Illuminate\\Support\\Facades\\Log;'] as $u) {
    if (strpos($s, $u) === false) { $s = preg_replace('/^namespace [^;]+;\n/m', "$0\n$u\n", $s, 1); }
}
file_put_contents($f, str_replace($anchor, $method . $anchor, $s));
echo "patched: $f (+ndrAction)\n";
PHP

DP="$DP" php <<'PHP' || restore
<?php
$f = getenv('DP'); $s = file_get_contents($f);
if (strpos($s, "\$courier === 'shipclues'") !== false) { echo "already there: dispatcher Shipclues branch\n"; exit(0); }
$anchor = "            if (\$action === 'rto') {";
if (substr_count($s, $anchor) !== 1) { fwrite(STDERR, "dispatcher anchor not found\n"); exit(1); }
$add = "            if (\$courier === 'shipclues' && class_exists(\\App\\Services\\ShipcluesService::class) && method_exists(\\App\\Services\\ShipcluesService::class, 'ndrAction')) {\n"
     . "                \$r = app(\\App\\Services\\ShipcluesService::class)->ndrAction(\$order, \$action, \$data);\n"
     . "                return \$this->result((bool) (\$r['status'] ?? false), \$r['message'] ?? '', \$r);\n"
     . "            }\n\n";
file_put_contents($f, str_replace($anchor, $add . $anchor, $s));
echo "patched: $f (Shipclues branch)\n";
PHP

for f in $SC $DP; do php -l "$f" >/dev/null 2>&1 || { echo "LINT FAIL $f"; php -l "$f"; restore; }; done
chown www-data:www-data $SC $DP 2>/dev/null
sudo -u www-data php artisan optimize:clear >/dev/null 2>&1

OUT=$(sudo -u www-data php -r '
require "vendor/autoload.php"; $a = require "bootstrap/app.php";
$k = $a->make(Illuminate\Contracts\Http\Kernel::class); $k->bootstrap();
echo "METHOD " . (method_exists(App\Services\ShipcluesService::class, "ndrAction") ? "ok" : "missing") . PHP_EOL;
$s = App\Models\SellerList::where("status", 1)->first() ?: App\Models\SellerList::first();
Auth::guard("seller")->setUser($s);
echo "PAGE " . $k->handle(Illuminate\Http\Request::create("/seller/xp/ndr"))->getStatusCode() . PHP_EOL;
' 2>&1)
echo "$OUT" | grep -E "^(METHOD|PAGE)"
echo "$OUT" | grep -q "^METHOD ok" || restore
echo "$OUT" | grep -qE "^PAGE (200|302)" || { echo "$OUT" | tail -5; restore; }
echo "NDR_SHIPCLUES_DONE backup=$BK"
