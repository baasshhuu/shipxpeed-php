#!/usr/bin/env bash
# Seller "B2B Shipments" section: own menu group (Create / All shipments / Rate calculator / Track),
# tabbed B2B pages, and a B2C | B2B switch on the rate calculator. B2B is removed from the B2C "Orders" group.
# Backs up first and restores automatically if any step fails. Safe to run twice.
# Usage:  bash deploy/b2b-shipments.sh
set -uo pipefail
APP=${APP:-/var/www/shipxpeed}
cd "$APP" || { echo "FAIL: $APP not found"; exit 1; }
REF=origin/main
BK=/root/pre-b2b-shipments-$(date +%Y%m%d-%H%M%S).tgz
COPY="
app/Http/Controllers/selleradmin/B2bOrderController.php
routes/b2b_web.php
resources/views/sellerdashboard/b2b/_style.blade.php
resources/views/sellerdashboard/b2b/_tabs.blade.php
resources/views/sellerdashboard/b2b/_calc_switch.blade.php
resources/views/sellerdashboard/b2b/index.blade.php
resources/views/sellerdashboard/b2b/create.blade.php
resources/views/sellerdashboard/b2b/show.blade.php
resources/views/sellerdashboard/b2b/rates.blade.php
resources/views/sellerdashboard/b2b/track.blade.php
"
MENU=resources/views/partials/vas-menu-link.blade.php
CALC=resources/views/sellerdashboard/tools/newratecalculater.blade.php

echo "Shipxpeed B2B Shipments section installer"
[ -f app/Services/BigshipService.php ] || { echo "FAIL: run deploy/bigship-b2b.sh first"; exit 1; }
git fetch -q origin || { echo "FAIL: git fetch"; exit 1; }
git cat-file -e "$REF:deploy/b2b-shipments.sh" || { echo "FAIL: latest code not found on $REF"; exit 1; }
EXIST=""; for f in $COPY $MENU $CALC; do [ -e "$f" ] && EXIST="$EXIST $f"; done
tar czf "$BK" $EXIST && echo "Backup: $BK"
restore() {
  echo "!! restoring backup"; tar xzf "$BK"
  for f in $COPY; do tar tzf "$BK" | grep -qxF "$f" || rm -f "$f"; done
  php artisan optimize:clear >/dev/null 2>&1
  echo "RESTORED - nothing changed"; exit 1
}

for f in $COPY; do mkdir -p "$(dirname "$f")"; git show "$REF:$f" > "$f" || restore; done
echo "Copied B2B pages"

PAIRS='W1siICAgIHZhciBCMkIgPSBAanNvbihSb3V0ZTo6aGFzKCdzZWxsZXIuYjJiLm9yZGVycycpICYmIGNsYXNzX2V4aXN0cyhcXEFwcFxcU2VydmljZXNcXEJpZ3NoaXBTZXJ2aWNlOjpjbGFzcykgJiYgYXBwKFxcQXBwXFxTZXJ2aWNlc1xcQmlnc2hpcFNlcnZpY2U6OmNsYXNzKS0+ZW5hYmxlZEZvclNlbGxlcnMoKSA/IHJvdXRlKCdzZWxsZXIuYjJiLm9yZGVycycpIDogbnVsbCk7IiwgIiAgICB2YXIgQjJCX09OID0gQGpzb24oUm91dGU6Omhhcygnc2VsbGVyLmIyYi5yYXRlcycpICYmIGNsYXNzX2V4aXN0cyhcXEFwcFxcU2VydmljZXNcXEJpZ3NoaXBTZXJ2aWNlOjpjbGFzcykgJiYgYXBwKFxcQXBwXFxTZXJ2aWNlc1xcQmlnc2hpcFNlcnZpY2U6OmNsYXNzKS0+ZW5hYmxlZEZvclNlbGxlcnMoKSk7XG4gICAgdmFyIFNQRUNJQUwgPSB7J0B2YXMnOiBWQVMsICdAbmRyJzogTkRSLFxuICAgICAgICAnQGIyYi1uZXcnOiBCMkJfT04gPyBAanNvbihSb3V0ZTo6aGFzKCdzZWxsZXIuYjJiLm9yZGVyJykgPyByb3V0ZSgnc2VsbGVyLmIyYi5vcmRlcicpIDogbnVsbCkgOiBudWxsLFxuICAgICAgICAnQGIyYic6IEIyQl9PTiA/IEBqc29uKFJvdXRlOjpoYXMoJ3NlbGxlci5iMmIub3JkZXJzJykgPyByb3V0ZSgnc2VsbGVyLmIyYi5vcmRlcnMnKSA6IG51bGwpIDogbnVsbCxcbiAgICAgICAgJ0BiMmItcmF0ZXMnOiBCMkJfT04gPyBAanNvbihSb3V0ZTo6aGFzKCdzZWxsZXIuYjJiLnJhdGVzJykgPyByb3V0ZSgnc2VsbGVyLmIyYi5yYXRlcycpIDogbnVsbCkgOiBudWxsLFxuICAgICAgICAnQGIyYi10cmFjayc6IEIyQl9PTiA/IEBqc29uKFJvdXRlOjpoYXMoJ3NlbGxlci5iMmIudHJhY2snKSA/IHJvdXRlKCdzZWxsZXIuYjJiLnRyYWNrJykgOiBudWxsKSA6IG51bGx9OyJdLCBbIiAgICAgICAgICAgIFsnQGIyYicsICdCMkIgb3JkZXJzJywgJ3RpIHRpLXRydWNrLWRlbGl2ZXJ5J10sXG4iLCAiIl0sIFsiICAgICAgICAgICAgWycvc2VsbGVyL2NoYW5uZWxzJywgJ0NoYW5uZWxzJywgJ3RpIHRpLXBsdWcnXV1dLFxuIiwgIiAgICAgICAgICAgIFsnL3NlbGxlci9jaGFubmVscycsICdDaGFubmVscycsICd0aSB0aS1wbHVnJ11dXSxcbiAgICAgICAgWydCMkIgU2hpcG1lbnRzJywgW1xuICAgICAgICAgICAgWydAYjJiLW5ldycsICdDcmVhdGUgQjJCIHNoaXBtZW50JywgJ3RpIHRpLWNpcmNsZS1wbHVzJ10sXG4gICAgICAgICAgICBbJ0BiMmInLCAnQWxsIEIyQiBzaGlwbWVudHMnLCAndGkgdGktdHJ1Y2stZGVsaXZlcnknXSxcbiAgICAgICAgICAgIFsnQGIyYi1yYXRlcycsICdCMkIgcmF0ZSBjYWxjdWxhdG9yJywgJ3RpIHRpLWNhbGN1bGF0b3InXSxcbiAgICAgICAgICAgIFsnQGIyYi10cmFjaycsICdUcmFjayBCMkIgc2hpcG1lbnQnLCAndGkgdGktbWFwLXBpbiddXV0sXG4iXSwgWyIgICAgICAgIGlmIChrZXkgPT09ICdAYjJiJykgcmV0dXJuIGhlcmUuaW5kZXhPZignL3NlbGxlci9iMmItb3JkZXJzJykgPT09IDA7IiwgIiAgICAgICAgaWYgKGtleSA9PT0gJ0BiMmInKSByZXR1cm4gL15cXC9zZWxsZXJcXC9iMmItb3JkZXJzKFxcL1xcZCsuKik/JC8udGVzdChoZXJlKTtcbiAgICAgICAgaWYgKGtleS5pbmRleE9mKCdAYjJiLScpID09PSAwKSByZXR1cm4gaGVyZSA9PT0gcDsiXSwgWyIgICAgICAgICAgICAgICAgaWYgKGtleSA9PT0gJ0B2YXMnIHx8IGtleSA9PT0gJ0BuZHInIHx8IGtleSA9PT0gJ0BiMmInKSB7XG4gICAgICAgICAgICAgICAgICAgIHZhciB1cmwgPSBrZXkgPT09ICdAdmFzJyA/IFZBUyA6IChrZXkgPT09ICdAbmRyJyA/IE5EUiA6IEIyQik7IGlmICghdXJsKSByZXR1cm47IiwgIiAgICAgICAgICAgICAgICBpZiAoa2V5IGluIFNQRUNJQUwpIHtcbiAgICAgICAgICAgICAgICAgICAgdmFyIHVybCA9IFNQRUNJQUxba2V5XTsgaWYgKCF1cmwpIHJldHVybjsiXV0=' php <<'PHP' || restore
<?php
// 1. seller menu: own "B2B Shipments" group
$m = 'resources/views/partials/vas-menu-link.blade.php';
if (is_file($m)) {
    $s = file_get_contents($m);
    if (strpos($s, "'B2B Shipments'") !== false) {
        echo "already there: B2B Shipments menu\n";
    } else {
        $pairs = json_decode(base64_decode(getenv('PAIRS')), true);
        $bad = [];
        foreach ($pairs as $i => [$a, $b]) { if (substr_count($s, $a) !== 1) $bad[] = $i; }
        if ($bad) {
            fwrite(STDERR, "FAIL: seller menu on the server differs (parts " . implode(',', $bad) . ")\n"); exit(1);
        }
        foreach ($pairs as [$a, $b]) $s = str_replace($a, $b, $s);
        file_put_contents($m, $s);
        echo "patched: $m\n";
    }
}
// 2. B2C rate calculator: B2C | B2B switch at the top
$c = 'resources/views/sellerdashboard/tools/newratecalculater.blade.php';
if (is_file($c)) {
    $s = file_get_contents($c);
    $inc = "@includeIf('sellerdashboard.b2b._calc_switch', ['calc' => 'b2c'])";
    if (strpos($s, '_calc_switch') !== false) {
        echo "already there: rate calculator switch\n";
    } else {
        $a = "<div class=\"pc-content\">";
        $p = strpos($s, $a);
        if ($p !== false) {
            $e = strpos($s, "\n", $p) + 1;
            $s = substr($s, 0, $e) . "        <div style=\"margin:0 0 0 12px\">$inc</div>\n" . substr($s, $e);
        } elseif (($p = strpos($s, "@section('content')")) !== false) {
            $e = strpos($s, "\n", $p) + 1;
            $s = substr($s, 0, $e) . $inc . "\n" . substr($s, $e);
        } else {
            fwrite(STDERR, "FAIL: rate calculator layout not recognised\n"); exit(1);
        }
        file_put_contents($c, $s);
        echo "patched: $c\n";
    }
}
PHP

for f in app/Http/Controllers/selleradmin/B2bOrderController.php routes/b2b_web.php; do
  php -l "$f" >/dev/null 2>&1 || { echo "LINT FAIL: $f"; php -l "$f"; restore; }
done
echo "Lint OK"
chown -R www-data:www-data app routes resources 2>/dev/null
sudo -u www-data php artisan optimize:clear >/dev/null

OUT=$(sudo -u www-data php -r 'require "vendor/autoload.php"; $a = require "bootstrap/app.php"; $a->make(Illuminate\Contracts\Http\Kernel::class)->bootstrap(); $r = app("router")->getRoutes(); $r->refreshNameLookups(); foreach (["seller.b2b.rates", "seller.b2b.track", "seller.ratecards"] as $n) { echo "ROUTE $n " . ($r->getByName($n) ? "ok" : "missing") . PHP_EOL; } foreach (["sellerdashboard.b2b.rates", "sellerdashboard.b2b.track", "sellerdashboard.tools.newratecalculater", "partials.vas-menu-link"] as $v) { try { app("view")->getFinder()->find($v); app("blade.compiler")->compile(app("view")->getFinder()->find($v)); echo "VIEW $v ok" . PHP_EOL; } catch (Throwable $e) { echo "VIEW $v FAIL " . $e->getMessage() . PHP_EOL; } }' 2>&1)
echo "$OUT" | grep -E '^(ROUTE|VIEW)'
echo "$OUT" | grep -q 'ROUTE seller.b2b.rates ok' || { echo "FAIL: routes"; echo "$OUT" | tail -15; restore; }
echo "$OUT" | grep -q 'ROUTE seller.b2b.track ok' || restore
echo "$OUT" | grep -q 'FAIL' && restore
for u in /seller/b2b-orders/rate-calculator /seller/b2b-orders/track /rate-cards; do
  CODE=$(curl -s -o /dev/null -w '%{http_code}' -k "https://shipxpeed.tech$u"); echo "Page $u (logged out): $CODE"
  [ "$CODE" = "500" ] && restore
done
echo "B2B_SHIPMENTS_DONE backup=$BK"
