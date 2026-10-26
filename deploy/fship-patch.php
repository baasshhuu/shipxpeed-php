<?php
/**
 * Inserts the Fship hooks into existing panel files at stable landmarks.
 *
 * The live server's files carry local edits, so nothing here relies on exact
 * line context: every hook is placed next to a landmark found with PHP's own
 * tokenizer or a whitespace-tolerant pattern. A file that already mentions
 * Fship is left alone, so running this twice is harmless.
 *
 * Nothing is written unless every REQUIRED hook can be placed. Optional hooks
 * that cannot be placed are reported and skipped (the feature still works).
 *
 * Usage: php fship-patch.php /var/www/shipxpeed      exit 0 = ok, 2 = required hook missing
 */

$app = rtrim($argv[1] ?? getcwd(), '/');
if (!is_dir($app) || !chdir($app)) {
    fwrite(STDERR, "FAIL: app dir not found: $app\n");
    exit(2);
}

$files = [];      // path => [original, current]
$required = [];   // required hooks that could not be placed
$warnings = [];

function load(string $f): ?string
{
    global $files;
    if (!isset($files[$f])) {
        if (!is_file($f)) {
            return null;
        }
        $c = file_get_contents($f);
        $files[$f] = [$c, $c];
    }
    return $files[$f][1];
}

function store(string $f, string $c): void
{
    global $files;
    $files[$f][1] = $c;
}

/** True when the file already contains Fship code (exact markers, not just the letters "fship"). */
function has_fship(string $s): bool
{
    return (bool) preg_match("~'fship'|'fship_'|FshipService|courier\\.fship|fship:track|admin\\.fship\\.|trackWithFship~", $s);
}

function need(string $msg): void
{
    global $required;
    $required[] = $msg;
    echo "  MISSING (required): $msg\n";
}

function warn(string $msg): void
{
    global $warnings;
    $warnings[] = $msg;
    echo "  skipped (optional): $msg\n";
}

function line_start(string $s, int $p): int
{
    $ls = strrpos(substr($s, 0, $p), "\n");
    return $ls === false ? 0 : $ls + 1;
}

function line_end(string $s, int $p): int
{
    $le = strpos($s, "\n", $p);
    return $le === false ? strlen($s) : $le + 1;
}

function indent_at(string $s, int $p): string
{
    preg_match('/^[ \t]*/', substr($s, line_start($s, $p)), $m);
    return $m[0];
}

function is_comment_line(string $s, int $p): bool
{
    $prefix = ltrim(substr($s, line_start($s, $p), $p - line_start($s, $p)));
    return str_starts_with($prefix, '//') || str_starts_with($prefix, '#') || str_starts_with($prefix, '*') || str_starts_with($prefix, '/*');
}

/** First occurrence of $needle in [$from, $to] that is not on a commented-out line. */
function find_code(string $s, string $needle, int $from = 0, ?int $to = null): ?int
{
    $at = $from;
    while (($p = strpos($s, $needle, $at)) !== false) {
        if ($to !== null && $p > $to) {
            return null;
        }
        if (!is_comment_line($s, $p)) {
            return $p;
        }
        $at = $p + 1;
    }
    return null;
}

/** First regex match (offset, text, groups) that is not on a commented-out line. */
function find_re(string $s, string $re, int $from = 0, ?int $to = null): ?array
{
    $at = $from;
    while (preg_match($re, $s, $m, PREG_OFFSET_CAPTURE, $at)) {
        $p = $m[0][1];
        if ($to !== null && $p > $to) {
            return null;
        }
        if (!is_comment_line($s, $p)) {
            return $m;
        }
        $at = $p + 1;
    }
    return null;
}

/** Byte offsets [open brace, close brace] of a named function's body, using PHP's tokenizer. */
function fn_body(string $code, string $name): ?array
{
    $pos = 0;
    $state = null;
    $depth = 0;
    $open = null;
    foreach (token_get_all($code) as $t) {
        $text = is_array($t) ? $t[1] : $t;
        $id = is_array($t) ? $t[0] : null;
        if ($state === 'body') {
            if ($text === '{' || $text === '${') {
                $depth++;
            } elseif ($text === '}') {
                $depth--;
                if ($depth === 0) {
                    return [$open, $pos];
                }
            }
        } elseif ($id === T_FUNCTION) {
            $state = 'name';
        } elseif ($state === 'name') {
            if ($id === T_STRING) {
                $state = ($text === $name) ? 'brace' : null;
            } elseif ($text === '(') {
                $state = null; // closure
            }
        } elseif ($state === 'brace') {
            if ($text === '{') {
                $state = 'body';
                $open = $pos;
                $depth = 1;
            } elseif ($text === ';') {
                $state = null; // abstract / interface
            }
        }
        $pos += strlen($text);
    }
    return null;
}

/** Insert $block (ending in "\n") on its own lines just before a body's closing brace. */
function insert_before_close(string $s, array $body, string $block): string
{
    $close = $body[1];
    $ls = line_start($s, $close);
    if (trim(substr($s, $ls, $close - $ls)) === '') {
        return substr($s, 0, $ls) . $block . substr($s, $ls);
    }
    return substr($s, 0, $close) . "\n" . $block . substr($s, $close);
}

function lines(string $indent, array $rows): string
{
    $out = '';
    foreach ($rows as $r) {
        $out .= ($r === '' ? '' : $indent . $r) . "\n";
    }
    return $out;
}

echo "Placing Fship hooks:\n";

/* ---------------------------------------------------------------- */
/* 1. config/couriers.php - courier code => service (required)       */
/* ---------------------------------------------------------------- */
$f = 'config/couriers.php';
$s = load($f);
if ($s === null) {
    need("$f not found");
} elseif (has_fship($s)) {
    echo "  already has Fship: $f\n";
} else {
    // first entry of the returned array: never depends on the last entry's trailing comma
    $m = find_re($s, '~return\s*\[[ \t]*\n~');
    if (!$m) {
        need("$f: return [ not found");
    } else {
        $at = $m[0][1] + strlen($m[0][0]);
        $s = substr($s, 0, $at) . "    'fship' => \\App\\Services\\FshipService::class,\n" . substr($s, $at);
        store($f, $s);
        echo "  ok: $f\n";
    }
}

/* ---------------------------------------------------------------- */
/* 2. CourierServiceProvider - explicit binding (optional)           */
/* ---------------------------------------------------------------- */
$f = 'app/Providers/CourierServiceProvider.php';
$s = load($f);
if ($s === null) {
    warn("$f not found");
} elseif (has_fship($s)) {
    echo "  already has Fship: $f\n";
} elseif (!($b = fn_body($s, 'register'))) {
    warn("$f: register() not found");
} else {
    $s = insert_before_close($s, $b, lines('        ', [
        '',
        '// Fship aggregator (bound here too so it works even when config is cached)',
        "\$this->app->bind('courier.fship', fn() => new \\App\\Services\\FshipService);",
    ]));
    store($f, $s);
    echo "  ok: $f\n";
}

/* ---------------------------------------------------------------- */
/* 3. routes/admin.php - admin page (required)                       */
/* ---------------------------------------------------------------- */
$f = 'routes/admin.php';
$s = load($f);
$routes = lines('    ', [
    '',
    '// Fship aggregator: status, courier list, rate test, API log',
    "Route::get('fship', [\\App\\Http\\Controllers\\admin\\FshipController::class, 'index'])->name('admin.fship.index');",
    "Route::post('fship/settings', [\\App\\Http\\Controllers\\admin\\FshipController::class, 'saveKey'])->name('admin.fship.settings');",
    "Route::post('fship/rate-test', [\\App\\Http\\Controllers\\admin\\FshipController::class, 'rateTest'])->name('admin.fship.rate-test');",
    "Route::get('fship/log/{id}', [\\App\\Http\\Controllers\\admin\\FshipController::class, 'logShow'])->whereNumber('id')->name('admin.fship.log');",
]);
if ($s === null) {
    need("$f not found");
} elseif (has_fship($s)) {
    echo "  already has Fship: $f\n";
} else {
    $at = null;
    if (($p = find_code($s, "->name('courier.master.update');")) !== null) {
        $at = line_end($s, $p);
    } elseif ($m = find_re($s, "~Route::middleware\(\[\s*'auth'\s*,\s*'permission'[^\]]*\]\)\s*->\s*group\(\s*function\s*\(\)\s*\{[^\n]*\n~")) {
        $at = $m[0][1] + strlen($m[0][0]);
    }
    if ($at === null) {
        need("$f: admin route group not found");
    } else {
        $s = substr($s, 0, $at) . $routes . substr($s, $at);
        store($f, $s);
        echo "  ok: $f\n";
    }
}

/* ---------------------------------------------------------------- */
/* 4. selleradmin/ShipmentController - booking from the seller panel */
/*    single booking (required), bulk booking (optional, as a pair)  */
/* ---------------------------------------------------------------- */
$f = 'app/Http/Controllers/selleradmin/ShipmentController.php';
$s = load($f);
if ($s === null) {
    need("$f not found");
} elseif (has_fship($s)) {
    echo "  already has Fship: $f\n";
} else {
    $b = fn_body($s, 'assignCourier');
    $p = $b ? find_code($s, '$response = app($key)->assignOrder($params);', $b[0], $b[1]) : null;
    if ($p === null) {
        need("$f: booking call in assignCourier() not found");
    } else {
        $i = indent_at($s, $p);
        $before = lines($i, [
            '// Fship: book with the courier the seller picked (Fship courier id + name from the serviceability list)',
            "if (\$logisticProvider && \$logisticProvider->code === 'fship') {",
            '    $params = [',
            "        'order_id' => \$orderId,",
            "        'provider_name' => \$provider_name,",
            "        'serviceability_id' => \$serviceability_id,",
            '    ];',
            "    \$fshipOrder = \\App\\Models\\Order::where('id', \$orderId)->first();",
            '    if ($fshipOrder) {',
            '        $fshipOrder->seller_amount_walate = $courierCharge;',
            '        $fshipOrder->save();',
            '    }',
            '}',
        ]);
        $after = lines($i, [
            "if ((\$response['couriername'] ?? null) === 'fship') {",
            "    if (!empty(\$response['status']) && !empty(\$response['awb_number'])) {",
            "        return redirect()->route('seller.order')->with('success', 'Order successfully assigned! AWB: ' . \$response['awb_number'] . (!empty(\$response['pickup_message']) ? ' (' . \$response['pickup_message'] . ')' : ''));",
            '    }',
            "    return redirect()->route('seller.order')->with('error', \\App\\Services\\FshipService::sellerMessage(\$response['message'] ?? null, 'Failed to assign courier. Please try again later.'));",
            '}',
        ]);
        $ls = line_start($s, $p);
        $le = line_end($s, $p);
        $s = substr($s, 0, $ls) . $before . substr($s, $ls, $le - $ls) . $after . substr($s, $le);
        echo "  ok: $f (single booking)\n";

        // bulk booking: params before the call + response handling, both or neither
        $b2 = fn_body($s, 'assignCourier_bulk');
        $p2 = $b2 ? find_code($s, '$response = app($key)->assignOrderbulk($params);', $b2[0], $b2[1]) : null;
        $b3 = fn_body($s, 'processBulkCourierResponse');
        if ($p2 === null || !$b3) {
            warn("$f: bulk booking landmarks not found (single booking still works)");
        } else {
            // insert the later landmark first so the earlier offset stays valid
            $respAt = line_end($s, $b3[0]);
            $resp = lines('        ', [
                '// Fship response',
                "if ((\$response['couriername'] ?? null) === 'fship') {",
                "    return !empty(\$response['status']) && !empty(\$response['awb_number'])",
                "        ? ['success' => true, 'awb' => \$response['awb_number']]",
                "        : ['success' => false, 'message' => \\App\\Services\\FshipService::sellerMessage(\$response['message'] ?? null, 'Courier booking failed')];",
                '}',
                '',
            ]);
            $i2 = indent_at($s, $p2);
            $bulk = lines($i2, [
                "if (\$logisticProvider->code === 'fship') {",
                "    \$params = ['order_id' => \$orderId, 'provider_name' => \$provider_name, 'serviceability_id' => \$serviceability_id];",
                '}',
            ]);
            $ls2 = line_start($s, $p2);
            if ($respAt > $ls2) {
                $s = substr($s, 0, $respAt) . $resp . substr($s, $respAt);
                $s = substr($s, 0, $ls2) . $bulk . substr($s, $ls2);
            } else {
                $s = substr($s, 0, $ls2) . $bulk . substr($s, $ls2);
                $s = substr($s, 0, $respAt) . $resp . substr($s, $respAt);
            }
            echo "  ok: $f (bulk booking)\n";
        }
        store($f, $s);
    }
}

/* ---------------------------------------------------------------- */
/* 5. selleradmin/CourierController - real courier name + logo       */
/*    for Fship rows, and use this server's own courier API (opt.)   */
/* ---------------------------------------------------------------- */
$f = 'app/Http/Controllers/selleradmin/CourierController.php';
$s = load($f);
if ($s === null) {
    warn("$f not found");
} elseif (has_fship($s) || str_contains($s, 'CourierBrand::isAggregator')) {
    echo "  already handles Fship rows: $f\n";
} else {
    $arm = "(\$localProvider && \$localProvider->code === 'fship') => (class_exists(\\App\\Support\\CourierBrand::class) ? \\App\\Support\\CourierBrand::sellerLogo(\$courier['courierName'] ?? '') : \\App\\Services\\FshipService::logoFor(\$courier['courierName'] ?? '', \$localProvider->logo)),";
    $logos = 0;
    $at = 0;
    while (($p = find_code($s, "'provider_logo' => match (true) {", $at)) !== null) {
        $le = line_end($s, $p);
        $row = indent_at($s, $p) . '    ' . $arm . "\n";
        $s = substr($s, 0, $le) . $row . substr($s, $le);
        $at = $le + strlen($row);
        $logos++;
    }
    $names = 0;
    $s = preg_replace_callback(
        "~^(?![ \t]*//)([^\n]*'provider_name'\s*=>\s*\(\\\$localProvider\s*&&\s*)in_array\((\\\$localProvider->name,\s*\[[^\]]*\])\)\)~m",
        function ($m) use (&$names) {
            $names++;
            return $m[1] . '(in_array(' . $m[2] . ") || \$localProvider->code === 'fship'))";
        },
        $s
    );
    $hosts = 0;
    $s = str_replace('https://shipxpeed.com/api/', 'https://shipxpeed.tech/api/', $s, $hosts);
    if ($logos === 0 && $names === 0) {
        warn("$f: courier list landmarks not found (Fship rows may show the aggregator name)");
    } else {
        store($f, $s);
        echo "  ok: $f ($logos logo, $names name hooks, $hosts API host fixes)\n";
    }
}

/* ---------------------------------------------------------------- */
/* 6. Kernel - tracking sync every 30 minutes (optional)             */
/* ---------------------------------------------------------------- */
$f = 'app/Console/Kernel.php';
$s = load($f);
if ($s === null) {
    warn("$f not found");
} elseif (has_fship($s)) {
    echo "  already has Fship: $f\n";
} elseif (!($b = fn_body($s, 'schedule'))) {
    warn("$f: schedule() not found (tracking sync not scheduled)");
} else {
    $s = insert_before_close($s, $b, lines('        ', [
        '',
        '// Fship: pull tracking status for open shipments',
        "\$schedule->command('fship:track')->everyThirtyMinutes()->withoutOverlapping(25)->runInBackground()->appendOutputTo(storage_path('logs/fship-track.log'));",
    ]));
    store($f, $s);
    echo "  ok: $f\n";
}

/* ---------------------------------------------------------------- */
/* 7. Frontend/TrackController - public tracking page (optional)     */
/* ---------------------------------------------------------------- */
$f = 'app/Http/Controllers/Frontend/TrackController.php';
$s = load($f);
if ($s === null) {
    warn("$f not found");
} elseif (has_fship($s)) {
    echo "  already has Fship: $f\n";
} else {
    $case = find_re($s, "~case\s+'delhivery_b2c'\s*:\s*\n[ \t]*return\s+\\\$this->trackWithDelhiveryB2C\(\\\$awb\);[ \t]*\n~");
    $def = find_re($s, "~^[ \t]*(?:(?:private|protected|public)\s+)?function\s+trackWithDelhiveryB2C\s*\(~m");
    if (!$case || !$def) {
        warn("$f: tracking landmarks not found");
    } else {
        $method = lines('', [
            'private function trackWithFship($awb)',
            '{',
            '    $t = app(\\App\\Services\\FshipService::class)->track($awb);',
            "    if (empty(\$t['status'])) {",
            "        return redirect()->back()->withErrors(['error' => 'Tracking not available yet.']);",
            '    }',
            '    // same shape the tracking page renders for Delhivery',
            '    $trackWithDelhiveryB2C = [',
            "        'awb_number'   => \$t['awb_number'],",
            "        'order_number' => \$t['order_number'],",
            "        'order_id'     => \$t['order_id'],",
            "        'status'       => \$t['raw_status'] ?? 'N/A',",
            "        'created'      => \$t['created'],",
            "        'history'      => \$t['history'],",
            '    ];',
            "    return view('frontend.website.pages.trackorder', compact('trackWithDelhiveryB2C'));",
            '}',
            '',
        ]);
        $caseEnd = $case[0][1] + strlen($case[0][0]);
        $ci = indent_at($s, $case[0][1]);
        $caseText = lines($ci, ["case 'fship':", '    return $this->trackWithFship($awb);']);
        $defAt = line_start($s, $def[0][1]);
        if ($defAt > $caseEnd) {
            $s = substr($s, 0, $defAt) . $method . substr($s, $defAt);
            $s = substr($s, 0, $caseEnd) . $caseText . substr($s, $caseEnd);
        } else {
            $s = substr($s, 0, $caseEnd) . $caseText . substr($s, $caseEnd);
            $s = substr($s, 0, $defAt) . $method . substr($s, $defAt);
        }
        store($f, $s);
        echo "  ok: $f\n";
    }
}

/* ---------------------------------------------------------------- */
/* 8. Shipxpeedapi/NdrController - NDR actions via seller API (opt.) */
/* ---------------------------------------------------------------- */
$f = 'app/Http/Controllers/Shipxpeedapi/NdrController.php';
$s = load($f);
if ($s === null) {
    warn("$f not found");
} elseif (has_fship($s)) {
    echo "  already has Fship: $f\n";
} elseif (!($m = find_re($s, "~return \\\$this->parcelXNdrReattempt\(\\\$request,\s*\\\$awb,\s*\\\$sellerid\);[ \t]*\n(?=[ \t]*\}\s*else\s*\{)~"))) {
    warn("$f: NDR landmark not found");
} else {
    $at = $m[0][1] + strlen($m[0][0]);
    $block = lines('        ', [
        "} elseif (\$courierId === 'fship') {",
        "    \$action = in_array(\$request->action, ['re-attempt', 'change-address', 'change-phone', 'rto'], true) ? \$request->action : 're-attempt';",
        '    return app(\\App\\Services\\FshipService::class)->ndrAction($order, $action, [',
        "        'reattempt_date' => \$request->reattempt_date,",
        "        'address'        => \$request->address,",
        "        'phone'          => \$request->phone,",
        "        'name'           => \$request->name,",
        "        'landmark'       => \$request->landmark,",
        "        'remark'         => \$request->remark,",
        '    ]);',
    ]);
    $s = substr($s, 0, $at) . $block . substr($s, $at);
    store($f, $s);
    echo "  ok: $f\n";
}

/* ---------------------------------------------------------------- */
/* 9. Shipxpeedapi/CancelshipmentController - cancel via API (opt.)  */
/* ---------------------------------------------------------------- */
$f = 'app/Http/Controllers/Shipxpeedapi/CancelshipmentController.php';
$s = load($f);
if ($s === null) {
    warn("$f not found");
} elseif (has_fship($s)) {
    echo "  already has Fship: $f\n";
} else {
    $bd = fn_body($s, 'detectCourierService');
    $map = $bd ? find_re($s, '~\$courierMappings\s*=\s*\[[^\n]*\n~', $bd[0], $bd[1]) : null;
    $mapEnd = $map ? $map[0][1] + strlen($map[0][0]) : null;   // first entry: no trailing-comma dependency
    $bc = fn_body($s, 'cancelShipmentByCourier');
    $sw = $bc ? find_re($s, '~switch\s*\(\s*\$courier\s*\)\s*\{[^\n]*\n~', $bc[0], $bc[1]) : null;
    if ($mapEnd === null || !$sw) {
        warn("$f: cancel landmarks not found");
    } else {
        $swAt = $sw[0][1] + strlen($sw[0][0]);
        $case = lines('            ', [
            "case 'fship':",
            "    \$r = app('courier.fship')->cancelShipment(\$awb);",
            "    return ['success' => !empty(\$r['status']), 'message' => \$r['message'] ?? null, 'data' => []];",
            '',
        ]);
        $mapLine = $mapEnd;
        $entry = "            'fship' => 'fship',\n";
        if ($swAt > $mapLine) {
            $s = substr($s, 0, $swAt) . $case . substr($s, $swAt);
            $s = substr($s, 0, $mapLine) . $entry . substr($s, $mapLine);
        } else {
            $s = substr($s, 0, $mapLine) . $entry . substr($s, $mapLine);
            $s = substr($s, 0, $swAt) . $case . substr($s, $swAt);
        }
        store($f, $s);
        echo "  ok: $f\n";
    }
}

/* ---------------------------------------------------------------- */
/* 10. Shipxpeedapi/ServiceabilityController - seller API (optional) */
/*     rates in both provider loops; create-shipment with fship_<id> */
/* ---------------------------------------------------------------- */
$f = 'app/Http/Controllers/Shipxpeedapi/ServiceabilityController.php';
$s = load($f);
if ($s === null) {
    warn("$f not found");
} elseif (has_fship($s)) {
    echo "  already has Fship: $f\n";
} else {
    $cases = 0;
    $s = preg_replace_callback(
        "~^([ \t]*)case\s+'shiprocket'\s*:[ \t]*\n([ \t]*)\\\$providerResults\s*=\s*\\\$this->callshiprocketAPI\((\\\$\w+)\);~m",
        function ($m) use (&$cases) {
            $cases++;
            return $m[1] . "case 'fship':\n"
                . $m[2] . "\$providerResults = app('courier.fship')->getRatesForParams(" . $m[3] . ");\n"
                . $m[2] . "break;\n"
                . $m[0];
        },
        $s
    );
    echo "  ok: $f ($cases rate hooks)\n";

    // create-shipment: accept courier_id "fship_<id>" and book it (validator + branch together)
    $s2 = $s;
    $validator = 0;
    $s2 = preg_replace('~if\s*\(\s*!in_array\(\$value,\s*\$allowed,\s*true\)~', "if (!in_array(\$value, \$allowed, true) && !str_starts_with((string) \$value, 'fship_')", $s2, 1, $validator);
    if ($validator === 0) {
        $s2 = preg_replace_callback(
            "~'courier_id'\s*=>\s*'nullable\|string\|in:([^']*)'~",
            function ($m) {
                return "'courier_id' => ['nullable', 'string', function (\$attribute, \$value, \$fail) {\n"
                    . "                    \$allowed = explode(',', '" . $m[1] . "');\n"
                    . "                    if (!in_array(\$value, \$allowed, true) && !str_starts_with((string) \$value, 'fship_')) {\n"
                    . "                        \$fail('The selected courier id is invalid.');\n"
                    . "                    }\n"
                    . "                }]";
            },
            $s2,
            1,
            $validator
        );
    }
    $branch = find_re($s2, "~\b(if|elseif)\s*\(\s*isset\(\\\$courierMapping\[\\\$request->courier_id\]\)\s*\)\s*\{~");
    if ($validator === 0 || !$branch) {
        warn("$f: create-shipment landmarks not found (seller API can still list Fship rates)");
    } else {
        $bi = indent_at($s2, $branch[0][1]);
        $fshipBranch = $branch[1][0] . " (str_starts_with((string) \$request->courier_id, 'fship_')) {\n"
            . lines($bi . '    ', [
                '// Fship couriers: courier_id = "fship_<Fship courier id>" (from the serviceability response)',
                "\$assignmentResult = app('courier.fship')->assignOrder([",
                "    'order_id' => \$order->id,",
                "    'serviceability_id' => (int) substr((string) \$request->courier_id, strlen('fship_')),",
                "    'seller_id' => \$sellerid,",
                ']);',
                "\$courierAssigned = !empty(\$assignmentResult['status']);",
                'if ($courierAssigned) {',
                '    $order->refresh();',
                "    \$courierMapping[\$request->courier_id] = \$order->courier_name ?: 'Courier';",
                '}',
            ])
            . $bi . "} elseif (isset(\$courierMapping[\$request->courier_id])) {";
        $s2 = substr($s2, 0, $branch[0][1]) . $fshipBranch . substr($s2, $branch[0][1] + strlen($branch[0][0]));
        $s = $s2;
        echo "  ok: $f (create-shipment)\n";
    }
    store($f, $s);
}

/* ---------------------------------------------------------------- */
/* 11. admin/ZonePriceSettingController - rate-card keys (optional)  */
/* ---------------------------------------------------------------- */
$f = 'app/Http/Controllers/admin/ZonePriceSettingController.php';
$s = load($f);
if ($s === null) {
    warn("$f not found");
} elseif (has_fship($s)) {
    echo "  already has Fship: $f\n";
} else {
    $n = 0;
    $at = 0;
    while (($p = find_code($s, '$LogisticProviders = [', $at)) !== null) {
        $end = find_code($s, '];', $p);
        if ($end === null) {
            break;
        }
        $le = line_end($s, $end);
        $add = lines('        ', [
            '// Fship couriers (live list from the Fship account)',
            '$LogisticProviders = array_merge($LogisticProviders, \\App\\Services\\FshipService::rateCardKeys());',
        ]);
        $s = substr($s, 0, $le) . $add . substr($s, $le);
        $at = $le + strlen($add);
        $n++;
    }
    if ($n === 0) {
        warn("$f: courier list not found");
    } else {
        store($f, $s);
        echo "  ok: $f ($n lists)\n";
    }
}

/* ---------------------------------------------------------------- */
/* 12. Admin sidebar link (optional; hidden unless the route exists) */
/* ---------------------------------------------------------------- */
$f = 'resources/views/partial/sidebar.blade.php';
$s = load($f);
if ($s === null) {
    warn("$f not found");
} elseif (str_contains($s, 'admin.fship.index')) {
    echo "  already has Fship: $f\n";
} else {
    $li = lines('        ', [
        "@if (Route::has('admin.fship.index'))",
        "<li class=\"menu @routeis('admin.fship.index') active @endrouteis\">",
        "    <a href=\"{{ route('admin.fship.index') }}\" class=\"dropdown-toggle\">",
        '        <div class="">',
        '            <i class="fa-solid fa-plug"></i>',
        '            <span>Fship Integration</span>',
        '        </div>',
        '    </a>',
        '</li>',
        '@endif',
    ]);
    $done = false;
    foreach (["@routeis('pricesetting')", "@routeis('zone.price.index')"] as $anchor) {
        $p = strpos($s, $anchor);
        if ($p !== false) {
            $ls = line_start($s, $p);
            $s = substr($s, 0, $ls) . $li . substr($s, $ls);
            store($f, $s);
            echo "  ok: $f\n";
            $done = true;
            break;
        }
    }
    if (!$done) {
        warn("$f: menu landmark not found (page still opens at /fship)");
    }
}

/* ---------------------------------------------------------------- */
if ($required) {
    echo "FAIL: " . count($required) . " required hook(s) could not be placed - no file was changed\n";
    exit(2);
}
$changed = 0;
foreach ($files as $f => [$orig, $cur]) {
    if ($cur !== $orig) {
        file_put_contents($f, $cur);
        $changed++;
    }
}
echo "Hooks placed: $changed file(s) changed" . ($warnings ? ', ' . count($warnings) . ' optional hook(s) skipped' : '') . "\n";
exit(0);
