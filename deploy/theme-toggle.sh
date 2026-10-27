#!/usr/bin/env bash
# Adds the dark / light theme button to the top bar (dark stays the default).
# Copies two files and adds one @include next to the theme stylesheet link. Safe to run twice.
set -uo pipefail
cd /var/www/shipxpeed || exit 1
REF=origin/main
STAMP=$(date +%Y%m%d-%H%M%S)
BK=/root/pre-theme-toggle-$STAMP.tgz
NEW_FILES="public/css/shipxpeed-light.css resources/views/partials/theme-toggle.blade.php"

echo "Shipxpeed theme toggle installer"
git fetch -q origin || { echo "FAIL: git fetch"; exit 1; }

# Every view that loads the dark theme stylesheet gets the toggle right after it
VIEWS=$(grep -rl --include='*.blade.php' 'shipxpeed-theme.css' resources/views | grep -v 'partials/theme-toggle')
[ -n "$VIEWS" ] || { echo "FAIL: no view loads shipxpeed-theme.css"; exit 1; }
echo "Views: $(echo $VIEWS)"

EXIST=""; for f in $NEW_FILES $VIEWS; do [ -e "$f" ] && EXIST="$EXIST $f"; done
tar czf "$BK" $EXIST && echo "Backup: $BK"
restore() {
  echo "!! restoring backup"; tar xzf "$BK"
  for f in $NEW_FILES; do tar tzf "$BK" | grep -qx "$f" || rm -f "$f"; done
  php artisan view:clear >/dev/null 2>&1
  echo "RESTORED - nothing changed"; exit 1
}

for f in $NEW_FILES; do mkdir -p "$(dirname "$f")"; git show "$REF:$f" > "$f" || restore; done
echo "Copied light theme and toggle"

for v in $VIEWS; do
  V="$v" php <<'PHP' || restore
<?php
$v = getenv('V');
$s = file_get_contents($v);
$inc = "@include('partials.theme-toggle')";
if (strpos($s, $inc) !== false) { echo "already there: $v\n"; exit(0); }
$lines = explode("\n", $s);
foreach ($lines as $i => $line) {
    if (strpos($line, 'shipxpeed-theme.css') !== false) {
        array_splice($lines, $i + 1, 0, [$inc]);
        file_put_contents($v, implode("\n", $lines));
        echo "patched: $v\n";
        exit(0);
    }
}
fwrite(STDERR, "theme link not found in $v\n"); exit(1);
PHP
done

php artisan view:clear >/dev/null
OUT=$(php artisan tinker --execute='
$s = App\Models\SellerList::where("status", 1)->first() ?? App\Models\SellerList::first();
Auth::guard("seller")->setUser($s);
foreach (["/seller/dashboard", "/seller/order"] as $u) {
  $r = app(Illuminate\Contracts\Http\Kernel::class)->handle(Illuminate\Http\Request::create("https://shipxpeed.tech" . $u));
  $c = (string) $r->getContent();
  echo "CHK " . $u . " " . $r->getStatusCode() . " toggle " . (str_contains($c, "sxLightCss") ? "yes" : "no") . ($r->getStatusCode() >= 500 ? " ERR " . substr(strip_tags($c), 0, 200) : "") . PHP_EOL;
}
' 2>&1 | grep -E "^CHK|Error|Exception")
echo "$OUT"
echo "$OUT" | grep -q "^CHK /seller/dashboard 200 toggle yes" || { echo "dashboard check failed"; restore; }
echo "$OUT" | grep -qE "^CHK .* 5[0-9][0-9]" && { echo "a page returned a server error"; restore; }

echo "DONE. Backup kept at $BK"
