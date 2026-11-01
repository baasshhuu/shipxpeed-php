<?php

namespace App\Http\Controllers\selleradmin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Recharge;
use App\Models\Ticket;
use App\Models\Warehouse;
use App\Support\Label\LabelRenderer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Xpeed Assistant - seller-side AI helper (Claude API with tools).
 * Read actions run immediately. Anything that changes data (raise ticket, create order,
 * cancel) is only *proposed*; it runs after the seller taps Confirm in the chat.
 */
class AssistantController extends Controller
{
    private const MAX_TURNS = 24;     // messages kept in session
    private const MAX_TOOL_LOOPS = 6;

    private function seller()
    {
        return auth()->guard('seller')->user();
    }

    private function sessionKey(): string
    {
        return 'xpeed_assistant_' . auth()->guard('seller')->id();
    }

    public function chat(Request $request)
    {
        $request->validate(['message' => 'required|string|max:2000']);
        $seller = $this->seller();
        if (!$seller) {
            return response()->json(['reply' => 'Please log in again.'], 401);
        }
        $key = config('services.anthropic.key') ?: env('ANTHROPIC_API_KEY');
        if (!$key) {
            return response()->json(['reply' => 'The assistant is not switched on yet: the admin needs to add ANTHROPIC_API_KEY to the server .env file.']);
        }

        $history = session($this->sessionKey(), []);
        $history[] = ['role' => 'user', 'content' => trim($request->input('message'))];

        $pending = null;
        $reply = '';
        try {
            for ($loop = 0; $loop < self::MAX_TOOL_LOOPS; $loop++) {
                $res = $this->callClaude($key, $history);
                $content = $res['content'] ?? [];
                $history[] = ['role' => 'assistant', 'content' => $content];

                $toolUses = array_values(array_filter($content, fn ($b) => ($b['type'] ?? '') === 'tool_use'));
                $reply = trim(implode("\n", array_map(fn ($b) => $b['text'] ?? '', array_filter($content, fn ($b) => ($b['type'] ?? '') === 'text'))));

                if (!$toolUses) {
                    break;
                }
                $results = [];
                foreach ($toolUses as $tu) {
                    [$out, $proposal] = $this->runTool($tu['name'], $tu['input'] ?? [], $seller);
                    if ($proposal) {
                        $pending = $proposal;
                    }
                    $results[] = ['type' => 'tool_result', 'tool_use_id' => $tu['id'], 'content' => json_encode($out, JSON_UNESCAPED_UNICODE)];
                }
                $history[] = ['role' => 'user', 'content' => $results];
                if (($res['stop_reason'] ?? '') !== 'tool_use') {
                    break;
                }
            }
        } catch (\Throwable $e) {
            Log::error('Assistant error: ' . $e->getMessage());
            return response()->json(['reply' => 'Sorry, I could not reach the AI service just now. Please try again in a minute.']);
        }

        // trim history but keep it starting with a plain user message
        $history = array_slice($history, -self::MAX_TURNS);
        while ($history && !($history[0]['role'] === 'user' && is_string($history[0]['content']))) {
            array_shift($history);
        }
        session([$this->sessionKey() => $history]);

        if ($pending) {
            $token = Str::random(24);
            session(['xpeed_assistant_pending_' . $token => $pending]);
            $pending = ['token' => $token, 'title' => $pending['title'], 'summary' => $pending['summary']];
        }

        return response()->json(['reply' => $reply ?: ($pending ? 'Please review and confirm below.' : 'Done.'), 'pending' => $pending]);
    }

    public function confirm(Request $request)
    {
        $request->validate(['token' => 'required|string|max:40', 'decision' => 'required|in:confirm,cancel']);
        $k = 'xpeed_assistant_pending_' . $request->token;
        $action = session($k);
        session()->forget($k);
        if (!$action) {
            return response()->json(['reply' => 'That request expired. Please ask again.']);
        }
        if ($request->decision === 'cancel') {
            $msg = 'Cancelled - nothing was changed.';
        } else {
            try {
                $msg = $this->execute($action, $this->seller());
            } catch (\Throwable $e) {
                Log::error('Assistant action failed: ' . $e->getMessage());
                $msg = 'That did not work: ' . mb_substr($e->getMessage(), 0, 200);
            }
        }
        $history = session($this->sessionKey(), []);
        $history[] = ['role' => 'user', 'content' => '[Seller ' . ($request->decision === 'confirm' ? 'confirmed' : 'cancelled') . ' the proposed action] Result: ' . $msg];
        $history[] = ['role' => 'assistant', 'content' => [['type' => 'text', 'text' => $msg]]];
        session([$this->sessionKey() => array_slice($history, -self::MAX_TURNS)]);
        return response()->json(['reply' => $msg]);
    }

    public function reset()
    {
        session()->forget($this->sessionKey());
        return response()->json(['ok' => true]);
    }

    // ---------------------------------------------------------------- Claude

    private function callClaude(string $key, array $history): array
    {
        $seller = $this->seller();
        $system = "You are Xpeed Assistant inside the Shipxpeed seller panel (a courier aggregator for Indian e-commerce sellers). "
            . "You help seller '" . ($seller->name ?? 'Seller') . "' (seller id {$seller->id}). Today is " . now('Asia/Kolkata')->format('d M Y') . ". "
            . "Use the tools to look up their real orders, shipments, failure reasons, wallet balance and warehouses. Never invent order data. "
            . "You can propose actions (raise_ticket, create_order, cancel_order); they only run after the seller taps Confirm, so say that. "
            . "Before create_order you need: buyer name, 10-digit phone, full address, city, state, 6-digit PIN, product name, quantity, price, payment type (cod/prepaid), weight in kg and box size in cm; ask for whatever is missing in one short message. "
            . "If the seller has one warehouse use it; if several, ask which. "
            . "After an order is created, tell them to open New Orders and pick a courier (Ship Now) - booking happens there. "
            . "When an order failed, explain the stored reason in plain words and the exact fix. "
            . "Useful pages: New orders /seller/order, Booked /seller/courier/Assigned, Delivered /seller/courier/Delivered, NDR /seller/courier/NDR, Wallet recharge /seller/recharge, Label settings /seller/custom-label, Tickets /seller/ticket, Rate calculator /seller/rate-calculator. "
            . "Keep replies short and friendly, plain text with simple lists; use the seller's language (English or Hinglish).";

        $resp = Http::timeout(60)->withHeaders([
            'x-api-key'         => $key,
            'anthropic-version' => '2023-06-01',
            'content-type'      => 'application/json',
        ])->post('https://api.anthropic.com/v1/messages', [
            'model'      => env('ASSISTANT_MODEL', 'claude-sonnet-5-5'),
            'max_tokens' => 1200,
            'system'     => $system,
            'tools'      => $this->tools(),
            'messages'   => $history,
        ]);
        if (!$resp->successful()) {
            throw new \RuntimeException('Claude API ' . $resp->status() . ': ' . mb_substr($resp->body(), 0, 300));
        }
        return $resp->json();
    }

    private function tools(): array
    {
        $orderRef = ['type' => 'string', 'description' => 'Order number, AWB or internal order id'];
        return [
            ['name' => 'find_orders', 'description' => 'Search the seller\'s orders. Use status to filter.',
             'input_schema' => ['type' => 'object', 'properties' => [
                 'status' => ['type' => 'string', 'enum' => ['any', 'new', 'failed', 'booked', 'in_transit', 'out_for_delivery', 'delivered', 'ndr', 'rto', 'cancelled']],
                 'query'  => ['type' => 'string', 'description' => 'Buyer name, phone, PIN, order number or AWB'],
                 'limit'  => ['type' => 'integer', 'minimum' => 1, 'maximum' => 20],
             ]]],
            ['name' => 'get_order', 'description' => 'Full details of one order: buyer, items, courier, AWB, status, failure reason, label link.',
             'input_schema' => ['type' => 'object', 'properties' => ['order' => $orderRef], 'required' => ['order']]],
            ['name' => 'wallet_balance', 'description' => 'Current wallet balance and last 5 transactions.',
             'input_schema' => ['type' => 'object', 'properties' => new \stdClass()]],
            ['name' => 'list_warehouses', 'description' => 'Seller pickup addresses (warehouses).',
             'input_schema' => ['type' => 'object', 'properties' => new \stdClass()]],
            ['name' => 'shipment_summary', 'description' => 'Counts of orders by status for the last N days.',
             'input_schema' => ['type' => 'object', 'properties' => ['days' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 90]]]],
            ['name' => 'raise_ticket', 'description' => 'Propose a support ticket (runs after seller confirms).',
             'input_schema' => ['type' => 'object', 'properties' => [
                 'order_id' => ['type' => 'string', 'description' => 'Order number or AWB the ticket is about, or "general"'],
                 'subject'  => ['type' => 'string'], 'message' => ['type' => 'string'],
             ], 'required' => ['order_id', 'message']]],
            ['name' => 'cancel_order', 'description' => 'Propose cancelling an order that has NOT been picked up (runs after seller confirms).',
             'input_schema' => ['type' => 'object', 'properties' => ['order' => $orderRef], 'required' => ['order']]],
            ['name' => 'create_order', 'description' => 'Propose a new order (runs after seller confirms). Courier is chosen later on New Orders.',
             'input_schema' => ['type' => 'object', 'properties' => [
                 'payment_type' => ['type' => 'string', 'enum' => ['cod', 'prepaid']],
                 'buyer' => ['type' => 'object', 'properties' => [
                     'name' => ['type' => 'string'], 'phone' => ['type' => 'string'], 'address' => ['type' => 'string'], 'address_2' => ['type' => 'string'],
                     'city' => ['type' => 'string'], 'state' => ['type' => 'string'], 'pincode' => ['type' => 'string'], 'email' => ['type' => 'string'],
                 ], 'required' => ['name', 'phone', 'address', 'city', 'state', 'pincode']],
                 'items' => ['type' => 'array', 'items' => ['type' => 'object', 'properties' => [
                     'name' => ['type' => 'string'], 'sku' => ['type' => 'string'], 'qty' => ['type' => 'integer'], 'price' => ['type' => 'number'],
                 ], 'required' => ['name', 'qty', 'price']]],
                 'weight_kg' => ['type' => 'number'], 'length_cm' => ['type' => 'number'], 'breadth_cm' => ['type' => 'number'], 'height_cm' => ['type' => 'number'],
                 'warehouse_id' => ['type' => 'integer', 'description' => 'From list_warehouses'],
             ], 'required' => ['payment_type', 'buyer', 'items', 'weight_kg', 'length_cm', 'breadth_cm', 'height_cm']]],
        ];
    }

    // ---------------------------------------------------------------- tools

    /** @return array{0: array, 1: ?array} [tool output, proposal needing confirmation] */
    private function runTool(string $name, array $in, $seller): array
    {
        try {
            switch ($name) {
                case 'find_orders':      return [$this->findOrders($in, $seller), null];
                case 'get_order':        return [$this->getOrder($in['order'] ?? '', $seller), null];
                case 'wallet_balance':   return [$this->wallet($seller), null];
                case 'list_warehouses':  return [Warehouse::where('seller_id', $seller->id)->get(['id', 'address_title', 'name', 'city', 'state', 'pincode', 'is_default'])->toArray(), null];
                case 'shipment_summary': return [$this->summary((int) ($in['days'] ?? 30), $seller), null];
                case 'raise_ticket':
                    $p = ['type' => 'raise_ticket', 'data' => $in, 'title' => 'Raise support ticket',
                          'summary' => 'Order: ' . ($in['order_id'] ?? '-') . "\nSubject: " . ($in['subject'] ?? 'Support request') . "\n" . mb_substr($in['message'] ?? '', 0, 400)];
                    return [['status' => 'shown_to_seller_for_confirmation'], $p];
                case 'cancel_order':
                    $o = $this->resolveOrder($in['order'] ?? '', $seller);
                    if (!$o) return [['error' => 'Order not found'], null];
                    $p = ['type' => 'cancel_order', 'data' => ['id' => $o->id], 'title' => 'Cancel order',
                          'summary' => 'Order #' . $o->order_number . ($o->awb_number ? ' (AWB ' . $o->awb_number . ')' : '') . ' will be cancelled. Charges are refunded as per policy.'];
                    return [['status' => 'shown_to_seller_for_confirmation'], $p];
                case 'create_order':
                    $check = $this->validateOrderInput($in, $seller);
                    if (isset($check['error'])) return [$check, null];
                    $b = $in['buyer'];
                    $items = implode(', ', array_map(fn ($i) => ($i['qty'] ?? 1) . ' x ' . $i['name'] . ' @ Rs ' . $i['price'], $in['items']));
                    $p = ['type' => 'create_order', 'data' => $in + ['warehouse_id' => $check['warehouse_id']], 'title' => 'Create order',
                          'summary' => "Buyer: {$b['name']}, {$b['phone']}\n{$b['address']}, {$b['city']}, {$b['state']} - {$b['pincode']}\nItems: {$items}\n"
                              . strtoupper($in['payment_type']) . ' | ' . $in['weight_kg'] . ' kg | ' . $in['length_cm'] . 'x' . $in['breadth_cm'] . 'x' . $in['height_cm'] . " cm\nPickup: " . $check['warehouse']];
                    return [['status' => 'shown_to_seller_for_confirmation'], $p];
            }
        } catch (\Throwable $e) {
            return [['error' => mb_substr($e->getMessage(), 0, 200)], null];
        }
        return [['error' => 'Unknown tool'], null];
    }

    private function resolveOrder(string $ref, $seller): ?Order
    {
        $ref = trim(ltrim(trim($ref), '#'));
        if ($ref === '') return null;
        return Order::where('seller_id', $seller->id)->where(function ($q) use ($ref) {
            $q->where('awb_number', $ref)->orWhere('order_number', $ref)->orWhere('customer_order_id', $ref);
            if (ctype_digit($ref)) $q->orWhere('id', (int) $ref);
        })->latest('id')->first();
    }

    private function brief(Order $o): array
    {
        $c = is_array($o->consignee) ? $o->consignee : (json_decode((string) $o->consignee, true) ?: []);
        [$brand, $mode] = LabelRenderer::courierBrand((string) $o->all_courier_name, (string) $o->courier_name, (string) $o->courier_id);
        return [
            'id' => $o->id, 'order_number' => $o->customer_order_id ?: $o->order_number, 'awb' => $o->awb_number,
            'courier' => trim($brand . ' ' . $mode) ?: null, 'status' => $o->shipping_status ?: ($o->awb_number ? 'booked' : 'new'),
            'buyer' => $c['name'] ?? null, 'city' => $c['city'] ?? null, 'pincode' => $c['pincode'] ?? null,
            'payment' => $o->payment_type, 'amount' => (float) $o->collectable_amount ?: (float) $o->order_amount,
            'booking_failure_reason' => $o->booking_error ?? null, 'created' => optional($o->created_at)->format('d M Y H:i'),
        ];
    }

    private function findOrders(array $in, $seller): array
    {
        $q = Order::where('seller_id', $seller->id);
        $st = $in['status'] ?? 'any';
        $like = fn ($v) => $q->where('shipping_status', 'like', "%{$v}%");
        switch ($st) {
            case 'new': $q->whereNull('awb_number')->where(fn ($x) => $x->whereNull('shipping_status')->orWhere('shipping_status', 'not like', '%cancel%')); break;
            case 'failed': $q->whereNull('awb_number')->whereNotNull('booking_error'); break;
            case 'booked': $q->whereNotNull('awb_number')->where(fn ($x) => $x->whereNull('shipping_status')->orWhereIn('shipping_status', ['assigned', 'manifested', 'pending', 'Pending', 'pickup scheduled'])); break;
            case 'in_transit': $like('transit'); break;
            case 'out_for_delivery': $like('out for delivery'); break;
            case 'delivered': $q->where('shipping_status', 'delivered'); break;
            case 'ndr': $like('NDR'); break;
            case 'rto': $like('rto'); break;
            case 'cancelled': $like('cancel'); break;
        }
        if (!empty($in['query'])) {
            $s = trim(ltrim($in['query'], '#'));
            $q->where(fn ($x) => $x->where('awb_number', 'like', "%{$s}%")->orWhere('order_number', 'like', "%{$s}%")
                ->orWhere('customer_order_id', 'like', "%{$s}%")->orWhere('consignee', 'like', "%{$s}%"));
        }
        $limit = max(1, min(20, (int) ($in['limit'] ?? 10)));
        $rows = $q->latest('id')->limit($limit)->get();
        return ['count' => $rows->count(), 'orders' => $rows->map(fn ($o) => $this->brief($o))->all()];
    }

    private function getOrder(string $ref, $seller): array
    {
        $o = $this->resolveOrder($ref, $seller);
        if (!$o) return ['error' => 'No order found for "' . $ref . '" in this account.'];
        $d = $this->brief($o);
        $d['items'] = $o->order_items;
        $d['weight_kg'] = $o->package_weight;
        $d['box_cm'] = trim($o->package_length . 'x' . $o->package_breadth . 'x' . $o->package_height, 'x');
        $d['label_link'] = $o->awb_number ? route('seller.order.custom-label.download', $o->id) : null;
        $d['rto_reason'] = $o->rto_reasen ?? null;
        return $d;
    }

    private function wallet($seller): array
    {
        $credit = Recharge::where('seller_id', $seller->id)->where('status', 1)->where('type', 'Credit')->sum('amount');
        $debit = Recharge::where('seller_id', $seller->id)->where('type', 'Debit')->sum('amount');
        $last = Recharge::where('seller_id', $seller->id)->latest('id')->limit(5)->get(['type', 'amount', 'description', 'created_at'])
            ->map(fn ($r) => ['type' => $r->type, 'amount' => (float) $r->amount, 'note' => $r->description, 'date' => optional($r->created_at)->format('d M Y')]);
        return ['balance' => round($credit - $debit, 2), 'currency' => 'INR', 'recent' => $last];
    }

    private function summary(int $days, $seller): array
    {
        $days = max(1, min(90, $days));
        $rows = Order::where('seller_id', $seller->id)->where('created_at', '>=', now()->subDays($days))
            ->selectRaw("COALESCE(NULLIF(shipping_status,''), CASE WHEN awb_number IS NULL THEN 'new' ELSE 'booked' END) as s, COUNT(*) as n")
            ->groupBy('s')->pluck('n', 's');
        return ['days' => $days, 'by_status' => $rows];
    }

    private function validateOrderInput(array $in, $seller): array
    {
        $b = $in['buyer'] ?? [];
        $phone = preg_replace('/\D/', '', (string) ($b['phone'] ?? ''));
        $phone = strlen($phone) > 10 ? substr($phone, -10) : $phone;
        if (!preg_match('/^[6-9]\d{9}$/', $phone)) return ['error' => 'Buyer phone must be a valid 10-digit Indian mobile number.'];
        if (!preg_match('/^\d{6}$/', (string) ($b['pincode'] ?? ''))) return ['error' => 'Buyer PIN code must be 6 digits.'];
        if (mb_strlen(trim((string) ($b['address'] ?? ''))) < 10) return ['error' => 'Buyer address is too short; ask for house number, street and area.'];
        if (empty($in['items'])) return ['error' => 'At least one product is needed.'];
        if (($in['weight_kg'] ?? 0) <= 0 || ($in['weight_kg'] ?? 0) > 50) return ['error' => 'Weight must be between 0.01 and 50 kg.'];
        $wq = Warehouse::where('seller_id', $seller->id);
        $wh = !empty($in['warehouse_id']) ? (clone $wq)->find($in['warehouse_id']) : null;
        if (!$wh) {
            $all = $wq->get();
            if ($all->count() === 0) return ['error' => 'Seller has no pickup address. Ask them to add one under Settings > Pickup address first.'];
            if ($all->count() > 1 && !$all->firstWhere('is_default', true)) return ['error' => 'Seller has several warehouses; call list_warehouses and ask which one.'];
            $wh = $all->firstWhere('is_default', true) ?: $all->first();
        }
        return ['warehouse_id' => $wh->id, 'warehouse' => trim(($wh->address_title ?: $wh->name) . ', ' . $wh->city . ' - ' . $wh->pincode)];
    }

    // ---------------------------------------------------------------- confirmed actions

    private function execute(array $action, $seller): string
    {
        $d = $action['data'];
        switch ($action['type']) {
            case 'raise_ticket':
                $req = Request::create('/seller/tickets/store', 'POST', [
                    'order_id' => mb_substr((string) ($d['order_id'] ?? 'general'), 0, 100),
                    'subject'  => mb_substr((string) ($d['subject'] ?? 'Support request'), 0, 100),
                    'message'  => mb_substr((string) ($d['message'] ?? ''), 0, 5000),
                ]);
                app(TicketController::class)->store($req); // also emails support
                $t = Ticket::where('seller_id', $seller->id)->latest('id')->first();
                return 'Ticket ' . ($t->ticket_id ?? '') . ' raised. Our team will reply on the Tickets page.';

            case 'cancel_order':
                $o = Order::where('seller_id', $seller->id)->find($d['id']);
                if (!$o) return 'Order not found.';
                if (!$o->awb_number) {
                    $o->shipping_status = 'cancelled';
                    $o->save();
                    return 'Order #' . $o->order_number . ' cancelled (it had no AWB, so no charge was taken).';
                }
                // Booked orders go through the normal cancel flow so the courier and wallet refund are handled.
                session()->forget(['error', 'success']);
                app(\App\Http\Controllers\Api\CourierController::class)->cancelAwb(
                    Request::create('/couriers/cancel', 'POST', ['awb' => $o->awb_number, 'order_id' => $o->id, 'courier' => $o->courier_id])
                );
                $flash = session('error') ? 'Could not cancel: ' . session('error') : session('success');
                session()->forget(['error', 'success']);
                return $flash ?: 'Cancellation requested for AWB ' . $o->awb_number . '. Check the Cancelled tab for the result.';

            case 'create_order':
                $wh = Warehouse::where('seller_id', $seller->id)->findOrFail($d['warehouse_id']);
                $b = $d['buyer'];
                $phone = substr(preg_replace('/\D/', '', $b['phone']), -10);
                $items = array_map(fn ($i) => ['name' => $i['name'], 'sku' => $i['sku'] ?? '', 'qty' => (int) ($i['qty'] ?? 1), 'price' => (float) $i['price']], $d['items']);
                $total = array_sum(array_map(fn ($i) => $i['qty'] * $i['price'], $items));
                $pickup = ['warehouse_name' => $wh->address_title ?: $wh->name, 'name' => $wh->name, 'address' => $wh->address_line1, 'address_2' => $wh->address_line2,
                           'city' => $wh->city, 'state' => $wh->state, 'pincode' => (string) $wh->pincode, 'phone' => (string) $wh->phone];
                $req = Request::create('/api/shipments/create', 'POST', [
                    'order_number' => 'AI' . now()->format('ymdHis'), 'unique_order_number' => 'yes', 'payment_type' => $d['payment_type'],
                    'order_amount' => $total, 'collectable_amount' => $d['payment_type'] === 'cod' ? $total : 0,
                    'package_weight' => $d['weight_kg'], 'package_length' => $d['length_cm'], 'package_breadth' => $d['breadth_cm'], 'package_height' => $d['height_cm'],
                    'request_auto_pickup' => 'yes', 'is_rto_different' => 'no',
                    'consignee' => ['name' => $b['name'], 'phone' => $phone, 'email' => $b['email'] ?? '', 'address' => $b['address'], 'address_2' => $b['address_2'] ?? '',
                                    'city' => $b['city'], 'state' => $b['state'], 'pincode' => (string) $b['pincode']],
                    'pickup' => $pickup, 'rto' => $pickup, 'order_items' => $items,
                ]);
                $req->attributes->set('api_seller', $seller);
                $res = app(ShipmentController::class)->createShipmentweb($req);
                $json = method_exists($res, 'getData') ? (array) $res->getData(true) : [];
                if (!empty($json['success'])) {
                    $o = Order::find($json['order_id']);
                    return 'Order #' . ($o->order_number ?? $json['order_id']) . ' created. Open New Orders and tap Ship Now to choose a courier.';
                }
                return $json['message'] ?? 'The order could not be created.';
        }
        return 'Unknown action.';
    }
}
