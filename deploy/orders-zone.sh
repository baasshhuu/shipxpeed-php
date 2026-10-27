#!/usr/bin/env bash
# Seller > Orders: adds a "Zone" column (which zone each order lands in, pickup state -> delivery state)
# and tidies the page's sizes/spacing on the dark theme.
# Patches the server copies in place (safe to run twice), backs up first, restores if any check fails.
set -uo pipefail
cd /var/www/shipxpeed || exit 1
REF=origin/main
STAMP=$(date +%Y%m%d-%H%M%S)
BK=/root/pre-orders-zone-$STAMP.tgz
VIEW=resources/views/sellerdashboard/order.blade.php
RC=app/Support/RateCard.php

git fetch -q origin || { echo "FAIL: git fetch"; exit 1; }
tar czf "$BK" "$VIEW" "$RC" && echo "Backup: $BK"
restore() {
  echo "!! restoring backup"; tar xzf "$BK"
  php artisan view:clear >/dev/null 2>&1
  echo "RESTORED - nothing changed"; exit 1
}

git show "$REF:$VIEW" > /tmp/order.new.blade.php || restore
git show "$REF:$RC" > /tmp/RateCard.new.php || restore

php <<'PHP' || restore
<?php
function between($s, $a, $b) {
    $i = strpos($s, $a); $j = strpos($s, $b, $i === false ? 0 : $i);
    if ($i === false || $j === false) { fwrite(STDERR, "marker missing: $a\n"); exit(1); }
    return substr($s, $i, $j + strlen($b) - $i);
}

// 1) RateCard::zoneForOrder()
$rc = 'app/Support/RateCard.php';
$s = file_get_contents($rc);
if (strpos($s, 'function zoneForOrder') !== false) {
    echo "already there: zoneForOrder\n";
} else {
    $new = file_get_contents('/tmp/RateCard.new.php');
    $sig = '    public static function zoneForStates(';
    $start = strpos($new, "    /**\n     * Zone an order lands in");
    $end = strpos($new, $sig);
    if ($start === false || $end === false || strpos($s, $sig) === false) { fwrite(STDERR, "RateCard anchor not found\n"); exit(1); }
    $s = str_replace($sig, substr($new, $start, $end - $start) . $sig, $s);
    file_put_contents($rc, $s);
    echo "patched: $rc\n";
}

// 2) Orders view
$v = 'resources/views/sellerdashboard/order.blade.php';
$s = file_get_contents($v);
$new = file_get_contents('/tmp/order.new.blade.php');
$changed = false;

if (strpos($s, '<th>Zone</th>') === false) {
    $th = '<th>Shipping Details</th>';
    if (substr_count($s, $th) < 1) { fwrite(STDERR, "header anchor not found\n"); exit(1); }
    $s = preg_replace('/(\s*)' . preg_quote($th, '/') . '/', '$1' . $th . '$1<th>Zone</th>', $s, 1);
    $changed = true;
}
if (strpos($s, 'zone-col:start') === false) {
    $cell = between($new, '{{-- zone-col:start --}}', '{{-- zone-col:end --}}');
    // insert right after the Shipping Details cell (the one holding .customer-info)
    $i = strpos($s, 'class="customer-info"');
    if ($i === false) { fwrite(STDERR, "customer-info cell not found\n"); exit(1); }
    $j = strpos($s, '</td>', $i);
    $s = substr($s, 0, $j + 5) . "\n                            " . $cell . substr($s, $j + 5);
    $changed = true;
}
if (strpos($s, 'orders-polish:start') === false) {
    $css = between($new, '{{-- orders-polish:start --}}', '{{-- orders-polish:end --}}');
    $k = strrpos($s, '@endsection');
    if ($k === false) { fwrite(STDERR, "@endsection not found\n"); exit(1); }
    $s = substr($s, 0, $k) . $css . "\n" . substr($s, $k);
    $changed = true;
} else {
    // refresh the style block to the latest version
    $old = between($s, '{{-- orders-polish:start --}}', '{{-- orders-polish:end --}}');
    $s = str_replace($old, between($new, '{{-- orders-polish:start --}}', '{{-- orders-polish:end --}}'), $s);
    $changed = true;
}
if ($changed) { file_put_contents($v, $s); echo "patched: $v\n"; }
PHP

php -l "$RC" >/dev/null || restore
php artisan view:clear >/dev/null

OUT=$(php artisan tinker --execute='
$s = App\Models\SellerList::where("status", 1)->first() ?? App\Models\SellerList::first();
Auth::guard("seller")->setUser($s);
$r = app(Illuminate\Contracts\Http\Kernel::class)->handle(Illuminate\Http\Request::create("https://shipxpeed.tech/seller/order"));
$c = (string) $r->getContent();
echo "CHK order " . $r->getStatusCode() . ($r->getStatusCode() >= 500 ? " ERR " . substr(strip_tags($c), 0, 300) : "") . PHP_EOL;
echo "CHK zonecol " . (str_contains($c, "<th>Zone</th>") ? "yes" : "no") . PHP_EOL;
$o = App\Models\Order::where("seller_id", $s->id)->latest()->first();
if ($o) { $z = App\Support\RateCard::zoneForOrder($o); echo "ZONE #" . $o->id . " " . ($z["from"] ?? "-") . " -> " . ($z["to"] ?? "-") . " = " . ($z["zone"] ?? "none") . PHP_EOL; }
' 2>&1 | grep -E "^(CHK|ZONE)|Error|Exception")
echo "$OUT"
echo "$OUT" | grep -q "^CHK order 200" || { echo "orders page check failed"; restore; }
echo "$OUT" | grep -q "^CHK zonecol yes" || { echo "zone column missing"; restore; }

chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
echo "DONE. Backup kept at $BK"
