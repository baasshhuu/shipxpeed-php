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

git config --global --get-all safe.directory 2>/dev/null | grep -qxF "$APP" || git config --global --add safe.directory "$APP"
git fetch -q origin || { echo "FAIL: git fetch (check the server can reach GitHub)"; exit 1; }
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

# Fallback when a screen differs on the server: change only the wording (SMS works either way)
cat > /tmp/sms-words.php <<'PHPEOF'
<?php
// Wording-only fallback. Single-quoted strings: nothing here is interpolated.
$f = $argv[1]; $s = file_get_contents($f); $o = $s;
$lbl = '\App\Services\WhatsApp\WhatsAppSettings::otpChannelLabel()';
$r = [
  'WhatsApp mobile number</label>' => 'Mobile number</label>',
  '>WhatsApp OTP</button>' => '>Mobile OTP</button>',
  'Code sent on WhatsApp</label>' => 'Code sent {{ $otpVerb }}</label>',
  '<span class="btn-text">Send code on WhatsApp</span>' => '<span class="btn-text">Send code {{ $otpVerb }}</span>',
  "'Send code on WhatsApp'" => "@json('Send code ' . \$otpVerb)",
  "'Code sent on WhatsApp.'" => "@json('Code sent ' . \$otpVerb . '.')",
  'and the WhatsApp code sent to' => 'and the {{ ' . $lbl . ' }} code sent to',
  '>WhatsApp code</span>' => '>{{ ' . $lbl . ' }} code</span>',
  "'Codes sent to your email and WhatsApp. Please verify to complete registration.'" => "'Codes sent to your email and ' . (WhatsAppSettings::otpChannel() === 'sms' ? 'mobile (SMS)' : 'WhatsApp') . '. Please verify to complete registration.'",
  "'WhatsApp login is not available right now. Please log in with email.'" => "'Mobile OTP login is not available right now. Please log in with email.'",
];
$s = strtr($s, $r);
// make sure $otpVerb exists wherever it is now used
if (strpos($s, '$otpVerb') !== false && strpos($s, '$otpVerb =') === false) {
  $def = "@php \$otpVerb = " . $lbl . " === 'SMS' ? 'by SMS' : 'on WhatsApp'; @endphp";
  $s = preg_replace('/\A(.*\n)/', '$1' . str_replace('$', '\$', $def) . "\n", $s, 1);
}
if ($s !== $o) { file_put_contents($f, $s); echo "  wording updated: $f\n"; } else { echo "  kept server version: $f\n"; }
PHPEOF

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
      cp "$f" /tmp/sms-ours.bak
      if git merge-file -q "$f" /tmp/sms-base /tmp/sms-theirs; then echo "  merged: $f"
      else cp /tmp/sms-ours.bak "$f"; php /tmp/sms-words.php "$f" || restore; fi
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

# 5. check (boots the app directly; no config cache, couriers keep reading their .env keys)
cat > /tmp/sms-check.php <<'PHPEOF'
<?php
require $argv[1].'/vendor/autoload.php';
$app = require $argv[1].'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
echo \App\Services\WhatsApp\WhatsAppSettings::otpChannel() ?? 'none';
PHPEOF
CH=$(sudo -u www-data php /tmp/sms-check.php "$APP" 2>/tmp/sms-check.err | tail -1)
echo "OTP channel now: $CH"
[ "$CH" = "sms" ] || { echo "FAIL: SMS channel not active"; tail -5 /tmp/sms-check.err; restore; }
CODE=$(curl -s -o /tmp/sms-login.html -w '%{http_code}' -k https://shipxpeed.tech/seller-login)
echo "Login page: HTTP $CODE"
case "$CODE" in 5*) echo "FAIL: login page error $CODE"; restore ;; 000) echo "WARN: could not reach the site from the server to double-check (deploy is done)" ;; esac
grep -q 'Mobile OTP' /tmp/sms-login.html && echo "Login page: Mobile OTP tab is showing" || echo "WARN: Mobile OTP tab text not found (SMS still works)"
echo "DEPLOY_DONE backup=$BK"
