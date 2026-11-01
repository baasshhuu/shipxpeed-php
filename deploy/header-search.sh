#!/usr/bin/env bash
# Header search: find orders by AWB, order no. / channel order id, buyer name, phone or e-mail, and open details.
# Replaces Dashboard::searchAwb() and Dashboard::getOrderDetails() on the server copy with the repo versions.
# Backs up first, restores on failure. Safe to run twice.
# Usage: cd /var/www/shipxpeed && git fetch origin && git show origin/main:deploy/header-search.sh | bash
set -uo pipefail
APP=${APP:-/var/www/shipxpeed}
cd "$APP" || { echo "FAIL: $APP not found"; exit 1; }
F=app/Http/Controllers/selleradmin/Dashboard.php
BK=/root/pre-header-search-$(date +%Y%m%d-%H%M%S).tgz
git fetch -q origin || { echo "FAIL: git fetch"; exit 1; }
[ -s "$F" ] || { echo "FAIL: $F missing"; exit 1; }
tar czf "$BK" "$F" && echo "Backup: $BK"
restore() { echo "!! restoring"; tar xzf "$BK"; php artisan optimize:clear >/dev/null 2>&1; echo "RESTORED - nothing changed"; exit 1; }
git show "origin/main:$F" > /tmp/dash-origin.php || restore

F="$F" php <<'PHP' || restore
<?php
function block($s, $what) {
    $a = strpos($s, "public function searchAwb(Request \$request)");
    if (!preg_match('/^public function getOrderDetails\(Request \$request, \$awb\)/m', $s, $m, PREG_OFFSET_CAPTURE, $a ?: 0)) { fwrite(STDERR, "getOrderDetails not found ($what)\n"); exit(1); }
    $b = $m[0][1];
    $c = strpos($s, "\n}\n", $b);
    if ($a === false || $c === false) { fwrite(STDERR, "searchAwb block not found ($what)\n"); exit(1); }
    return [$a, $c + 3];
}
$f = getenv('F'); $s = file_get_contents($f); $o = file_get_contents('/tmp/dash-origin.php');
if (strpos($s, 'Header search: AWB, order no.') !== false) { echo "already there: header search\n"; exit(0); }
[$oa, $ob] = block($o, 'repo'); [$sa, $sb] = block($s, 'server');
file_put_contents($f, substr($s, 0, $sa) . substr($o, $oa, $ob - $oa) . substr($s, $sb));
echo "patched: $f (searchAwb + getOrderDetails)\n";
PHP

php -l "$F" >/dev/null 2>&1 || { php -l "$F"; restore; }
chown www-data:www-data "$F" 2>/dev/null
sudo -u www-data php artisan optimize:clear >/dev/null 2>&1

OUT=$(sudo -u www-data php -r '
require "vendor/autoload.php"; $a = require "bootstrap/app.php";
$a->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
try {
  $o = App\Models\Order::whereNotNull("awb_number")->orderByDesc("id")->first();
  if (!$o) { echo "SEARCH skip (no orders)\n"; exit; }
  $s = App\Models\SellerList::find($o->seller_id); auth()->guard("seller")->setUser($s);
  $c = app(App\Http\Controllers\selleradmin\Dashboard::class);
  $r = $c->searchAwb(new Illuminate\Http\Request(["q" => substr($o->awb_number, 0, 6)]))->getData(true);
  echo "SEARCH " . (($r["success"] ?? false) ? "ok " . count($r["orders"]) : "fail " . ($r["message"] ?? "")) . "\n";
  $d = $c->getOrderDetails(new Illuminate\Http\Request(), $o->awb_number)->getData(true);
  echo "DETAILS " . (($d["success"] ?? false) ? "ok" : "fail " . ($d["message"] ?? "")) . "\n";
} catch (\Throwable $e) { echo "SEARCH fail " . $e->getMessage() . "\n"; }' 2>&1)
echo "$OUT" | grep -E "^(SEARCH|DETAILS)"
echo "$OUT" | grep -qE "^SEARCH (ok|skip)" || { echo "$OUT" | tail -5; restore; }
echo "HEADER_SEARCH_DONE backup=$BK"
