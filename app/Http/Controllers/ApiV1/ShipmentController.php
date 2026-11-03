<?php

namespace App\Http\Controllers\ApiV1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Shipxpeedapi\CancelshipmentController;
use App\Http\Controllers\Shipxpeedapi\TrackController;
use App\Models\B2bOrder;
use App\Models\Order;
use App\Services\BigshipService;
use App\Support\ApiV1\Present;
use App\Support\ApiV1\Respond;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/** Shipment lookups and post-booking actions for B2C and reverse (B2B AWBs are detected and routed too). */
class ShipmentController extends Controller
{
    private function find(int $sellerId, string $ref): ?Order
    {
        $q = Order::where('seller_id', $sellerId);
        return (clone $q)->where('awb_number', $ref)->first()
            ?? (ctype_digit($ref) ? (clone $q)->find((int) $ref) : null)
            ?? (clone $q)->where('order_number', $ref)->first()
            ?? (clone $q)->where('customer_order_id', $ref)->orderByDesc('id')->first();
    }

    private function findB2b(int $sellerId, string $ref): ?B2bOrder
    {
        return B2bOrder::where('seller_id', $sellerId)
            ->where(fn ($w) => $w->where('awb', $ref)->orWhere('order_ref', $ref)->orWhere('lr_number', $ref))->first();
    }

    /** GET /api/v1/shipments */
    public function index(Request $request)
    {
        $seller = Respond::seller($request);
        $v = Validator::make($request->query(), [
            'type'     => 'nullable|in:b2c,reverse',
            'status'   => 'nullable|in:' . implode(',', array_keys(Present::STATUSES)),
            'from'     => 'nullable|date',
            'to'       => 'nullable|date',
            'page'     => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);
        if ($v->fails()) {
            return Respond::invalid($v);
        }
        $q = Order::where('seller_id', $seller->id)->orderByDesc('id');
        if ($request->query('type') === 'reverse') {
            $q->where('reverse', 'reverse');
        } elseif ($request->query('type') === 'b2c') {
            $q->where(fn ($w) => $w->whereNull('reverse')->orWhere('reverse', '!=', 'reverse'));
        }
        if ($f = $request->query('from')) {
            $q->where('created_at', '>=', \Illuminate\Support\Carbon::parse($f)->startOfDay());
        }
        if ($t = $request->query('to')) {
            $q->where('created_at', '<=', \Illuminate\Support\Carbon::parse($t)->endOfDay());
        }
        $per = (int) $request->query('per_page', 25);
        $status = $request->query('status');

        if ($status) {
            // status is derived (courier status text varies), so filter after loading a bounded window
            $all = $q->limit(2000)->get()->filter(fn ($o) => Present::status($o) === $status)->values();
            $page = max(1, (int) $request->query('page', 1));
            $items = $all->slice(($page - 1) * $per, $per)->values();
            $total = $all->count();
        } else {
            $p = $q->paginate($per);
            $items = collect($p->items());
            $page = $p->currentPage();
            $total = $p->total();
        }

        return Respond::ok([
            'page' => $page, 'per_page' => $per, 'total' => $total,
            'shipments' => $items->map(fn ($o) => Present::shipment($o))->all(),
        ]);
    }

    /** GET /api/v1/shipments/{ref}  (AWB, shipment_id, order_number or client_order_id) */
    public function show(Request $request, string $ref)
    {
        $seller = Respond::seller($request);
        if ($o = $this->find($seller->id, $ref)) {
            return Respond::ok(Present::shipment($o));
        }
        if ($b = $this->findB2b($seller->id, $ref)) {
            return Respond::ok(Present::b2b($b));
        }
        return Respond::error('Shipment not found.', 404, 'not_found');
    }

    /** POST /api/v1/shipments/cancel  {"awb_numbers": [...]} (up to 50) */
    public function cancel(Request $request)
    {
        $seller = Respond::seller($request);
        $awbs = $request->input('awb_numbers', $request->filled('awb_number') ? [$request->input('awb_number')] : []);
        $v = Validator::make(['awb_numbers' => $awbs], ['awb_numbers' => 'required|array|min:1|max:50', 'awb_numbers.*' => 'required|string|max:60']);
        if ($v->fails()) {
            return Respond::invalid($v);
        }

        $results = [];
        foreach (array_unique($awbs) as $awb) {
            $awb = trim((string) $awb);
            if ($b2b = $this->findB2b($seller->id, $awb)) {
                $r = app(BigshipService::class)->cancel($b2b);
                $results[] = ['awb_number' => $awb, 'success' => (bool) $r['ok'], 'message' => $r['message']];
                continue;
            }
            $order = Order::where('seller_id', $seller->id)->where('awb_number', $awb)->first();
            if (!$order) {
                $results[] = ['awb_number' => $awb, 'success' => false, 'message' => 'AWB not found in your account'];
                continue;
            }
            $r = Respond::legacy($request, CancelshipmentController::class, 'cancelByAwb', ['awb' => $awb]);
            $results[] = ['awb_number' => $awb, 'success' => Respond::legacyOk($r), 'message' => $r['message'] ?? (Respond::legacyOk($r) ? 'Cancelled' : 'Could not cancel')];
        }

        $ok = count(array_filter($results, fn ($r) => $r['success']));
        $msg = "$ok of " . count($results) . ' cancelled';
        return count($results) === 1 && !$ok
            ? Respond::error($results[0]['message'], 422, 'cancel_failed', ['results' => $results])
            : Respond::ok(['cancelled' => $ok, 'failed' => count($results) - $ok, 'results' => $results], $msg);
    }

    /**
     * GET /api/v1/shipments/{awb}/label          -> JSON with label_url (PDF, kept 7 days)
     * GET /api/v1/shipments/{awb}/label?format=pdf -> the PDF itself
     * Uses the panel's label design and the client's label settings (Seller panel > Settings > Label).
     */
    public function label(Request $request, string $awb)
    {
        $seller = Respond::seller($request);
        if ($b2b = $this->findB2b($seller->id, $awb)) {
            $doc = app(BigshipService::class)->documentUrl($b2b, 'label');
            return $doc['ok'] ? Respond::ok(['awb_number' => $awb, 'label_url' => $doc['url'], 'format' => 'pdf'], 'Label ready')
                : Respond::error('Label: ' . $doc['error'], 422, 'label_failed');
        }
        $order = Order::where('seller_id', $seller->id)->where('awb_number', $awb)->first();
        if (!$order) {
            return Respond::error('AWB not found in your account.', 404, 'not_found');
        }
        return $this->labelResponse($request, collect([$order]), $awb);
    }

    /** POST /api/v1/labels  {"awb_numbers": [...]} - one PDF with up to 100 labels. */
    public function labels(Request $request)
    {
        $seller = Respond::seller($request);
        $v = Validator::make($request->all(), ['awb_numbers' => 'required|array|min:1|max:100', 'awb_numbers.*' => 'required|string|max:60']);
        if ($v->fails()) {
            return Respond::invalid($v);
        }
        $awbs = array_values(array_unique(array_map('trim', $request->awb_numbers)));
        $orders = Order::where('seller_id', $seller->id)->whereIn('awb_number', $awbs)->get()
            ->sortBy(fn ($o) => array_search($o->awb_number, $awbs))->values();
        if ($orders->isEmpty()) {
            return Respond::error('None of these AWBs are in your account.', 404, 'not_found');
        }
        $missing = array_values(array_diff($awbs, $orders->pluck('awb_number')->all()));
        return $this->labelResponse($request, $orders, 'labels-' . now()->format('Ymd-His'), $missing);
    }

    private function labelResponse(Request $request, $orders, string $name, array $missing = [])
    {
        try {
            $sellerId = (int) $orders->first()->seller_id;
            $settings = \App\Models\LabelSetting::where('seller_id', $sellerId)->first();
            $cfg = \App\Support\Label\LabelRenderer::config($settings);
            $seller = \App\Models\SellerList::find($sellerId);
            $logo = null;
            if ($cfg['show_logo'] && $settings && !empty($settings->logo_path) && is_file(public_path($settings->logo_path))) {
                $path = public_path($settings->logo_path);
                $mime = @mime_content_type($path) ?: 'image/png';
                if (in_array($mime, ['image/png', 'image/jpeg', 'image/gif', 'image/svg+xml'], true)) {
                    $logo = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
                }
            }
            $labels = $orders->map(fn ($o) => \App\Support\Label\LabelRenderer::fromOrder($o, $seller, $logo))->all();
            $html = \App\Support\Label\LabelRenderer::render($labels, $cfg);
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->setOptions(['isRemoteEnabled' => false, 'defaultFont' => 'DejaVu Sans']);
            $cfg['label_type'] === 'thermal' ? $pdf->setPaper([0, 0, 288, 432], 'portrait') : $pdf->setPaper('A4', 'portrait');
            $out = $pdf->output();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('ApiV1 label failed', ['e' => $e->getMessage()]);
            return Respond::error('Label could not be generated right now. Try again in a minute.', 500, 'label_failed');
        }

        $file = preg_replace('/[^A-Za-z0-9_-]/', '-', $name) . '.pdf';
        if ($request->query('format') === 'pdf') {
            return response($out, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $file . '"',
                'Content-Length' => strlen($out),
            ]);
        }

        // public link with an unguessable name; old files are cleaned up after 7 days
        $dir = public_path('labels/api');
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        foreach (glob($dir . '/*.pdf') ?: [] as $old) {
            if (@filemtime($old) < time() - 7 * 86400) {
                @unlink($old);
            }
        }
        $stored = \Illuminate\Support\Str::random(32) . '-' . $file;
        file_put_contents($dir . '/' . $stored, $out);

        $data = [
            'awb_numbers' => $orders->pluck('awb_number')->values()->all(),
            'count'       => $orders->count(),
            'label_url'   => url('labels/api/' . $stored),
            'format'      => 'pdf',
            'size'        => $cfg['label_type'] === 'thermal' ? '4x6' : 'A4',
            'expires_at'  => now()->addDays(7)->toIso8601String(),
        ];
        if ($missing) {
            $data['not_found'] = $missing;
        }
        if ($request->boolean('base64')) {
            $data['label_base64'] = base64_encode($out);
        }
        return Respond::ok($data, 'Label ready');
    }

    /** GET /api/v1/track/{awb} */
    public function track(Request $request, string $awb)
    {
        $seller = Respond::seller($request);
        if ($b2b = $this->findB2b($seller->id, $awb)) {
            app(BigshipService::class)->track($b2b);
            $b2b->refresh();
            $events = array_map(fn ($e) => [
                'status'   => $e['status'] ?? $e['scan_status'] ?? null,
                'location' => $e['location'] ?? $e['scan_location'] ?? null,
                'time'     => $e['datetime'] ?? $e['scan_datetime'] ?? $e['time'] ?? null,
                'remarks'  => $e['remarks'] ?? null,
            ], array_values(array_filter((array) ($b2b->tracking ?? []), 'is_array')));
            return Respond::ok(Present::b2b($b2b) + ['events' => $events]);
        }

        $order = Order::where('seller_id', $seller->id)->where('awb_number', $awb)->first();
        if (!$order) {
            return Respond::error('AWB not found in your account.', 404, 'not_found');
        }
        $r = Respond::legacy($request, TrackController::class, 'trackShipment', ['awb' => $awb]);
        $order->refresh();
        $d = (array) ($r['data'] ?? []);
        $raw = $d['tracking_history'] ?? $d['scans'] ?? $d['events'] ?? $d['history'] ?? [];
        $events = [];
        foreach ((array) $raw as $e) {
            if (!is_array($e)) {
                continue;
            }
            $events[] = [
                'status'   => $e['status'] ?? $e['activity'] ?? $e['ScanType'] ?? $e['Scan'] ?? $e['scan_status'] ?? null,
                'location' => $e['location'] ?? $e['ScannedLocation'] ?? $e['scan_location'] ?? null,
                'time'     => $e['date'] ?? $e['time'] ?? $e['ScanDateTime'] ?? $e['scan_datetime'] ?? $e['timestamp'] ?? null,
                'remarks'  => $e['remarks'] ?? $e['Instructions'] ?? $e['instructions'] ?? null,
            ];
        }
        $out = Present::shipment($order);
        $out['courier_status'] = $d['current_status'] ?? $d['status'] ?? $out['courier_status'];
        $out['expected_delivery_date'] = $d['expected_delivery_date'] ?? $d['edd'] ?? null;
        $out['events'] = $events;
        return Respond::ok($out, Respond::legacyOk($r) ? 'Tracking fetched' : 'Live tracking unavailable right now; showing last known status');
    }

    /** GET /api/v1/ndr - shipments with a failed delivery attempt waiting for your action. */
    public function ndrList(Request $request)
    {
        $seller = Respond::seller($request);
        $rows = Order::where('seller_id', $seller->id)->whereNotNull('awb_number')
            ->where(fn ($w) => $w->where('shipping_status', 'like', '%ndr%')->orWhere('shipping_status', 'like', '%undeliver%')
                ->orWhere('shipping_status', 'like', '%failed%')->orWhere('shipping_status', 'like', '%pending%attempt%'))
            ->orderByDesc('updated_at')->limit(500)->get()
            ->filter(fn ($o) => Present::status($o) === 'ndr')->values();
        return Respond::ok([
            'total' => $rows->count(),
            'shipments' => $rows->map(fn ($o) => Present::shipment($o) + [
                'ndr_reason' => $o->rto_reasen ?? null,
                'allowed_actions' => ['re-attempt', 'change-address', 'change-phone', 'rto'],
            ])->all(),
        ]);
    }

    /** POST /api/v1/ndr/action */
    public function ndrAction(Request $request)
    {
        $seller = Respond::seller($request);
        $v = Validator::make($request->all(), [
            'awb_number'     => 'required|string|max:60',
            'action'         => 'required|in:re-attempt,change-address,change-phone,rto',
            'reattempt_date' => 'nullable|date|after_or_equal:today',
            'name'           => 'nullable|string|max:100',
            'address'        => 'required_if:action,change-address|nullable|string|max:300',
            'landmark'       => 'nullable|string|max:120',
            'phone'          => ['required_if:action,change-phone', 'nullable', 'regex:/^(\+?91)?[6-9]\d{9}$/'],
            'remarks'        => 'nullable|string|max:300',
        ]);
        if ($v->fails()) {
            return Respond::invalid($v);
        }
        $order = Order::where('seller_id', $seller->id)->where('awb_number', $request->awb_number)->first();
        if (!$order) {
            return Respond::error('AWB not found in your account.', 404, 'not_found');
        }
        // Same path as Seller panel > NDR: routed per courier, recorded in the panel's NDR history (source "api").
        $data = [
            'action' => $request->action, 'reattempt_date' => $request->reattempt_date, 'address' => $request->address,
            'landmark' => $request->landmark, 'phone' => $request->phone ? Respond::phone10($request->phone) : null,
            'remark' => $request->remarks,
        ];
        try {
            $suite = app(\App\Http\Controllers\Xpeed\SellerSuiteController::class);
            $res = (fn ($d, $o, $x) => $this->dispatchNdr($d, $o, $x, 'api'))
                ->call($suite, app(\App\Services\WhatsApp\NdrActionDispatcher::class), $order, $data);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('ApiV1 NDR failed', ['awb' => $order->awb_number, 'e' => $e->getMessage()]);
            return Respond::error('NDR action could not be submitted. Try again.', 500, 'ndr_failed');
        }
        $status = (string) ($res['status'] ?? 'failed');
        $out = ['awb_number' => $order->awb_number, 'action' => $request->action, 'result' => $status];
        return match ($status) {
            'sent_to_courier' => Respond::ok($out, 'Request sent to the courier'),
            'needs_manual'    => Respond::ok($out, 'Recorded. The Shipxpeed ops team will raise it with the courier today.'),
            default           => Respond::error('Courier rejected the request: ' . mb_strimwidth((string) ($res['message'] ?? ''), 0, 150, '…'), 422, 'ndr_failed', ['result' => $status]),
        };
    }
}
