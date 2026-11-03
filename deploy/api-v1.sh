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
deploy/old-api-delhivery-backfill.php
"
SHARED="routes/api.php routes/web.php app/Observers/OrderObserver.php app/Providers/AppServiceProvider.php app/Http/Controllers/Shipxpeedapi/ServiceabilityController.php"

git fetch -q origin || { echo "FAIL: git fetch"; exit 1; }
git cat-file -e "$REF:routes/api_v1.php" 2>/dev/null || { echo "FAIL: API v1 not found on $REF"; exit 1; }

EXIST=""; for f in $NEW_FILES $SHARED; do [ -e "$f" ] && EXIST="$EXIST $f"; done
tar czf "$BK" $EXIST && echo "Backup: $BK" || { echo "FAIL: backup"; exit 1; }
restore() {
  echo "!! restoring backup"; tar xzf "$BK"
  local saved; saved=$(tar tzf "$BK")
  for f in $NEW_FILES; do [[ $'\n'"$saved"$'\n' == *$'\n'"$f"$'\n'* ]] || rm -f "$f"; done
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

// Old seller API: Delhivery priced from the client's rate card + panel switches, saved as courier_id "delhivery_b2c"
// so the tracking sync, NDR, cancel and weight checks pick it up.
edit('app/Http/Controllers/Shipxpeedapi/ServiceabilityController.php', 'delhiveryRateRow', function ($s) {
    $span = function ($s, $sig) {
        $a = strpos($s, $sig);
        if ($a === false) { return null; }
        $i = strpos($s, '{', $a); $d = 0;
        for ($n = strlen($s); $i < $n; $i++) {
            if ($s[$i] === '{') { $d++; }
            elseif ($s[$i] === '}' && --$d === 0) { return [$a, $i + 1]; }
        }
        return null;
    };
    foreach ([['callDelhiveryAPI', 'Delhivery', 'Delhivery'], ['callDelhiveryAirAPI', 'Delhivery_Air', 'Delhivery B2C (Express)']] as [$fn, $key, $name]) {
        $sp = $span($s, "public function $fn(array \$params): array");
        if (!$sp) { return null; }
        $s = substr($s, 0, $sp[0])
            . "public function $fn(array \$params): array\n    {\n"
            . "        // Same price and on/off switch as the seller panel and API v1 (RateCard / CourierAccess):\n"
            . "        // client's own zone rate, else the default card; first 500 g + each additional 500 g, COD, + 18% GST.\n"
            . "        return \$this->delhiveryRateRow(\$params, '$key', '$name');\n    }"
            . substr($s, $sp[1]);
    }
    $helper = <<<'H'
    /** One Delhivery rate row (direct account) priced from the client's rate card; [] when off, no zone or no rate. */
    private function delhiveryRateRow(array $params, string $rateKey, string $courierName): array
    {
        $sellerId = (int) ($params['seller_id'] ?? 0);
        if (!\App\Support\CourierAccess::isOn($sellerId, $rateKey)) {
            return [];
        }
        $zone = \App\Support\RateCard::zoneForStates($params['pickup_state'] ?? null, $params['destination_state'] ?? null);
        if (!$zone) {
            return [];
        }
        $weight = (float) ($params['package_weight'] ?? 0);
        $cod = strtolower((string) ($params['payment_type'] ?? '')) === 'cod';
        $p = \App\Support\RateCard::price(\App\Support\RateCard::find($sellerId, $zone, $rateKey), $weight, $cod, (float) ($params['order_amount'] ?? 0));
        if (!$p) {
            return [];
        }
        return [[
            'serviceabilityId' => $params['destination_state'] ?? null,
            'courierName'      => $courierName,
            'courierCharge'    => $p['courierCharge'],
            'freightCharges'   => $p['freightCharges'],
            'codCharge'        => $p['codCharge'],
            'zone'             => $zone,
            'minWeight'        => $weight,
            'volWeight'        => $weight,
        ]];
    }

H;
    $s = str_replace('    public function callDelhiveryAPI(array $params): array', $helper . "\n    public function callDelhiveryAPI(array \$params): array", $s);

    // after a successful booking: save Delhivery as the panel does
    $s = preg_replace(
        '/(\$order->courier_id = \$request->courier_id;\s*\n\s*\$order->all_courier_name = \$providerName;)/',
        "$1\n                            // Delhivery: save it the way the panel does, so the tracking sync, NDR, cancel and weight\n"
        . "                            // disputes all pick it up (they look for courier_id \"delhivery_b2c\").\n"
        . "                            if (in_array(\$request->courier_id, ['delhivery', 'delhivery_air'], true)) {\n"
        . "                                \$order->courier_id = 'delhivery_b2c';\n"
        . "                                \$order->all_courier_name = \$request->courier_id === 'delhivery_air' ? 'Delhivery Air' : 'delhivery_b2c';\n"
        . "                            }",
        $s, 1, $count);
    if ($count !== 1) { return null; }

    // "continue" inside a switch (PHP compile warning on every load): skip unknown providers explicitly
    return preg_replace('/(default:\s*\n\s*)continue;/', '$1continue 2;', $s);
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
has() { local body; body=$(curl -s "$1"); [[ "$body" == *"$2"* ]]; }   # no pipe: grep -q + pipefail gives false failures
has "$SITE/developers" 'Shipxpeed shipping API' && echo "OK   /developers docs page" || { echo "FAIL /developers"; FAILED=1; }
has "$SITE/developers/postman.json" '"Shipxpeed API v1"' && echo "OK   Postman download" || { echo "FAIL Postman download"; FAILED=1; }
has "$SITE/api/v1/public/couriers" '"success":true' && echo "OK   live courier list" || { echo "FAIL courier list"; FAILED=1; }
c=$(code -X POST -H 'Accept: application/json' "$SITE/api/serviceability");      [ "$c" = 401 ] && echo "OK   old API still answers (no token -> 401)" || { echo "FAIL old API -> $c"; FAILED=1; }
[ "$FAILED" = 0 ] || restore

# 5b. existing old-API Delhivery orders still in transit (last 30 days) -> start getting tracking updates
php deploy/old-api-delhivery-backfill.php --apply 2>&1 | sed 's/^/     /'

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
    r=$(get "$p"); [[ "$r" == *'"success":true'* ]] && echo "  OK   GET $p" || { echo "  FAIL GET $p: ${r:0:200}"; FAILED=1; }
  done
  WH=$(get /warehouses)
  PIN=$(echo "$WH" | php -r '$j=json_decode(stream_get_contents(STDIN),true); echo $j["data"]["warehouses"][0]["pincode"] ?? "110053";')
  ST=$(echo "$WH" | php -r '$j=json_decode(stream_get_contents(STDIN),true); echo $j["data"]["warehouses"][0]["state"] ?? "Delhi";')
  r=$(curl -s -X POST -H "X-API-Key: $KEY" -H 'Accept: application/json' -H 'Content-Type: application/json' "$SITE/api/v1/b2c/rates" \
      -d "{\"origin_pincode\":\"$PIN\",\"destination_pincode\":\"400001\",\"payment_type\":\"prepaid\",\"order_amount\":999,\"weight_g\":500,\"origin_state\":\"$ST\",\"destination_state\":\"Maharashtra\"}")
  if [[ "$r" == *'"success":true'* ]]; then
    echo "  OK   POST /b2c/rates $PIN -> 400001:"
    echo "$r" | php -r '$j=json_decode(stream_get_contents(STDIN),true); foreach(($j["data"]["couriers"]??[]) as $c) printf("         %-32s Rs %8.2f  (%s)\n",$c["courier_name"],$c["total_charge"],$c["courier_id"]); if(empty($j["data"]["couriers"])) echo "         (no courier ON for this account on this lane)\n";'
  else echo "  FAIL POST /b2c/rates: ${r:0:300}"; FAILED=1; fi
  [ "$FAILED" = 0 ] || echo "!! Some live checks failed (the API itself is installed). Send this output to Claude."
else
  echo "Skipped live account test (no account with an API key + warehouse). Run again with SMOKE_KEY=sxp_..."
fi
echo "DONE - API v1 is live: $SITE/api/v1   Docs: $SITE/developers   (backup: $BK)"
