#!/usr/bin/env bash
# Puts SMS OTP (2Factor.in) live for seller sign-up and Mobile OTP login.
#  - backs up every file it touches, restores automatically on any failure
#  - new files + the two WhatsApp OTP service files are copied from origin/main;
#    controller and views get only the [sms-otp] changes as a 3-way patch
#  - writes TWOFACTOR_API_KEY and OTP_CHANNEL=sms to .env
# Usage:  TWOFACTOR_KEY=<2factor api key> bash deploy/sms-otp-live.sh
set -uo pipefail
APP=${APP:-/var/www/shipxpeed}
cd "$APP" || { echo "FAIL: $APP not found"; exit 1; }
REF=origin/main
STAMP=$(date +%Y%m%d-%H%M%S)
BK=/root/pre-sms-otp-$STAMP.tgz

NEW_FILES="
config/sms.php
app/Services/Sms/TwoFactorClient.php
app/Services/WhatsApp/WhatsAppSettings.php
app/Services/WhatsApp/WhatsAppOtpService.php
"
SHARED="
app/Http/Controllers/Frontend/SellerAuthController.php
resources/views/email/verify_otp.blade.php
resources/views/frontend/selleruser/login.blade.php
resources/views/frontend/selleruser/register.blade.php
"

[ -n "${TWOFACTOR_KEY:-}" ] || grep -q '^TWOFACTOR_API_KEY=.' .env || { echo "FAIL: pass TWOFACTOR_KEY=<2factor api key>"; exit 1; }
[ -f app/Services/WhatsApp/AiSensyClient.php ] || { echo "FAIL: mobile OTP base (WhatsApp OTP update) is not on this server yet"; exit 1; }

git fetch -q origin || { echo "FAIL: git fetch"; exit 1; }
COMMITS=$(git log "$REF" --reverse --no-merges --grep='\[sms-otp\]' --format=%H)
[ -n "$COMMITS" ] || { echo "FAIL: SMS OTP commits not found on $REF"; exit 1; }

EXIST=""; for f in $NEW_FILES $SHARED .env; do [ -e "$f" ] && EXIST="$EXIST $f"; done
tar czf "$BK" $EXIST && echo "Backup: $BK"
restore() {
  echo "!! restoring backup"; tar xzf "$BK"
  for f in $NEW_FILES; do grep -qxF "$f" <(tar tzf "$BK") || rm -f "$f"; done
  sudo -u www-data php artisan optimize:clear >/dev/null 2>&1
  echo "RESTORED - nothing changed"; exit 1
}

# 1. new / replaced files
for f in $NEW_FILES; do mkdir -p "$(dirname "$f")"; git show "$REF:$f" > "$f" || restore; done
echo "Copied service files"

# 2. shared files: skip ones already patched, 3-way patch the rest
TODO=""
for f in $SHARED; do
  if grep -qE 'otpChannelLabel|otpChannel\(\)|Mobile number</label>' "$f" 2>/dev/null; then echo "  already patched: $f"; else TODO="$TODO $f"; fi
done
if [ -n "$TODO" ]; then
  for c in $COMMITS; do
    for f in $(git diff --name-only "$c^" "$c" -- $TODO); do
      git show "$c^:$f" > /tmp/sms-base 2>/dev/null || : > /tmp/sms-base
      git show "$c:$f"  > /tmp/sms-theirs || restore
      if git merge-file -q "$f" /tmp/sms-base /tmp/sms-theirs; then echo "  merged: $f"; else echo "CONFLICT: $f"; restore; fi
    done
  done
  grep -l '^<<<<<<<' $TODO 2>/dev/null && { echo "FAIL: merge conflict markers"; restore; }
  echo "Patched controller and screens"
fi

# 3. lint
for f in $NEW_FILES $SHARED; do
  case "$f" in *.blade.php) ;; *.php) php -l "$f" >/dev/null 2>&1 || { echo "LINT FAIL: $f"; php -l "$f"; restore; } ;; esac
done
echo "Lint OK"

# 4. .env
setenv() { if grep -q "^$1=" .env; then sed -i "s|^$1=.*|$1=$2|" .env; else echo "$1=$2" >> .env; fi; }
[ -n "${TWOFACTOR_KEY:-}" ] && setenv TWOFACTOR_API_KEY "$TWOFACTOR_KEY"
setenv OTP_CHANNEL sms
echo "2Factor key + OTP_CHANNEL=sms written to .env"

chown -R www-data:www-data app config resources storage bootstrap/cache 2>/dev/null
sudo -u www-data php artisan optimize:clear >/dev/null
sudo -u www-data php artisan config:cache >/dev/null 2>&1 || true

# 5. check
CH=$(sudo -u www-data php artisan tinker --execute="echo \App\Services\WhatsApp\WhatsAppSettings::otpChannel() ?? 'none';" 2>/dev/null | tail -1)
echo "OTP channel now: $CH"
[ "$CH" = "sms" ] || { echo "FAIL: SMS channel not active"; restore; }
LOGIN=$(curl -s -k https://shipxpeed.tech/seller-login)
echo "$LOGIN" | grep -q 'Mobile OTP' && echo "Login page: Mobile OTP tab is showing" || echo "WARN: Mobile OTP tab not found on login page"
echo "DEPLOY_DONE backup=$BK"
