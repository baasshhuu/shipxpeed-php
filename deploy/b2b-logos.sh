#!/usr/bin/env bash
# Courier logos for B2B (and any other page using CourierBrand): downloads official logos into public/brands/b2b,
# shows them on B2B rate lists, the assign pop-up, the rate calculator, the shipment list and the shipment page.
# Backs up first, restores on failure. Safe to run twice (re-downloads missing logos only).
# Usage: cd /var/www/shipxpeed && git fetch origin && git show origin/main:deploy/b2b-logos.sh | bash
set -uo pipefail
APP=${APP:-/var/www/shipxpeed}
cd "$APP" || { echo "FAIL: $APP not found"; exit 1; }
REF=origin/main
BASE=746d8aa
CB=app/Support/CourierBrand.php
VIEWS="app/Http/Controllers/selleradmin/B2bOrderController.php resources/views/sellerdashboard/b2b/_style.blade.php resources/views/sellerdashboard/b2b/index.blade.php resources/views/sellerdashboard/b2b/show.blade.php resources/views/sellerdashboard/b2b/rates.blade.php"
BK=/root/pre-b2b-logos-$(date +%Y%m%d-%H%M%S).tgz
git fetch -q origin || { echo "FAIL: git fetch"; exit 1; }
for f in $CB $VIEWS; do [ -s "$f" ] || { echo "FAIL: $f missing"; exit 1; }; done
for f in $VIEWS; do
  cur=$(git hash-object "$f"); known=""
  for c in $BASE $(git log --format=%H "$BASE..$REF" -- "$f"); do [ "$(git rev-parse "$c:$f" 2>/dev/null)" = "$cur" ] && known=1; done
  [ -n "$known" ] || { echo "STOP: $f was changed on this server; not overwriting. Send this output to Claude."; exit 1; }
done
tar czf "$BK" $CB $VIEWS && echo "Backup: $BK"
restore() { echo "!! restoring"; tar xzf "$BK"; php artisan optimize:clear >/dev/null 2>&1; echo "RESTORED - code unchanged (downloaded logos kept in public/brands/b2b)"; exit 1; }

# ---------------------------------------------------------------- logos
D=public/brands/b2b; mkdir -p "$D"
UA="Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126 Safari/537.36"
fav() { echo "https://www.google.com/s2/favicons?domain=$1&sz=256"; }
get() { # slug url...
  local slug=$1; shift
  ls "$D/$slug".* >/dev/null 2>&1 && { echo "logo ok (kept): $slug"; return; }
  for u in "$@"; do
    tmp=$(mktemp); code=$(curl -sSL -A "$UA" --max-time 25 -o "$tmp" -w '%{http_code}' "$u" 2>/dev/null)
    type=$(file -b --mime-type "$tmp" 2>/dev/null); size=$(stat -c %s "$tmp" 2>/dev/null || echo 0)
    if [ "$code" = "200" ] && [ "$size" -gt 400 ]; then
      case "$type" in
        image/svg+xml|text/xml|text/plain) grep -qi "<svg" "$tmp" && { mv "$tmp" "$D/$slug.svg"; echo "logo downloaded: $slug.svg"; return; } ;;
        image/png) mv "$tmp" "$D/$slug.png"; echo "logo downloaded: $slug.png"; return ;;
        image/webp) mv "$tmp" "$D/$slug.webp"; echo "logo downloaded: $slug.webp"; return ;;
        image/jpeg) mv "$tmp" "$D/$slug.jpg"; echo "logo downloaded: $slug.jpg"; return ;;
      esac
    fi
    rm -f "$tmp"
  done
  echo "logo missing: $slug (shows initials instead)"
}
get movin       https://www.movin.in/themes/custom/ups/logo.svg https://www.movin.in/themes/custom/ups/logo-print.png "$(fav movin.in)"
get gati        https://www.allcargologistics.com/assets/images/Gati-logo.webp "$(fav gati.com)"
get dpworld     https://cdn.worldvectorlogo.com/logos/dp-world-2021-logo.svg https://cdn.worldvectorlogo.com/logos/dp-world.svg "$(fav dpworld.com)"
get tci         https://www.tciexpress.in/images/logo.png "$(fav tciexpress.in)"
get safexpress  https://www.safexpress.com/assets/images/new-logo.png https://www.safexpress.com/assets/images/logo.png "$(fav safexpress.com)"
get vxpress     https://vxpress.in/wp-content/themes/tf_child/images/v-xpress-logo.svg "$(fav vxpress.in)"
get rivigo      https://cdn.worldvectorlogo.com/logos/rivigo.svg "$(fav rivigo.com)"
get omlogistics "$(fav omlogistics.co.in)"
get smartr      https://cdn.worldvectorlogo.com/logos/smartr.svg "$(fav smartr.in)"
get ecom        https://cdn.worldvectorlogo.com/logos/ecom-express.svg "$(fav ecomexpress.in)"
get trackon     "$(fav trackon.in)"
chown -R www-data:www-data "$D" 2>/dev/null

# ---------------------------------------------------------------- code
git show "$REF:$CB" > /tmp/cb-origin.php || restore
CB=$CB php <<'PHP' || restore
<?php
$f = getenv('CB'); $s = file_get_contents($f); $o = file_get_contents('/tmp/cb-origin.php');
if (strpos($s, 'COURIER-LOGOS-B2B') !== false) { echo "already there: CourierBrand\n"; exit(0); }
$a = strpos($s, 'public static function logo(?string $courierName): ?string');
$b = strpos($s, '    /** Logo to show a seller:', $a ?: 0);
if ($a === false || $b === false) { fwrite(STDERR, "CourierBrand::logo not found\n"); exit(1); }
$body = substr($s, $a, $b - $a);
if (substr_count($body, "default => null,") !== 1) { fwrite(STDERR, "logo() default not found\n"); exit(1); }
$oa = strpos($o, '    /** COURIER-LOGOS-B2B'); $ob = strpos($o, '    /** Logo to show a seller:');
if ($oa === false || $ob === false) { fwrite(STDERR, "repo block not found\n"); exit(1); }
$body = str_replace("default => null,", "default => self::extraLogo(\$n),", $body);
$s = substr($s, 0, $a) . $body . substr($o, $oa, $ob - $oa) . substr($s, $b);
file_put_contents($f, $s); echo "patched: $f\n";
PHP
for f in $VIEWS; do git show "$REF:$f" > "$f" || restore; done
echo "updated: B2B pages + controller"
for f in $CB app/Http/Controllers/selleradmin/B2bOrderController.php; do php -l "$f" >/dev/null 2>&1 || { php -l "$f"; restore; }; done
chown www-data:www-data $CB $VIEWS 2>/dev/null
sudo -u www-data php artisan optimize:clear >/dev/null 2>&1

OUT=$(sudo -u www-data php -r '
require "vendor/autoload.php"; $a = require "bootstrap/app.php";
$a->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
foreach (["LTL Ekart", "LTL MOVIN", "LTL Gati", "LTL Delhivery", "LTL DP World", "LTL TCI Express", "Safexpress"] as $n) echo "LOGO $n: " . (App\Support\CourierBrand::logoUrl($n) ? "yes" : "initials") . "\n";
try {
  $o = App\Models\B2bOrder::orderByDesc("id")->first();
  $s = $o ? App\Models\SellerList::find($o->seller_id) : App\Models\SellerList::first();
  auth()->guard("seller")->setUser($s);
  View::share("site_settings", App\Models\Setting::select("setting_name", "filed_value")->get()->pluck("filed_value", "setting_name")->toArray());
  View::share("config", []); View::share("errors", new Illuminate\Support\ViewErrorBag());
  $c = app(App\Http\Controllers\selleradmin\B2bOrderController::class);
  echo "PAGE list " . (str_contains($c->index(Illuminate\Http\Request::create("/seller/b2b-orders"))->render(), "function logo(r)") ? "ok" : "fail") . "\n";
  echo "PAGE rates " . (str_contains($c->rates()->render(), "function logo(r)") ? "ok" : "fail") . "\n";
  if ($o) echo "PAGE show " . (str_contains($c->show($o->id)->render(), "b2-") ? "ok" : "fail") . "\n";
} catch (\Throwable $e) { echo "PAGE fail " . $e->getMessage() . " @ " . basename($e->getFile()) . ":" . $e->getLine() . "\n"; }' 2>&1)
echo "$OUT" | grep -E "^(LOGO|PAGE)"
echo "$OUT" | grep -q "^PAGE fail\|^PAGE .* fail" && restore
echo "$OUT" | grep -q "^PAGE list ok" || { echo "$OUT" | tail -5; restore; }
echo "B2B_LOGOS_DONE backup=$BK"
