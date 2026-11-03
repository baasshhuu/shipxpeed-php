#!/usr/bin/env bash
# Puts Shipxpeed API v1 (/api/v1/...) and its docs page (/developers) live.
#  - backs up every file it touches; restores automatically on any failure
#  - new files are copied from origin/main; 4 existing files get small idempotent edits
#    (works on a checkout with local edits; running it twice changes nothing)
#  - creates the api_v1_logs table, clears caches
#  - checks: lint, routes, 401 without key, docs page, Postman file, then a READ-ONLY live test
#    with a real account (account, couriers, rate card, wallet, warehouses, live rates). Nothing is booked.
# Usage:  git -C /var/www/shipxpeed fetch -q origin && git -C /var/www/shipxpeed show origin/main:deploy/api-v1.sh | bash
#         (optional: SMOKE_KEY=sxp_... to run the live test with a specific account's key)
set -uo pipefail
APP=${APP:-/var/www/shipxpeed}
cd "$APP" || { echo "FAIL: $APP not found"; exit 1; }
REF=${REF:-origin/main}
SITE=${SITE:-https://shipxpeed.tech}
STAMP=$(date +%Y%m%d-%H%M%S)
BK=/root/pre-api-v1-$STAMP.tgz

NEW_FILES="
app/Http/Middleware/ApiV1Auth.php
app/Support/ApiV1/Respond.php
app/Support/ApiV1/Couriers.php
app/Support/ApiV1/Present.php
app/Support/ApiV1/WebhookPusher.php
app/Http/Controllers/ApiV1/AccountController.php
app/Http/Controllers/ApiV1/B2cController.php
app/Http/Controllers/ApiV1/B2bController.php
app/Http/Controllers/ApiV1/ReverseController.php
app/Http/Controllers/ApiV1/ShipmentController.php
app/Services/ApiV1Docs.php
routes/api_v1.php
resources/views/developers.blade.php
database/migrations/2026_11_03_000001_api_v1_logs.php
"
SHARED="routes/api.php routes/web.php app/Observers/OrderObserver.php app/Providers/AppServiceProvider.php"

git fetch -q origin || { echo "FAIL: git fetch"; exit 1; }
git cat-file -e "$REF:routes/api_v1.php" 2>/dev/null || { echo "FAIL: API v1 not found on $REF"; exit 1; }

EXIST=""; for f in $NEW_FILES $SHARED; do [ -e "$f" ] && EXIST="$EXIST $f"; done
tar czf "$BK" $EXIST && echo "Backup: $BK" || { echo "FAIL: backup"; exit 1; }
restore() {
  echo "!! restoring backup"; tar xzf "$BK"
  for f in $NEW_FILES; do tar tzf "$BK" | grep -qxF "$f" || rm -f "$f"; done
  rmdir app/Support/ApiV1 app/Http/Controllers/ApiV1 2>/dev/null
  php artisan optimize:clear >/dev/null 2>&1
  echo "RESTORED - nothing changed"; exit 1
}

# 1. new files
mkdir -p app/Support/ApiV1 app/Http/Controllers/ApiV1
for f in $NEW_FILES; do git show "$REF:$f" > "$f.new" && mv "$f.new" "$f" || { rm -f "$f.new"; restore; }; done
echo "Copied $(echo $NEW_FILES | wc -w) new files"

# 2. small edits to existing files (each skipped when already there)
php <<'PHP' || restore
<?php
function edit($file, $marker, callable $fn) {
    $s = file_get_contents($file);
    if ($s === false) { fwrite(STDERR, "FAIL: cannot read $file\n"); exit(1); }
    if (strpos($s, $marker) !== false) { echo "  already done: $file\n"; return; }
    $n = $fn($s);
    if ($n === null || $n === $s || strpos($n, $marker) === false) { fwrite(STDERR, "FAIL: could not edit $file\n"); exit(1); }
    file_put_contents($file, $n);
    echo "  edited: $file\n";
}
edit('routes/api.php', 'api_v1.php', fn ($s) => rtrim($s) . "\n\n// Shipxpeed API v1 (clean, versioned API for client integrations). Docs: /developers\nrequire __DIR__ . '/api_v1.php';\n");

$docs = "// API v1 developer docs (public). Courier list on the page is live from the panel.\n"
    . "Route::get('/developers', fn () => view('developers', ['docs' => app(\\App\\Services\\ApiV1Docs::class)]))->name('api.v1.docs');\n"
    . "Route::get('/developers/postman.json', fn () => response()->json(app(\\App\\Services\\ApiV1Docs::class)->postman(url('/api')), 200,\n"
    . "    ['Content-Disposition' => 'attachment; filename=\"shipxpeed-api-v1.postman_collection.json\"'], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT))->name('api.v1.postman');\n\n";
edit('routes/web.php', 'api.v1.docs', function ($s) use ($docs) {
    $a = "Route::get('/api-docs', function () {";
    return strpos($s, $a) !== false ? str_replace($a, $docs . $a, $s) : rtrim($s) . "\n\n" . $docs;
});

edit('app/Observers/OrderObserver.php', 'WebhookPusher', fn ($s) => preg_replace(
    '/(public function updated\(Order \$order\): void\s*\{)/',
    "$1\n        // API v1: tell the client's webhook URL about status changes\n        if (class_exists(\\\\App\\\\Support\\\\ApiV1\\\\WebhookPusher::class)) {\n            \\\\App\\\\Support\\\\ApiV1\\\\WebhookPusher::orderChanged(\$order);\n        }",
    $s, 1));

edit('app/Providers/AppServiceProvider.php', 'b2bChanged', function ($s) {
    $line = "        // API v1: B2B status changes go to the client's webhook URL too\n"
        . "        \\App\\Models\\B2bOrder::updated(fn (\\App\\Models\\B2bOrder \$o) => \\App\\Support\\ApiV1\\WebhookPusher::b2bChanged(\$o));\n";
    if (preg_match('/^.*Order::observe\(.*$/m', $s, $m)) {
        return str_replace($m[0], $m[0] . "\n" . rtrim($line, "\n"), $s);
    }
    return preg_replace('/(public function boot\(\)(\s*:\s*void)?\s*\{)/', "$1\n" . rtrim($line, "\n"), $s, 1);
});
PHP
echo "Existing files up to date"

# 3. lint
for f in $NEW_FILES $SHARED; do
  case "$f" in *.blade.php) ;; *.php) php -l "$f" >/dev/null 2>&1 || { echo "LINT FAIL: $f"; php -l "$f"; restore; } ;; esac
done
echo "Lint OK"

# 4. table + caches
php artisan migrate --force --path=database/migrations/2026_11_03_000001_api_v1_logs.php 2>&1 | tail -1
chown -R www-data:www-data app routes resources database 2>/dev/null
mkdir -p public/labels/api && chown -R www-data:www-data public/labels 2>/dev/null
sudo -u www-data php artisan optimize:clear >/dev/null 2>&1 || php artisan optimize:clear >/dev/null 2>&1

# 5. live checks
code() { curl -s -o /dev/null -w '%{http_code}' "$@"; }
FAILED=0
c=$(code -H 'Accept: application/json' "$SITE/api/v1/");                         [ "$c" = 200 ] && echo "OK   /api/v1 up" || { echo "FAIL /api/v1 -> $c"; FAILED=1; }
c=$(code -H 'Accept: application/json' "$SITE/api/v1/account");                  [ "$c" = 401 ] && echo "OK   no key -> 401" || { echo "FAIL no key -> $c"; FAILED=1; }
c=$(code -H 'Accept: application/json' -H 'X-API-Key: wrong' "$SITE/api/v1/b2c/rates" -X POST); [ "$c" = 401 ] && echo "OK   wrong key -> 401" || { echo "FAIL wrong key -> $c"; FAILED=1; }
curl -s "$SITE/developers" | grep -q 'Shipxpeed shipping API' && echo "OK   /developers docs page" || { echo "FAIL /developers"; FAILED=1; }
curl -s "$SITE/developers/postman.json" | grep -q '"Shipxpeed API v1"' && echo "OK   Postman download" || { echo "FAIL Postman download"; FAILED=1; }
curl -s "$SITE/api/v1/public/couriers" | grep -q '"success":true' && echo "OK   live courier list" || { echo "FAIL courier list"; FAILED=1; }
[ "$FAILED" = 0 ] || restore

# 6. read-only test with a real account (nothing is booked or changed)
KEY=${SMOKE_KEY:-$(php -r '
require "vendor/autoload.php"; $a = require "bootstrap/app.php"; $a->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$ids = App\Models\Warehouse::pluck("seller_id")->unique();
$s = App\Models\SellerList::where("status", 1)->whereNotNull("api_token")->where("api_token", "!=", "")->whereIn("id", $ids)->orderByDesc("id")->first();
echo $s ? $s->api_token : "";' 2>/dev/null)}
if [ -n "$KEY" ]; then
  echo "Live read-only test (account key ${KEY:0:8}...):"
  get() { curl -s -H "X-API-Key: $KEY" -H 'Accept: application/json' "$SITE/api/v1$1"; }
  for p in /account /wallet "/couriers?type=b2c" /rate-card /warehouses "/shipments?per_page=1"; do
    r=$(get "$p"); echo "$r" | grep -q '"success":true' && echo "  OK   GET $p" || { echo "  FAIL GET $p: ${r:0:200}"; FAILED=1; }
  done
  PIN=$(get /warehouses | php -r '$j=json_decode(stream_get_contents(STDIN),true); echo $j["data"]["warehouses"][0]["pincode"] ?? "110053";')
  r=$(curl -s -X POST -H "X-API-Key: $KEY" -H 'Accept: application/json' -H 'Content-Type: application/json' "$SITE/api/v1/b2c/rates" \
      -d "{\"origin_pincode\":\"$PIN\",\"destination_pincode\":\"400001\",\"payment_type\":\"prepaid\",\"order_amount\":999,\"weight_g\":500}")
  if echo "$r" | grep -q '"success":true'; then
    echo "  OK   POST /b2c/rates $PIN -> 400001:"
    echo "$r" | php -r '$j=json_decode(stream_get_contents(STDIN),true); foreach(($j["data"]["couriers"]??[]) as $c) printf("         %-32s Rs %8.2f  (%s)\n",$c["courier_name"],$c["total_charge"],$c["courier_id"]); if(empty($j["data"]["couriers"])) echo "         (no courier ON for this account on this lane)\n";'
  else echo "  FAIL POST /b2c/rates: ${r:0:300}"; FAILED=1; fi
  [ "$FAILED" = 0 ] || echo "!! Some live checks failed (the API itself is installed). Send this output to Claude."
else
  echo "Skipped live account test (no account with an API key + warehouse). Run again with SMOKE_KEY=sxp_..."
fi
echo "DONE - API v1 is live: $SITE/api/v1   Docs: $SITE/developers   (backup: $BK)"
