<?php

namespace App\Support\Label;

/**
 * Builds Shipxpeed shipping labels as dompdf-safe HTML (tables only, no flexbox).
 *
 *  - Thermal 4in x 6in (288 x 432 pt) or Desktop A4.
 *  - AWB barcode (Code 128) generated locally -> always encodes the real AWB.
 *  - Options mirror the Delhivery "Shipping Label Config" screen.
 *  - Never prints dummy data: missing fields are simply left out.
 */
class LabelRenderer
{
    public const DEFAULTS = [
        'label_type'                 => 'thermal',   // thermal | standard
        'show_watermark'             => true,        // faint Shipxpeed logo behind the label
        'show_logo'                  => true,
        'show_invoice_value_prepaid' => true,
        'show_invoice_number'        => true,
        'show_seller_name'           => true,
        'show_seller_address'        => true,
        'show_seller_gst'            => false,
        'show_return_address'        => true,
        'show_customer_mobile'       => true,
        'show_support_contact'       => false,
        'show_qty'                   => true,
        'show_item_price'            => true,
        'show_taxes'                 => true,
        'tax_rate'                   => 18,
        'show_product_name'          => true,
        'product_name_trim'          => 128,
        'show_sku'                   => true,
        'sku_trim'                   => 32,
        'multi_page'                 => false,
        'max_products'               => 4,
    ];

    /** Merge stored LabelSetting (old columns + new JSON config) into one config array. */
    public static function config($settings = null): array
    {
        $cfg = self::DEFAULTS;
        if (!$settings) {
            return $cfg;
        }
        $get = fn ($k) => is_array($settings) ? ($settings[$k] ?? null) : ($settings->{$k} ?? null);

        // legacy columns
        if ($get('label_type')) {
            $cfg['label_type'] = $get('label_type') === 'thermal' ? 'thermal' : 'standard';
        }
        if ($get('show_logo') !== null)            $cfg['show_logo'] = (bool) $get('show_logo');
        if ($get('hide_customer_mobile') !== null) $cfg['show_customer_mobile'] = !$get('hide_customer_mobile');
        if ($get('hide_prepaid_amount') !== null)  $cfg['show_invoice_value_prepaid'] = !$get('hide_prepaid_amount');
        if ($get('hide_sku') !== null)             $cfg['show_sku'] = !$get('hide_sku');
        if ($get('hide_product') !== null)         $cfg['show_product_name'] = !$get('hide_product');
        if ($get('hide_qty') !== null)             $cfg['show_qty'] = !$get('hide_qty');
        if ($get('hide_amount') !== null)          $cfg['show_item_price'] = !$get('hide_amount');
        if ($get('show_support_contact') !== null) $cfg['show_support_contact'] = (bool) $get('show_support_contact');

        // new JSON config column wins
        $json = $get('config');
        if (is_string($json)) {
            $json = json_decode($json, true);
        }
        if (is_array($json)) {
            foreach ($json as $k => $v) {
                if (array_key_exists($k, self::DEFAULTS)) {
                    $cfg[$k] = is_bool(self::DEFAULTS[$k]) ? (bool) $v : (is_int(self::DEFAULTS[$k]) ? (int) $v : $v);
                }
            }
        }
        $cfg['product_name_trim'] = max(10, min(250, (int) $cfg['product_name_trim']));
        $cfg['sku_trim']          = max(4, min(64, (int) $cfg['sku_trim']));
        $cfg['max_products']      = max(1, min(20, (int) $cfg['max_products']));
        $cfg['tax_rate']          = max(0, min(28, (float) $cfg['tax_rate']));
        return $cfg;
    }

    /** Normalize an Order model (+ seller) into the plain array the template uses. */
    public static function fromOrder($order, $seller = null, ?string $logoPath = null): array
    {
        $arr = fn ($v) => is_array($v) ? $v : (is_string($v) ? (json_decode($v, true) ?: []) : []);
        $consignee = $arr($order->consignee ?? []);
        $pickup    = $arr($order->pickup ?? []);
        $rto       = $arr($order->rto ?? []);
        if (empty(array_filter($rto))) {
            $rto = $pickup;
        }

        $items = [];
        foreach ($arr($order->order_items ?? []) as $it) {
            if (!is_array($it)) continue;
            $items[] = [
                'name'     => trim((string) ($it['name'] ?? $it['product_name'] ?? $it['title'] ?? '')),
                'sku'      => trim((string) ($it['sku'] ?? $it['sku_code'] ?? '')),
                'qty'      => max(1, (int) ($it['qty'] ?? $it['quantity'] ?? $it['units'] ?? 1)),
                'price'    => (float) ($it['price'] ?? $it['unit_price'] ?? $it['selling_price'] ?? 0),
                'tax_rate' => isset($it['tax_rate']) ? (float) $it['tax_rate'] : (isset($it['gst']) ? (float) $it['gst'] : null),
            ];
        }

        $payment = strtoupper((string) ($order->payment_type ?? $order->payment_method ?? ''));
        $isCod = str_contains($payment, 'COD') || str_contains($payment, 'CASH');

        $orderValue = (float) ($order->order_amount ?? 0);
        if ($orderValue <= 0 && $items) {
            $orderValue = array_sum(array_map(fn ($i) => $i['price'] * $i['qty'], $items));
        }

        $sellerName = '';
        $sellerGst = '';
        $sellerPhone = '';
        if ($seller) {
            $sellerName  = (string) ($seller->gst_verified_name ?: $seller->name ?? '');
            $sellerGst   = (string) ($seller->gst_number ?: $seller->gst_no ?? '');
            $sellerPhone = (string) ($seller->phone_number ?? '');
        }

        [$courier, $mode] = self::courierBrand((string) ($order->all_courier_name ?? ''), (string) ($order->courier_name ?? ''), (string) ($order->courier_id ?? ''));

        return [
            'awb'          => trim((string) ($order->awb_number ?? '')),
            'order_no'     => (string) ($order->customer_order_id ?: $order->order_number ?? $order->id),
            'invoice_no'   => (string) ($order->order_number ?? ''),
            'date'         => $order->created_at ? \Carbon\Carbon::parse($order->created_at)->format('d M Y') : date('d M Y'),
            'is_cod'       => $isCod,
            'cod_amount'   => (float) ($order->collectable_amount ?: ($isCod ? $orderValue : 0)),
            'order_value'  => $orderValue,
            'weight'       => (float) ($order->package_weight ?? 0),
            'dims'         => array_filter([(float) ($order->package_length ?? 0), (float) ($order->package_breadth ?? 0), (float) ($order->package_height ?? 0)]),
            'courier'      => $courier,
            'mode'         => $mode,
            'sort_code'    => (string) ($order->fwd_destination_code ?? ''),
            'zone'         => (string) ($order->zone ?? ''),
            'consignee'    => [
                'name'    => (string) ($consignee['name'] ?? ''),
                'phone'   => (string) ($consignee['phone'] ?? $consignee['mobile'] ?? ''),
                'address' => trim(implode(', ', array_filter([$consignee['address'] ?? '', $consignee['address_2'] ?? '', $consignee['landmark'] ?? '']))),
                'city'    => (string) ($consignee['city'] ?? ''),
                'state'   => (string) ($consignee['state'] ?? ''),
                'pincode' => (string) ($consignee['pincode'] ?? $consignee['pin'] ?? ''),
            ],
            'seller'       => [
                'name'    => $sellerName ?: (string) ($pickup['name'] ?? $pickup['warehouse_name'] ?? ''),
                'gst'     => $sellerGst,
                'phone'   => $sellerPhone,
                'address' => self::addressLine($pickup),
            ],
            'return'       => [
                'name'    => (string) ($rto['name'] ?? $rto['warehouse_name'] ?? ''),
                'phone'   => (string) ($rto['phone'] ?? ''),
                'address' => self::addressLine($rto),
            ],
            'items'        => $items,
            'logo'         => $logoPath,
        ];
    }

    private static function addressLine(array $a): string
    {
        $parts = array_filter([
            $a['address'] ?? '', $a['address_2'] ?? '', $a['city'] ?? '',
            trim(($a['state'] ?? '') . (isset($a['pincode']) && $a['pincode'] !== '' ? ' - ' . $a['pincode'] : '')),
        ], fn ($p) => trim((string) $p) !== '');
        return implode(', ', $parts);
    }

    /** Real courier brand only (never aggregator names) + service mode. */
    public static function courierBrand(string ...$names): array
    {
        $s = strtolower(implode(' ', $names));
        $brands = [
            'delhivery' => 'DELHIVERY', 'xpressbee' => 'XPRESSBEES', 'xpress bee' => 'XPRESSBEES',
            'ekart' => 'EKART', 'bluedart' => 'BLUE DART', 'blue dart' => 'BLUE DART', 'blue_dart' => 'BLUE DART',
            'dtdc' => 'DTDC', 'shadowfax' => 'SHADOWFAX', 'amazon' => 'AMAZON SHIPPING', 'ecom' => 'ECOM EXPRESS',
            'smartr' => 'SMARTR', 'gati' => 'GATI', 'aramex' => 'ARAMEX', 'dhl' => 'DHL', 'india post' => 'INDIA POST',
        ];
        $brand = '';
        foreach ($brands as $k => $v) {
            if (str_contains($s, $k)) { $brand = $v; break; }
        }
        $mode = '';
        if (preg_match('/\b(air|express|prime)\b/', $s, $m)) {
            $mode = $m[1] === 'air' ? 'AIR' : strtoupper($m[1]);
        } elseif (str_contains($s, 'surface')) {
            $mode = 'SURFACE';
        }
        return [$brand, $mode];
    }

    private static function e($v): string
    {
        return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
    }

    private static function money(float $v): string
    {
        return number_format($v, 2);
    }

    private static function trim(string $s, int $n): string
    {
        return mb_strlen($s) > $n ? mb_substr($s, 0, $n - 1) . '…' : $s;
    }

    private static ?string $wm = null;

    /** Faint Shipxpeed logo (already transparent + grey) as a data URI. */
    public static function watermark(): ?string
    {
        if (self::$wm === null) {
            $f = dirname(__DIR__, 3) . '/resources/label/watermark.png';
            self::$wm = is_file($f) ? 'data:image/png;base64,' . base64_encode(file_get_contents($f)) : '';
        }
        return self::$wm ?: null;
    }

    /** Full HTML document for one or more labels. */
    public static function render(array $labels, array $cfg): string
    {
        $thermal = $cfg['label_type'] === 'thermal';
        $pages = [];
        foreach ($labels as $L) {
            foreach (self::labelPages($L, $cfg) as $p) {
                $pages[] = $p;
            }
        }

        $base = $thermal ? 7.6 : 10;
        $css = '
            @page { margin: ' . ($thermal ? '8pt' : '28pt') . '; }
            * { box-sizing: border-box; }
            body { margin:0; font-family: "DejaVu Sans", Arial, sans-serif; font-size: ' . $base . 'pt; color:#000; line-height:1.25; }
            .pg { page-break-after: always; position: relative; }
            .wm { position: absolute; left: 50%; z-index: -1; }
            .pg:last-child { page-break-after: auto; }
            table { border-collapse: collapse; width:100%; }
            td { vertical-align: top; padding:0; }
            .lbl { border: 1.4pt solid #000; width:100%; }
            .sec { border-bottom: 1pt solid #000; }
            .pad { padding: ' . ($thermal ? '4pt 6pt' : '7pt 10pt') . '; }
            .vr { border-left: 1pt solid #000; }
            .muted { color:#333; }
            .xs { font-size: ' . ($base - 1.6) . 'pt; }
            .sm { font-size: ' . ($base - 0.8) . 'pt; }
            .b { font-weight: bold; }
            .up { text-transform: uppercase; letter-spacing: .3pt; }
            .brand { font-size: ' . ($thermal ? 15 : 20) . 'pt; font-weight: bold; letter-spacing: .5pt; }
            .seller-brand { font-size: ' . ($thermal ? 10 : 13) . 'pt; font-weight: bold; }
            .awb-txt { font-size: ' . ($thermal ? 11 : 14) . 'pt; font-weight: bold; letter-spacing: 1pt; }
            .pin { font-size: ' . ($thermal ? 12 : 15) . 'pt; font-weight: bold; }
            .pay { font-size: ' . ($thermal ? 13 : 16) . 'pt; font-weight: bold; }
            .tag { display:inline-block; border:1pt solid #000; padding:1pt 4pt; font-weight:bold; font-size:' . ($base - 1) . 'pt; }
            .tag-inv { background:#000; color:#fff; }
            .items th { font-size:' . ($base - 1.4) . 'pt; text-align:left; border-bottom:1pt solid #000; padding:2pt 3pt; }
            .items td { font-size:' . ($base - 1.2) . 'pt; padding:' . ($thermal ? '1.5pt 3pt' : '2pt 3pt') . '; border-bottom: .4pt solid #999; }
            .r { text-align:right; }
            .c { text-align:center; }
        ';

        $wm = $cfg['show_watermark'] ? self::watermark() : null;
        $wmW = $thermal ? 215 : 300;                       // pt
        $wmTop = $thermal ? 158 : 172;                     // below the AWB barcode
        $wmHtml = $wm ? '<img class="wm" src="' . $wm . '" style="width:' . $wmW . 'pt; margin-left:-' . ($wmW / 2) . 'pt; top:' . $wmTop . 'pt;">' : '';
        $body = '';
        foreach ($pages as $p) {
            $body .= '<div class="pg">' . $wmHtml . $p . '</div>';
        }
        return '<!doctype html><html><head><meta charset="utf-8"><style>' . $css . '</style></head><body>' . $body . '</body></html>';
    }

    /** Returns one or more page HTML strings for a single shipment. */
    private static function labelPages(array $L, array $cfg): array
    {
        $thermal = $cfg['label_type'] === 'thermal';
        $max = $thermal ? min($cfg['max_products'], 3) : $cfg['max_products'];
        $items = $L['items'];
        $chunks = [array_slice($items, 0, $max)];
        $moreCount = max(0, count($items) - $max);
        if ($cfg['multi_page'] && $moreCount > 0) {
            $rest = array_slice($items, $max);
            foreach (array_chunk($rest, $thermal ? 12 : 25) as $c) {
                $chunks[] = $c;
            }
            $moreCount = 0;
        }
        $n = count($chunks);
        $pages = [self::mainPage($L, $cfg, $chunks[0], $moreCount, $n)];
        for ($i = 1; $i < $n; $i++) {
            $pages[] = self::continuationPage($L, $cfg, $chunks[$i], $i + 1, $n);
        }
        return $pages;
    }

    private static function mainPage(array $L, array $cfg, array $items, int $moreCount, int $pageCount): string
    {
        $e = [self::class, 'e'];
        $thermal = $cfg['label_type'] === 'thermal';
        $h = '<table class="lbl">';

        // ---- Header: seller brand | courier
        $left = '';
        if ($cfg['show_logo'] && !empty($L['logo'])) {
            $left = '<img src="' . $L['logo'] . '" style="max-height:' . ($thermal ? 28 : 40) . 'pt; max-width:' . ($thermal ? 110 : 170) . 'pt;">';
        } elseif ($L['seller']['name'] !== '') {
            $left = '<div class="seller-brand">' . $e(self::trim($L['seller']['name'], 28)) . '</div>';
        }
        $right = '<div class="brand">' . $e($L['courier'] ?: 'COURIER') . '</div>';
        if ($L['mode']) {
            $right .= '<div class="xs b up">' . $e($L['mode']) . '</div>';
        }
        $h .= '<tr><td class="sec pad" style="width:55%; vertical-align:middle;">' . $left . '</td>'
            . '<td class="sec pad r" style="vertical-align:middle;">' . $right . '</td></tr>';

        // ---- AWB barcode
        $awb = $L['awb'];
        $h .= '<tr><td colspan="2" class="sec pad c">';
        if ($awb !== '') {
            $h .= '<img src="' . Code128::dataUri($awb, 70, 2) . '" style="width:' . ($thermal ? 250 : 340) . 'pt; height:' . ($thermal ? 46 : 64) . 'pt;">';
            $h .= '<table style="margin-top:2pt;"><tr><td class="awb-txt">AWB ' . $e($awb) . '</td>';
            $h .= '<td class="r">' . ($L['sort_code'] !== '' ? '<span class="tag tag-inv">' . $e($L['sort_code']) . '</span>' : '') . '</td></tr></table>';
        } else {
            $h .= '<div class="awb-txt" style="padding:14pt 0;">AWB NOT ASSIGNED YET</div>';
        }
        $h .= '</td></tr>';

        // ---- Ship to | payment
        $c = $L['consignee'];
        $ship = '<div class="xs b up muted">Ship To</div>'
            . '<div class="b" style="font-size:' . ($thermal ? 9.5 : 12) . 'pt;">' . $e($c['name']) . '</div>'
            . '<div class="sm">' . $e($c['address']) . '</div>'
            . '<div class="sm b">' . $e(trim($c['city'] . ($c['state'] ? ', ' . $c['state'] : ''))) . '</div>'
            . ($c['pincode'] !== '' ? '<div class="pin">PIN ' . $e($c['pincode']) . '</div>' : '')
            . ($cfg['show_customer_mobile'] && $c['phone'] !== '' ? '<div class="sm">Ph: ' . $e($c['phone']) . '</div>' : '');

        $pay = '';
        if ($L['is_cod']) {
            $pay .= '<div class="tag tag-inv">COD</div><div class="xs muted" style="margin-top:3pt;">Collect</div><div class="pay">&#8377; ' . self::money($L['cod_amount']) . '</div>';
        } else {
            $pay .= '<div class="tag">PREPAID</div>';
            if ($cfg['show_invoice_value_prepaid'] && $L['order_value'] > 0) {
                $pay .= '<div class="xs muted" style="margin-top:3pt;">Invoice value</div><div class="b">&#8377; ' . self::money($L['order_value']) . '</div>';
            }
            $pay .= '<div class="xs b" style="margin-top:2pt;">DO NOT COLLECT CASH</div>';
        }
        $meta = [];
        if ($L['weight'] > 0) $meta[] = 'Wt: ' . rtrim(rtrim(number_format($L['weight'], 3), '0'), '.') . ' kg';
        if (count($L['dims']) === 3) $meta[] = implode('x', array_map(fn ($d) => rtrim(rtrim(number_format($d, 1), '0'), '.'), $L['dims'])) . ' cm';
        $meta[] = 'Date: ' . $L['date'];
        if ($cfg['show_invoice_number'] && $L['invoice_no'] !== '') $meta[] = 'Inv: ' . $L['invoice_no'];
        $pay .= '<div class="xs" style="margin-top:4pt;">' . implode('<br>', array_map($e, $meta)) . '</div>';

        $h .= '<tr><td class="sec pad">' . $ship . '</td><td class="sec pad vr" style="width:38%;">' . $pay . '</td></tr>';

        // ---- Order barcode + seller
        $sellerBlock = '';
        if ($cfg['show_seller_name'] && $L['seller']['name'] !== '') {
            $sellerBlock .= '<div class="xs muted b up">Seller</div><div class="sm b">' . $e($L['seller']['name']) . '</div>';
        }
        if ($cfg['show_seller_address'] && $L['seller']['address'] !== '') {
            $sellerBlock .= '<div class="xs">' . $e($L['seller']['address']) . '</div>';
        }
        if ($cfg['show_seller_gst'] && $L['seller']['gst'] !== '') {
            $sellerBlock .= '<div class="xs b">GSTIN: ' . $e($L['seller']['gst']) . '</div>';
        }
        $orderBc = '<div class="xs muted b up">Order</div><div class="sm b">' . $e($L['order_no']) . '</div>';
        if ($L['order_no'] !== '') {
            $orderBc .= '<img src="' . Code128::dataUri($L['order_no'], 40, 1.4) . '" style="width:' . ($thermal ? 95 : 140) . 'pt; height:' . ($thermal ? 20 : 26) . 'pt; margin-top:2pt;">';
        }
        $h .= '<tr><td class="sec pad">' . ($sellerBlock ?: '&nbsp;') . '</td><td class="sec pad vr c">' . $orderBc . '</td></tr>';

        // ---- Products
        if ($items && ($cfg['show_product_name'] || $cfg['show_sku'])) {
            $h .= '<tr><td colspan="2" class="sec" style="padding:2pt 3pt;">' . self::itemsTable($items, $cfg, $moreCount) . '</td></tr>';
        }

        // ---- Return address + footer
        $foot = '';
        if ($cfg['show_return_address'] && $L['return']['address'] !== '') {
            $foot .= '<span class="b">Return to:</span> ' . $e(trim($L['return']['name'] . ', ' . $L['return']['address'], ', '))
                . ($L['return']['phone'] !== '' ? ' | Ph: ' . $e($L['return']['phone']) : '');
        }
        if ($cfg['show_support_contact'] && $L['seller']['phone'] !== '') {
            $foot .= ($foot ? '<br>' : '') . '<span class="b">Seller support:</span> ' . $e($L['seller']['phone']);
        }
        $h .= '<tr><td class="pad xs">' . ($foot ?: '&nbsp;') . '</td><td class="pad xs r" style="vertical-align:bottom;">'
            . ($pageCount > 1 ? 'Page 1 of ' . $pageCount : '') . '</td></tr>';

        $h .= '</table>';
        return $h;
    }

    private static function continuationPage(array $L, array $cfg, array $items, int $page, int $count): string
    {
        $e = [self::class, 'e'];
        $h = '<table class="lbl">';
        $h .= '<tr><td class="sec pad"><span class="awb-txt">AWB ' . $e($L['awb']) . '</span><br><span class="xs">Order ' . $e($L['order_no']) . ' &middot; continued</span></td>'
            . '<td class="sec pad r"><span class="b">' . $e($L['courier']) . '</span></td></tr>';
        $h .= '<tr><td colspan="2" style="padding:2pt 3pt;">' . self::itemsTable($items, $cfg, 0) . '</td></tr>';
        $h .= '<tr><td class="pad xs">&nbsp;</td><td class="pad xs r">Page ' . $page . ' of ' . $count . '</td></tr></table>';
        return $h;
    }

    private static function itemsTable(array $items, array $cfg, int $moreCount): string
    {
        $e = [self::class, 'e'];
        $thermal = $cfg['label_type'] === 'thermal';
        $taxes = $cfg['show_taxes'] && $cfg['show_item_price'];
        $h = '<table class="items"><tr><th>Product' . ($cfg['show_sku'] ? ' / SKU' : '') . '</th>';
        if ($cfg['show_qty']) $h .= '<th class="r">Qty</th>';
        if ($cfg['show_item_price']) $h .= '<th class="r">Price</th>';
        if ($taxes) $h .= ($thermal ? '' : '<th class="r">Taxable</th>') . '<th class="r">Tax</th>';
        if ($cfg['show_item_price']) $h .= '<th class="r">Total</th>';
        $h .= '</tr>';

        foreach ($items as $it) {
            $nameLen = $thermal ? min($cfg['product_name_trim'], 38) : $cfg['product_name_trim'];
            $name = $cfg['show_product_name'] ? self::trim($it['name'] ?: 'Item', $nameLen) : '';
            $sku = $cfg['show_sku'] && $it['sku'] !== '' ? self::trim($it['sku'], $thermal ? min($cfg['sku_trim'], 20) : $cfg['sku_trim']) : '';
            $skuHtml = '<span class="b">SKU: ' . $e($sku) . '</span>';
            $cell = $e($name) . ($sku !== '' ? ($name !== '' ? ($thermal ? ' &middot; ' : '<br>') : '') . $skuHtml : '');
            $line = $it['price'] * $it['qty'];
            $rate = $it['tax_rate'] ?? $cfg['tax_rate'];
            $taxable = $rate > 0 ? $line / (1 + $rate / 100) : $line;
            $h .= '<tr><td>' . ($cell ?: '&nbsp;') . '</td>';
            if ($cfg['show_qty']) $h .= '<td class="r">' . (int) $it['qty'] . '</td>';
            if ($cfg['show_item_price']) $h .= '<td class="r">' . self::money($it['price']) . '</td>';
            if ($taxes) $h .= ($thermal ? '' : '<td class="r">' . self::money($taxable) . '</td>') . '<td class="r">' . self::money($line - $taxable) . '</td>';
            if ($cfg['show_item_price']) $h .= '<td class="r">' . self::money($line) . '</td>';
            $h .= '</tr>';
        }
        if ($moreCount > 0) {
            $h .= '<tr><td colspan="6" class="b">+' . $moreCount . ' more SKU' . ($moreCount > 1 ? 's' : '') . '</td></tr>';
        }
        return $h . '</table>';
    }

    /** Sample data for the settings-page preview. */
    public static function sample(): array
    {
        return [
            'awb' => '1234567891011', 'order_no' => 'SX10293', 'invoice_no' => 'INV-10293', 'date' => date('d M Y'),
            'is_cod' => false, 'cod_amount' => 0, 'order_value' => 2399, 'weight' => 0.5, 'dims' => [20, 15, 10],
            'courier' => 'DELHIVERY', 'mode' => 'SURFACE', 'sort_code' => 'BLR/KRM', 'zone' => 'D',
            'consignee' => ['name' => 'John Doe', 'phone' => '9876543210', 'address' => '43/2, Koramangala Park Street, 2nd Phase', 'city' => 'Bengaluru', 'state' => 'Karnataka', 'pincode' => '560025'],
            'seller' => ['name' => 'Your Brand Pvt Ltd', 'gst' => '27ABCDE1234F1Z5', 'phone' => '9000000000', 'address' => 'Sagar Society, Pune, Maharashtra - 411014'],
            'return' => ['name' => 'Your Brand Warehouse', 'phone' => '9000000000', 'address' => 'Sagar Society, Pune, Maharashtra - 411014'],
            'items' => [
                ['name' => 'Blue printed rodeo flannel shirt with flappy sleeves', 'sku' => 'ABCD12345678910', 'qty' => 1, 'price' => 1199.5, 'tax_rate' => null],
                ['name' => 'Cotton chino trousers, slim fit, khaki', 'sku' => 'CHN-KHK-32', 'qty' => 1, 'price' => 1199.5, 'tax_rate' => null],
            ],
            'logo' => null,
        ];
    }
}
