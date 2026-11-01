#!/usr/bin/env bash
# Seller login: adds the "Email OTP" tab (email -> 6-digit code by email -> logged in). Re-runnable.
# Copies 4 new files; replaces the login page only if the server copy is the known git version
# (otherwise stops and leaves it untouched). Backs up and restores on any failure.
# Usage:  cd /var/www/shipxpeed && git fetch origin && git show origin/main:deploy/email-otp-login.sh | bash
set -uo pipefail
cd /var/www/shipxpeed || exit 1
REF=origin/main
KNOWN=a43745b9ae1184b993e7c033bf1b8ec7a6479cda   # login page version this change was built on
BK=/root/pre-email-otp-$(date +%Y%m%d-%H%M%S).tgz
NEW="app/Support/Xpeed/EmailLoginOtp.php app/Http/Controllers/Xpeed/EmailOtpLoginController.php resources/views/email/xp-login-otp.blade.php routes/xpeed.php"
LOGIN=resources/views/frontend/selleruser/login.blade.php
git fetch -q origin || { echo "FAIL: git fetch"; exit 1; }
EXIST=""; for f in $NEW $LOGIN; do [ -e "$f" ] && EXIST="$EXIST $f"; done
tar czf "$BK" $EXIST; echo "Backup: $BK"
restore() { echo "!! restoring"; tar xzf "$BK"; for f in $NEW; do tar tzf "$BK" | grep -qxF "$f" || rm -f "$f"; done; sudo -u www-data php artisan optimize:clear >/dev/null 2>&1; echo "RESTORED - nothing changed"; exit 1; }

if git show "$REF:$LOGIN" | cmp -s - "$LOGIN"; then echo "Login page already up to date"
elif git show "$KNOWN:$LOGIN" | cmp -s - "$LOGIN"; then echo "Login page matches the known version - updating"
else echo "FAIL: the server login page has local changes; not touching it. Differences:"; git show "$KNOWN:$LOGIN" | diff - "$LOGIN" | head -40; exit 1; fi

for f in $NEW $LOGIN; do mkdir -p "$(dirname "$f")"; git show "$REF:$f" > "$f" || restore; done
for f in app/Support/Xpeed/EmailLoginOtp.php app/Http/Controllers/Xpeed/EmailOtpLoginController.php routes/xpeed.php; do php -l "$f" >/dev/null || restore; done
mkdir -p storage/framework/cache/data
chown -R www-data:www-data app routes resources storage 2>/dev/null
sudo -u www-data php artisan optimize:clear >/dev/null || restore

cat > /tmp/emotp-check.php <<'PHPEOF'
<?php
chdir($argv[1]); require 'vendor/autoload.php'; $app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
echo Illuminate\Support\Facades\Route::has('seller.login.emailotp.send') ? "ROUTE_OK\n" : "ROUTE_MISSING\n";
Illuminate\Support\Facades\Cache::store('file')->put('xp_emotp_probe', 'ok', 60);
echo Illuminate\Support\Facades\Cache::store('file')->get('xp_emotp_probe') === 'ok' ? "CACHE_OK\n" : "CACHE_FAIL\n";
echo "MAILER ", config('mail.default'), " FROM ", config('mail.from.address'), "\n";
$r = $app->make(Illuminate\Contracts\Http\Kernel::class)->handle(Illuminate\Http\Request::create('https://shipxpeed.tech/seller-login'));
$b = (string) $r->getContent();
echo "LOGIN_PAGE ", $r->getStatusCode(), str_contains($b, 'id="emForm"') ? " HAS_EMAIL_OTP_TAB" : " NO_TAB", "\n";
PHPEOF
R=$(sudo -u www-data env HOME=/tmp php /tmp/emotp-check.php /var/www/shipxpeed 2>&1 | tail -6); echo "$R"
echo "$R" | grep -q ROUTE_OK && echo "$R" | grep -q CACHE_OK && echo "$R" | grep -q "LOGIN_PAGE 200 HAS_EMAIL_OTP_TAB" || restore
echo "DEPLOY_DONE backup=$BK"
