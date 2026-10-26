#!/usr/bin/env bash
# Fixes "419 Page Expired" on seller logout, on a server whose checkout has local edits.
# Patches only 2 files in place (idempotent). Takes a backup first and restores it
# automatically if either file fails to lint.
set -uo pipefail
cd /var/www/shipxpeed || exit 1
H=app/Exceptions/Handler.php
C=app/Http/Controllers/Frontend/SellerAuthController.php
BK=/root/pre-logout419-$(date +%Y%m%d-%H%M%S).tgz

tar czf "$BK" "$H" "$C" && echo "Backup: $BK" || { echo "FAIL: backup"; exit 1; }
restore() { echo "!! restoring backup"; tar xzf "$BK"; echo "RESTORED - nothing changed"; exit 1; }

php <<'PHP' || restore
<?php
function must($c, $m) { if (!$c) { fwrite(STDERR, "FAIL: $m\n"); exit(1); } }

// 1. Exception handler: expired CSRF token on seller logout -> finish logout, go to login
$h = 'app/Exceptions/Handler.php'; $s = file_get_contents($h);
if (!str_contains($s, "seller/seller-logout")) {
    $block = <<<'BLOCK'

        // Seller logout with an expired/stale CSRF token (page left open past the
        // session lifetime, or restored with the Back button): finish the logout
        // and go to the login page instead of showing "419 Page Expired".
        $this->renderable(function (\Throwable $e, $request) {
            $expired = $e instanceof \Illuminate\Session\TokenMismatchException
                || ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface && $e->getStatusCode() === 419);

            if ($expired && $request->is('seller/seller-logout')) {
                \Illuminate\Support\Facades\Auth::guard('seller')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('seller.login')->with('success', 'You have been logged out.');
            }
        });

BLOCK;
    $s = preg_replace_callback('~public function register\(\)\s*:\s*void\s*\{~', fn ($m) => $m[0] . $block, $s, 1, $n);
    must($n === 1, 'register() not found in Handler.php');
    file_put_contents($h, $s); echo "Handler patched\n";
} else echo "Handler already patched\n";

// 2. Normal logout: go to the login page instead of back to the dashboard
$c = 'app/Http/Controllers/Frontend/SellerAuthController.php'; $s = file_get_contents($c);
$pos = strpos($s, 'public function logout(');
if ($pos !== false) {
    $next = strpos($s, 'function ', $pos + 25); if ($next === false) $next = strlen($s);
    $body = substr($s, $pos, $next - $pos);
    if (str_contains($body, 'return redirect()->back();')) {
        $body = str_replace('return redirect()->back();', "return redirect()->route('seller.login')->with('success', 'You have been logged out.');", $body);
        file_put_contents($c, substr($s, 0, $pos) . $body . substr($s, $next)); echo "Logout redirect patched\n";
    } else echo "Logout redirect already changed (skipped)\n";
} else echo "logout() not found in controller (skipped)\n";
PHP

php -l "$H" >/dev/null && php -l "$C" >/dev/null || restore
echo "Lint OK"

# Reload PHP-FPM if present so OPcache picks up the change (graceful, no downtime)
for s in $(systemctl list-units --type=service --no-legend 'php*-fpm*' 2>/dev/null | awk '{print $1}'); do
    systemctl reload "$s" && echo "Reloaded $s"
done
echo "DONE - seller logout 419 fix is live"
