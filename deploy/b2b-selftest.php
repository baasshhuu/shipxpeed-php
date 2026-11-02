<?php
/**
 * Bigship B2B live self-test: for every courier Bigship offers on a test route,
 *   draft order -> final price -> REAL booking (AWB) -> details -> label -> tracking -> CANCEL.
 * Uses the platform's Bigship account directly; seller wallets are NOT touched.
 * Every call is also written to Admin > Bigship B2B > API log.
 *
 * Usage (on the server):
 *   sudo -u www-data php /tmp/b2b-selftest.php --live            all couriers
 *   sudo -u www-data php /tmp/b2b-selftest.php --live --only=21  one courier id
 * Without --live it stops after the final price (no booking, no charge).
 */
$app = getenv('APP') ?: '/var/www/shipxpeed';
chdir($app);
require $app . '/vendor/autoload.php';
$laravel = require $app . '/bootstrap/app.php';
$laravel->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\BigshipService;

$opts = getopt('', ['live', 'only:']);
$LIVE = isset($opts['live']);
$ONLY = isset($opts['only']) ? array_map('trim', explode(',', $opts['only'])) : null;

$svc = app(BigshipService::class);
if (!$svc->isConfigured()) {
    exit("FAIL: Bigship login is not saved (Admin > Bigship B2B)\n");
}
$login = $svc->login(true);
if (!$login['ok']) {
    exit('FAIL: Bigship login: ' . $login['error'] . "\n");
}
$wallet = $login['data']['userWallet']['Balance'] ?? '?';
echo "Bigship login OK - wallet Rs {$wallet}\n";
echo $LIVE ? "MODE: LIVE (real bookings, cancelled straight after)\n" : "MODE: dry run (stops before booking)\n";

// ---- test shipment ------------------------------------------------------
$pickup = [
    'name' => 'Shipxpeed', 'phone' => '6396697722',
    'address_line1' => 'U-152/3, Gali No. 6, Arvind Nagar, Ghonda', 'address_line2' => 'Near Ghonda',
    'landmark' => 'Arvind Nagar Ghonda', 'city' => 'Delhi', 'state' => 'Delhi', 'pincode' => '110053',
];
$to = ['name' => 'TEST ORDER CANCELLED', 'phone' => '6396697722', 'pincode' => '500001',
       'address' => 'TEST ORDER - CANCELLED - DO NOT PICK UP, Abids Road', 'city' => 'Hyderabad', 'state' => 'Telangana'];
$box = ['count' => 1, 'length' => 30, 'breadth' => 30, 'height' => 30, 'weight' => 10];
$invoiceAmt = 5000; // below the e-way bill limit

// minimal one-page PDF as the invoice file
$pdfPath = sys_get_temp_dir() . '/b2b-test-invoice.pdf';
$txt = 'TEST INVOICE - Shipxpeed B2B self-test - not a real sale';
$objs = [
    '<< /Type /Catalog /Pages 2 0 R >>',
    '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
    '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
    null,
    '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
];
$stream = "BT /F1 14 Tf 60 780 Td ({$txt}) Tj 0 -24 Td (Amount: Rs {$invoiceAmt}) Tj ET";
$objs[3] = '<< /Length ' . strlen($stream) . " >>\nstream\n{$stream}\nendstream";
$pdf = "%PDF-1.4\n";
$off = [];
foreach ($objs as $i => $o) {
    $off[] = strlen($pdf);
    $pdf .= ($i + 1) . " 0 obj\n{$o}\nendobj\n";
}
$x = strlen($pdf);
$pdf .= "xref\n0 " . (count($objs) + 1) . "\n0000000000 65535 f \n";
foreach ($off as $o) {
    $pdf .= sprintf("%010d 00000 n \n", $o);
}
$pdf .= "trailer\n<< /Size " . (count($objs) + 1) . " /Root 1 0 R >>\nstartxref\n{$x}\n%%EOF";
file_put_contents($pdfPath, $pdf);

// ---- couriers on this route ---------------------------------------------
$q = $svc->quote(['from' => $pickup['pincode'], 'to' => $to['pincode'], 'invoice_amount' => $invoiceAmt,
                  'payment' => 'prepaid', 'risk_type_id' => 2, 'boxes' => [$box]], false);
if (!$q['ok']) {
    exit('FAIL: rate calculator: ' . $q['error'] . "\n");
}
$couriers = $q['rates'];
if ($ONLY) {
    $couriers = array_values(array_filter($couriers, fn ($r) => in_array($r['courier_id'], $ONLY, true)));
}
echo 'Rate calculator OK - ' . count($q['rates']) . " couriers on 110053 -> 500001\n";
if (!$couriers) {
    exit("Nothing to test.\n");
}

$wh = $svc->warehouseIdFor(0, $pickup);
if (!$wh['ok']) {
    exit('FAIL: pickup warehouse: ' . $wh['error'] . "\n");
}
echo "Pickup warehouse OK - Bigship id {$wh['id']}\n\n";

$rows = [];
foreach ($couriers as $c) {
    $id = $c['courier_id'];
    $r = ['id' => $id, 'name' => $c['courier_name'], 'quote' => $c['cost'], 'draft' => '-', 'final' => '-',
          'awb' => '-', 'label' => '-', 'track' => '-', 'cancel' => '-', 'note' => ''];
    $ctx = ['awb' => null];
    echo "== {$id} {$c['courier_name']} ==\n";

    $draft = $svc->request('api/outbound/create-order', 'POST', [
        'segment_type' => BigshipService::SEGMENT,
        'MasterOrderPickUpLocation' => $wh['id'], 'MasterOrderReturnLocation' => $wh['id'],
        'MasterOrderDate' => now('UTC')->format('Y-m-d H:i:s'), 'MasterOrderPaymentMode' => 1,
        'OrderInvoiceNo' => 'TEST' . date('ymdHis') . $id, 'MasterOrderInvoiceAmount' => $invoiceAmt,
        'MasterOrderShippingName' => $to['name'], 'MasterOrderShippingMobileNo' => $to['phone'],
        'MasterOrderShippingAddress' => $to['address'], 'MasterOrderShippingZipCode' => $to['pincode'],
        'MasterOrderShippingCity' => $to['city'], 'MasterOrderShippingState' => $to['state'],
        'MasterOrderShippingCountry' => 'India', 'ProductName' => 'Test goods', 'totalNumOfBoxes' => 1,
        'boxes' => [['weight_unit' => 'kg', 'dimension_unit' => 'cm', 'noOfBoxes' => 1,
                     'dimensions' => [['length' => 30, 'breadth' => 30, 'height' => 30, 'weight' => 10]]]],
    ], $ctx);
    $gid = $draft['data']['data']['CustomGlobalOrderId'] ?? null;
    if (!$draft['ok'] || !$gid) {
        $r['draft'] = 'FAIL';
        $r['note'] = (string) $draft['error'];
        $rows[] = $r;
        echo "  draft FAIL: {$draft['error']}\n";
        continue;
    }
    $r['draft'] = 'ok';
    echo "  draft {$gid}\n";

    $cost = $svc->request('api/outbound/courier-wise-shipment-cost', 'POST', ['MasterCustomOrderId' => $gid], $ctx);
    $rate = collect($cost['data']['data']['calculatedRates'] ?? [])->first(fn ($x) => (string) ($x['courierId'] ?? '') === (string) $id);
    if (!$cost['ok'] || !$rate) {
        $r['final'] = 'FAIL';
        $r['note'] = $cost['ok'] ? 'courier not in final price list' : (string) $cost['error'];
        $rows[] = $r;
        echo "  final price FAIL: {$r['note']}\n";
        continue;
    }
    $r['final'] = $rate['total'] ?? '?';
    echo "  final price Rs {$r['final']}\n";
    if (!$LIVE) {
        $rows[] = $r;
        continue;
    }

    $place = $svc->request('api/outbound/place-order', 'POST', [
        'MasterCustomOrderId' => $gid, 'courierId' => $id, 'invoiceType' => 'uploaded', 'EwaybillNo' => '', 'riskTypeId' => '2',
    ], $ctx, ['InvoiceData' => ['path' => $pdfPath, 'name' => 'invoice.pdf']]);
    if (!$place['ok']) {
        $r['awb'] = 'FAIL';
        $r['note'] = (string) $place['error'];
        $rows[] = $r;
        echo "  BOOK FAIL: {$place['error']}\n";
        continue;
    }
    $awb = $place['data']['data']['awb_assigned'] ?? null;
    $awb = (is_string($awb) || is_int($awb)) && (string) $awb !== '' ? (string) $awb : null;
    sleep(2);
    $det = $svc->request('api/outbound/order-shipment-details', 'GET', ['MasterCustomOrderId' => $gid], $ctx);
    $awb = ($det['data']['data']['getOrderDetails']['AwbNumber'] ?? null) ?: $awb;
    $lr = $det['data']['data']['getOrderDetails']['lr_number'] ?? null;
    $r['awb'] = $awb ?: 'none';
    $ctx['awb'] = $awb;
    echo '  BOOKED awb ' . ($awb ?: 'none') . ($lr ? " lr {$lr}" : '') . "\n";

    $doc = $svc->request('api/outbound/download-shipment-documents', 'GET', ['CustomGlobalOrderId' => $gid, 'document_type' => 'label'], $ctx);
    $r['label'] = !empty($doc['data']['data']['AttachmentData']) ? 'ok' : 'none';
    $tr = $svc->request('api/outbound/track-order', 'GET', $awb ? ['TrackingNumber' => $awb, 'CustomGlobalOrderId' => $gid] : ['CustomGlobalOrderId' => $gid], $ctx);
    $r['track'] = $tr['ok'] ? ((string) ($tr['data']['data']['order_status'] ?? 'ok') ?: 'ok') : 'none';

    $can = $svc->request('api/outbound/cancel-order', 'POST', ['CustomGlobalOrderId' => $gid, 'MasterCustomOrderId' => $gid], $ctx);
    if (!$can['ok'] && $awb) { // some setups cancel by AWB
        $can = $svc->request('api/outbound/cancel-order', 'POST', ['CustomGlobalOrderId' => $gid, 'MasterCustomOrderId' => $gid, 'AwbNumber' => $awb, 'TrackingNumber' => $awb], $ctx);
    }
    $r['cancel'] = $can['ok'] ? 'ok' : 'FAIL';
    if (!$can['ok']) {
        $r['note'] = 'CANCEL FAILED - cancel AWB ' . ($awb ?: $gid) . ' in Bigship: ' . $can['error'];
    }
    echo "  label {$r['label']} | track {$r['track']} | cancel {$r['cancel']}\n";
    $rows[] = $r;
    usleep(500000);
}

// ---- summary ------------------------------------------------------------
echo "\nRESULT\n";
printf("%-4s %-22s %9s %6s %9s %-16s %-6s %-14s %-6s\n", 'ID', 'COURIER', 'QUOTE', 'DRAFT', 'FINAL', 'AWB', 'LABEL', 'TRACK', 'CANCEL');
foreach ($rows as $r) {
    printf("%-4s %-22s %9s %6s %9s %-16s %-6s %-14s %-6s\n", $r['id'], mb_substr($r['name'], 0, 22), $r['quote'], $r['draft'], $r['final'],
        mb_substr((string) $r['awb'], 0, 16), $r['label'], mb_substr((string) $r['track'], 0, 14), $r['cancel']);
}
foreach ($rows as $r) {
    if ($r['note'] !== '') {
        echo "NOTE {$r['id']} {$r['name']}: " . mb_substr($r['note'], 0, 300) . "\n";
    }
}
$after = $svc->login(true)['data']['userWallet']['Balance'] ?? '?';
echo "Bigship wallet before Rs {$wallet} / after Rs {$after}\n";
echo "B2B_SELFTEST_DONE\n";
