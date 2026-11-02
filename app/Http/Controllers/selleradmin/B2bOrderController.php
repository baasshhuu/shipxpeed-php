<?php

namespace App\Http\Controllers\selleradmin;

use App\Http\Controllers\Controller;
use App\Models\B2bOrder;
use App\Models\Warehouse;
use App\Services\BigshipService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/** Seller > B2B orders (domestic B2B, multi-box, invoice + e-way bill). */
class B2bOrderController extends Controller
{
    public function __construct(private BigshipService $bigship)
    {
    }

    private function seller()
    {
        return Auth::guard('seller')->user();
    }

    private function own(int $id): B2bOrder
    {
        return B2bOrder::where('seller_id', $this->seller()->id)->findOrFail($id);
    }

    public function index(Request $request)
    {
        $status = $request->query('status');
        $q = B2bOrder::where('seller_id', $this->seller()->id)->orderByDesc('id');
        if ($status && isset(B2bOrder::STATUS_LABELS[$status])) {
            $q->where('status', $status);
        }
        if ($s = trim((string) $request->query('q'))) {
            $q->where(fn ($w) => $w->where('order_ref', 'like', "%$s%")->orWhere('awb', 'like', "%$s%")->orWhere('invoice_no', 'like', "%$s%"));
        }
        $orders = $q->paginate(20)->withQueryString();

        // keep in-transit shipments fresh (a few per page view, at most every 2 hours each)
        $stale = B2bOrder::where('seller_id', $this->seller()->id)
            ->whereIn('status', ['booked', 'in_transit'])
            ->where(fn ($w) => $w->whereNull('last_tracked_at')->orWhere('last_tracked_at', '<', now()->subHours(2)))
            ->limit(3)->get();
        foreach ($stale as $o) {
            try {
                $this->bigship->track($o);
            } catch (\Throwable $e) {
            }
        }

        $counts = B2bOrder::where('seller_id', $this->seller()->id)->selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status');

        return view('sellerdashboard.b2b.index', [
            'orders'  => $orders,
            'status'  => $status,
            'counts'  => $counts,
            'enabled' => $this->bigship->enabledForSellers(),
        ]);
    }

    public function create()
    {
        $warehouses = Warehouse::where('seller_id', $this->seller()->id)->orderByDesc('is_default')->orderBy('id')->get();
        return view('sellerdashboard.b2b.create', [
            'warehouses' => $warehouses,
            'enabled'    => $this->bigship->enabledForSellers(),
            'balance'    => BigshipService::walletBalance((int) $this->seller()->id),
            'threshold'  => (int) config('bigship.ewaybill_threshold', 50000),
        ]);
    }

    private function boxRules(): array
    {
        return [
            'boxes'           => 'required|array|min:1|max:50',
            'boxes.*.count'   => 'required|integer|min:1|max:999',
            'boxes.*.length'  => 'required|numeric|min:1|max:500',
            'boxes.*.breadth' => 'required|numeric|min:1|max:500',
            'boxes.*.height'  => 'required|numeric|min:1|max:500',
            'boxes.*.weight'  => 'required|numeric|min:0.1|max:2000',
        ];
    }

    private function warehouseFor($id): ?Warehouse
    {
        return $id ? Warehouse::where('seller_id', $this->seller()->id)->find($id) : null;
    }

    private static function addr(Warehouse $w): array
    {
        return [
            'title'         => $w->address_title,
            'name'          => $w->name,
            'phone'         => $w->phone,
            'email'         => $w->email,
            'address_line1' => $w->address_line1,
            'address_line2' => $w->address_line2,
            'city'          => $w->city,
            'state'         => $w->state,
            'pincode'       => (string) $w->pincode,
        ];
    }

    /** AJAX: courier options with seller prices. */
    public function quote(Request $request)
    {
        abort_unless($this->bigship->enabledForSellers(), 403, 'B2B shipping is not enabled yet.');
        $d = $request->validate([
            'pickup_warehouse_id' => 'required|integer',
            'to_pincode'          => 'required|digits:6',
            'invoice_amount'      => 'required|numeric|min:1',
            'payment_mode'        => 'required|in:prepaid,cod',
            'cod_amount'          => 'nullable|numeric|min:0',
            'risk_type_id'        => 'required|in:2,3',
        ] + $this->boxRules());
        $wh = $this->warehouseFor($d['pickup_warehouse_id']);
        if (!$wh) {
            return response()->json(['ok' => false, 'error' => 'Choose a pickup warehouse.'], 422);
        }
        if ($d['payment_mode'] === 'cod' && (float) ($d['cod_amount'] ?? 0) >= (float) $d['invoice_amount']) {
            return response()->json(['ok' => false, 'error' => 'COD amount must be less than the invoice amount.'], 422);
        }

        $res = $this->bigship->quote([
            'from'           => $wh->pincode,
            'to'             => $d['to_pincode'],
            'invoice_amount' => $d['invoice_amount'],
            'payment'        => $d['payment_mode'],
            'cod_amount'     => $d['cod_amount'] ?? 0,
            'risk_type_id'   => $d['risk_type_id'],
            'boxes'          => BigshipService::normaliseBoxes($d['boxes']),
        ]);
        if (!$res['ok']) {
            return response()->json(['ok' => false, 'error' => 'Could not get rates: ' . $res['error']], 422);
        }
        return response()->json([
            'ok'    => true,
            'rates' => array_map(fn ($r) => [
                'courier_id'     => $r['courier_id'],
                'courier_name'   => $r['courier_name'],
                'tat'            => $r['tat'],
                'charged_weight' => $r['charged_weight'],
                'price'          => $r['price'],
                'note'           => $r['note'],
            ], $res['rates']),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($this->bigship->enabledForSellers(), 403, 'B2B shipping is not enabled yet.');
        $threshold = (int) config('bigship.ewaybill_threshold', 50000);
        $needEway = (float) $request->input('invoice_amount') >= $threshold;

        $d = $request->validate([
            'pickup_warehouse_id'   => 'required|integer',
            'return_warehouse_id'   => 'nullable|integer',
            'consignee.name'        => 'required|string|max:100',
            'consignee.company'     => 'nullable|string|max:100',
            'consignee.phone'       => ['required', 'regex:/^(\+?91[\s-]?)?[6-9]\d{9}$/'],
            'consignee.email'       => 'nullable|email|max:120',
            'consignee.gstin'       => 'nullable|string|max:15',
            'consignee.address_line1' => 'required|string|min:3|max:200',
            'consignee.address_line2' => 'nullable|string|max:200',
            'consignee.landmark'    => 'nullable|string|max:100',
            'consignee.pincode'     => 'required|digits:6',
            'consignee.city'        => 'required|string|max:60',
            'consignee.state'       => 'required|string|max:60',
            'invoice_no'            => 'required|string|max:60',
            'invoice_date'          => 'nullable|date',
            'invoice_amount'        => 'required|numeric|min:1|max:99999999',
            'payment_mode'          => 'required|in:prepaid,cod',
            'cod_amount'            => 'required_if:payment_mode,cod|nullable|numeric|min:1|lt:invoice_amount',
            'product_name'          => 'required|string|max:150',
            'risk_type_id'          => 'required|in:2,3',
            'invoice_file'          => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'ewaybill_no'           => [$needEway ? 'required' : 'nullable', 'regex:/^\d{12}$/'],
            'ewaybill_file'         => [$needEway ? 'required' : 'nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'courier_id'            => 'required|string|max:20',
            'quoted_price'          => 'required|numeric|min:1',
        ] + $this->boxRules(), [
            'consignee.phone.regex'   => 'Receiver phone must be a 10-digit Indian mobile number.',
            'ewaybill_no.required'    => 'E-way bill number is required for invoices of ₹' . number_format($threshold) . ' or more.',
            'ewaybill_no.regex'       => 'E-way bill number has 12 digits.',
            'ewaybill_file.required'  => 'Upload the e-way bill for invoices of ₹' . number_format($threshold) . ' or more.',
            'courier_id.required'     => 'Get rates and choose a courier first.',
            'cod_amount.lt'           => 'COD amount must be less than the invoice amount.',
        ]);

        $pick = $this->warehouseFor($d['pickup_warehouse_id']);
        if (!$pick) {
            return back()->withInput()->with('error', 'Choose a pickup warehouse.');
        }
        $ret = $this->warehouseFor($d['return_warehouse_id'] ?? null) ?: $pick;
        $boxes = BigshipService::normaliseBoxes($d['boxes']);
        $sellerId = (int) $this->seller()->id;

        $order = B2bOrder::create([
            'seller_id'           => $sellerId,
            'status'              => 'draft',
            'pickup_warehouse_id' => $pick->id,
            'return_warehouse_id' => $ret->id,
            'pickup'              => self::addr($pick),
            'return_address'      => self::addr($ret),
            'consignee'           => array_merge($d['consignee'], ['phone' => BigshipService::phone10($d['consignee']['phone'])]),
            'invoice_no'          => $d['invoice_no'],
            'invoice_date'        => $d['invoice_date'] ?? now()->toDateString(),
            'invoice_amount'      => $d['invoice_amount'],
            'payment_mode'        => $d['payment_mode'],
            'cod_amount'          => $d['payment_mode'] === 'cod' ? $d['cod_amount'] : 0,
            'product_name'        => $d['product_name'],
            'risk_type_id'        => (int) $d['risk_type_id'],
            'boxes'               => $boxes,
            'total_boxes'         => array_sum(array_column($boxes, 'count')),
            'total_weight'        => array_sum(array_map(fn ($b) => $b['count'] * $b['weight'], $boxes)),
            'ewaybill_no'         => $d['ewaybill_no'] ?? null,
            'quote'               => ['courier_id' => $d['courier_id'], 'price' => (float) $d['quoted_price']],
        ]);
        $order->order_ref = 'B2B' . str_pad((string) $order->id, 6, '0', STR_PAD_LEFT);
        $dir = "b2b/{$sellerId}/{$order->id}";
        $order->invoice_file = $request->file('invoice_file')->store($dir, 'local');
        if ($request->hasFile('ewaybill_file')) {
            $order->ewaybill_file = $request->file('ewaybill_file')->store($dir, 'local');
        }
        $order->save();

        $res = $this->bigship->book($order, (string) $d['courier_id'], (float) $d['quoted_price']);
        return redirect()->route('seller.b2b.order.show', $order->id)->with($res['ok'] ? 'success' : 'error', $res['message']);
    }

    public function show(int $id)
    {
        $order = $this->own($id);
        if (in_array($order->status, ['booked', 'in_transit'], true) && (!$order->last_tracked_at || $order->last_tracked_at->lt(now()->subMinutes(30)))) {
            try {
                $this->bigship->track($order);
            } catch (\Throwable $e) {
            }
            $order->refresh();
        }
        return view('sellerdashboard.b2b.show', [
            'order'   => $order,
            'enabled' => $this->bigship->enabledForSellers(),
            'balance' => BigshipService::walletBalance((int) $this->seller()->id),
        ]);
    }

    /** AJAX: rates for a saved draft. */
    public function draftRates(int $id)
    {
        $order = $this->own($id);
        abort_unless($order->status === 'draft' && $this->bigship->enabledForSellers(), 404);
        $res = $this->bigship->quote([
            'from'           => $order->pickup['pincode'] ?? '',
            'to'             => $order->consignee['pincode'] ?? '',
            'invoice_amount' => $order->invoice_amount,
            'payment'        => $order->payment_mode,
            'cod_amount'     => $order->cod_amount,
            'risk_type_id'   => $order->risk_type_id,
            'boxes'          => $order->boxes ?? [],
        ]);
        if (!$res['ok']) {
            return response()->json(['ok' => false, 'error' => 'Could not get rates: ' . $res['error']], 422);
        }
        $final = $order->quote['final_price'] ?? null;
        $finalCourier = (string) ($order->quote['courier_id'] ?? '');
        return response()->json(['ok' => true, 'rates' => array_map(fn ($r) => [
            'courier_id'     => $r['courier_id'],
            'courier_name'   => $r['courier_name'],
            'tat'            => $r['tat'],
            'charged_weight' => $r['charged_weight'],
            // a confirmed final price replaces the estimate for that courier
            'price'          => ($final && $finalCourier === $r['courier_id']) ? max($final, $r['price']) : $r['price'],
            'note'           => $r['note'],
        ], $res['rates'])]);
    }

    public function book(Request $request, int $id)
    {
        $order = $this->own($id);
        $d = $request->validate(['courier_id' => 'required|string|max:20', 'quoted_price' => 'required|numeric|min:1']);
        abort_unless($this->bigship->enabledForSellers(), 403);
        $res = $this->bigship->book($order, $d['courier_id'], (float) $d['quoted_price']);
        return redirect()->route('seller.b2b.order.show', $order->id)->with($res['ok'] ? 'success' : 'error', $res['message']);
    }

    public function cancel(int $id)
    {
        $order = $this->own($id);
        if ($order->status === 'draft') {
            $order->status = 'cancelled';
            $order->cancelled_at = now();
            $order->save();
            return redirect()->route('seller.b2b.order.show', $order->id)->with('success', 'Draft cancelled.');
        }
        $res = $this->bigship->cancel($order);
        return redirect()->route('seller.b2b.order.show', $order->id)->with($res['ok'] ? 'success' : 'error', $res['message']);
    }

    public function track(int $id)
    {
        $order = $this->own($id);
        $res = $this->bigship->track($order);
        return redirect()->route('seller.b2b.order.show', $order->id)->with($res['ok'] ? 'success' : 'error', 'Tracking: ' . $res['message']);
    }

    /** Label / manifest / invoice / e-way bill from the courier, streamed through the panel. */
    public function document(int $id, string $type)
    {
        abort_unless(in_array($type, ['label', 'manifest', 'invoice', 'ewaybill'], true), 404);
        $order = $this->own($id);
        abort_unless($order->provider_order_id && $order->status !== 'draft', 404);
        $doc = $this->bigship->documentUrl($order, $type);
        if (!$doc['ok']) {
            return back()->with('error', ucfirst($type) . ': ' . $doc['error']);
        }
        try {
            $r = Http::timeout(40)->get($doc['url']);
            if ($r->successful() && strlen($r->body()) > 100) {
                $ext = str_contains($doc['mime'], 'pdf') ? 'pdf' : (explode('/', $doc['mime'])[1] ?? 'pdf');
                return response($r->body(), 200, [
                    'Content-Type'        => $doc['mime'] ?: 'application/pdf',
                    'Content-Disposition' => 'inline; filename="' . $order->order_ref . '-' . $type . '.' . $ext . '"',
                ]);
            }
        } catch (\Throwable $e) {
        }
        return redirect()->away($doc['url']);
    }

    /** The seller's own uploaded invoice / e-way bill. */
    public function file(int $id, string $which)
    {
        $order = $this->own($id);
        $path = $which === 'invoice' ? $order->invoice_file : ($which === 'ewaybill' ? $order->ewaybill_file : null);
        abort_unless($path && Storage::disk('local')->exists($path), 404);
        return Storage::disk('local')->response($path, $order->order_ref . '-' . $which . '.' . pathinfo($path, PATHINFO_EXTENSION));
    }
}
