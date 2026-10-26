#!/usr/bin/env bash
# Puts the new admin UI live (dark theme, grouped sidebar, dashboard, seller list/edit,
# admin -> seller panel link) on a server whose checkout has local edits.
# Copies the admin UI files from origin/main and patches the 3 shared files in place.
# Takes a backup first and restores it automatically if any PHP file fails to lint.
set -uo pipefail
cd /var/www/shipxpeed || exit 1
REF=origin/main
STAMP=$(date +%Y%m%d-%H%M%S)
BK=/root/pre-admin-ui-$STAMP.tgz

COPY_FILES="
public/assets/css/admin-theme.css
resources/views/home.blade.php
resources/views/partial/sidebar.blade.php
resources/views/partial/common/header.blade.php
resources/views/layouts/app.blade.php
resources/views/sellerlist/index.blade.php
resources/views/sellerlist/edit.blade.php
app/Http/Controllers/admin/HomeController.php
app/Http/Controllers/admin/SellerListController.php
"
PATCHED="routes/admin.php routes/web.php resources/views/layouts/sellerdash.blade.php"

git fetch -q origin || { echo "FAIL: git fetch"; exit 1; }

EXIST=""; for f in $COPY_FILES $PATCHED; do [ -e "$f" ] && EXIST="$EXIST $f"; done
tar czf "$BK" $EXIST && echo "Backup: $BK"
restore() {
  echo "!! restoring backup"; tar xzf "$BK"
  for f in $COPY_FILES; do tar tzf "$BK" | grep -qx "$f" || rm -f "$f"; done
  php artisan view:clear >/dev/null 2>&1; php artisan route:clear >/dev/null 2>&1
  echo "RESTORED - nothing changed"; exit 1
}

for f in $COPY_FILES; do mkdir -p "$(dirname "$f")"; git show "$REF:$f" > "$f" || restore; done
echo "Copied admin UI files"

php <<'PHP' || restore
<?php
function patch($file, $find, $add, $marker) {
    $s = file_get_contents($file);
    if (strpos($s, $marker) !== false) { echo "already patched: $file\n"; return; }
    if (strpos($s, $find) === false) { fwrite(STDERR, "anchor not found in $file\n"); exit(1); }
    file_put_contents($file, str_replace($find, $add, $s));
    echo "patched: $file\n";
}

// 1) quick-toggle route for the seller list
$anchor = "        Route::get('seller-list/{id}', 'edit')";
patch('routes/admin.php', $anchor,
    "        Route::post('seller-list/{id}/toggle', 'toggle')->name('seller-list.toggle')->middleware('isAllow:104,can_edit');\n" . $anchor,
    "seller-list.toggle");

// 2) "Back to admin" goes to a real route and signs the seller session out
patch('routes/web.php',
    "        Auth::guard('web')->loginUsingId(\$adminId);\n        Session::forget('admin_impersonator_id');\n        return redirect()->route('admin.dashboard')->with('success', 'Returned to admin account.');",
    "        \$sellerId = Auth::guard('seller')->id();\n        Auth::guard('seller')->logout();\n        Auth::guard('web')->loginUsingId(\$adminId);\n        Session::forget('admin_impersonator_id');\n        return \$sellerId\n            ? redirect()->route('seller-list.edit', \$sellerId)->with('success', 'Back in admin.')\n            : redirect()->route('dashboard')->with('success', 'Back in admin.');",
    "Back in admin.");

// 3) admin-view banner in the seller panel (inserted right after the <body ...> tag, whatever its attributes)
$file = 'resources/views/layouts/sellerdash.blade.php';
$s = file_get_contents($file);
if (strpos($s, 'admin_impersonator_id') !== false) {
    echo "already patched: $file\n";
} elseif (!preg_match('/<body\b[^>]*>/i', $s, $m, PREG_OFFSET_CAPTURE)) {
    fwrite(STDERR, "no <body> tag in $file - skipping banner (everything else still goes live)\n");
} else {
    $banner = <<<'HTML'

    @if (session('admin_impersonator_id'))
        <div style="position:fixed;bottom:16px;left:50%;transform:translateX(-50%);z-index:3000;background:#0b1020;color:#e8edf7;border:1px solid #4f8cff;border-radius:999px;padding:8px 10px 8px 16px;display:flex;align-items:center;gap:12px;box-shadow:0 10px 30px rgba(0,0,0,.35);font-size:13.5px;max-width:calc(100% - 24px)">
            <span>Admin view: <strong>{{ Auth::guard('seller')->user()->name ?? 'Seller' }}</strong></span>
            <a href="{{ route('impersonate.leave') }}" style="background:#4f8cff;color:#fff;border-radius:999px;padding:5px 12px;text-decoration:none;font-weight:600;white-space:nowrap">Back to admin</a>
        </div>
    @endif
HTML;
    $pos = $m[0][1] + strlen($m[0][0]);
    file_put_contents($file, substr($s, 0, $pos) . $banner . substr($s, $pos));
    echo "patched: $file\n";
}
PHP

for f in app/Http/Controllers/admin/HomeController.php app/Http/Controllers/admin/SellerListController.php routes/admin.php routes/web.php; do
  php -l "$f" >/dev/null 2>&1 || { echo "LINT FAIL: $f"; restore; }
done

php artisan view:clear
php artisan route:clear
php artisan route:list --name=seller-list >/dev/null 2>&1 || { echo "route:list failed"; restore; }
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

echo "--- checks ---"
for u in /login /seller-login /assets/css/admin-theme.css; do
  printf '%-34s %s\n' "$u" "$(curl -s -o /dev/null -w '%{http_code}' "https://shipxpeed.tech$u")"
done
echo "DONE. Backup kept at $BK"
