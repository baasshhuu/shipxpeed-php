#!/usr/bin/env bash
# Puts live: new shipping labels (AWB barcode, phone download fix, label config page),
# clear courier failure reasons, working bulk cancel, couriers:health check, Xpeed AI assistant.
# Shared files (routes, layout, config) are PATCHED in place, never replaced, so other updates stay intact.
# Backs up every touched file first and restores automatically if any check fails. Re-runnable.
# Usage:  cd /var/www/shipxpeed && git fetch origin && git show origin/main:deploy/labels-ai.sh | bash
set -uo pipefail
cd /var/www/shipxpeed || exit 1
REF=77b0a6bef0e0
STAMP=$(date +%Y%m%d-%H%M%S)
BK=/root/pre-labels-ai-$STAMP.tgz

COPY_FILES="
app/Console/Commands/CourierHealth.php
app/Http/Controllers/Api/CourierController.php
app/Http/Controllers/selleradmin/AssistantController.php
app/Http/Controllers/selleradmin/Dashboard.php
app/Http/Controllers/selleradmin/LebalController.php
app/Http/Controllers/selleradmin/ShipmentController.php
app/Models/LabelSetting.php
app/Services/DelhiveryB2CService.php
app/Services/DelhiveryB2CServiceExpress.php
app/Services/DelhiveryZapdealService.php
app/Support/CourierError.php
app/Support/Label/Code128.php
app/Support/Label/LabelRenderer.php
resources/label/watermark.png
database/migrations/2026_11_01_000001_label_settings_config.php
database/migrations/2026_11_01_000002_courier_booking_failures.php
resources/views/partial/sellerdash/assistant.blade.php
resources/views/sellerdashboard/custom-label.blade.php
resources/views/sellerdashboard/order.blade.php
resources/views/sellerdashboard/order_assigned.blade.php
"
PATCHED="routes/web.php routes/api.php resources/views/layouts/sellerdash.blade.php config/services.php"

git fetch -q origin || { echo "FAIL: git fetch"; exit 1; }
git cat-file -e "$REF^{commit}" 2>/dev/null || { echo "FAIL: commit $REF not found"; exit 1; }

EXIST=""; for f in $COPY_FILES $PATCHED; do [ -e "$f" ] && EXIST="$EXIST $f"; done
tar czf "$BK" $EXIST && echo "Backup: $BK"
restore() {
  echo "!! restoring backup"; tar xzf "$BK"
  for f in $COPY_FILES; do tar tzf "$BK" | grep -qx "$f" || rm -f "$f"; done
  php artisan optimize:clear >/dev/null 2>&1; echo "RESTORED - nothing changed"; exit 1
}

for f in $COPY_FILES; do
  mkdir -p "$(dirname "$f")"
  git show "$REF:$f" > "$f.new" && mv "$f.new" "$f" || { echo "FAIL: copy $f"; rm -f "$f.new"; restore; }
done

php <<'PHP' || restore
<?php
function patch($file, $cb) { $s = file_get_contents($file); $n = $cb($s); if ($n === null) { fwrite(STDERR, "FAIL: patch $file\n"); exit(1); } if ($n !== $s) file_put_contents($file, $n); echo "patched $file\n"; }

patch('routes/web.php', function ($s) {
    if (strpos($s, 'seller.assistant.chat') !== false) return $s;
    $anchor = "->name('seller.custom-label.store');";
    $p = strpos($s, $anchor); if ($p === false) return null;
    $p += strlen($anchor);
    $add = "\nRoute::get('/custom-label/preview', [\\App\\Http\\Controllers\\selleradmin\\Dashboard::class, 'previewLabel'])->name('seller.custom-label.preview');"
         . "\n// Xpeed Assistant (AI helper)"
         . "\nRoute::post('/assistant/chat', [\\App\\Http\\Controllers\\selleradmin\\AssistantController::class, 'chat'])->middleware('throttle:30,1')->name('seller.assistant.chat');"
         . "\nRoute::post('/assistant/confirm', [\\App\\Http\\Controllers\\selleradmin\\AssistantController::class, 'confirm'])->middleware('throttle:20,1')->name('seller.assistant.confirm');"
         . "\nRoute::post('/assistant/reset', [\\App\\Http\\Controllers\\selleradmin\\AssistantController::class, 'reset'])->name('seller.assistant.reset');";
    return substr($s, 0, $p) . $add . substr($s, $p);
});

patch('routes/api.php', function ($s) {
    $old = "Route::post('/api/shipments/create', [ShipmentController::class, 'createShipmentweb'])->name('api.shipments.create');";
    if (strpos($s, $old) === false) return $s; // already patched or changed
    return str_replace($old, "// Needs the seller's API token (was open to any seller id)\nRoute::post('/api/shipments/create', [ShipmentController::class, 'createShipmentweb'])->middleware(\\App\\Http\\Middleware\\SellerApiToken::class)->name('api.shipments.create');", $s);
});

patch('resources/views/layouts/sellerdash.blade.php', function ($s) {
    if (strpos($s, "partial.sellerdash.assistant") !== false) return $s;
    $p = strrpos($s, '</body>'); if ($p === false) return null;
    return substr($s, 0, $p) . "    @if(auth()->guard('seller')->check())\n        @include('partial.sellerdash.assistant')\n    @endif\n" . substr($s, $p);
});

patch('config/services.php', function ($s) {
    if (strpos($s, "'anthropic'") !== false) return $s;
    $p = strrpos($s, '];'); if ($p === false) return null;
    return substr($s, 0, $p) . "    'anthropic' => [\n        'key' => env('ANTHROPIC_API_KEY'),\n    ],\n\n" . substr($s, $p);
});
PHP

for f in $COPY_FILES $PATCHED; do case "$f" in *.blade.php) ;; *.php) php -l "$f" >/dev/null || { echo "FAIL lint $f"; restore; } ;; esac; done

php artisan optimize:clear >/dev/null 2>&1
php artisan route:list >/tmp/routes.txt 2>&1 || { tail -5 /tmp/routes.txt; echo "FAIL: routes"; restore; }
for r in seller.assistant.chat seller.custom-label.preview seller.order.custom-label.download seller.order.bulk-label-download; do
  grep -q "$r" /tmp/routes.txt || { echo "FAIL: route $r missing"; restore; }
done
php artisan migrate --force || { echo "FAIL: migrate"; restore; }
php artisan view:cache >/dev/null 2>&1 || { php artisan view:cache 2>&1 | tail -5; echo "FAIL: blade compile"; restore; }
php -r 'require "vendor/autoload.php"; $a=require "bootstrap/app.php"; $a->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); $h=App\Support\Label\LabelRenderer::render([App\Support\Label\LabelRenderer::sample()], App\Support\Label\LabelRenderer::config(null)); $p=Barryvdh\DomPDF\Facade\Pdf::loadHTML($h)->setPaper([0,0,288,432])->output(); exit(strlen($p)>3000?0:1);' \
  || { echo "FAIL: label PDF test"; restore; }
chown -R www-data:www-data storage bootstrap/cache public/seller-logos 2>/dev/null

echo "OK - labels, failure reasons, bulk cancel and assistant are live."
grep -q "^ANTHROPIC_API_KEY=." .env || echo "NOTE: add ANTHROPIC_API_KEY=... to .env to switch on the AI assistant"
echo "---- Courier check ----"; php artisan couriers:health --orders=3 2>&1 | tail -60
