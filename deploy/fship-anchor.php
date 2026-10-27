<?php
/*
 * Adds the Fship hooks to one shared panel file by anchor (not by line numbers),
 * so it works on server copies that have their own local edits.
 * Usage: php deploy/fship-anchor.php <relative/file.php>
 * Exit 0 = file written (or nothing needed), 2 = required anchor missing (file untouched).
 * Prints "OK"/"WARN" lines describing what was done.
 */
$file = $argv[1] ?? '';
if (!is_file($file)) { fwrite(STDERR, "no such file: $file\n"); exit(2); }
$s = file_get_contents($file);
$orig = $s;
$warn = [];
$need = false; // true when a required edit failed

function insertBefore(string &$s, string $needle, string $add, int $limit = 1): int {
    $n = 0; $off = 0;
    while ($n < $limit && ($p = strpos($s, $needle, $off)) !== false) {
        $ls = strrpos(substr($s, 0, $p), "\n"); $ls = $ls === false ? 0 : $ls + 1;
        $s = substr($s, 0, $ls) . $add . substr($s, $ls);
        $off = $ls + strlen($add) + strlen($needle); $n++;
    }
    return $n;
}
function insertAfterLine(string &$s, int $pos, string $add): void {
    $le = strpos($s, "\n", $pos); $le = $le === false ? strlen($s) : $le + 1;
    $s = substr($s, 0, $le) . $add . substr($s, $le);
}
function afterFunctionBrace(string &$s, string $sigRegex, string $add): bool {
    if (!preg_match($sigRegex, $s, $m, PREG_OFFSET_CAPTURE)) return false;
    $b = strpos($s, '{', $m[0][1] + strlen($m[0][0]) - 1);
    if ($b === false) return false;
    insertAfterLine($s, $b, $add);
    return true;
}

switch (str_replace('\\', '/', $file)) {

case 'config/couriers.php':
    if (!preg_match('/\n\s*\];\s*$/', $s, $m, PREG_OFFSET_CAPTURE)) { $need = true; break; }
    $p = $m[0][1];
    $s = substr($s, 0, $p) . "\n    'fship' => \\App\\Services\\FshipService::class," . substr($s, $p);
    break;

case 'app/Providers/CourierServiceProvider.php':
    $ok = afterFunctionBrace($s, '/public function register\s*\(\s*\)\s*(:\s*void)?/',
        "        // Fship aggregator\n        \$this->app->bind('courier.fship', fn() => new \\App\\Services\\FshipService);\n");
    if (!$ok) $need = true;
    break;

case 'routes/admin.php':
    $r = "    // Fship aggregator: status, courier list, rate test, API log\n"
       . "    Route::get('fship', [\\App\\Http\\Controllers\\admin\\FshipController::class, 'index'])->name('admin.fship.index');\n"
       . "    Route::post('fship/settings', [\\App\\Http\\Controllers\\admin\\FshipController::class, 'saveKey'])->name('admin.fship.settings');\n"
       . "    Route::post('fship/rate-test', [\\App\\Http\\Controllers\\admin\\FshipController::class, 'rateTest'])->name('admin.fship.rate-test');\n"
       . "    Route::get('fship/log/{id}', [\\App\\Http\\Controllers\\admin\\FshipController::class, 'logShow'])->whereNumber('id')->name('admin.fship.log');\n\n";
    if (!insertBefore($s, 'Profile::routes();', $r)
        && !insertBefore($s, "Route::get('dashboard'", $r)) $need = true;
    break;

case 'app/Console/Kernel.php':
    $ok = afterFunctionBrace($s, '/protected function schedule\s*\(\s*Schedule \$schedule\s*\)\s*(:\s*void)?/',
        "        // Fship: pull tracking status for open shipments\n"
      . "        \$schedule->command('fship:track')->everyThirtyMinutes()->withoutOverlapping(25)->runInBackground()\n"
      . "            ->appendOutputTo(storage_path('logs/fship-track.log'));\n\n");
    if (!$ok) $warn[] = 'tracking schedule not added (run fship:track by cron instead)';
    break;

case 'app/Http/Controllers/Shipxpeedapi/ServiceabilityController.php':
    // rate switches: add "case 'fship'" before an existing aggregator case in every switch
    $n = 0;
    foreach (['shiprocket' => 'callshiprocketAPI', 'tekipost' => 'callTekipostAPI'] as $case => $fn) {
        $n = preg_match_all("/^([ \\t]*)case '$case':\\s*\\n\\s*\\\$providerResults = \\\$this->$fn\\(\\\$(\\w+)\\);/m", $s);
        if ($n) {
            $s = preg_replace_callback("/^([ \\t]*)case '$case':(\\s*\\n\\s*\\\$providerResults = \\\$this->$fn\\(\\\$(\\w+)\\);)/m",
                fn($m) => "{$m[1]}case 'fship':\n{$m[1]}    \$providerResults = app('courier.fship')->getRatesForParams(\${$m[3]});\n{$m[1]}    break;\n{$m[1]}case '$case':{$m[2]}", $s);
            break;
        }
    }
    if (!$n) { $need = true; break; }
    // seller API courier_id validation: accept fship_<id>
    $s = preg_replace('/if \(!in_array\(\$value, \$allowed, true\)/', "if (!str_starts_with((string) \$value, 'fship_') && !in_array(\$value, \$allowed, true)", $s, -1, $c1);
    if (!$c1) $warn[] = 'seller-API courier_id validation not updated';
    // seller API booking with fship_<id>
    $assign = "            } elseif (str_starts_with((string) \$request->courier_id, 'fship_')) {\n"
            . "                \$assignmentResult = app('courier.fship')->assignOrder([\n"
            . "                    'order_id' => \$order->id,\n"
            . "                    'serviceability_id' => (int) substr((string) \$request->courier_id, strlen('fship_')),\n"
            . "                    'seller_id' => \$sellerid ?? null,\n"
            . "                ]);\n"
            . "                \$courierAssigned = !empty(\$assignmentResult['status']);\n"
            . "                if (\$courierAssigned) {\n"
            . "                    \$order->refresh();\n"
            . "                    \$courierMapping[\$request->courier_id] = \$order->courier_name ?: 'Fship';\n"
            . "                }\n";
    if (!insertBefore($s, '} elseif (isset($courierMapping[$request->courier_id])) {', $assign))
        $warn[] = 'seller-API booking via fship_ id not added (panel booking still works)';
    break;

case 'app/Http/Controllers/selleradmin/CourierController.php':
    $logo = "                    (isset(\$localProvider) && \$localProvider && \$localProvider->code === 'fship') => \\App\\Services\\FshipService::logoFor(\$courier['courierName'] ?? '', \$localProvider->logo),\n";
    $off = 0; $n = 0;
    while (($p = strpos($s, "'provider_logo' => match (true) {", $off)) !== false) {
        // only where $localProvider is used in the same match block
        $ls = strrpos(substr($s, 0, $p), "\n"); $lineStart = $ls === false ? '' : ltrim(substr($s, $ls + 1, $p - $ls - 1));
        $commented = str_starts_with($lineStart, '//') || str_starts_with($lineStart, '#') || str_starts_with($lineStart, '*');
        if (!$commented && strpos(substr($s, $p, 6000), '$localProvider') !== false) { insertAfterLine($s, $p, $logo); $n++; }
        $off = $p + 10;
    }
    $s = preg_replace('/\(\$localProvider && in_array\(\$localProvider->name, (\[[^\]]*\])\)\)/',
        "(\$localProvider && (in_array(\$localProvider->name, \$1) || \$localProvider->code === 'fship'))", $s, -1, $c2);
    if (!$n && !$c2) $warn[] = 'courier logos/names not mapped (Fship couriers show with default logo)';
    break;

case 'app/Http/Controllers/selleradmin/ShipmentController.php':
    // single assign
    $fp = strpos($s, 'function assignCourier(Request $request)');
    $ap = $fp === false ? false : strpos($s, '$response = app($key)->assignOrder($params);', $fp);
    if ($ap === false) { $need = true; break; }
    $after = "\n        if (isset(\$logisticProvider) && \$logisticProvider && \$logisticProvider->code === 'fship') {\n"
           . "            if (!empty(\$response['status']) && !empty(\$response['awb_number'])) {\n"
           . "                return redirect()->route('seller.order')->with('success', 'Order successfully assigned! AWB: ' . \$response['awb_number'] . (!empty(\$response['pickup_message']) ? ' (' . \$response['pickup_message'] . ')' : ''));\n"
           . "            }\n"
           . "            return redirect()->route('seller.order')->with('error', \$response['message'] ?? 'Fship booking failed');\n"
           . "        }\n";
    insertAfterLine($s, $ap, $after);
    $before = "        if (isset(\$logisticProvider) && \$logisticProvider && \$logisticProvider->code === 'fship') {\n"
            . "            \$params = ['order_id' => \$orderId, 'provider_name' => \$provider_name ?? null, 'serviceability_id' => \$serviceability_id ?? null];\n"
            . "            \$fsOrder = Order::where('id', \$orderId)->first();\n"
            . "            if (\$fsOrder && isset(\$courierCharge)) { \$fsOrder->seller_amount_walate = \$courierCharge; \$fsOrder->save(); }\n"
            . "        }\n";
    $ls = strrpos(substr($s, 0, $ap), "\n") + 1;
    $s = substr($s, 0, $ls) . $before . substr($s, $ls);
    // bulk assign
    $bp = strpos($s, 'function assignCourier_bulk(Request $request)');
    if ($bp !== false && preg_match('/\$params = \$this->prepareBulkCourierParams\([^;]*\);/', $s, $m, PREG_OFFSET_CAPTURE, $bp)) {
        insertAfterLine($s, $m[0][1],
            "                if (isset(\$logisticProvider) && \$logisticProvider && \$logisticProvider->code === 'fship') {\n"
          . "                    \$params = ['order_id' => \$orderId, 'provider_name' => \$provider_name ?? null, 'serviceability_id' => \$serviceability_id ?? null];\n"
          . "                }\n");
        $ok = afterFunctionBrace($s, '/function processBulkCourierResponse\s*\(/',
            "        if ((\$response['couriername'] ?? null) === 'fship') {\n"
          . "            return !empty(\$response['status']) && !empty(\$response['awb_number'])\n"
          . "                ? ['success' => true, 'awb' => \$response['awb_number']]\n"
          . "                : ['success' => false, 'message' => \$response['message'] ?? 'Fship booking failed'];\n"
          . "        }\n");
        if (!$ok) $warn[] = 'bulk assign result for Fship not handled';
    } else {
        $warn[] = 'bulk assign not hooked (single assign works)';
    }
    break;

case 'app/Http/Controllers/Frontend/TrackController.php':
    $c = 0;
    $s = preg_replace("/^([ \\t]*)default:(\\s*\\n\\s*return redirect\\(\\)->back\\(\\)->withErrors\\(\\['error' => 'Tracking not available for this courier\\.'\\]\\);)/m",
        "\$1case 'fship':\n\$1    return \$this->trackWithFship(\$awb);\n\$1default:\$2", $s, 1, $c);
    $fn = "\n    private function trackWithFship(\$awb)\n    {\n"
        . "        \$t = app(\\App\\Services\\FshipService::class)->track(\$awb);\n"
        . "        if (empty(\$t['status'])) {\n"
        . "            return redirect()->back()->withErrors(['error' => \$t['message'] ?? 'Tracking not available yet.']);\n"
        . "        }\n"
        . "        \$trackWithDelhiveryB2C = ['awb_number' => \$t['awb_number'], 'order_number' => \$t['order_number'], 'order_id' => \$t['order_id'],\n"
        . "            'status' => \$t['raw_status'] ?? 'N/A', 'created' => \$t['created'], 'history' => \$t['history']];\n"
        . "        return view('frontend.website.pages.trackorder', compact('trackWithDelhiveryB2C'));\n"
        . "    }\n";
    if ($c && preg_match('/\}\s*$/', $s, $m, PREG_OFFSET_CAPTURE)) {
        $s = substr($s, 0, $m[0][1]) . $fn . "}\n";
    } else { $s = $orig; $warn[] = 'public tracking page not hooked (fship:track still updates order status)'; }
    break;

case 'app/Http/Controllers/Shipxpeedapi/CancelshipmentController.php':
    $c = 0;
    $s = preg_replace("/^([ \\t]*)'shiprocket' => 'shiprocket',/m", "\$1'fship' => 'fship',\n\$0", $s, 1, $c);
    $add = "            case 'fship':\n"
         . "                \$r = app('courier.fship')->cancelShipment(\$awb);\n"
         . "                return ['success' => !empty(\$r['status']), 'message' => \$r['message'] ?? null, 'data' => []];\n\n";
    if (!$c || !insertBefore($s, "case 'shiprocket':", $add)) { $s = $orig; $warn[] = 'seller-API cancel not hooked (panel cancel uses the Fship adapter directly)'; }
    break;

case 'app/Http/Controllers/Shipxpeedapi/NdrController.php':
    $add = "        } elseif (\$courierId === 'fship') {\n"
         . "            \$action = in_array(\$request->action, ['re-attempt', 'change-address', 'change-phone', 'rto'], true) ? \$request->action : 're-attempt';\n"
         . "            return app(\\App\\Services\\FshipService::class)->ndrAction(\$order, \$action, ['reattempt_date' => \$request->reattempt_date,\n"
         . "                'address' => \$request->address, 'phone' => \$request->phone, 'name' => \$request->name, 'landmark' => \$request->landmark, 'remark' => \$request->remark]);\n";
    if (!preg_match('/^\s*\} elseif \(in_array\(\$courierId, \[\'parcelx\'/m', $s, $m, PREG_OFFSET_CAPTURE)) { $warn[] = 'seller-API NDR not hooked'; break; }
    // insert after that elseif block's single return line
    $rp = strpos($s, 'return $this->parcelXNdrReattempt', $m[0][1]);
    if ($rp === false) { $warn[] = 'seller-API NDR not hooked'; break; }
    insertAfterLine($s, $rp, $add);
    break;

case 'app/Http/Controllers/admin/ZonePriceSettingController.php':
    $add = "        // Fship couriers (live list from the Fship account)\n"
         . "        if (isset(\$LogisticProviders) && is_array(\$LogisticProviders)) { \$LogisticProviders = array_merge(\$LogisticProviders, \\App\\Services\\FshipService::rateCardKeys()); }\n";
    $n = 0;
    $s = preg_replace_callback('/^([ \t]*)(\$selectedSeller = null;|return view\(\'admin\.zonepricesetting\.zone_price_edit\')/m',
        function ($m) use ($add, &$n) { $n++; return $add . $m[0]; }, $s);
    if (!$n) $warn[] = 'Fship couriers not added to the zone-rate dropdown (set rates via the rate template instead)';
    break;

default:
    fwrite(STDERR, "no anchor rules for $file\n"); exit(2);
}

if ($need) { echo "FAIL $file: required anchor not found\n"; exit(2); }
foreach ($warn as $w) echo "WARN $file: $w\n";
if ($s !== $orig) { file_put_contents($file, $s); echo "OK   $file: Fship hooks added by anchor\n"; }
else echo "OK   $file: no change\n";
exit(0);
