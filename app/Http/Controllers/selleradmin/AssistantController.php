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
            // No AI key: answer with the built-in (rule-based) assistant.
            return response()->json($this->ruleReply((string) $request->input('message'), $seller));
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
            // AI unavailable: fall back to the built-in assistant so sellers still get an answer.
            return response()->json($this->ruleReply((string) $request->input('message'), $seller));
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
        session()->forget([$this->sessionKey(), $this->ruleStateKey()]);
        return response()->json(['ok' => true]);
    }

    // ---------------------------------------------------------------- built-in (rule-based) assistant
    // Works with no AI key. Understands simple English/Hinglish keywords, AWB / order numbers,
    // and guided steps for tickets and cancellations. Uses the same tools and Confirm flow as the AI.

    private const MENU = ['Wallet balance', 'Track my latest shipment', 'Why did my orders fail?', 'Shipment summary', 'Raise a ticket', 'Cancel an order', 'Create a new order'];

    private function ruleStateKey(): string
    {
        return 'xpeed_rules_' . auth()->guard('seller')->id();
    }

    private function ruleReply(string $msg, $seller): array
    {
        $text = trim($msg);
        $m = mb_strtolower($text);
        $state = session($this->ruleStateKey());
        $has = fn (array $words) => (bool) preg_match('/\b(' . implode('|', array_map('preg_quote', $words)) . ')/u', $m);

        // Typing "menu", "cancel"/"stop" during a step, or "hi" always resets to the menu.
        if ($has(['menu', 'start over', 'main menu']) || preg_match('/^(hi|hii+|hello|hey|namaste|hlo)\b/u', $m)) {
            session()->forget($this->ruleStateKey());
            return $this->say("Hi {$this->firstName($seller)}! Tap an option below, or type an AWB / order number to track it.", self::MENU);
        }

        // ---- guided steps
        if ($state) {
            if (preg_match('/^(stop|exit|quit|no|nahi|leave it|rehne do)\b/u', $m)) {
                session()->forget($this->ruleStateKey());
                return $this->say('Okay, stopped. What else can I help with?', self::MENU);
            }
            switch ($state['step'] ?? '') {
                case 'ticket_order':
                    $ref = $has(['general', 'other', 'wallet', 'account']) ? 'general' : $text;
                    if ($ref !== 'general') {
                        $o = $this->resolveOrder($ref, $seller);
                        if (!$o) return $this->say("I couldn't find \"{$text}\" in your account. Type the order number or AWB again, or tap General.", ['General', 'Stop']);
                        $ref = $o->awb_number ?: ($o->customer_order_id ?: $o->order_number);
                    }
                    session([$this->ruleStateKey() => ['step' => 'ticket_msg', 'order' => $ref]]);
                    return $this->say('Got it. Now describe the problem in one message (what happened and what you need).', ['Stop']);

                case 'ticket_msg':
                    if (mb_strlen($text) < 5) return $this->say('Please write a little more about the problem so our team can help.', ['Stop']);
                    session()->forget($this->ruleStateKey());
                    [, $p] = $this->runTool('raise_ticket', ['order_id' => $state['order'], 'subject' => 'Support request', 'message' => $text], $seller);
                    return $this->say('Please check the ticket below and tap Confirm to send it.', [], $p);

                case 'cancel_order':
                    $o = $this->resolveOrder($text, $seller);
                    if (!$o) return $this->say("I couldn't find \"{$text}\" in your account. Type the order number or AWB again.", ['Stop']);
                    session()->forget($this->ruleStateKey());
                    if (preg_match('/deliver|transit|out for delivery|rto|cancel/i', (string) $o->shipping_status)) {
                        return $this->say("This order can't be cancelled because its status is \"{$o->shipping_status}\". If you need help, raise a ticket.", ['Raise a ticket', 'Menu']);
                    }
                    [, $p] = $this->runTool('cancel_order', ['order' => $text], $seller);
                    return $this->say('Please check the details below and tap Confirm to cancel.', [], $p);
            }
            session()->forget($this->ruleStateKey());
        }

        // ---- an AWB / order number on its own (or inside a sentence) → show that order
        if (preg_match_all('/#?\b([A-Za-z0-9\-]{5,30})\b/u', $text, $mm)) {
            foreach ($mm[1] as $tok) {
                if (!preg_match('/\d{3,}/', $tok)) continue;
                if ($o = $this->resolveOrder($tok, $seller)) {
                    return $this->say($this->orderText($o), $o->awb_number ? ['Raise a ticket', 'Menu'] : ['Cancel an order', 'Menu']);
                }
            }
        }

        // ---- intents (order matters: specific before general)
        if ($has(['cancel'])) {
            session([$this->ruleStateKey() => ['step' => 'cancel_order']]);
            return $this->say('Which order should I cancel? Type its order number or AWB.', ['Stop']);
        }
        if ($has(['ticket', 'complain', 'complaint', 'support', 'issue', 'problem', 'shikayat'])) {
            session([$this->ruleStateKey() => ['step' => 'ticket_order']]);
            return $this->say('Which order is this about? Type the order number or AWB, or tap General for account / wallet issues.', ['General', 'Stop']);
        }
        if ($has(['fail', 'error', 'reject', 'not book', 'nahi hua', 'nhi hua', 'stuck'])) {
            $r = $this->findOrders(['status' => 'failed', 'limit' => 5], $seller);
            if (!$r['count']) return $this->say('Good news: none of your recent orders failed to book.', self::MENU);
            $lines = array_map(fn ($o) => '• #' . $o['order_number'] . ($o['buyer'] ? ' (' . $o['buyer'] . ')' : '') . "\n  Reason: " . $this->plainReason($o['booking_failure_reason']), $r['orders']);
            return $this->say("Orders that failed to book:\n" . implode("\n", $lines) . "\n\nFix the details on New Orders and tap Ship Now again: " . url('/seller/order'), ['Raise a ticket', 'Menu']);
        }
        if ($has(['wallet', 'balance', 'recharge', 'paisa', 'paise', 'money', 'debit', 'credit'])) {
            $w = $this->wallet($seller);
            $lines = collect($w['recent'])->map(fn ($r) => '• ' . $r['date'] . '  ' . ($r['type'] === 'Credit' ? '+' : '-') . 'Rs ' . number_format($r['amount'], 2) . ($r['note'] ? '  ' . mb_substr($r['note'], 0, 40) : ''))->implode("\n");
            return $this->say('Your wallet balance is Rs ' . number_format($w['balance'], 2) . '.' . ($lines ? "\n\nRecent transactions:\n{$lines}" : '') . "\n\nRecharge here: " . url('/seller/recharge'), self::MENU);
        }
        if ($has(['ndr', 'undeliver', 'not deliver', 'attempt'])) {
            return $this->listOrders('ndr', 'Orders with a failed delivery attempt (NDR)', $seller, url('/seller/xp/ndr'));
        }
        if ($has(['rto', 'return'])) {
            return $this->listOrders('rto', 'Orders returning to origin (RTO)', $seller);
        }
        if ($has(['delivered'])) {
            return $this->listOrders('delivered', 'Recently delivered orders', $seller);
        }
        if ($has(['summary', 'report', 'how many', 'count', 'kitne', 'total', 'stats'])) {
            $s = $this->summary(30, $seller);
            if (!count($s['by_status'])) return $this->say('No orders in the last 30 days.', self::MENU);
            $lines = collect($s['by_status'])->map(fn ($n, $st) => '• ' . ucwords((string) $st) . ': ' . $n)->implode("\n");
            return $this->say("Your orders in the last 30 days:\n{$lines}", self::MENU);
        }
        if ($has(['track', 'status', 'where', 'kahan', 'kaha', 'shipment', 'parcel', 'latest', 'awb'])) {
            $r = $this->findOrders(['status' => 'any', 'limit' => 10], $seller);
            $o = collect($r['orders'])->firstWhere('awb', '!=', null);
            if (!$o) return $this->say('You have no booked shipments yet. Type an order number to check a specific order.', self::MENU);
            return $this->say("Your latest shipment:\n" . $this->orderText($this->resolveOrder((string) $o['id'], $seller)) . "\n\nTo track another, type its AWB or order number.", ['Menu']);
        }
        if ($has(['create', 'new order', 'add order', 'book', 'ship'])) {
            return $this->say('Create the order here (it takes a minute): ' . url('/seller/order-add') . "\n\nAfter saving, open New Orders and tap Ship Now to choose a courier.", self::MENU);
        }
        if ($has(['rate', 'price', 'charge', 'cost', 'kitna'])) {
            return $this->say('Check courier rates for any PIN code and weight here: ' . url('/rate-cards'), self::MENU);
        }
        if ($has(['cod', 'remit', 'settle'])) {
            return $this->say('Your COD remittances and upcoming payouts are here: ' . url('/seller/cod-remittance'), self::MENU);
        }
        if ($has(['label', 'print'])) {
            return $this->say('Type the order number or AWB and I will give you its label link. Label settings: ' . url('/seller/custom-label'), ['Menu']);
        }

        return $this->say("Sorry, I didn't understand that. Tap an option below, or type an AWB / order number.", self::MENU);
    }

    private function say(string $reply, array $chips = [], ?array $pending = null): array
    {
        if ($pending) {
            $token = Str::random(24);
            session(['xpeed_assistant_pending_' . $token => $pending]);
            $pending = ['token' => $token, 'title' => $pending['title'], 'summary' => $pending['summary']];
        }
        return ['reply' => $reply, 'chips' => array_values($chips), 'pending' => $pending];
    }

    private function listOrders(string $status, string $title, $seller, ?string $link = null): array
    {
        $r = $this->findOrders(['status' => $status, 'limit' => 5], $seller);
        if (!$r['count']) return $this->say('No ' . strtolower($title) . ' right now.', self::MENU);
        $lines = array_map(fn ($o) => '• #' . $o['order_number'] . ' | ' . ($o['awb'] ?: 'no AWB') . ($o['courier'] ? ' | ' . $o['courier'] : '') . ($o['buyer'] ? ' | ' . $o['buyer'] : ''), $r['orders']);
        return $this->say($title . ":\n" . implode("\n", $lines) . ($link ? "\n\nManage them here: {$link}" : "\n\nType an AWB to see full details."), self::MENU);
    }

    private function orderText(?Order $o): string
    {
        if (!$o) return 'Order not found.';
        $d = $this->getOrder((string) $o->id, $this->seller());
        $t = 'Order #' . $d['order_number']
            . "\nStatus: " . ucwords((string) $d['status'])
            . ($d['awb'] ? "\nAWB: {$d['awb']}" : '')
            . ($d['courier'] ? "\nCourier: {$d['courier']}" : '')
            . ($d['buyer'] ? "\nBuyer: {$d['buyer']}" . ($d['city'] ? ", {$d['city']}" : '') . ($d['pincode'] ? " - {$d['pincode']}" : '') : '')
            . "\nPayment: " . strtoupper((string) $d['payment']) . ' | Rs ' . number_format((float) $d['amount'], 2)
            . ($d['created'] ? "\nCreated: {$d['created']}" : '');
        if (!$d['awb'] && $d['booking_failure_reason']) $t .= "\nBooking failed: " . $this->plainReason($d['booking_failure_reason']);
        if (!empty($d['rto_reason'])) $t .= "\nRTO reason: {$d['rto_reason']}";
        if ($d['label_link']) $t .= "\nLabel: {$d['label_link']}";
        if ($d['awb']) $t .= "\nTrack: " . url('/seller/track-order-in');
        return $t;
    }

    /** Turn raw courier errors into a short, plain explanation with the fix. */
    private function plainReason(?string $raw): string
    {
        $r = trim(strip_tags((string) $raw));
        if ($r === '') return 'No reason was stored. Try Ship Now again, or raise a ticket.';
        $map = [
            '/pin ?code|pincode|serviceab|not service/i' => 'The courier does not deliver to this PIN code. Pick another courier on Ship Now.',
            '/balance|insufficient|wallet/i'            => 'Not enough wallet balance. Recharge the wallet, then tap Ship Now again.',
            '/phone|mobile/i'                            => 'The buyer phone number is invalid. Use a 10-digit mobile number.',
            '/address/i'                                 => 'The buyer address is incomplete. Add house number, street and area.',
            '/weight|dimension|length|breadth|height/i'  => 'The weight or box size is missing or out of range. Correct it and retry.',
            '/duplicate|already exist/i'                 => 'This order number was already used. Change the order number and retry.',
            '/warehouse|pickup/i'                        => 'There is a problem with the pickup address. Check it under Settings, then retry.',
        ];
        foreach ($map as $re => $plain) {
            if (preg_match($re, $r)) return $plain . ' (' . mb_substr($r, 0, 120) . ')';
        }
        return mb_substr($r, 0, 200);
    }

    private function firstName($seller): string
    {
        return trim(explode(' ', (string) ($seller->name ?? 'there'))[0]) ?: 'there';
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
