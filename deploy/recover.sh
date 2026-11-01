#!/usr/bin/env bash
# Emergency recovery after files went missing on the server.
# For each core file that is missing or broken, restores the newest good copy from the
# server's own installer backups (/root/pre-*.tgz = the server's latest version), else
# from origin/main. Then re-adds the Notify / Value services routes and sidebar hook,
# restores any other code file that is missing, clears caches and checks the site.
# Never overwrites a file that is already fine. Keeps a copy of everything it replaces.
set -uo pipefail
APP=${APP:-/var/www/shipxpeed}
cd "$APP" || { echo "FAIL: $APP not found"; exit 1; }
REF=origin/main
STAMP=$(date +%Y%m%d-%H%M%S)
KEEP=/root/recover-$STAMP
mkdir -p "$KEEP"
git fetch -q origin || echo "warn: git fetch failed, using local origin/main"

CORE="routes/web.php routes/admin.php routes/api.php routes/console.php routes/auth.php resources/views/layouts/sellerdash.blade.php"
TMP=$(mktemp -d)

good() { # file -> 0 if it looks complete
  local f=$1
  [ -s "$f" ] || return 1
  case "$f" in
    *.blade.php) [ "$(wc -l < "$f")" -gt 50 ] ;;
    routes/web.php|routes/admin.php) head -c 5 "$f" | grep -q '<?php' && php -l "$f" >/dev/null 2>&1 && [ "$(wc -l < "$f")" -gt 100 ] ;;
    *.php) head -c 5 "$f" | grep -q '<?php' && php -l "$f" >/dev/null 2>&1 ;;
    *) true ;;
  esac
}

for f in $CORE; do
  if [ -e "$f" ] && good "$f"; then echo "ok        $f"; continue; fi
  [ -e "$f" ] && { mkdir -p "$KEEP/$(dirname "$f")"; cp -p "$f" "$KEEP/$f"; }
  done_=""
  for t in $(ls -t /root/pre-*.tgz 2>/dev/null); do
    tar tzf "$t" 2>/dev/null | grep -qxF "$f" || continue
    rm -rf "$TMP/x"; mkdir -p "$TMP/x"
    tar xzf "$t" -C "$TMP/x" "$f" 2>/dev/null || continue
    if ( cd "$TMP/x" && good "$f" ); then
      mkdir -p "$(dirname "$f")"; cp "$TMP/x/$f" "$f"
      echo "restored  $f  <- $(basename "$t") ($(date -r "$t" '+%d %b %H:%M'))"; done_=1; break
    fi
  done
  if [ -z "$done_" ]; then
    if git cat-file -e "$REF:$f" 2>/dev/null; then
      mkdir -p "$(dirname "$f")"; git show "$REF:$f" > "$f"; echo "restored  $f  <- repo ($REF)"
    else
      echo "MISSING   $f  (no backup, not in repo)"
    fi
  fi
done

# any other code file that exists in the repo but is missing here
n=0
while IFS= read -r f; do
  [ -n "$f" ] && [ ! -e "$f" ] || continue
  mkdir -p "$(dirname "$f")"; git show "$REF:$f" > "$f"; n=$((n+1))
done < <(git ls-tree -r --name-only "$REF" -- app routes config resources database bootstrap/app.php bootstrap/providers.php artisan public/index.php)
echo "restored  $n other missing file(s) from repo"

# re-add the hooks the installers had added (only if missing)
php <<'PHP'
<?php
$r = 'routes/web.php'; $s = file_get_contents($r);
foreach (['notify_web.php' => "// Seller › Notify (keep last: replaces the old /notifications page)", 'vas_web.php' => "// Seller › Value Added Services hub"] as $file => $comment) {
    if (is_file('routes/' . $file) && strpos($s, $file) === false) { $s = rtrim($s) . "\n\n$comment\nrequire __DIR__ . '/$file';\n"; echo "hook      routes/web.php += $file\n"; }
}
file_put_contents($r, $s);

$a = 'routes/admin.php'; $s = file_get_contents($a);
if (strpos($s, 'weight.dispute.autofetch') === false && preg_match("/^.*->name\('weight\.dispatching\.download'\).*$/m", $s, $m)) {
    $s = str_replace($m[0], $m[0] . "\n        Route::post('weight-dispute/auto-fetch', 'autoFetch')->name('weight.dispute.autofetch')->middleware('isAllow:104,can_view');"
        . "\n        Route::post('weight-dispute/{id}/proof-links', 'proofLinks')->name('weight.dispute.links')->middleware('isAllow:104,can_view');", $s);
    file_put_contents($a, $s); echo "hook      routes/admin.php += weight auto-fetch routes\n";
}
foreach ([
    ["/^.*->name\('weight\.dispatching\.download'\).*$/m", 'weight.dispute.review', "        Route::get('weight-dispute-template', 'template')->name('weight.dispute.template')->middleware('isAllow:104,can_view');\n        Route::post('weight-dispute/{id}/review', 'review')->name('weight.dispute.review')->middleware('isAllow:104,can_view');\n        Route::get('weight-dispute/{id}/proof/{n}', 'proof')->name('weight.dispute.proof')->middleware('isAllow:104,can_view');", 'routes/admin.php'],
    ["/^.*->name\('seller\.weight\.discrepancy\.submit'\).*$/m", 'seller.weight.dispute.raise', "    Route::post('/weight/discrepancy/{id}/raise', [WeightDispatchingController::class, 'raise'])->name('seller.weight.dispute.raise');\n    Route::get('/weight/discrepancy/{id}/proof/{n}', [WeightDispatchingController::class, 'proof'])->name('seller.weight.dispute.proof');", 'routes/web.php'],
    ["/^.*->name\('seller\.weight\.discrepancy\.submit'\).*$/m", 'seller.weight.dispute.accept', "    Route::post('/weight/discrepancy/{id}/accept', [WeightDispatchingController::class, 'accept'])->name('seller.weight.dispute.accept');", 'routes/web.php'],
] as [$re, $marker, $lines, $file]) {
    $s = file_get_contents($file);
    if (strpos($s, $marker) === false && preg_match($re, $s, $m)) {
        file_put_contents($file, str_replace($m[0], $m[0] . "\n" . $lines, $s)); echo "hook      $file += $marker\n";
    }
}

foreach (glob('resources/views/layouts/*.blade.php') as $l) {
    $s = file_get_contents($l);
    if (preg_match('/sxSide|sx-side/', $s) && strpos($s, 'vas-menu-link') === false && ($k = strripos($s, '</body>')) !== false) {
        file_put_contents($l, substr($s, 0, $k) . "@includeIf('partials.vas-menu-link')\n" . substr($s, $k)); echo "hook      $l += sidebar link\n";
    }
}
PHP

for f in routes/*.php app/Providers/*.php; do php -l "$f" >/dev/null 2>&1 || echo "LINT FAIL $f"; done
chown -R www-data:www-data app routes config resources database bootstrap storage 2>/dev/null
sudo -u www-data php artisan optimize:clear >/dev/null 2>&1 || php artisan optimize:clear 2>&1 | tail -3
rm -rf "$TMP"

for u in / /seller-login /seller/dashboard /notifications /seller/services/rto-score /seller/weight-discrepancy /admin/login; do
  echo "page $(curl -s -o /dev/null -w '%{http_code}' "https://shipxpeed.tech$u")  $u"
done
echo "Replaced files kept in $KEEP"
echo "RECOVER_DONE (seller pages should show 302 = redirect to login, not 404/500)"
