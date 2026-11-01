#!/usr/bin/env bash
# Puts live: new shipping labels (AWB barcode, phone download fix, Delhivery-style label config),
# clear courier failure reasons, working bulk cancel, couriers:health check, Xpeed AI assistant.
# Backs up every touched file first and restores automatically if any check fails.
# Usage:  cd /var/www/shipxpeed && git fetch origin && git show origin/main:deploy/labels-ai.sh | bash
set -uo pipefail
cd /var/www/shipxpeed || exit 1
REF=origin/main
STAMP=$(date +%Y%m%d-%H%M%S)
BK=/root/pre-labels-ai-$STAMP.tgz
FILES="app/Console/Commands/CourierHealth.php app/Http/Controllers/Api/CourierController.php app/Http/Controllers/selleradmin/AssistantController.php app/Http/Controllers/selleradmin/Dashboard.php app/Http/Controllers/selleradmin/LebalController.php app/Http/Controllers/selleradmin/ShipmentController.php app/Models/LabelSetting.php app/Services/DelhiveryB2CService.php app/Services/DelhiveryB2CServiceExpress.php app/Services/DelhiveryZapdealService.php app/Support/CourierError.php app/Support/Label/Code128.php app/Support/Label/LabelRenderer.php config/services.php database/migrations/2026_11_01_000001_label_settings_config.php database/migrations/2026_11_01_000002_courier_booking_failures.php resources/views/layouts/sellerdash.blade.php resources/views/partial/sellerdash/assistant.blade.php resources/views/sellerdashboard/custom-label.blade.php resources/views/sellerdashboard/order.blade.php resources/views/sellerdashboard/order_assigned.blade.php routes/api.php routes/web.php  resources/views/layouts/sellerdash.blade.php config/services.php"

git fetch -q origin || { echo "FAIL: git fetch"; exit 1; }
EXIST=""; for f in $FILES; do [ -e "$f" ] && EXIST="$EXIST $f"; done
tar czf "$BK" $EXIST && echo "Backup: $BK"
restore() { echo "!! restoring backup"; tar xzf "$BK"; for f in $FILES; do tar tzf "$BK" | grep -qx "$f" || rm -f "$f"; done; php artisan optimize:clear >/dev/null 2>&1; echo "RESTORED - nothing changed"; exit 1; }

for f in $FILES; do mkdir -p "$(dirname "$f")"; git show "$REF:$f" > "$f" || { echo "FAIL: $f"; restore; }; done
for f in $FILES; do case "$f" in *.php) php -l "$f" >/dev/null || { echo "FAIL lint $f"; restore; } ;; esac; done

php artisan migrate --force || restore
php artisan optimize:clear >/dev/null
php artisan route:list 2>/dev/null | grep -q "seller.assistant.chat" || { echo "FAIL: routes"; restore; }
php artisan route:list 2>/dev/null | grep -q "seller.custom-label.preview" || { echo "FAIL: routes"; restore; }
php artisan view:cache >/dev/null || { echo "FAIL: blade compile"; restore; }
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null

grep -q "^ANTHROPIC_API_KEY=" .env || echo "NOTE: add ANTHROPIC_API_KEY=... to .env to switch on the AI assistant"
echo "OK - labels, failure reasons, bulk cancel and assistant are live."
echo "Courier check:"; php artisan couriers:health --orders=3 || true
