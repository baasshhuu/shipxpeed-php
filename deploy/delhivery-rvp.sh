#!/usr/bin/env bash
# Delhivery reverse pickup (RVP) on the Reverse orders tab:
#  - Delhivery shows on the reverse courier page only when Delhivery can pick up from the buyer's pincode,
#    priced from the seller's Delhivery rate card (no COD charge)
#  - booking sends payment_mode "Pickup" to Delhivery (buyer -> seller's warehouse)
#  - the server-side price check uses the reverse price for reverse orders
# Patches the server copies in place. Backs up first, restores on failure. Safe to run twice.
# Usage: cd /var/www/shipxpeed && git fetch origin && git show origin/main:deploy/delhivery-rvp.sh | bash
set -uo pipefail
APP=${APP:-/var/www/shipxpeed}
cd "$APP" || { echo "FAIL: $APP not found"; exit 1; }
REF=origin/main
SV=app/Services/DelhiveryB2CService.php
SR=app/Support/ShipRate.php
SC=app/Http/Controllers/selleradmin/ShipmentController.php
BK=/root/pre-delhivery-rvp-$(date +%Y%m%d-%H%M%S).tgz
git fetch -q origin || { echo "FAIL: git fetch"; exit 1; }
for f in $SV $SR $SC; do [ -s "$f" ] || { echo "FAIL: $f missing"; exit 1; }; done
tar czf "$BK" $SV $SR $SC && echo "Backup: $BK"
restore() { echo "!! restoring"; tar xzf "$BK"; php artisan optimize:clear >/dev/null 2>&1; echo "RESTORED - nothing changed"; exit 1; }
git show "$REF:$SV" > /tmp/rvp-sv.php || restore

SV=$SV SR=$SR SC=$SC php <<'PHP' || restore
<?php
function die1($m) { fwrite(STDERR, $m . "\n"); exit(1); }

// 1) service: replace reversegetServiceability() with the repo version (+ helpers)
$f = getenv('SV'); $s = file_get_contents($f);
if (strpos($s, 'DELHIVERY-RVP') === false) {
    $o = file_get_contents('/tmp/rvp-sv.php');
    $start = 'public function reversegetServiceability(array $params): array';
    $end = '    public function assignOrder($orderPayload)';
    $oa = strpos($o, $start); $ob = strpos($o, $end);
    $sa = strpos($s, $start); $sb = strpos($s, $end);
    if ($oa === false || $ob === false) die1('repo service block not found');
    if ($sa === false || $sb === false || $sb < $sa) die1('server service block not found');
    $s = substr($s, 0, $sa) . substr($o, $oa, $ob - $oa) . substr($s, $sb);
    foreach (['use Illuminate\\Support\\Facades\\Cache;', 'use Illuminate\\Support\\Facades\\Http;'] as $u) {
        if (strpos($s, $u) === false) $s = preg_replace('/^namespace [^;]+;\n/m', "$0\n$u\n", $s, 1);
    }
    file_put_contents($f, $s); echo "patched: $f (reverse pickup)\n";
} else echo "already there: service\n";

// 2) ShipRate: reverse price for reverse orders
$f = getenv('SR'); $s = file_get_contents($f);
if (strpos($s, '$isReverse') === false) {
    $old = "\$entries = array_values(array_filter((array) \$svc->getServiceability(['order_id' => \$orderId]), 'is_array'));";
    if (substr_count($s, $old) !== 1) die1('ShipRate anchor not found');
    $new = "\$isReverse = optional(\\App\\Models\\Order::find(\$orderId))->reverse === 'reverse' && method_exists(\$svc, 'reversegetServiceability');\n"
         . "            \$entries = array_values(array_filter((array) (\$isReverse ? \$svc->reversegetServiceability(['order_id' => \$orderId]) : \$svc->getServiceability(['order_id' => \$orderId])), 'is_array'));";
    file_put_contents($f, str_replace($old, $new, $s)); echo "patched: $f\n";
} else echo "already there: ShipRate\n";

// 3) booking: RVP payload for reverse orders on Delhivery (courier 4)
$f = getenv('SC'); $s = file_get_contents($f);
if (strpos($s, 'DELHIVERY-RVP') === false) {
    if (!preg_match("/(\\} else if \\(\\\$courierId == '4'\\) \\{\\s*\\n\\s*\\\$order = Order::where\\('id', \\\$orderId\\)->first\\(\\);\\s*\\n\\s*\\\$params = json_decode\\(\\\$order->delhivery_b2c\\);)/", $s, $m) || substr_count($s, $m[1]) !== 1) die1('assignCourier courier 4 branch not found');
    $add = "\n            if (\$order->reverse === 'reverse') {\n"
         . "                // DELHIVERY-RVP: reverse pickup from the buyer to the seller's warehouse\n"
         . "                \$params = \\App\\Services\\DelhiveryB2CService::toRvpPayload(\$params);\n"
         . "            }";
    file_put_contents($f, str_replace($m[1], $m[1] . $add, $s)); echo "patched: $f (assignCourier)\n";
} else echo "already there: assignCourier\n";
PHP

for f in $SV $SR $SC; do php -l "$f" >/dev/null 2>&1 || { php -l "$f"; restore; }; done
chown www-data:www-data $SV $SR $SC 2>/dev/null
sudo -u www-data php artisan optimize:clear >/dev/null 2>&1

OUT=$(sudo -u www-data php -r '
require "vendor/autoload.php"; $a = require "bootstrap/app.php";
$a->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
try {
  $p = App\Services\DelhiveryB2CService::toRvpPayload(["shipments" => [["payment_mode" => "Prepaid", "cod_amount" => "100"]]]);
  echo "PAYLOAD " . ($p["shipments"][0]["payment_mode"] === "Pickup" ? "ok" : "fail") . "\n";
  echo "PINCHECK 110053 " . (App\Services\DelhiveryB2CService::rvpPickupServiceable("110053") ? "pickup-yes" : "pickup-no") . "\n";
  $o = App\Models\Order::where("reverse", "reverse")->whereNull("awb_number")->orderByDesc("id")->first();
  if ($o) { $r = app("courier.delhivery_b2c")->reversegetServiceability(["order_id" => $o->id]); echo "REVERSE order " . $o->id . ": " . ($r ? "Delhivery Rs " . $r[0]["courierCharge"] : "not offered (pincode/rate card)") . "\n"; }
} catch (\Throwable $e) { echo "PAYLOAD fail " . $e->getMessage() . "\n"; }' 2>&1)
echo "$OUT" | grep -E "^(PAYLOAD|PINCHECK|REVERSE)"
echo "$OUT" | grep -q "^PAYLOAD ok" || { echo "$OUT" | tail -5; restore; }
echo "DELHIVERY_RVP_DONE backup=$BK"
