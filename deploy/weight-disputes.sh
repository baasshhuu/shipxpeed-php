#!/usr/bin/env bash
# Puts the working weight dispute flow live (re-runnable):
#  Admin > Weight Disputes
#   - download Excel template (AWB, charged weight kg, Dead / Volumetric, optional courier L×B×H, manual ₹, note)
#   - upload it -> seller + order found from AWB, excess calculated from the seller's own rate card
#     (extra 500 g slabs + 18% GST) and debited from the seller wallet immediately; same AWB never charged twice
#   - review seller disputes with their photos / videos -> Accept (auto refund to wallet) or Reject (closed)
#  Seller > Weight Discrepancy
#   - every charge with full weight details + timeline
#   - "Raise dispute" only within 48 hours of the charge (WEIGHT_DISPUTE_WINDOW_HOURS in .env), up to 5 photos/videos, 10 MB each
#  Raises PHP / nginx upload limits so 10 MB videos go through.
# Backs up every touched file first and restores automatically if any check fails.
# Usage:  git fetch origin && git show origin/main:deploy/weight-disputes.sh | bash
set -uo pipefail
cd /var/www/shipxpeed || exit 1
REF=origin/main
STAMP=$(date +%Y%m%d-%H%M%S)
BK=/root/pre-weight-disputes-$STAMP.tgz

COPY_FILES="
app/Models/WeightDisputes.php
app/Support/WeightDisputeService.php
app/Exports/WeightDisputeTemplateExport.php
app/Exports/WeightDiscrepancyExport.php
app/Http/Controllers/admin/WeightDispatchingController.php
app/Http/Controllers/selleradmin/WeightDispatchingController.php
resources/views/WeightDispatching/index.blade.php
resources/views/sellerdashboard/WeightDispatching/index.blade.php
database/migrations/2026_10_27_000005_weight_dispute_workflow.php
"
PATCHED="routes/admin.php routes/web.php public/.user.ini public/.htaccess"

git fetch -q origin || { echo "FAIL: git fetch"; exit 1; }

EXIST=""; for f in $COPY_FILES $PATCHED; do [ -e "$f" ] && EXIST="$EXIST $f"; done
tar czf "$BK" $EXIST && echo "Backup: $BK"
restore() {
  echo "!! restoring backup"; tar xzf "$BK"
  for f in $COPY_FILES public/.user.ini; do tar tzf "$BK" | grep -qx "$f" || rm -f "$f"; done
  php artisan config:clear >/dev/null 2>&1; php artisan view:clear >/dev/null 2>&1; php artisan route:clear >/dev/null 2>&1
  echo "RESTORED - code is back as it was (the extra weight_disputes columns, if added, are harmless)"; exit 1
}

for f in $COPY_FILES; do mkdir -p "$(dirname "$f")"; git show "$REF:$f" > "$f" || restore; done
echo "Copied files"

php <<'PHP' || restore
<?php
function addAfter($file, $anchorRegex, $marker, $lines) {
    $s = file_get_contents($file);
    if (strpos($s, $marker) !== false) { echo "already patched: $file\n"; return; }
    if (!preg_match($anchorRegex, $s, $m, PREG_OFFSET_CAPTURE)) { fwrite(STDERR, "anchor not found in $file\n"); exit(1); }
    $pos = $m[0][1] + strlen($m[0][0]);
    $s = substr($s, 0, $pos) . "\n" . $lines . substr($s, $pos);
    file_put_contents($file, $s); echo "patched: $file\n";
}
addAfter('routes/admin.php',
    "/^.*->name\('weight\.dispatching\.download'\).*$/m",
    "weight.dispute.review",
    "        Route::get('weight-dispute-template', 'template')->name('weight.dispute.template')->middleware('isAllow:104,can_view');\n" .
    "        Route::post('weight-dispute/{id}/review', 'review')->name('weight.dispute.review')->middleware('isAllow:104,can_view');\n" .
    "        Route::get('weight-dispute/{id}/proof/{n}', 'proof')->name('weight.dispute.proof')->middleware('isAllow:104,can_view');");
addAfter('routes/web.php',
    "/^.*->name\('seller\.weight\.discrepancy\.submit'\).*$/m",
    "seller.weight.dispute.raise",
    "    Route::post('/weight/discrepancy/{id}/raise', [WeightDispatchingController::class, 'raise'])->name('seller.weight.dispute.raise');\n" .
    "    Route::get('/weight/discrepancy/{id}/proof/{n}', [WeightDispatchingController::class, 'proof'])->name('seller.weight.dispute.proof');");
PHP

for f in $COPY_FILES routes/admin.php routes/web.php; do
  case "$f" in *.php) php -l "$f" >/dev/null 2>&1 || { echo "LINT FAIL: $f"; restore; } ;; esac
done

php artisan migrate --force --path=database/migrations/2026_10_27_000005_weight_dispute_workflow.php || restore
mkdir -p storage/app/weight-disputes
php artisan config:clear >/dev/null 2>&1
php artisan view:clear >/dev/null
php artisan route:clear >/dev/null

# --- upload limits: 5 files x 10 MB per dispute ---
# PHP-FPM reads public/.user.ini; mod_php reads php_value in public/.htaccess
touch public/.user.ini
for kv in "upload_max_filesize=12M" "post_max_size=64M" "max_file_uploads=20"; do
  k=${kv%%=*}
  grep -q "^$k" public/.user.ini && sed -i "s/^$k.*/$kv/" public/.user.ini || echo "$kv" >> public/.user.ini
done
echo "PHP limits set in public/.user.ini"
if [ -f public/.htaccess ] && ! grep -q "shipxpeed-upload-limits" public/.htaccess; then
  printf '\n# shipxpeed-upload-limits\n<IfModule mod_php.c>\n    php_value upload_max_filesize 12M\n    php_value post_max_size 64M\n</IfModule>\n<IfModule mod_php7.c>\n    php_value upload_max_filesize 12M\n    php_value post_max_size 64M\n</IfModule>\n' >> public/.htaccess
fi
if command -v nginx >/dev/null 2>&1; then
  NGBK=/root/pre-weight-disputes-nginx-$STAMP.tgz
  tar czf "$NGBK" /etc/nginx 2>/dev/null
  SMALLRE='client_max_body_size[[:space:]]+([1-9]|[1-5][0-9]|6[0-3])[mM][[:space:]]*;'
  SMALL=$(grep -rlE "$SMALLRE" /etc/nginx 2>/dev/null || true)
  for f in $SMALL; do sed -i -E "s/$SMALLRE/client_max_body_size 64m;/" "$f"; echo "nginx limit raised in $f"; done
  if ! grep -rqE "client_max_body_size" /etc/nginx 2>/dev/null; then
    echo "client_max_body_size 64m;" > /etc/nginx/conf.d/shipxpeed-uploads.conf; echo "nginx limit added"
  fi
  if nginx -t >/dev/null 2>&1; then systemctl reload nginx 2>/dev/null || nginx -s reload; echo "nginx reloaded"
  else echo "nginx test failed - putting nginx config back"; tar xzf "$NGBK" -C / 2>/dev/null; rm -f /etc/nginx/conf.d/shipxpeed-uploads.conf; nginx -t >/dev/null 2>&1 && (systemctl reload nginx 2>/dev/null || nginx -s reload); fi
fi
for svc in $(systemctl list-units --type=service --no-legend 2>/dev/null | awk '{print $1}' | grep -E '^php.*fpm'); do systemctl reload "$svc" 2>/dev/null && echo "reloaded $svc"; done

# Open the admin and seller pages inside the app and check they load
OUT=$(php artisan tinker --execute='
$k = app(Illuminate\Contracts\Http\Kernel::class);
$chk = function ($label, $url) use ($k) {
  $r = $k->handle(Illuminate\Http\Request::create($url));
  $c = $r->getStatusCode();
  echo "CHK " . $label . " " . $c . ($c >= 500 ? " ERR " . substr(strip_tags((string) $r->getContent()), 0, 300) : "") . PHP_EOL;
};
Auth::guard("web")->setUser(App\Models\User::first());
$chk("admin-weight", route("weight.dispatching.index"));
$chk("admin-template", route("weight.dispute.template"));
$s = App\Models\SellerList::first();
if ($s) { Auth::guard("seller")->setUser($s); $chk("seller-weight", route("seller.weight.discrepancy")); }
echo "COLS " . (Schema::hasColumn("weight_disputes", "dispute_status") && Schema::hasColumn("weight_disputes", "seller_proofs") ? "ok" : "missing") . PHP_EOL;
' 2>&1 | grep -E "^(CHK|COLS)|Error|Exception" )
echo "$OUT"
echo "$OUT" | grep -q "^COLS ok" || { echo "weight_disputes columns missing"; restore; }
echo "$OUT" | grep -qE "^CHK admin-weight (200|302)" || { echo "admin weight page check failed"; restore; }
echo "$OUT" | grep -qE "^CHK .* 5[0-9][0-9]" && { echo "a page returned a server error"; restore; }

chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
echo "DONE. Backup kept at $BK"
echo "Dispute window: ${WEIGHT_DISPUTE_WINDOW_HOURS:-48} hours (set WEIGHT_DISPUTE_WINDOW_HOURS in .env to change, then php artisan config:clear)"
