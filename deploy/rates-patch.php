<?php
/**
 * In-place patches for deploy/rates.sh (run from the app root). Idempotent: each patch has a marker.
 * Usage: php deploy/rates-patch.php [--only=delhivery,shipclues,shipment,routes,sidebar]
 * Exits 1 (and changes nothing in that file) when a required anchor is missing.
 */
$only = null;
foreach ($argv as $a) {
    if (str_starts_with($a, '--only=')) {
        $only = explode(',', substr($a, 7));
    }
}
$want = fn ($n) => $only === null || in_array($n, $only, true);
$failed = false;

function edit(string $file, string $marker, callable $fn, bool $required = true): void
{
    global $failed;
    if (!file_exists($file)) {
        echo "skip (missing): $file\n";
        return;
    }
    $s = file_get_contents($file);
    if (strpos($s, $marker) !== false) {
        echo "already patched: $file\n";
        return;
    }
    $out = $fn($s);
    if ($out === null) {
        if ($required) {
            fwrite(STDERR, "anchor not found: $file\n");
            $failed = true;
        } else {
            echo "left as is (anchor not found): $file\n";
        }
        return;
    }
    file_put_contents($file, $out);
    echo "patched: $file\n";
}

/** preg_replace that returns null when nothing matched. */
function rep(string $re, string $to, string $s, int $limit = -1): ?string
{
    $n = 0;
    $r = preg_replace($re, $to, $s, $limit, $n);
    return $n ? $r : null;
}

// ---------------------------------------------------------------- Delhivery Surface / Air
if ($want('delhivery')) {
    foreach (['app/Services/DelhiveryB2CService.php', 'app/Services/DelhiveryB2CServiceExpress.php'] as $f) {
        edit($f, 'RATE-MANAGER-PRICE', function ($s) {
            $s = rep(
                '/(\$charges = \$calculateDelhivery(?:Air)?Charge\(\$zonePricing\);)/',
                "$1\n        // RATE-MANAGER-PRICE: first 500 g + additional 500 g, COD, + 18% GST (Admin > Rate Manager)\n" .
                "        \$charges = \\App\\Support\\RateCard::price(\$zonePricing, (float) \$weight, \$paymentType === 'COD', (float) \$orderAmount) ?? \$charges;",
                $s
            );
            if ($s === null) {
                return null;
            }
            // The seller pays exactly the rate card price (no hidden +5)
            $s2 = rep("/(\\\$charges\\['courierCharge'\\])\\s*\\+\\s*5,/", '$1,', $s);
            return $s2 ?? $s;
        });
    }
}

// ---------------------------------------------------------------- Shipclues (server copy with local edits)
if ($want('shipclues')) {
    edit('app/Services/ShipcluesService.php', 'RATE-MANAGER-PRICE', function ($s) {
        // remember order / seller / zone at each entry point (next to the Courier Status hook)
        $s = rep('/(\$this->scRememberSeller\(\$params\);)/', "$1 \\App\\Support\\ShipRate::remember(\$params);", $s);
        if ($s === null) {
            return null;
        }
        // listing price: rate card when set for this courier, else the margin pricing already above
        $s = rep(
            '/(\$codSeller\s*=\s*\(\$cost > 0 && \$codCost > 0\)[^;]*;)/',
            "$1\n        // RATE-MANAGER-PRICE: zone rate card price when set for this courier (Admin > Rate Manager)\n" .
            "        if (\$__rc = \\App\\Support\\ShipRate::shipcluesPrice(\$r)) {\n" .
            "            \$price = \$__rc['courierCharge'];\n" .
            "            \$codSeller = \$__rc['codCharge'] > 0 ? round(\$__rc['codCharge'] * 1.18, 2) : 0;\n" .
            "        }",
            $s, 1
        );
        if ($s === null) {
            return null;
        }
        // booking: wallet check + debit use the same rate card price
        $hook = "\n            if (isset(\$rate) && is_array(\$rate) && (\$__rc = \\App\\Support\\ShipRate::shipcluesPrice(\$rate))) {\n" .
                "                \$sellerCharge = \$__rc['courierCharge'];\n" .
                "            }";
        $s2 = rep('/(\$sellerCharge = \$this->sellerPrice\(\(float\) \(\$rate\[\'total_charges\'\] \?\? 0\)\);)/', '$1' . $hook, $s, 1);
        $s = $s2 ?? $s;
        $s = rep('/(\n(\s*)\$order->seller_amount_walate = \$sellerCharge;)/', $hook . '$1', $s, 1);
        return $s;
    });
}

// ---------------------------------------------------------------- Seller ship screen: server-side price + debit
if ($want('shipment')) {
    edit('app/Http/Controllers/selleradmin/ShipmentController.php', 'RATE-MANAGER-VERIFY', function ($s) {
        // single order
        $i = strpos($s, 'public function assignCourier(Request $request)');
        if ($i === false) {
            return null;
        }
        $anchor = '$key = \'courier.\' . $logisticProvider->code;';
        $j = strpos($s, $anchor, $i);
        $next = strpos($s, 'public function ', $i + 20);
        if ($j === false || ($next !== false && $j > $next)) {
            return null;
        }
        $single = $anchor . "\n\n" .
            "        // RATE-MANAGER-VERIFY: recompute the price on the server; never trust the amount sent by the browser\n" .
            "        \$__p = \\App\\Support\\ShipRate::verifiedCharge((int) \$orderId, \$logisticProvider, \$provider_name ?? null, \$serviceability_id ?? null);\n" .
            "        if (\$__p === false) {\n" .
            "            return redirect()->route('seller.order')->with('error', 'This courier is not available for this order at your rates any more. Please choose the courier again.');\n" .
            "        }\n" .
            "        if (is_array(\$__p)) {\n" .
            "            \$courierCharge = \$__p['courierCharge'];\n" .
            "            \$freightCharges = \$__p['freightCharges'];\n" .
            "            \$codCharges = \$__p['codCharge'];\n" .
            "        }";
        $s = substr($s, 0, $j) . $single . substr($s, $j + strlen($anchor));

        // bulk
        $i = strpos($s, 'public function assignCourier_bulk(Request $request)');
        if ($i !== false) {
            $next = strpos($s, 'public function ', $i + 20);
            $body = substr($s, $i, ($next === false ? strlen($s) : $next) - $i);
            $line = '$order->seller_amount_walate = $courierCharge;';
            $k = strpos($body, $line);
            if ($k === false) {
                return null;
            }
            $bulk = "// RATE-MANAGER-VERIFY: price each order on the server\n" .
                "                \$__p = \\App\\Support\\ShipRate::verifiedCharge((int) \$orderId, \$logisticProvider, \$provider_name ?? null, \$serviceability_id ?? null);\n" .
                "                if (\$__p === false) {\n" .
                "                    \$failureCount++;\n" .
                "                    \$results[] = \"Order ID {\$orderId}: Failed - courier not available for this order at your rates\";\n" .
                "                    continue;\n" .
                "                }\n" .
                "                \$order->seller_amount_walate = is_array(\$__p) ? \$__p['courierCharge'] : \$courierCharge;";
            $body = substr($body, 0, $k) . $bulk . substr($body, $k + strlen($line));
            $s = substr($s, 0, $i) . $body . ($next === false ? '' : substr($s, $next));
        }
        return $s;
    });
}

// ---------------------------------------------------------------- Admin routes
if ($want('routes')) {
    edit('routes/admin.php', 'rate.manager.upload', function ($s) {
        $a = "Route::post('pricesetting/courier-remove', 'removeCourier')->name('pricesetting.courier.remove')->middleware('isAllow:104,can_add');";
        $p = strpos($s, $a);
        if ($p === false) {
            return null;
        }
        $close = strpos($s, '});', $p);
        if ($close === false) {
            return null;
        }
        $block = "});\n    // ----------------------- Rate Manager (zone rate card + Excel template) -----------------------\n" .
            "    Route::controller(\\App\\Http\\Controllers\\admin\\RateManagerController::class)->group(function () {\n" .
            "        Route::get('rate-manager', 'index')->name('rate.manager')->middleware('isAllow:104,can_add');\n" .
            "        Route::post('rate-manager/save', 'save')->name('rate.manager.save')->middleware('isAllow:104,can_add');\n" .
            "        Route::get('rate-manager/template', 'template')->name('rate.manager.template')->middleware('isAllow:104,can_add');\n" .
            "        Route::post('rate-manager/upload', 'upload')->name('rate.manager.upload')->middleware('isAllow:104,can_add');\n" .
            "    });";
        return substr($s, 0, $close) . $block . substr($s, $close + 3);
    });
}

// ---------------------------------------------------------------- Sidebar link (best effort)
if ($want('sidebar')) {
    edit('resources/views/partial/sidebar.blade.php', "route('rate.manager')", function ($s) {
        $a = "<li class=\"menu @routeis('active.slebs') active @endrouteis\">";
        $p = strpos($s, $a);
        if ($p === false) {
            return null;
        }
        $li = "<li class=\"menu @routeis('rate.manager') active @endrouteis\">\n" .
            "            <a href=\"{{ route('rate.manager') }}\" class=\"dropdown-toggle\">\n" .
            "                <div class=\"\">\n" .
            "                    <i class=\"fa-solid fa-file-invoice-dollar\"></i>\n" .
            "                    <span>Rate Manager</span>\n" .
            "                </div>\n" .
            "            </a>\n" .
            "        </li>\n        ";
        return substr($s, 0, $p) . $li . substr($s, $p);
    }, false);
}

exit($failed ? 1 : 0);
