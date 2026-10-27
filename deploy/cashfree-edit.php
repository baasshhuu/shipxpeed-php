<?php
/**
 * Targeted, idempotent edits for Cashfree PG wallet recharge (no git patch — works on servers with local edits).
 * Run from the Laravel root:  php deploy/cashfree-edit.php
 * Exit code 0 = ok, 1 = a required edit could not be made.
 */
$fail = false;
function say($m) { echo $m, PHP_EOL; }
function load($f) { if (!is_file($f)) { say("MISSING: $f"); return null; } return file_get_contents($f); }

/* ---------- routes/web.php ---------- */
$f = 'routes/web.php';
if (($s = load($f)) === null) { $fail = true; } else {
    $out = [];
    foreach (preg_split('/(?<=\n)/', $s) as $line) {
        $code = ltrim($line);
        $isComment = str_starts_with($code, '//');
        if (!$isComment && !str_contains($line, 'WalletRechargeController') && (
            str_contains($line, "->name('recharge.add')") ||
            str_contains($line, "'redirectToPayU'") ||
            str_contains($line, "'payuSuccess_pesa'") ||
            str_contains($line, "'payuFailure_pesa'") ||
            str_contains($line, "'payuSuccess'") ||
            str_contains($line, "'payuFailure'")
        )) {
            continue; // drop old PayU / old recharge route
        }
        $out[] = $line;
    }
    $s = implode('', $out);
    if (!str_contains($s, 'WalletRechargeController')) {
        $s = rtrim($s) . "\n\n// Seller wallet recharge — Cashfree Payment Gateway (PayU removed)\n"
            . "Route::post('/recharge', [\\App\\Http\\Controllers\\selleradmin\\WalletRechargeController::class, 'start'])->middleware('auth:seller')->name('recharge.add');\n"
            . "Route::get('/seller/recharge/cashfree/return', [\\App\\Http\\Controllers\\selleradmin\\WalletRechargeController::class, 'handleReturn'])->name('seller.recharge.cashfree.return');\n"
            . "Route::post('/webhooks/cashfree/pg', [\\App\\Http\\Controllers\\selleradmin\\WalletRechargeController::class, 'webhook'])->name('cashfree.pg.webhook');\n";
    }
    file_put_contents($f, $s);
    say("OK  $f");
}

/* ---------- CSRF exceptions ---------- */
$f = 'app/Http/Middleware/VerifyCsrfToken.php';
if (($s = load($f)) === null) { $fail = true; } else {
    $s = preg_replace("/^[ \t]*'\/?pesa-payment-(success|failure)-new\/\*',[ \t]*\r?\n/m", '', $s);
    if (!str_contains($s, 'webhooks/cashfree/')) {
        $n = 0;
        $s = preg_replace('/(protected\s+\$except\s*=\s*\[)/', "$1\n        'webhooks/cashfree/*',", $s, 1, $n);
        if (!$n) { say("FAIL $f: \$except array not found"); $fail = true; }
    }
    file_put_contents($f, $s);
    say("OK  $f");
}

/* ---------- Recharge model fillable ---------- */
$f = 'app/Models/Recharge.php';
if (($s = load($f)) === null) { $fail = true; } else {
    if (!str_contains($s, 'gateway_order_id') && str_contains($s, '$fillable')) {
        $n = 0;
        $s = preg_replace('/(\$fillable\s*=\s*\[)/', "$1'gateway','gateway_order_id','gateway_payment_id','gateway_status','paid_at',", $s, 1, $n);
        if (!$n) { say("FAIL $f: fillable not found"); $fail = true; }
    }
    file_put_contents($f, $s);
    say("OK  $f");
}

/* ---------- config/cashfree.php: add 'pg' block ---------- */
$f = 'config/cashfree.php';
$pg = "\n    // Cashfree Payment Gateway — seller wallet recharge (separate keys from KYC)\n"
    . "    'pg' => [\n"
    . "        'app_id'      => env('CASHFREE_PG_APP_ID'),\n"
    . "        'secret_key'  => env('CASHFREE_PG_SECRET_KEY'),\n"
    . "        'environment' => env('CASHFREE_PG_ENV', 'sandbox'),\n"
    . "        'min_amount'  => env('WALLET_RECHARGE_MIN', 1),\n"
    . "    ],\n\n";
$s = is_file($f) ? file_get_contents($f) : "<?php\n\nreturn [\n];\n";
if (!preg_match("/'pg'\s*=>/", $s)) {
    $p = strrpos($s, '];');
    if ($p === false) { say("FAIL $f: closing ]; not found"); $fail = true; }
    else { $s = substr($s, 0, $p) . $pg . substr($s, $p); }
}
file_put_contents($f, $s);
say("OK  $f");

/* ---------- remove PayU methods (best effort; they are unreachable once routes are gone) ---------- */
function removeMethod(string $s, string $name, bool &$changed): string {
    if (!preg_match('/\n([ \t]*)public\s+function\s+' . preg_quote($name, '/') . '\s*\(/', $s, $m, PREG_OFFSET_CAPTURE)) return $s;
    $start = $m[0][1] + 1;
    $brace = strpos($s, '{', $start);
    if ($brace === false) return $s;
    $depth = 0; $len = strlen($s);
    for ($i = $brace; $i < $len; $i++) {
        if ($s[$i] === '{') $depth++;
        elseif ($s[$i] === '}') { $depth--; if ($depth === 0) { $changed = true; return substr($s, 0, $start) . substr($s, $i + 1); } }
    }
    return $s;
}
foreach (['app/Http/Controllers/selleradmin/Dashboard.php', 'app/Http/Controllers/selleradmin/ReversepickupController.php'] as $f) {
    if (!is_file($f)) continue;
    $s = file_get_contents($f); $changed = false;
    foreach (['redirectToPayU', 'redirectToPayUold', 'payuSuccess_pesa', 'payuFailure_pesa'] as $m) {
        $s = removeMethod($s, $m, $changed);
    }
    if ($changed) {
        $tmp = tempnam(sys_get_temp_dir(), 'cf');
        file_put_contents($tmp, $s);
        exec('php -l ' . escapeshellarg($tmp) . ' 2>&1', $o, $rc);
        unlink($tmp);
        if ($rc === 0) { file_put_contents($f, $s); say("OK  $f (PayU methods removed)"); }
        else { say("SKIP $f (left as is; PayU methods are unreachable anyway)"); }
    }
}

exit($fail ? 1 : 0);
