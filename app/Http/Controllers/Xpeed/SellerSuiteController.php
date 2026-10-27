<?php

namespace App\Http\Controllers\Xpeed;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\WhatsApp\NdrActionDispatcher;
use App\Support\RateCard;
use App\Support\ShipRate;
use App\Support\Xpeed\CourierPreferences;
use App\Support\Xpeed\Kit;
use App\Support\Xpeed\Protection;
use App\Support\Xpeed\Reports;
use App\Support\Xpeed\RtoRisk;
use App\Support\Xpeed\Tracking;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Seller › Control Center: NDR, bulk actions, courier preferences, reports,
 * RTO risk check, branded tracking, shipment protection & early COD.
 */
class SellerSuiteController extends Controller
{
    private function seller()
    {
        return Auth::guard('seller')->user();
    }

    private function page(string $view, array $data = [])
    {
        $seller = $this->seller();
        return view('sellerdashboard.xpeed.' . $view, $data + [
            'seller' => $seller,
            'totalAmount' => Kit::balance((int) $seller->id),
            'xpReady' => Kit::ready('xp_courier_prefs'),
        ]);
    }

    // ================================================================= NDR

    public function ndr(Request $request)
    {
        $seller = $this->seller();
        $tab = $request->get('tab', 'action');
        $hasNdr = Kit::ready('ndr_responses');

        $q = Order::where('seller_id', $seller->id)->whereNotNull('awb_number')
            ->where('created_at', '>=', now()->subDays(90))
            ->whereRaw("LOWER(REPLACE(REPLACE(COALESCE(shipping_status,''),' ','_'),'-','_')) IN ('ndr','undelivered','failed_delivery','delivery_failed','not_delivered')");
        if ($s = trim((string) $request->get('q'))) {
            $q->where(fn ($w) => $w->where('awb_number', $s)->orWhere('order_number', $s));
        }
        $orders = $q->orderByDesc('updated_at')->limit(300)->get();

        $actions = collect();
        if ($hasNdr) {
            $actions = DB::table('ndr_responses')->where('seller_id', $seller->id)->whereNotNull('action')
                ->orderByDesc('id')->limit(500)->get()->groupBy('order_id');
        }

        $pending = $orders->filter(function ($o) use ($actions) {
            $last = optional($actions->get($o->id))->first();
            return !$last || ($last->responded_at && Carbon::parse($last->responded_at)->lt(Carbon::parse($o->updated_at)->subHours(1)));
        });
        $taken = $orders->diff($pending);

        $history = $hasNdr ? DB::table('ndr_responses')->where('seller_id', $seller->id)->whereNotNull('action')->orderByDesc('id')->limit(100)->get() : collect();
        $awbOf = Order::whereIn('id', $history->pluck('order_id')->unique())->pluck('awb_number', 'id');

        return $this->page('ndr', compact('tab', 'orders', 'pending', 'taken', 'actions', 'history', 'awbOf', 'hasNdr'));
    }

    public function ndrAction(Request $request, NdrActionDispatcher $dispatcher)
    {
        $seller = $this->seller();
        $min = now()->addDay()->toDateString();
        $max = now()->addDays(5)->toDateString();
        $data = $request->validate([
            'order_id'       => 'required|integer',
            'action'         => 'required|in:re-attempt,change-address,change-phone,rto',
            'reattempt_date' => "nullable|date|after_or_equal:{$min}|before_or_equal:{$max}",
            'address'        => 'required_if:action,change-address|nullable|string|min:10|max:300',
            'landmark'       => 'nullable|string|max:120',
            'phone'          => ['required_if:action,change-phone', 'nullable', 'regex:/^[6-9][0-9]{9}$/'],
            'remark'         => 'nullable|string|max:200',
        ], ['phone.regex' => 'Enter a valid 10-digit mobile number.']);

        $order = Order::where('seller_id', $seller->id)->findOrFail($data['order_id']);
        $res = $this->dispatchNdr($dispatcher, $order, $data, 'seller');

        return back()->with($res['status'] === 'failed' ? 'error' : 'success',
            'AWB ' . $order->awb_number . ': ' . $this->ndrMessage($res));
    }

    public function ndrBulk(Request $request, NdrActionDispatcher $dispatcher)
    {
        $seller = $this->seller();
        $data = $request->validate(['order_ids' => 'required|array|max:100', 'order_ids.*' => 'integer', 'action' => 'required|in:re-attempt,rto']);
        $done = ['sent_to_courier' => 0, 'needs_manual' => 0, 'failed' => 0];
        foreach (Order::where('seller_id', $seller->id)->whereIn('id', $data['order_ids'])->get() as $o) {
            $r = $this->dispatchNdr($dispatcher, $o, ['action' => $data['action'], 'reattempt_date' => now()->addDay()->toDateString(), 'remark' => 'Bulk action by seller'], 'seller');
            $done[$r['status']] = ($done[$r['status']] ?? 0) + 1;
        }
        return back()->with('success', "Done: {$done['sent_to_courier']} sent to courier, {$done['needs_manual']} passed to Shipxpeed ops, {$done['failed']} rejected by courier.");
    }

    private function dispatchNdr(NdrActionDispatcher $dispatcher, Order $order, array $data, string $source): array
    {
        if (!Kit::ready('ndr_responses')) {
            return ['status' => 'failed', 'message' => 'NDR module is being set up.'];
        }
        $data['remark'] = trim(ucfirst($source) . ': ' . ($data['remark'] ?? ''), ': ');
        if ($data['action'] !== 'change-address') {
            $data['address'] = $data['landmark'] = null;
        }
        if ($data['action'] !== 'change-phone') {
            $data['phone'] = null;
        }
        if (empty($data['reattempt_date']) && $data['action'] !== 'rto') {
            $data['reattempt_date'] = now()->addDay()->toDateString();
        }
        $res = $dispatcher->dispatch($order, $data['action'], $data);

        $row = [
            'action' => $data['action'], 'reattempt_date' => $data['reattempt_date'] ?? null,
            'address' => $data['address'] ?? null, 'landmark' => $data['landmark'] ?? null,
            'phone' => $data['phone'] ?? null, 'remark' => mb_substr($data['remark'], 0, 255),
            'status' => $res['status'], 'courier_response' => mb_substr(json_encode($res['response'] ?? $res['message']), 0, 60000),
            'responded_at' => now(), 'updated_at' => now(),
        ];
        if (Kit::hasCol('ndr_responses', 'source')) {
            $row['source'] = $source;
        }
        // a pending buyer link for this order is closed by the seller's decision
        $open = DB::table('ndr_responses')->where('order_id', $order->id)->where('status', 'awaiting_buyer')->first();
        if ($open) {
            DB::table('ndr_responses')->where('id', $open->id)->update($row);
        } else {
            DB::table('ndr_responses')->insert($row + [
                'order_id' => $order->id, 'seller_id' => $order->seller_id, 'awb_number' => $order->awb_number,
                'token' => Str::random(40), 'expires_at' => now(), 'created_at' => now(),
            ]);
        }
        return $res;
    }

    private function ndrMessage(array $res): string
    {
        return match ($res['status']) {
            'sent_to_courier' => 'request sent to the courier.',
            'needs_manual' => 'recorded — the Shipxpeed ops team will raise it with the courier today.',
            default => 'courier rejected the request (' . mb_strimwidth((string) $res['message'], 0, 120, '…') . ').',
        };
    }

    // ================================================================= Bulk actions

    public function bulk()
    {
        $ids = (array) session('xp_bulk_found', []);
        $found = $ids ? Order::where('seller_id', $this->seller()->id)->whereIn('id', $ids)->orderByDesc('id')->get() : collect();
        return $this->page('bulk', ['found' => $found, 'missing' => (array) session('xp_bulk_missing', [])]);
    }

    public function bulkRun(Request $request)
    {
        $seller = $this->seller();
        $data = $request->validate(['refs' => 'required|string|max:30000', 'action' => 'required|in:find,labels,manifest,cancel,export']);
        $refs = Kit::tokens($data['refs'], 500);
        $orders = Kit::resolveOrders((int) $seller->id, $refs);
        $missing = array_values(array_diff($refs, $orders->pluck('awb_number')->merge($orders->pluck('order_number'))->merge($orders->pluck('id')->map(fn ($i) => (string) $i))
            ->merge(Kit::hasCol('orders', 'customer_order_id') ? $orders->pluck('customer_order_id') : [])->filter()->map(fn ($v) => (string) $v)->all()));

        if ($orders->isEmpty()) {
            return back()->withInput()->with('error', 'None of these IDs / AWBs belong to your account.');
        }

        switch ($data['action']) {
            case 'export':
                return Kit::csv('shipxpeed-selected-' . now()->format('Ymd-His') . '.csv',
                    ['Order ID', 'Order No.', 'AWB', 'Courier', 'Status', 'Payment', 'Value', 'COD', 'Buyer', 'Phone', 'Pincode', 'Created'],
                    $orders->map(function ($o) {
                        $c = Kit::consignee($o);
                        return [$o->id, Kit::ref($o), $o->awb_number, $o->awb_number ? Kit::courierLabel($o) : '', Kit::BUCKET_LABELS[Kit::bucket($o)] ?? '',
                            strtoupper((string) $o->payment_type), $o->order_amount, $o->collectable_amount, $c['name'] ?? '', $c['phone'] ?? '', $c['pincode'] ?? '', optional($o->created_at)->format('Y-m-d H:i')];
                    }));

            case 'labels':
                $ids = $orders->whereNotNull('awb_number')->pluck('id')->all();
                if (!$ids) {
                    return back()->withInput()->with('error', 'None of these orders has an AWB yet, so there is no label to print.');
                }
                if (!\Illuminate\Support\Facades\Route::has('seller.order.bulk-custom-labels.download')) {
                    return back()->withInput()->with('error', 'Label printing is not available right now. Please print from the Orders page.');
                }
                return $this->page('bulk-post', ['action' => route('seller.order.bulk-custom-labels.download'), 'ids' => $ids, 'what' => count($ids) . ' label(s)']);

            case 'manifest':
                return $this->renderManifest($orders->filter(fn ($o) => $o->awb_number && Kit::bucket($o) === 'booked'));

            case 'cancel':
                $ok = 0; $fail = [];
                foreach ($orders as $o) {
                    $b = Kit::bucket($o);
                    if ($b === 'cancelled') {
                        continue;
                    }
                    if (!$o->awb_number) {
                        $fail[] = Kit::ref($o) . ' (no AWB — delete it from Orders instead)';
                        continue;
                    }
                    if (!in_array($b, ['booked', 'new'], true)) {
                        $fail[] = $o->awb_number . ' (already ' . strtolower(Kit::BUCKET_LABELS[$b] ?? $b) . ')';
                        continue;
                    }
                    try {
                        $sub = Request::create('/couriers/cancel', 'POST', ['awb' => $o->awb_number, 'order_id' => $o->id, 'courier' => $o->courier_id]);
                        $sub->setLaravelSession($request->session());
                        app(\App\Http\Controllers\Api\CourierController::class)->cancelAwb($sub);
                    } catch (\Throwable $e) {
                        \Log::warning('Bulk cancel failed', ['awb' => $o->awb_number, 'error' => $e->getMessage()]);
                    }
                    $o->refresh();
                    if (Kit::bucket($o) === 'cancelled') {
                        $ok++;
                    } else {
                        $fail[] = $o->awb_number;
                    }
                }
                $request->session()->forget(['success', 'error']);
                $msg = "{$ok} shipment(s) cancelled.";
                if ($fail) {
                    $msg .= ' Not cancelled: ' . implode(', ', array_slice($fail, 0, 30)) . (count($fail) > 30 ? '…' : '');
                }
                return back()->with($fail ? 'error' : 'success', $msg);

            default: // find
                return back()->withInput()->with('xp_bulk_found', $orders->pluck('id')->all())->with('xp_bulk_missing', array_slice($missing, 0, 200));
        }
    }

    public function manifest(Request $request)
    {
        $data = $request->validate(['order_ids' => 'required|array|max:500', 'order_ids.*' => 'integer']);
        $orders = Order::where('seller_id', $this->seller()->id)->whereIn('id', $data['order_ids'])->whereNotNull('awb_number')->get()
            ->filter(fn ($o) => Kit::bucket($o) === 'booked');
        return $this->renderManifest($orders);
    }

    private function renderManifest($orders)
    {
        if ($orders->isEmpty()) {
            return back()->with('error', 'None of these shipments is waiting for pickup, so there is nothing to put on a manifest.');
        }
        $groups = $orders->groupBy(fn ($o) => Kit::courierLabel($o))->sortKeys();
        return view('sellerdashboard.xpeed.manifest', ['groups' => $groups, 'seller' => $this->seller(), 'no' => 'MF-' . now()->format('ymd-His')]);
    }

    // ================================================================= Courier preferences

    public function couriers()
    {
        $seller = $this->seller();
        return $this->page('couriers', [
            'prefs' => CourierPreferences::prefs((int) $seller->id),
            'rules' => CourierPreferences::rules((int) $seller->id),
            'brands' => CourierPreferences::brandsFor((int) $seller->id),
            'zones' => $this->zones(),
        ]);
    }

    public function saveCourierPrefs(Request $request)
    {
        $seller = $this->seller();
        $data = $request->validate([
            'mode' => 'required|in:cheapest,fastest,custom',
            'brand_order' => 'nullable|array|max:30', 'brand_order.*' => 'string|max:40',
            'blocked_pincodes' => 'nullable|string|max:60000',
            'block_brand' => 'nullable|array', 'block_brand.*' => 'nullable|string|max:20000',
        ]);
        $blocks = [];
        foreach ((array) ($data['block_brand'] ?? []) as $brand => $raw) {
            $pins = Kit::pincodes($raw, 2000);
            if ($pins && in_array($brand, CourierPreferences::brandsFor((int) $seller->id), true)) {
                $blocks[$brand] = $pins;
            }
        }
        $pins = Kit::pincodes($data['blocked_pincodes'] ?? '', 5000);
        DB::table('xp_courier_prefs')->updateOrInsert(['seller_id' => $seller->id], [
            'mode' => $data['mode'],
            'brand_order' => json_encode(array_values(array_unique(array_filter($data['brand_order'] ?? [])))),
            'blocked_pincodes' => implode(',', $pins),
            'courier_blocks' => json_encode($blocks),
            'updated_at' => now(), 'created_at' => now(),
        ]);
        return back()->with('success', 'Courier preferences saved. ' . count($pins) . ' pincode(s) blocked for all couriers.');
    }

    public function saveRule(Request $request)
    {
        $seller = $this->seller();
        $data = $request->validate([
            'id' => 'nullable|integer',
            'name' => 'required|string|max:120',
            'priority' => 'nullable|integer|min:1|max:999',
            'payment' => 'required|in:any,cod,prepaid',
            'min_value' => 'nullable|numeric|min:0', 'max_value' => 'nullable|numeric|min:0',
            'min_weight_kg' => 'nullable|numeric|min:0|max:100', 'max_weight_kg' => 'nullable|numeric|min:0|max:100',
            'zones' => 'nullable|array', 'zones.*' => 'in:' . implode(',', $this->zones()),
            'pincodes' => 'nullable|string|max:20000',
            'courier_brand' => 'required|string|max:60',
            'courier_mode' => 'required|in:any,surface,air',
        ]);
        if (!in_array($data['courier_brand'], CourierPreferences::brandsFor((int) $seller->id), true)) {
            return back()->withInput()->with('error', 'Pick a courier from the list.');
        }
        $row = [
            'seller_id' => $seller->id, 'name' => strip_tags($data['name']), 'priority' => $data['priority'] ?? 10, 'active' => 1,
            'payment' => $data['payment'], 'min_value' => $data['min_value'] ?? null, 'max_value' => $data['max_value'] ?? null,
            'min_weight_g' => isset($data['min_weight_kg']) ? (int) round($data['min_weight_kg'] * 1000) : null,
            'max_weight_g' => isset($data['max_weight_kg']) ? (int) round($data['max_weight_kg'] * 1000) : null,
            'zones' => !empty($data['zones']) ? implode(',', $data['zones']) : null,
            'pincodes' => ($p = Kit::pincodes($data['pincodes'] ?? '', 2000)) ? implode(',', $p) : null,
            'courier_brand' => $data['courier_brand'], 'courier_mode' => $data['courier_mode'], 'updated_at' => now(),
        ];
        if (!empty($data['id'])) {
            DB::table('xp_courier_rules')->where('seller_id', $seller->id)->where('id', $data['id'])->update($row);
        } else {
            if (DB::table('xp_courier_rules')->where('seller_id', $seller->id)->count() >= 50) {
                return back()->with('error', 'You can keep up to 50 rules.');
            }
            DB::table('xp_courier_rules')->insert($row + ['created_at' => now()]);
        }
        return back()->with('success', 'Rule "' . $row['name'] . '" saved.');
    }

    private function zones(): array
    {
        try {
            return array_slice(RateCard::zones(), 0, 12);
        } catch (\Throwable $e) {
            return ['A', 'B', 'C', 'D', 'E'];
        }
    }

    public function deleteRule(int $id)
    {
        DB::table('xp_courier_rules')->where('seller_id', $this->seller()->id)->where('id', $id)->delete();
        return back()->with('success', 'Rule deleted.');
    }

    public function toggleRule(int $id)
    {
        $r = DB::table('xp_courier_rules')->where('seller_id', $this->seller()->id)->where('id', $id)->first();
        if ($r) {
            DB::table('xp_courier_rules')->where('id', $id)->update(['active' => $r->active ? 0 : 1, 'updated_at' => now()]);
        }
        return back()->with('success', $r && $r->active ? 'Rule paused.' : 'Rule switched on.');
    }

    public function zoneLookup(Request $request)
    {
        $data = $request->validate(['from' => ['required', 'regex:/^[1-9][0-9]{5}$/'], 'to' => ['required', 'regex:/^[1-9][0-9]{5}$/']]);
        try {
            $fromState = ShipRate::stateForPincode($data['from']);
            $toState = ShipRate::stateForPincode($data['to']);
            $zone = ShipRate::zoneForPincodes($data['from'], $data['to']);
        } catch (\Throwable $e) {
            $fromState = $toState = $zone = null;
        }
        return response()->json([
            'zone' => $zone ? strtoupper((string) $zone) : null, 'zone_name' => null,
            'from_state' => $fromState, 'to_state' => $toState,
        ]);
    }

    // ================================================================= Reports

    public function reports()
    {
        $seller = $this->seller();
        $schedules = Kit::ready('xp_report_schedules') ? DB::table('xp_report_schedules')->where('seller_id', $seller->id)->orderByDesc('id')->get() : collect();
        return $this->page('reports', ['schedules' => $schedules, 'types' => Reports::TYPES, 'frequencies' => Reports::FREQUENCIES]);
    }

    public function reportDownload(Request $request)
    {
        $data = $request->validate([
            'report' => 'required|in:' . implode(',', array_keys(Reports::TYPES)),
            'from' => 'required|date', 'to' => 'required|date|after_or_equal:from',
        ]);
        $from = Carbon::parse($data['from']);
        $to = Carbon::parse($data['to']);
        if ($from->diffInDays($to) > 366) {
            return back()->with('error', 'Pick a range of one year or less.');
        }
        [$head, $rows] = Reports::build((int) $this->seller()->id, $data['report'], $from, $to);
        return Kit::csv('shipxpeed-' . $data['report'] . '-' . $from->format('Ymd') . '-' . $to->format('Ymd') . '.csv', $head, $rows);
    }

    public function reportSchedule(Request $request)
    {
        $seller = $this->seller();
        $data = $request->validate([
            'report' => 'required|in:' . implode(',', array_keys(Reports::TYPES)),
            'frequency' => 'required|in:daily,weekly,monthly',
            'emails' => 'required|string|max:500',
        ]);
        $emails = array_values(array_filter(Kit::tokens($data['emails'], 10), fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL)));
        if (!$emails) {
            return back()->withInput()->with('error', 'Enter at least one valid e-mail address.');
        }
        if (DB::table('xp_report_schedules')->where('seller_id', $seller->id)->count() >= 10) {
            return back()->with('error', 'You can keep up to 10 scheduled reports.');
        }
        DB::table('xp_report_schedules')->insert([
            'seller_id' => $seller->id, 'report' => $data['report'], 'frequency' => $data['frequency'],
            'emails' => implode(', ', $emails), 'active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        return back()->with('success', 'Scheduled. The first e-mail goes out with the next run after 8 am.');
    }

    public function reportScheduleDelete(int $id)
    {
        DB::table('xp_report_schedules')->where('seller_id', $this->seller()->id)->where('id', $id)->delete();
        return back()->with('success', 'Scheduled report removed.');
    }

    // ================================================================= RTO risk

    public function risk(Request $request)
    {
        $seller = $this->seller();
        $orders = Order::where('seller_id', $seller->id)
            ->where(fn ($q) => $q->whereNull('awb_number')->orWhere('awb_number', ''))
            ->where('created_at', '>=', now()->subDays(30))
            ->when($request->get('cod', '1') === '1', fn ($q) => $q->where('payment_type', 'cod'))
            ->orderByDesc('id')->limit(150)->get()
            ->filter(fn ($o) => Kit::bucket($o) === 'new');
        $rows = $orders->map(fn ($o) => ['order' => $o, 'risk' => RtoRisk::score($o)])
            ->sortByDesc(fn ($r) => $r['risk']['score'])->values();
        return $this->page('risk', ['rows' => $rows, 'codOnly' => $request->get('cod', '1') === '1']);
    }

    // ================================================================= Branded tracking

    public function tracking()
    {
        $seller = $this->seller();
        $brand = Tracking::brand((int) $seller->id);
        $sample = Order::where('seller_id', $seller->id)->whereNotNull('awb_number')->where('awb_number', '!=', '')->orderByDesc('id')->first();
        return $this->page('tracking', ['brand' => $brand, 'sample' => $sample]);
    }

    public function saveTracking(Request $request)
    {
        $seller = $this->seller();
        $data = $request->validate([
            'active' => 'nullable|in:0,1',
            'brand_name' => 'nullable|string|max:80',
            'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'support_phone' => ['nullable', 'regex:/^[0-9+\- ]{8,20}$/'],
            'support_email' => 'nullable|email|max:120',
            'website' => 'nullable|url|max:190',
            'banner_text' => 'nullable|string|max:160',
            'banner_link' => 'nullable|url|max:255',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:1024',
            'remove_logo' => 'nullable|in:1',
        ]);
        $row = [
            'active' => (int) ($data['active'] ?? 1),
            'brand_name' => isset($data['brand_name']) ? strip_tags($data['brand_name']) : null,
            'color' => strtolower($data['color']),
            'support_phone' => $data['support_phone'] ?? null, 'support_email' => $data['support_email'] ?? null,
            'website' => $data['website'] ?? null,
            'banner_text' => isset($data['banner_text']) ? strip_tags($data['banner_text']) : null,
            'banner_link' => $data['banner_link'] ?? null,
            'updated_at' => now(),
        ];
        $old = Tracking::brand((int) $seller->id);
        if ($request->hasFile('logo')) {
            $dir = public_path('uploads/tracking-logos');
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
            $name = $seller->id . '-' . Str::random(10) . '.' . $request->file('logo')->extension();
            $request->file('logo')->move($dir, $name);
            $row['logo_path'] = 'uploads/tracking-logos/' . $name;
            if ($old && $old->logo_path && str_starts_with($old->logo_path, 'uploads/tracking-logos/')) {
                @unlink(public_path($old->logo_path));
            }
        } elseif (!empty($data['remove_logo'])) {
            $row['logo_path'] = null;
        }
        DB::table('xp_tracking_brands')->updateOrInsert(['seller_id' => $seller->id], $row + ['created_at' => now()]);
        return back()->with('success', 'Tracking page saved.');
    }

    // ================================================================= Protection, claims, early COD

    public function protection()
    {
        $seller = $this->seller();
        $svc = Protection::service((int) $seller->id);
        $ready = Kit::ready('xp_seller_services', 'xp_protection_charges', 'xp_claims');
        $charges = $ready ? DB::table('xp_protection_charges')->where('seller_id', $seller->id)->orderByDesc('id')->limit(50)->get() : collect();
        $claims = $ready ? DB::table('xp_claims')->where('seller_id', $seller->id)->orderByDesc('id')->limit(50)->get() : collect();
        $month = $ready ? DB::table('xp_protection_charges')->where('seller_id', $seller->id)->where('status', 'charged')
            ->where('created_at', '>=', now()->startOfMonth())->selectRaw('COUNT(*) c, COALESCE(SUM(fee),0) f')->first() : null;
        $dueAmount = $svc->early_cod_status === 'active' && $svc->early_cod_plan
            ? (float) Protection::earlyCodDue((int) $seller->id, $svc->early_cod_plan)->sum('collectable_amount') : 0;
        return $this->page('protection', [
            'svc' => $svc, 'ready' => $ready, 'charges' => $charges, 'claims' => $claims, 'month' => $month,
            'plans' => Protection::earlyCodPlans(), 'dueAmount' => $dueAmount,
            'cfg' => [
                'enabled' => Kit::setting('protection_enabled') === '1', 'pct' => Kit::num('protection_fee_pct'), 'min' => Kit::num('protection_min_fee'),
                'min_value' => Kit::num('protection_min_value'), 'max_cover' => Kit::num('protection_max_cover'),
                'early' => Kit::setting('early_cod_enabled') === '1', 'window' => (int) Kit::num('claim_window_days'),
            ],
        ]);
    }

    public function saveProtection(Request $request)
    {
        $seller = $this->seller();
        $on = $request->validate(['on' => 'required|in:0,1'])['on'] === '1';
        if ($on && Kit::setting('protection_enabled') !== '1') {
            return back()->with('error', 'Shipment protection is not open for new sign-ups right now.');
        }
        $svc = Protection::service((int) $seller->id);
        Protection::saveService((int) $seller->id, ['protection_on' => $on ? 1 : 0, 'protection_since' => $on ? ($svc->protection_on ? $svc->protection_since : now()) : $svc->protection_since]);
        return back()->with('success', $on ? 'Shipment protection is ON for every new shipment from now.' : 'Shipment protection is OFF. Shipments already protected stay covered.');
    }

    public function earlyCod(Request $request)
    {
        $seller = $this->seller();
        $data = $request->validate(['plan' => 'required|in:d2,d3,d4,stop']);
        if ($data['plan'] === 'stop') {
            Protection::saveService((int) $seller->id, ['early_cod_status' => 'stopped', 'early_cod_decided_at' => now()]);
            return back()->with('success', 'Early COD stopped. Your COD goes back to the normal remittance cycle.');
        }
        if (Kit::setting('early_cod_enabled') !== '1') {
            return back()->with('error', 'Early COD is not open for new requests right now.');
        }
        Protection::saveService((int) $seller->id, ['early_cod_plan' => $data['plan'], 'early_cod_status' => 'requested', 'early_cod_requested_at' => now(), 'early_cod_note' => null]);
        return back()->with('success', 'Request sent. Shipxpeed will review it within one working day.');
    }

    public function claim(Request $request)
    {
        $seller = $this->seller();
        $data = $request->validate([
            'awb' => 'required|string|max:60',
            'type' => 'required|in:lost,damaged,missing_item',
            'description' => 'required|string|min:15|max:1500',
            'claim_amount' => 'required|numeric|min:1|max:500000',
            'proofs' => 'nullable|array|max:5',
            'proofs.*' => 'file|mimes:jpg,jpeg,png,webp,pdf,mp4,mov|max:10240',
        ]);
        $order = Order::where('seller_id', $seller->id)->where('awb_number', trim($data['awb']))->first();
        if (!$order) {
            return back()->withInput()->with('error', 'This AWB is not on your account.');
        }
        if (DB::table('xp_claims')->where('order_id', $order->id)->whereIn('status', ['pending', 'approved'])->exists()) {
            return back()->withInput()->with('error', 'A claim for this AWB is already open or settled.');
        }
        if ($order->created_at && $order->created_at->lt(now()->subDays(max(15, (int) Kit::num('claim_window_days') + 30)))) {
            return back()->withInput()->with('error', 'This shipment is too old to claim.');
        }
        $b = Kit::bucket($order);
        if ($data['type'] !== 'lost' && $b !== 'delivered' && $b !== 'rto') {
            return back()->withInput()->with('error', 'Damage / missing-item claims can be raised once the shipment is delivered or returned.');
        }
        $proofs = [];
        foreach ((array) $request->file('proofs', []) as $f) {
            $path = $f->storeAs('xp-claims/' . $order->id, Str::random(24) . '.' . strtolower($f->getClientOriginalExtension() ?: 'bin'), 'local');
            $proofs[] = ['path' => $path, 'name' => mb_substr($f->getClientOriginalName(), 0, 120), 'mime' => (string) $f->getMimeType()];
        }
        $protected = Protection::isProtected((int) $order->id);
        $amount = min((float) $data['claim_amount'], (float) $order->order_amount ?: (float) $data['claim_amount']);
        DB::table('xp_claims')->insert([
            'seller_id' => $seller->id, 'order_id' => $order->id, 'awb' => $order->awb_number, 'type' => $data['type'],
            'description' => strip_tags($data['description']), 'proofs' => json_encode($proofs),
            'declared_value' => (float) $order->order_amount, 'claim_amount' => round($amount, 2),
            'protected' => $protected ? 1 : 0, 'status' => 'pending', 'created_at' => now(), 'updated_at' => now(),
        ]);
        return back()->with('success', 'Claim filed for AWB ' . $order->awb_number . ($protected ? ' (protected shipment — priority review).' : '. We will take it up with the courier.'));
    }

    public function claimProof(int $id, int $n)
    {
        $c = DB::table('xp_claims')->where('seller_id', $this->seller()->id)->where('id', $id)->first();
        $p = $c ? (json_decode((string) $c->proofs, true)[$n] ?? null) : null;
        abort_unless($p && Storage::disk('local')->exists($p['path']), 404);
        return Storage::disk('local')->response($p['path'], $p['name']);
    }
}
