#!/usr/bin/env bash
# Puts WhatsApp (AiSensy) OTP + buyer order alerts + NDR-by-WhatsApp live on a server whose checkout has local edits.
#  - backs up every file it touches, restores automatically on any failure
#  - new files are copied from origin/main; shared files get only the WhatsApp changes as a 3-way patch
#  - runs the WhatsApp migration, optionally stores the AiSensy API key in .env
# Usage:  AISENSY_KEY=<api key> bash deploy/whatsapp-live.sh      (AISENSY_KEY optional; can also be pasted on the admin page)
set -uo pipefail
APP=${APP:-/var/www/shipxpeed}
cd "$APP" || { echo "FAIL: $APP not found"; exit 1; }
REF=origin/main
STAMP=$(date +%Y%m%d-%H%M%S)
BK=/root/pre-whatsapp-$STAMP.tgz

NEW_FILES="
config/whatsapp.php
database/migrations/2026_10_27_000005_whatsapp_aisensy.php
app/Services/WhatsApp/AiSensyClient.php
app/Services/WhatsApp/WhatsAppSettings.php
app/Services/WhatsApp/WhatsAppOtpService.php
app/Services/WhatsApp/OrderStatusNotifier.php
app/Services/WhatsApp/NdrActionDispatcher.php
app/Observers/OrderObserver.php
app/Http/Controllers/admin/WhatsAppController.php
app/Http/Controllers/selleradmin/WhatsAppAlertsController.php
app/Http/Controllers/Frontend/NdrResponseController.php
resources/views/admin/whatsapp/index.blade.php
resources/views/sellerdashboard/whatsapp-alerts/index.blade.php
resources/views/frontend/ndr/respond.blade.php
"
SHARED="
app/Providers/AppServiceProvider.php
app/Http/Controllers/Frontend/SellerAuthController.php
app/Http/Controllers/Api/StatusController.php
app/Console/Commands/TrackDelhiveryOrders.php
app/Http/Controllers/admin/SendWhatsAppMessageController.php
routes/web.php
routes/admin.php
resources/views/email/verify_otp.blade.php
resources/views/frontend/selleruser/login.blade.php
resources/views/frontend/selleruser/register.blade.php
resources/views/partial/sidebar.blade.php
resources/views/partial/sellerdash/sidebar.blade.php
resources/views/sellerdashboard/shipping-notification/index.blade.php
"

git fetch -q origin || { echo "FAIL: git fetch"; exit 1; }
COMMITS=$(git log "$REF" --reverse --no-merges --grep='\[whatsapp\]' --format=%H)
[ -n "$COMMITS" ] || { echo "FAIL: WhatsApp commits not found on $REF"; exit 1; }

EXIST=""; for f in $NEW_FILES $SHARED .env; do [ -e "$f" ] && EXIST="$EXIST $f"; done
tar czf "$BK" $EXIST && echo "Backup: $BK"
restore() {
  echo "!! restoring backup"; tar xzf "$BK"
  for f in $NEW_FILES; do grep -qxF "$f" <(tar tzf "$BK") || rm -f "$f"; done
  echo "RESTORED - nothing changed"; exit 1
}

# 1. new files
for f in $NEW_FILES; do mkdir -p "$(dirname "$f")"; git show "$REF:$f" > "$f" || restore; done
echo "Copied new files"

# 2. shared files: skip ones already patched, 3-way patch the rest
TODO=""; SKIPPED_MENU=""
for f in $SHARED; do
  if grep -qiE 'WhatsAppSettings|whatsapp\.alerts|admin\.whatsapp|OrderObserver|ndr\.respond|WhatsApp buyer alerts have moved|mobile-otp-hidden|waLogin|WhatsApp mobile number' "$f" 2>/dev/null; then echo "  already patched: $f"; else TODO="$TODO $f"; fi
done
if [ -n "$TODO" ]; then
  # Per-file 3-way merge (works even when the checkout has uncommitted local edits / a stale index)
  for c in $COMMITS; do
    for f in $(git diff --name-only "$c^" "$c" -- $TODO); do
      git show "$c^:$f" > /tmp/wa-base 2>/dev/null || : > /tmp/wa-base
      git show "$c:$f"  > /tmp/wa-theirs || restore
      cp "$f" /tmp/wa-ours.bak
      if git merge-file -q "$f" /tmp/wa-base /tmp/wa-theirs; then
        echo "  merged: $f"
      else
        case "$f" in
          *sidebar.blade.php)
            # menu link only: keep the server's menu, pages still open by URL
            cp /tmp/wa-ours.bak "$f"; echo "  WARN: kept server version of $f (menu link not added)"; SKIPPED_MENU="$SKIPPED_MENU $f" ;;
          *)
            echo "CONFLICT: $f"; restore ;;
        esac
      fi
    done
  done
  grep -l '^<<<<<<<' $TODO 2>/dev/null && { echo "FAIL: merge conflict markers"; restore; }
  echo "Patched shared files"
fi

# 3. lint
for f in $NEW_FILES $SHARED; do
  case "$f" in *.blade.php) ;; *.php) php -l "$f" >/dev/null 2>&1 || { echo "LINT FAIL: $f"; php -l "$f"; restore; } ;; esac
done
echo "Lint OK"

# 4. API key (optional)
if [ -n "${AISENSY_KEY:-}" ]; then
  if grep -q '^AISENSY_API_KEY=' .env; then sed -i "s|^AISENSY_API_KEY=.*|AISENSY_API_KEY=$AISENSY_KEY|" .env; else echo "AISENSY_API_KEY=$AISENSY_KEY" >> .env; fi
  echo "Key written to .env"
fi
grep -q '^WHATSAPP_PUBLIC_URL=' .env || echo 'WHATSAPP_PUBLIC_URL=https://shipxpeed.tech' >> .env

chown -R www-data:www-data app config routes resources database storage bootstrap/cache 2>/dev/null
sudo -u www-data php artisan migrate --path=database/migrations/2026_10_27_000005_whatsapp_aisensy.php --force || restore
sudo -u www-data php artisan optimize:clear >/dev/null
sudo -u www-data php artisan route:list --name=whatsapp 2>&1 | tail -8
echo "Login page: $(curl -s -o /dev/null -w '%{http_code}' -k https://shipxpeed.tech/seller-login)"
[ -n "$SKIPPED_MENU" ] && echo "NOTE: menu links not added in:$SKIPPED_MENU (open /whatsapp and /seller/whatsapp-alerts directly)"
echo "DEPLOY_DONE backup=$BK"
