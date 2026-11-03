<?php

namespace App\Http\Controllers\ApiV1;

use App\Http\Controllers\Controller;
use App\Models\B2bOrder;
use App\Models\Warehouse;
use App\Services\BigshipService;
use App\Support\ApiV1\Couriers;
use App\Support\ApiV1\Present;
use App\Support\ApiV1\Respond;
use App\Support\CourierBrand;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Domestic B2B (multi-box / LTL) shipments - same engine as Seller panel > B2B orders:
 * admin-approved B2B couriers only, seller price (markup + GST) and wallet debit on booking.
 */
class B2bController extends Controller
{
    public function __construct(private BigshipService $bigship)
    {
    }

    private function disabled()
    {
        return Respond::error('B2B shipping is not enabled for your account yet. Contact support@shipxpeed.com.', 403, 'b2b_disabled');
    }

    private function boxRules(): array
    {
        return [
            'boxes'              => 'required|array|min:1|max:50',
            'boxes.*.count'      => 'required|integer|min:1|max:999',
            'boxes.*.length_cm'  => 'required|numeric|min:1|max:500',
            'boxes.*.breadth_cm' => 'required|numeric|min:1|max:500',
            'boxes.*.height_cm'  => 'required|numeric|min:1|max:500',
            'boxes.*.weight_kg'  => 'required|numeric|min:0.1|max:2000',
        ];
    }

    private static function boxes(array $in): array
    {
        return BigshipService::normaliseBoxes(array_map(fn ($b) => [
            'count' => $b['count'], 'length' => $b['length_cm'], 'breadth' => $b['breadth_cm'],
            'height' => $b['height_cm'], 'weight' => $b['weight_kg'],
        ], $in));
    }

    private static function risk(?string $r): int
    {
        return $r === 'carrier' ? 3 : 2;
    }

    private function rows(array $rates): array
    {
        return array_map(fn ($r) => [
            'courier_id'      => (string) $r['courier_id'],
            'courier_code'    => Couriers::code($r['courier_name']),
            'courier_name'    => CourierBrand::cleanName($r['courier_name'], 'Courier'),
            'brand'           => Couriers::brand($r['courier_name']),
            'logo'            => CourierBrand::logoUrl($r['courier_name']),
            'zone'            => $r['zone'] ?: null,
            'tat_days'        => (int) $r['tat'],
            'charged_weight_kg' => (float) $r['charged_weight'],
            'total_charge'    => round((float) $r['price'], 2),
            'gst_included'    => true,
            'note'            => $r['note'] ?: null,
        ], $rates);
    }

    /** POST /api/v1/b2b/rates */
    public function rates(Request $request)
    {
        if (!$this->bigship->enabledForSellers()) {
            return $this->disabled();
        }
        $seller = Respond::seller($request);
        $v = Validator::make($request->all(), [
            'pickup_warehouse_id' => 'required_without:origin_pincode|nullable|integer',
            'origin_pincode'      => 'required_without:pickup_warehouse_id|nullable|digits:6',
            'destination_pincode' => 'required|digits:6',
            'invoice_amount'      => 'required|numeric|min:1',
            'payment_type'        => 'required|in:prepaid,cod',
            'cod_amount'          => 'required_if:payment_type,cod|nullable|numeric|min:1|lt:invoice_amount',
            'risk'                => 'nullable|in:owner,carrier',
        ] + $this->boxRules());
        if ($v->fails()) {
            return Respond::invalid($v);
        }
        $d = $v->validated();
        $from = $d['origin_pincode'] ?? null;
        if (!empty($d['pickup_warehouse_id'])) {
            $wh = Warehouse::where('seller_id', $seller->id)->find($d['pickup_warehouse_id']);
            if (!$wh) {
                return Respond::error('Pickup warehouse not found.', 422, 'warehouse_not_found');
            }
            $from = (string) $wh->pincode;
        }
        $res = $this->bigship->quote([
            'from' => $from, 'to' => $d['destination_pincode'], 'invoice_amount' => $d['invoice_amount'],
            'payment' => $d['payment_type'], 'cod_amount' => $d['cod_amount'] ?? 0,
            'risk_type_id' => self::risk($d['risk'] ?? null), 'boxes' => self::boxes($d['boxes']),
        ]);
        if (!$res['ok']) {
            return Respond::error('Could not get B2B rates: ' . $res['error'], 502, 'courier_error');
        }
        $rows = $this->rows($res['rates']);
        return Respond::ok([
            'origin_pincode' => (string) $from, 'destination_pincode' => (string) $d['destination_pincode'],
            'count' => count($rows), 'couriers' => $rows,
        ], $rows ? 'Rates fetched' : 'No B2B courier serves this lane');
    }

    /** POST /api/v1/b2b/shipments - create and book (multipart for files, or base64 in JSON). */
    public function create(Request $request)
    {
        if (!$this->bigship->enabledForSellers()) {
            return $this->disabled();
        }
        $seller = Respond::seller($request);
        $threshold = (int) config('bigship.ewaybill_threshold', 50000);
        $needEway = (float) $request->input('invoice_amount') >= $threshold;

        $v = Validator::make($request->all(), [
            'client_order_id'        => 'nullable|string|max:100',
            'pickup_warehouse_id'    => 'required|integer',
            'return_warehouse_id'    => 'nullable|integer',
            'consignee'              => 'required|array',
            'consignee.name'         => 'required|string|max:100',
            'consignee.company'      => 'nullable|string|max:100',
            'consignee.phone'        => ['required', 'regex:/^(\+?91[\s-]?)?[6-9]\d{9}$/'],
            'consignee.email'        => 'nullable|email|max:120',
            'consignee.gstin'        => 'nullable|string|max:15',
            'consignee.address'      => 'required|string|min:3|max:200',
            'consignee.address_2'    => 'nullable|string|max:200',
            'consignee.landmark'     => 'nullable|string|max:100',
            'consignee.pincode'      => 'required|digits:6',
            'consignee.city'         => 'required|string|max:60',
            'consignee.state'        => 'required|string|max:60',
            'invoice_no'             => 'required|string|max:60',
            'invoice_date'           => 'nullable|date',
            'invoice_amount'         => 'required|numeric|min:1|max:99999999',
            'payment_type'           => 'required|in:prepaid,cod',
            'cod_amount'             => 'required_if:payment_type,cod|nullable|numeric|min:1|lt:invoice_amount',
            'product_name'           => 'required|string|max:150',
            'risk'                   => 'nullable|in:owner,carrier',
            'invoice_file'           => 'required_without:invoice_file_base64|nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'invoice_file_base64'    => 'required_without:invoice_file|nullable|string',
            'ewaybill_no'            => [$needEway ? 'required' : 'nullable', 'regex:/^\d{12}$/'],
            'ewaybill_file'          => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'ewaybill_file_base64'   => 'nullable|string',
            'courier_id'             => 'nullable|string|max:20',
            'courier_code'           => 'nullable|string|max:80',
            'max_price'              => 'nullable|numeric|min:1',
        ] + $this->boxRules(), [
            'ewaybill_no.required' => 'ewaybill_no is required for invoices of ₹' . number_format($threshold) . ' or more.',
            'ewaybill_no.regex'    => 'ewaybill_no has 12 digits.',
        ]);
        if ($v->fails()) {
            return Respond::invalid($v);
        }
        $d = $v->validated();
        if ($needEway && !$request->hasFile('ewaybill_file') && !$request->filled('ewaybill_file_base64')) {
            return Respond::error('Upload the e-way bill (ewaybill_file or ewaybill_file_base64) for invoices of ₹' . number_format($threshold) . ' or more.', 422, 'validation_failed');
        }

        // idempotency: one live B2B shipment per invoice number
        $dup = B2bOrder::where('seller_id', $seller->id)->where('invoice_no', $d['invoice_no'])
            ->where('status', '!=', 'cancelled')->first();
        if ($dup) {
            return Respond::error('A B2B shipment with this invoice_no already exists.', 409, 'duplicate_order', ['shipment' => Present::b2b($dup)]);
        }

        $pick = Warehouse::where('seller_id', $seller->id)->find($d['pickup_warehouse_id']);
        if (!$pick) {
            return Respond::error('Pickup warehouse not found.', 422, 'warehouse_not_found');
        }
        $ret = !empty($d['return_warehouse_id']) ? Warehouse::where('seller_id', $seller->id)->find($d['return_warehouse_id']) : null;
        $ret = $ret ?: $pick;

        // files first, so nothing is saved when one is unreadable
        $files = [];
        foreach (['invoice', 'ewaybill'] as $kind) {
            $f = $this->fileFrom($request, $kind);
            if ($f === false) {
                return Respond::error("{$kind}_file_base64 is not a valid PDF, JPG or PNG (max 5 MB).", 422, 'validation_failed');
            }
            $files[$kind] = $f;
        }

        $boxes = self::boxes($d['boxes']);
        $risk = self::risk($d['risk'] ?? null);
        $quote = $this->bigship->quote([
            'from' => (string) $pick->pincode, 'to' => $d['consignee']['pincode'], 'invoice_amount' => $d['invoice_amount'],
            'payment' => $d['payment_type'], 'cod_amount' => $d['cod_amount'] ?? 0, 'risk_type_id' => $risk, 'boxes' => $boxes,
        ]);
        if (!$quote['ok']) {
            return Respond::error('Could not get B2B rates: ' . $quote['error'], 502, 'courier_error');
        }
        $rows = $this->rows($quote['rates']);
        $choice = null;
        foreach ($rows as $r) {
            if ((!empty($d['courier_id']) && $r['courier_id'] === (string) $d['courier_id'])
                || (!empty($d['courier_code']) && $r['courier_code'] === $d['courier_code'])) {
                $choice = $r;
                break;
            }
        }
        if (empty($d['courier_id']) && empty($d['courier_code'])) {
            $choice = $rows[0] ?? null;
        }
        if (!$choice) {
            return Respond::error('That B2B courier is not available on this lane.', 422, 'courier_unavailable', ['available' => $rows]);
        }
        if (!empty($d['max_price']) && $choice['total_charge'] > (float) $d['max_price']) {
            return Respond::error('Price ₹' . number_format($choice['total_charge'], 2) . ' is above your max_price.', 422, 'price_above_max', ['quote' => $choice]);
        }

        $addr = fn (Warehouse $w) => [
            'title' => $w->address_title, 'name' => $w->name, 'phone' => $w->phone, 'email' => $w->email,
            'address_line1' => $w->address_line1, 'address_line2' => $w->address_line2,
            'city' => $w->city, 'state' => $w->state, 'pincode' => (string) $w->pincode,
        ];
        $c = $d['consignee'];
        $order = B2bOrder::create([
            'seller_id'           => $seller->id,
            'status'              => 'draft',
            'pickup_warehouse_id' => $pick->id,
            'return_warehouse_id' => $ret->id,
            'pickup'              => $addr($pick),
            'return_address'      => $addr($ret),
            'consignee'           => [
                'name' => $c['name'], 'company' => $c['company'] ?? null, 'phone' => BigshipService::phone10($c['phone']),
                'email' => $c['email'] ?? null, 'gstin' => $c['gstin'] ?? null, 'address_line1' => $c['address'],
                'address_line2' => $c['address_2'] ?? null, 'landmark' => $c['landmark'] ?? null,
                'pincode' => (string) $c['pincode'], 'city' => $c['city'], 'state' => $c['state'],
            ],
            'invoice_no'          => $d['invoice_no'],
            'invoice_date'        => $d['invoice_date'] ?? now()->toDateString(),
            'invoice_amount'      => $d['invoice_amount'],
            'payment_mode'        => $d['payment_type'],
            'cod_amount'          => $d['payment_type'] === 'cod' ? $d['cod_amount'] : 0,
            'product_name'        => $d['product_name'],
            'risk_type_id'        => $risk,
            'boxes'               => $boxes,
            'total_boxes'         => array_sum(array_column($boxes, 'count')),
            'total_weight'        => array_sum(array_map(fn ($b) => $b['count'] * $b['weight'], $boxes)),
            'ewaybill_no'         => $d['ewaybill_no'] ?? null,
            'quote'               => null,
        ]);
        $order->order_ref = 'B2B' . str_pad((string) $order->id, 6, '0', STR_PAD_LEFT);
        $dir = "b2b/{$seller->id}/{$order->id}";
        $order->invoice_file = $this->store($files['invoice'], $dir);
        if ($files['ewaybill']) {
            $order->ewaybill_file = $this->store($files['ewaybill'], $dir);
        }
        $order->save();

        $res = $this->bigship->book($order, $choice['courier_id'], (float) $choice['total_charge']);
        $order->refresh();
        if (!$res['ok']) {
            return Respond::error('B2B shipment saved but not booked: ' . $res['message'], 422, !empty($res['price_changed']) ? 'price_changed' : 'booking_failed', [
                'shipment' => Present::b2b($order),
                'retry'    => 'POST /api/v1/b2b/shipments/' . $order->order_ref . '/book',
            ]);
        }
        return Respond::ok(Present::b2b($order), $res['message'], 201);
    }

    /** POST /api/v1/b2b/shipments/{ref}/book - book a saved (unbooked) B2B shipment; send max_price to accept a changed price. */
    public function book(Request $request, string $ref)
    {
        if (!$this->bigship->enabledForSellers()) {
            return $this->disabled();
        }
        $order = $this->own($request, $ref);
        if (!$order) {
            return Respond::error('B2B shipment not found.', 404, 'not_found');
        }
        if ($order->status !== 'draft') {
            return Respond::error('This shipment is already ' . $order->status . '.', 409, 'not_draft', ['shipment' => Present::b2b($order)]);
        }
        $quote = $this->bigship->quote([
            'from' => $order->pickup['pincode'] ?? '', 'to' => $order->consignee['pincode'] ?? '', 'invoice_amount' => $order->invoice_amount,
            'payment' => $order->payment_mode, 'cod_amount' => $order->cod_amount, 'risk_type_id' => $order->risk_type_id, 'boxes' => $order->boxes ?? [],
        ]);
        if (!$quote['ok']) {
            return Respond::error('Could not get B2B rates: ' . $quote['error'], 502, 'courier_error');
        }
        $rows = $this->rows($quote['rates']);
        $choice = $request->filled('courier_id') ? collect($rows)->firstWhere('courier_id', (string) $request->courier_id)
            : ($request->filled('courier_code') ? collect($rows)->firstWhere('courier_code', $request->courier_code) : ($rows[0] ?? null));
        if (!$choice) {
            return Respond::error('That B2B courier is not available on this lane.', 422, 'courier_unavailable', ['available' => $rows]);
        }
        $price = max((float) $choice['total_charge'], (float) $request->input('max_price', 0));
        $res = $this->bigship->book($order, $choice['courier_id'], $price);
        $order->refresh();
        return $res['ok'] ? Respond::ok(Present::b2b($order), $res['message'])
            : Respond::error($res['message'], 422, !empty($res['price_changed']) ? 'price_changed' : 'booking_failed', ['shipment' => Present::b2b($order)]);
    }

    /** GET /api/v1/b2b/shipments */
    public function index(Request $request)
    {
        $seller = Respond::seller($request);
        $q = B2bOrder::where('seller_id', $seller->id)->orderByDesc('id');
        $map = ['new' => 'draft', 'booked' => 'booked', 'in_transit' => 'in_transit', 'delivered' => 'delivered', 'rto' => 'rto', 'cancelled' => 'cancelled'];
        if ($s = $request->query('status')) {
            $q->where('status', $map[$s] ?? $s);
        }
        $p = $q->paginate(min(100, max(1, (int) $request->query('per_page', 25))));
        return Respond::ok(['page' => $p->currentPage(), 'per_page' => $p->perPage(), 'total' => $p->total(),
            'shipments' => collect($p->items())->map(fn ($o) => Present::b2b($o))->all()]);
    }

    /** GET /api/v1/b2b/shipments/{ref} */
    public function show(Request $request, string $ref)
    {
        $order = $this->own($request, $ref);
        return $order ? Respond::ok(Present::b2b($order)) : Respond::error('B2B shipment not found.', 404, 'not_found');
    }

    /** POST /api/v1/b2b/shipments/{ref}/cancel */
    public function cancel(Request $request, string $ref)
    {
        $order = $this->own($request, $ref);
        if (!$order) {
            return Respond::error('B2B shipment not found.', 404, 'not_found');
        }
        if ($order->status === 'cancelled') {
            return Respond::error('Already cancelled.', 409, 'already_cancelled');
        }
        if ($order->status === 'draft') {
            $order->status = 'cancelled';
            $order->cancelled_at = now();
            $order->save();
            return Respond::ok(Present::b2b($order), 'Cancelled');
        }
        $r = $this->bigship->cancel($order);
        $order->refresh();
        return $r['ok'] ? Respond::ok(Present::b2b($order), $r['message']) : Respond::error($r['message'], 422, 'cancel_failed');
    }

    /** GET /api/v1/b2b/shipments/{ref}/documents/{type}  type: label | manifest */
    public function document(Request $request, string $ref, string $type)
    {
        if (!in_array($type, ['label', 'manifest'], true)) {
            return Respond::error('type must be label or manifest.', 422, 'validation_failed');
        }
        $order = $this->own($request, $ref);
        if (!$order) {
            return Respond::error('B2B shipment not found.', 404, 'not_found');
        }
        if (!$order->provider_order_id || $order->status === 'draft') {
            return Respond::error('Documents are available after booking.', 422, 'not_booked');
        }
        $doc = $this->bigship->documentUrl($order, $type);
        return $doc['ok'] ? Respond::ok(['order_ref' => $order->order_ref, 'type' => $type, 'url' => $doc['url'], 'mime' => $doc['mime'] ?? 'application/pdf'])
            : Respond::error(ucfirst($type) . ': ' . $doc['error'], 422, 'document_failed');
    }

    private function own(Request $request, string $ref): ?B2bOrder
    {
        $q = B2bOrder::where('seller_id', Respond::seller($request)->id);
        return (clone $q)->where('order_ref', $ref)->first()
            ?? (clone $q)->where('awb', $ref)->first()
            ?? (ctype_digit($ref) ? (clone $q)->find((int) $ref) : null);
    }

    /** UploadedFile, ['bytes','ext'] from base64, null when not sent, false when invalid. */
    private function fileFrom(Request $request, string $kind)
    {
        if ($request->hasFile("{$kind}_file")) {
            return $request->file("{$kind}_file");
        }
        $b64 = (string) $request->input("{$kind}_file_base64", '');
        if ($b64 === '') {
            return null;
        }
        if (preg_match('#^data:([\w/+.-]+);base64,#', $b64, $m)) {
            $b64 = substr($b64, strlen($m[0]));
        }
        $bytes = base64_decode($b64, true);
        if ($bytes === false || strlen($bytes) < 50 || strlen($bytes) > 5 * 1024 * 1024) {
            return false;
        }
        $ext = match (true) {
            str_starts_with($bytes, '%PDF') => 'pdf',
            str_starts_with($bytes, "\x89PNG") => 'png',
            str_starts_with($bytes, "\xFF\xD8") => 'jpg',
            default => null,
        };
        return $ext ? ['bytes' => $bytes, 'ext' => $ext] : false;
    }

    private function store($file, string $dir): string
    {
        if ($file instanceof UploadedFile) {
            return $file->store($dir, 'local');
        }
        $path = $dir . '/' . Str::random(40) . '.' . $file['ext'];
        Storage::disk('local')->put($path, $file['bytes']);
        return $path;
    }
}
