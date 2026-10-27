#!/usr/bin/env bash
# Makes the Courier Status switches apply to Shipclues couriers on a server whose
# ShipcluesService.php has local edits (patches it in place instead of replacing it).
#  - remembers the seller at the start of getServiceability / getRatesForParams / assignOrder,
#  - drops couriers that are OFF for that seller (own switch, else default, removed = always off)
#    right where Shipclues rate rows are returned.
# Backs up first and restores automatically if any check fails. Safe to re-run.
set -uo pipefail
cd /var/www/shipxpeed || exit 1
F=app/Services/ShipcluesService.php
STAMP=$(date +%Y%m%d-%H%M%S)
BK=/root/pre-shipclues-switches-$STAMP.tgz

[ -e "$F" ] || { echo "FAIL: $F not found"; exit 1; }
[ -e app/Support/CourierAccess.php ] || { echo "FAIL: run deploy/courier-status.sh first"; exit 1; }
if grep -q "CourierAccess::isOn" "$F" || grep -q "RateCard::hiddenFor" "$F"; then
  echo "Shipclues switches already active - nothing to do"; exit 0
fi

tar czf "$BK" "$F" && echo "Backup: $BK"
restore() { echo "!! restoring backup"; tar xzf "$BK"; echo "RESTORED - nothing changed"; exit 1; }

php <<'PHP' || restore
<?php
$f = 'app/Services/ShipcluesService.php';
$s = file_get_contents($f);
$fail = function ($m) { fwrite(STDERR, "anchor not found: $m\n"); exit(1); };

// 1) property + helper right after the class opens
$n = 0;
$s = preg_replace('/(class\s+ShipcluesService\b[^{]*\{)/', "$1\n" .
"    /** Seller whose rates are being fetched (0 = unknown -> courier defaults apply). */\n" .
"    private int \$scSellerId = 0;\n\n" .
"    private function scRememberSeller(\$params): void\n" .
"    {\n" .
"        try {\n" .
"            \$p = is_array(\$params) ? \$params : (array) \$params;\n" .
"            \$id = (int) (\$p['seller_id'] ?? 0);\n" .
"            if (!\$id && !empty(\$p['order_id'])) {\n" .
"                \$id = (int) (\\App\\Models\\Order::whereKey(\$p['order_id'])->value('seller_id') ?? 0);\n" .
"            }\n" .
"            if (!\$id && \\Illuminate\\Support\\Facades\\Auth::guard('seller')->check()) {\n" .
"                \$id = (int) \\Illuminate\\Support\\Facades\\Auth::guard('seller')->id();\n" .
"            }\n" .
"            \$this->scSellerId = \$id;\n" .
"        } catch (\\Throwable \$e) {\n" .
"            \$this->scSellerId = 0;\n" .
"        }\n" .
"    }\n", $s, 1, $n);
if (!$n) $fail('class ShipcluesService {');

// 2) remember the seller at the start of each entry point
foreach (['getServiceability\(array \$params\): array', 'getRatesForParams\(array \$params\): array', 'assignOrder\(\$params\)'] as $sig) {
    $n = 0;
    $s = preg_replace('/(public function ' . $sig . '\s*\{)/', "$1\n        \$this->scRememberSeller(\$params);", $s, 1, $n);
    if (!$n) $fail($sig);
}

// 3) filter the rate rows where rateCalculator returns them
$n = 0;
$s = preg_replace(
    '/(\$rows\s*=\s*is_array\(\$rows\)\s*\?\s*array_values\(array_filter\(\$rows,\s*fn\s*\(\$r\)\s*=>\s*is_array\(\$r\)\s*&&\s*isset\(\$r\[\'courier_id\'\]\)\)\)\s*:\s*\[\];)/',
    "$1\n        // Courier Status switches (Admin > Price Setting > Courier Status)\n" .
    "        \$rows = array_values(array_filter(\$rows, function (\$r) {\n" .
    "            \$name = \\App\\Support\\CourierBrand::cleanName(\$r['courier_name'] ?? null);\n" .
    "            \$key = 'Shipclues_' . trim(preg_replace('/[^A-Za-z0-9]+/', '_', \$name), '_');\n" .
    "            return \\App\\Support\\CourierAccess::isOn(\$this->scSellerId, \$key, 'Shipclues');\n" .
    "        }));",
    $s, 1, $n);
if (!$n) $fail('$rows = is_array($rows) ? ... courier_id ...');

file_put_contents($f, $s);
echo "patched: $f\n";
PHP

php -l "$F" >/dev/null 2>&1 || { echo "LINT FAIL: $F"; restore; }
php artisan view:clear >/dev/null 2>&1; php artisan cache:clear >/dev/null 2>&1 || true

# Live check: Delhi -> Hyderabad, 500 g prepaid, default switches
OUT=$(php artisan tinker --execute='
$r = (new App\Services\ShipcluesService)->getRatesForParams(["origin_pincode"=>"110053","destination_pincode"=>"500001","package_weight"=>500,"payment_type"=>"prepaid"]);
echo "SHOWN " . count($r) . ": " . collect($r)->pluck("courierName")->implode(", ") . PHP_EOL;
' 2>&1 | grep -E "^SHOWN|Error|Exception")
echo "$OUT"
echo "$OUT" | grep -q "^SHOWN" || { echo "rate check failed"; restore; }

echo "DONE. Backup kept at $BK"
