#!/usr/bin/env bash
# Puts the rebuilt Admin › Shipment Report live (page + Excel export with every field).
#  - backs up every file it touches and restores automatically on any failure
#  - report files are copied from origin/main; in HomeController only the shipment_report()
#    method is swapped (matched by name), so other local edits in that file stay as they are
# Usage:  bash deploy/shipment-report.sh
set -uo pipefail
APP=${APP:-/var/www/shipxpeed}
cd "$APP" || { echo "FAIL: $APP not found"; exit 1; }
REF=origin/main
BK=/root/pre-shipment-report-$(date +%Y%m%d-%H%M%S).tgz
NEW="app/Support/ShipmentReport.php app/Exports/AllShipmentReportExport.php resources/views/shipment_report.blade.php"
CTRL=app/Http/Controllers/admin/HomeController.php

git fetch -q origin || { echo "FAIL: git fetch"; exit 1; }
[ -f app/Services/WhatsApp/OrderStatusNotifier.php ] || { echo "FAIL: run deploy/whatsapp-live.sh first (report uses its status mapping)"; exit 1; }
EXIST=""; for f in $NEW $CTRL; do [ -e "$f" ] && EXIST="$EXIST $f"; done
tar czf "$BK" $EXIST && echo "Backup: $BK"
restore() { echo "!! restoring backup"; tar xzf "$BK"; [ -n "$(tar tzf "$BK" | grep -x app/Support/ShipmentReport.php)" ] || rm -f app/Support/ShipmentReport.php; echo "RESTORED - nothing changed"; exit 1; }

for f in $NEW; do mkdir -p "$(dirname "$f")"; git show "$REF:$f" > "$f" || restore; done
echo "Copied report files"

git show "$REF:$CTRL" > /tmp/sr-home-new.php || restore
php -r '
  function method($src, $name) {
    $p = strpos($src, "function $name(");
    if ($p === false) return null;
    $start = strrpos(substr($src, 0, $p), "public");
    $b = strpos($src, "{", $p); $d = 0;
    for ($i = $b; $i < strlen($src); $i++) {
      if ($src[$i] === "{") $d++;
      elseif ($src[$i] === "}") { $d--; if ($d === 0) return [$start, $i + 1]; }
    }
    return null;
  }
  [$cur, $new] = [file_get_contents($argv[1]), file_get_contents($argv[2])];
  $n = method($new, "shipment_report"); $c = method($cur, "shipment_report");
  if (!$n || !$c) { fwrite(STDERR, "FAIL: shipment_report() not found\n"); exit(1); }
  $body = substr($new, $n[0], $n[1] - $n[0]);
  if (substr($cur, $c[0], $c[1] - $c[0]) === $body) { echo "  HomeController already up to date\n"; exit(0); }
  file_put_contents($argv[1], substr($cur, 0, $c[0]) . $body . substr($cur, $c[1]));
  echo "  HomeController: shipment_report() replaced\n";
' "$CTRL" /tmp/sr-home-new.php || restore

for f in $NEW $CTRL; do case "$f" in *.blade.php) ;; *) php -l "$f" >/dev/null 2>&1 || { php -l "$f"; restore; } ;; esac; done
echo "Lint OK"

chown -R www-data:www-data app resources storage bootstrap/cache 2>/dev/null
sudo -u www-data php artisan optimize:clear >/dev/null
echo "Report page: $(curl -s -o /dev/null -w '%{http_code}' -k https://shipxpeed.tech/shipment-report) (302 = login redirect, fine)"
echo "DEPLOY_DONE backup=$BK"
