#!/usr/bin/env bash
# Fixes "419 Page Expired" on admin/seller login (stale page / expired CSRF token):
# instead of the 419 page, the user is sent back to the form with a fresh token and
# "Session expired, please try again". Also repairs session folder permissions.
# Backs up first, restores automatically if lint fails.
set -uo pipefail
cd /var/www/shipxpeed || exit 1
H=app/Exceptions/Handler.php
BK=/root/pre-login419-$(date +%Y%m%d-%H%M%S).tgz
tar czf "$BK" "$H" && echo "Backup: $BK"
restore() { echo "!! restoring"; tar xzf "$BK"; echo "RESTORED - nothing changed"; exit 1; }

php <<'PHP' || restore
<?php
$h = 'app/Exceptions/Handler.php'; $s = file_get_contents($h);
if (str_contains($s, 'Generic expired-token fallback')) { echo "OK   handler already patched\n"; exit(0); }
$block = <<<'BLOCK'
        // Generic expired-token fallback (login and other forms left open too long):
        // go back to the form with a fresh token instead of "419 Page Expired".
        $this->renderable(function (\Throwable $e, $request) {
            $expired = $e instanceof \Illuminate\Session\TokenMismatchException
                || ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface && $e->getStatusCode() === 419);
            if (!$expired || $request->is('api/*') || $request->expectsJson()) {
                return null;
            }
            $request->session()->regenerateToken();
            $back = url()->previous();
            if (!$back || $back === $request->fullUrl()) {
                $back = $request->is('admin/*') || $request->is('admin') ? url('admin/login') : url('login');
            }
            return redirect()->to($back)
                ->withInput($request->except(['password', 'password_confirmation', '_token']))
                ->with('error', 'Your session expired. Please try again.')
                ->withErrors(['email' => 'Your session expired. Please try again.']);
        });

BLOCK;
$anchor = '$this->renderable(function (MethodNotAllowedHttpException';
$p = strpos($s, $anchor);
if ($p === false) { $p = strpos($s, 'public function register(): void'); if ($p === false) { fwrite(STDERR, "FAIL: anchor\n"); exit(1); } $p = strpos($s, "{", $p) + 1; $s = substr($s, 0, $p) . "\n" . $block . substr($s, $p); }
else { $ls = strrpos(substr($s, 0, $p), "\n") + 1; $s = substr($s, 0, $ls) . $block . substr($s, $ls); }
file_put_contents($h, $s); echo "OK   handler patched\n";
PHP
php -l "$H" >/dev/null || { php -l "$H"; restore; }

# Session storage must be writable by the web server, or every login 419s
mkdir -p storage/framework/sessions && chown -R www-data:www-data storage bootstrap/cache && chmod -R ug+rwX storage bootstrap/cache
sudo -u www-data php artisan optimize:clear >/dev/null
echo "Session settings:"; grep -E '^(SESSION_DRIVER|SESSION_DOMAIN|SESSION_SECURE_COOKIE|SESSION_LIFETIME|APP_URL)=' .env
c=$(curl -s -o /dev/null -w '%{http_code}' -k https://shipxpeed.tech/admin/login); echo "admin/login -> $c"; [ "$c" = 500 ] && restore
echo "LOGIN419_DONE"
