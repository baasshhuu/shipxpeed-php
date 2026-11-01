#!/usr/bin/env bash
# NDR tab -> dedicated NDR page: courier-wise filter, one-click + bulk re-attempt via courier APIs,
# XpressBees NDR API. Patches the server copy in place (never replaces PHP files), replaces the NDR view.
# Backs up first, restores on any failure. Safe to run twice.
# Usage: cd /var/www/shipxpeed && git fetch origin && git show origin/main:deploy/ndr-page.sh | bash
set -uo pipefail
APP=${APP:-/var/www/shipxpeed}
cd "$APP" || { echo "FAIL: $APP not found"; exit 1; }
REF=origin/main
BK=/root/pre-ndr-page-$(date +%Y%m%d-%H%M%S).tgz
XB=app/Services/XpressBeesService.php
DP=app/Services/WhatsApp/NdrActionDispatcher.php
SS=app/Http/Controllers/Xpeed/SellerSuiteController.php
DB=app/Http/Controllers/selleradmin/Dashboard.php
VW=resources/views/sellerdashboard/xpeed/ndr.blade.php
FILES="$XB $DP $SS $DB $VW"

git fetch -q origin || { echo "FAIL: git fetch"; exit 1; }
for f in $FILES; do [ -s "$f" ] || { echo "FAIL: $f missing on this server"; exit 1; }; done
tar czf "$BK" $FILES && echo "Backup: $BK"
restore() { echo "!! restoring"; tar xzf "$BK"; php artisan optimize:clear >/dev/null 2>&1; echo "RESTORED - nothing changed"; exit 1; }

mkdir -p /tmp/ndrpage
for f in $XB $DP $SS $DB; do git show "$REF:$f" > "/tmp/ndrpage/$(basename $f)" || restore; done
git show "$REF:$VW" > "$VW" || restore
echo "replaced: $VW"

XB=$XB DP=$DP SS=$SS DB=$DB php <<'PHP' || restore
<?php
function cut($o, $from, $to) { $a = strpos($o, $from); $b = strpos($o, $to, $a === false ? 0 : $a); if ($a === false || $b === false) { fwrite(STDERR, "repo block not found: " . substr($from, 0, 50) . "\n"); exit(1); } return substr($o, $a, $b - $a); }
function once($s, $anchor, $what) { if (substr_count($s, $anchor) !== 1) { fwrite(STDERR, "anchor not found ($what)\n"); exit(1); } }

// 1) XpressBees ndrAction
$f = getenv('XB'); $s = file_get_contents($f);
if (strpos($s, 'function ndrAction(') === false) {
    $o = file_get_contents('/tmp/ndrpage/XpressBeesService.php');
    $a = strpos($o, "    /**\n     * NDR action via XpressBees");
    if ($a === false) { fwrite(STDERR, "XB method not in repo\n"); exit(1); }
    $method = rtrim(substr($o, $a)); $method = rtrim(substr($method, 0, strrpos($method, '}'))) . "\n";
    $s = rtrim($s); $s = rtrim(substr($s, 0, strrpos($s, '}'))) . "\n\n" . $method . "}\n";
    file_put_contents($f, $s); echo "patched: $f (+ndrAction)\n";
} else { echo "already there: XpressBees ndrAction\n"; }

// 2) dispatcher: XpressBees branch + channel()
$f = getenv('DP'); $s = file_get_contents($f);
if (strpos($s, "\$courier === 'xpressbees'") === false) {
    $anchor = "            if (\$action === 'rto') {"; once($s, $anchor, 'dispatcher rto');
    $add = "            if (\$courier === 'xpressbees' && \$action !== 'rto') {\n"
         . "                \$r = app(\\App\\Services\\XpressBeesService::class)->ndrAction(\$order, \$action, \$data);\n"
         . "                return \$this->result((bool) (\$r['status'] ?? false), \$r['message'] ?? '', \$r);\n"
         . "            }\n\n";
    $s = str_replace($anchor, $add . $anchor, $s); echo "patched: dispatcher (XpressBees)\n";
}
if (strpos($s, 'function channel(') === false) {
    $o = file_get_contents('/tmp/ndrpage/NdrActionDispatcher.php');
    $block = cut($o, "    /**\n     * How a re-attempt for this courier", "    /** Delhivery: details via");
    $anchor = "    /** Delhivery: details via"; once($s, $anchor, 'dispatcher delhivery');
    $s = str_replace($anchor, $block . $anchor, $s); echo "patched: dispatcher (channel)\n";
}
file_put_contents($f, $s);

// 3) seller suite controller: courier filter + bulk with date
$f = getenv('SS'); $s = file_get_contents($f); $o = file_get_contents('/tmp/ndrpage/SellerSuiteController.php');
if (strpos($s, "'couriers', 'courier')") === false) {
    $block = cut($o, "        // courier-wise grouping (filter chips)", "        \$history = ");
    $anchor = "        \$taken = \$orders->diff(\$pending);\n"; once($s, $anchor, 'ndr taken');
    $s = str_replace($anchor, $anchor . "\n" . $block, $s);
    $s = str_replace("compact('tab', 'orders', 'pending', 'taken', 'actions', 'history', 'awbOf', 'hasNdr')", "compact('tab', 'orders', 'pending', 'taken', 'actions', 'history', 'awbOf', 'hasNdr', 'couriers', 'courier')", $s);
    if (strpos($s, "'couriers', 'courier')") === false) { fwrite(STDERR, "compact() not patched\n"); exit(1); }
    echo "patched: suite ndr() courier filter\n";
}
if (strpos($s, "'order_ids' => 'required|array|max:300'") === false) {
    $new = cut($o, "    public function ndrBulk(", "    private function dispatchNdr(");
    $a = strpos($s, "    public function ndrBulk("); $b = strpos($s, "    private function dispatchNdr(");
    if ($a === false || $b === false || $b < $a) { fwrite(STDERR, "ndrBulk not found on server\n"); exit(1); }
    $s = substr($s, 0, $a) . $new . substr($s, $b); echo "patched: suite ndrBulk()\n";
}
foreach (['use App\\Services\\WhatsApp\\NdrActionDispatcher;', 'use App\\Support\\Xpeed\\Kit;'] as $u) {
    if (strpos($s, $u) === false) { $s = preg_replace('/^namespace [^;]+;\n/m', "$0\n$u\n", $s, 1); }
}
file_put_contents($f, $s);

// 4) order list NDR tab -> NDR page
$f = getenv('DB'); $s = file_get_contents($f);
if (strpos($s, "redirect()->route('xp.ndr'") === false) {
    $anchor = "    public function NDR(Request \$request)\n    {\n"; once($s, $anchor, 'Dashboard::NDR');
    $add = "        // The NDR tab opens the dedicated NDR page (NDR orders only, courier-wise\n"
         . "        // re-attempt / bulk re-attempt sent straight to the courier's API).\n"
         . "        if (\\Illuminate\\Support\\Facades\\Route::has('xp.ndr')) {\n"
         . "            return redirect()->route('xp.ndr', array_filter(['q' => \$request->get('search'), 'courier' => \$request->get('courier')]));\n"
         . "        }\n\n";
    file_put_contents($f, str_replace($anchor, $anchor . $add, $s)); echo "patched: Dashboard::NDR redirect\n";
} else { echo "already there: NDR redirect\n"; }
PHP

for f in $XB $DP $SS $DB; do php -l "$f" >/dev/null 2>&1 || { echo "LINT FAIL $f"; php -l "$f"; restore; }; done
chown www-data:www-data $FILES 2>/dev/null
sudo -u www-data php artisan optimize:clear >/dev/null 2>&1

OUT=$(sudo -u www-data php -r '
require "vendor/autoload.php"; $a = require "bootstrap/app.php";
$k = $a->make(Illuminate\Contracts\Http\Kernel::class); $k->bootstrap();
$s = App\Models\SellerList::where("status", 1)->first() ?: App\Models\SellerList::first();
Auth::guard("seller")->setUser($s);
echo "PAGE " . $k->handle(Illuminate\Http\Request::create("/seller/xp/ndr"))->getStatusCode() . PHP_EOL;
$r = $k->handle(Illuminate\Http\Request::create("/seller/courier/NDR"));
echo "TAB " . $r->getStatusCode() . " " . $r->headers->get("Location") . PHP_EOL;
' 2>&1)
echo "$OUT" | grep -E "^(PAGE|TAB)"
echo "$OUT" | grep -qE "^PAGE (200|302)" || { echo "$OUT" | tail -8; restore; }
echo "NDR_PAGE_DONE backup=$BK"
