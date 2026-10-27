<?php

namespace App\Http\Controllers\selleradmin;

use App\Http\Controllers\Controller;
use App\Models\Recharge;
use App\Services\CashfreePgService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Seller wallet recharge via Cashfree Payment Gateway (replaces PayU).
 *
 * Flow:
 *  1. POST /recharge                       → pending recharge row + Cashfree order → checkout page
 *  2. Cashfree checkout (JS SDK v3)        → seller pays
 *  3. GET  /seller/recharge/cashfree/return → order re-fetched from Cashfree API, wallet credited if PAID
 *  4. POST /webhooks/cashfree/pg            → signed webhook, same settle logic (covers closed tabs)
 *
 * The wallet is credited ONLY after Cashfree's API says the order is PAID for the exact amount,
 * and only once (row lock + status check), whichever of return/webhook arrives first.
 */
class WalletRechargeController extends Controller
{
    public function __construct(protected CashfreePgService $cashfree)
    {
    }

    /** Step 1 — seller submits the recharge modal. */
    public function start(Request $request)
    {
        $seller = auth('seller')->user();
        if (!$seller) {
            return redirect()->route('seller.login')->with('error', 'Please log in to recharge your wallet.');
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:' . (float) config('cashfree.pg.min_amount', 1), 'max:500000'],
            'code'   => ['nullable', 'string', 'max:50'],
        ]);

        if (!$this->cashfree->isConfigured()) {
            Log::error('Cashfree PG keys missing (CASHFREE_PG_APP_ID / CASHFREE_PG_SECRET_KEY)');
            return back()->with('error', 'Online recharge is temporarily unavailable. Please contact support.');
        }

        $phone = preg_replace('/\D/', '', (string) ($seller->phone ?? $seller->phone_number ?? ''));
        $phone = substr($phone, -10);
        if (strlen($phone) !== 10) {
            return back()->with('error', 'Please add a valid 10-digit mobile number to your profile before recharging.');
        }

        $amount = round((float) $validated['amount'], 2);

        $recharge = Recharge::create([
            'seller_id'      => $seller->id,
            'amount'         => $amount,
            'code'           => $validated['code'] ?? null,
            'type'           => 'Credit',
            'status'         => '0',
            'description'    => 'Wallet Recharge (Cashfree)',
            'gateway'        => 'cashfree',
            'gateway_status' => 'CREATED',
        ]);

        $notifyUrl = route('cashfree.pg.webhook');

        // Default: Payment Link (Cashfree-hosted page, works without domain whitelisting).
        // Set CASHFREE_PG_CHECKOUT=sdk (config cashfree.pg.checkout) to use the embedded JS checkout instead.
        if (config('cashfree.pg.checkout', 'link') !== 'sdk') {
            $linkId = 'SXL_' . $recharge->id . '_' . time();
            $linkMeta = ['return_url' => route('seller.recharge.cashfree.return') . '?link_id=' . $linkId];
            if (str_starts_with($notifyUrl, 'https://')) {
                $linkMeta['notify_url'] = $notifyUrl;
            }

            try {
                $link = $this->cashfree->createLink([
                    'link_id'          => $linkId,
                    'link_amount'      => $amount,
                    'link_currency'    => 'INR',
                    'link_purpose'     => 'Shipxpeed wallet recharge',
                    'customer_details' => array_filter([
                        'customer_name'  => mb_substr((string) ($seller->name ?? 'Seller'), 0, 100),
                        'customer_email' => filter_var($seller->email, FILTER_VALIDATE_EMAIL) ?: null,
                        'customer_phone' => $phone,
                    ]),
                    'link_meta'        => $linkMeta,
                    'link_notify'      => ['send_sms' => false, 'send_email' => false],
                    'link_expiry_time' => now()->addDay()->toIso8601String(),
                    'link_notes'       => ['recharge_id' => (string) $recharge->id, 'seller_id' => (string) $seller->id],
                ]);
            } catch (\Throwable $e) {
                $recharge->update(['gateway_status' => 'ORDER_FAILED']);
                return back()->with('error', 'Could not start payment: ' . $e->getMessage());
            }

            $recharge->update(['gateway_order_id' => $linkId, 'gateway_status' => 'ACTIVE']);

            return redirect()->away($link['link_url']);
        }

        $orderId = 'SXR_' . $recharge->id . '_' . time();

        $meta = [
            'return_url' => route('seller.recharge.cashfree.return') . '?order_id={order_id}',
        ];
        if (str_starts_with($notifyUrl, 'https://')) {
            $meta['notify_url'] = $notifyUrl; // Cashfree only accepts https notify URLs
        }

        try {
            $order = $this->cashfree->createOrder([
                'order_id'         => $orderId,
                'order_amount'     => $amount,
                'order_currency'   => 'INR',
                'order_note'       => 'Shipxpeed wallet recharge',
                'customer_details' => array_filter([
                    'customer_id'    => 'seller_' . $seller->id,
                    'customer_name'  => mb_substr((string) ($seller->name ?? 'Seller'), 0, 100),
                    'customer_email' => filter_var($seller->email, FILTER_VALIDATE_EMAIL) ?: null,
                    'customer_phone' => $phone,
                ]),
                'order_meta'       => $meta,
                'order_tags'       => ['recharge_id' => (string) $recharge->id],
            ]);
        } catch (\Throwable $e) {
            $recharge->update(['gateway_status' => 'ORDER_FAILED']);
            return back()->with('error', 'Could not start payment: ' . $e->getMessage());
        }

        $recharge->update(['gateway_order_id' => $orderId, 'gateway_status' => 'ACTIVE']);

        return view('recharge.cashfree_checkout', [
            'paymentSessionId' => $order['payment_session_id'],
            'mode'             => $this->cashfree->mode(),
            'amount'           => $amount,
        ]);
    }

    /** Step 3 — browser returns from Cashfree. Never trusts query params; asks Cashfree. */
    public function handleReturn(Request $request)
    {
        $orderId = (string) ($request->query('link_id') ?: $request->query('order_id', ''));
        $recharge = $orderId !== '' ? Recharge::where('gateway_order_id', $orderId)->first() : null;

        $target = auth('seller')->check() ? redirect()->route('seller.dashboard') : redirect()->route('seller.login');

        if (!$recharge) {
            return $target->with('error', 'Payment not found.');
        }

        $result = $this->settle($recharge);

        return match ($result) {
            'paid'    => $target->withSuccess('Payment successful — ₹' . number_format((float) $recharge->amount, 2) . ' added to your wallet.'),
            'pending' => $target->with('error', 'Payment is still processing. Your wallet will be credited automatically once Cashfree confirms it.'),
            default   => $target->with('error', 'Payment failed or was cancelled. No amount was added.'),
        };
    }

    /** Step 4 — Cashfree server-to-server webhook. */
    public function webhook(Request $request)
    {
        $raw = $request->getContent();

        if (!$this->cashfree->verifyWebhook($request->header('x-webhook-signature'), $request->header('x-webhook-timestamp'), $raw)) {
            Log::warning('Cashfree PG webhook: bad signature');
            return response()->json(['ok' => false], 401);
        }

        $data = json_decode($raw, true) ?: [];
        $ids = array_filter([
            data_get($data, 'data.link_id'),                    // payment link events
            data_get($data, 'data.order.order_tags.link_id'),   // payment events for link orders
            data_get($data, 'data.order.order_id'),             // SDK checkout orders
        ]);
        $recharge = $ids ? Recharge::whereIn('gateway_order_id', array_values($ids))->first() : null;

        if ($recharge) {
            $this->settle($recharge);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Re-check the order with Cashfree and credit exactly once.
     *
     * @return string paid | pending | failed
     */
    protected function settle(Recharge $recharge): string
    {
        if ((string) $recharge->status === '1') {
            return 'paid';
        }

        if (str_starts_with((string) $recharge->gateway_order_id, 'SXL_')) {
            return $this->settleLink($recharge);
        }

        $order = $this->cashfree->getOrder($recharge->gateway_order_id);
        if (!$order) {
            return 'pending';
        }

        $status = strtoupper((string) ($order['order_status'] ?? ''));

        if ($status === 'PAID') {
            if (abs((float) ($order['order_amount'] ?? 0) - (float) $recharge->amount) > 0.009) {
                Log::critical('Cashfree PG amount mismatch', ['recharge_id' => $recharge->id, 'order' => $order]);
                $recharge->update(['gateway_status' => 'AMOUNT_MISMATCH']);
                return 'failed';
            }

            $paymentId = null;
            foreach ($this->cashfree->getPayments($recharge->gateway_order_id) as $p) {
                if (strtoupper($p['payment_status'] ?? '') === 'SUCCESS') {
                    $paymentId = (string) ($p['cf_payment_id'] ?? '');
                    break;
                }
            }

            DB::transaction(function () use ($recharge, $paymentId) {
                $row = Recharge::whereKey($recharge->id)->lockForUpdate()->first();
                if ($row && (string) $row->status !== '1') {
                    $row->update([
                        'status'             => '1',
                        'gateway_status'     => 'PAID',
                        'gateway_payment_id' => $paymentId,
                        'paid_at'            => now(),
                    ]);
                }
            });

            $recharge->refresh();
            return 'paid';
        }

        if (in_array($status, ['EXPIRED', 'TERMINATED', 'TERMINATION_REQUESTED'], true)) {
            $recharge->update(['status' => '0', 'gateway_status' => $status]);
            return 'failed';
        }

        // ACTIVE = not paid yet (user cancelled, or payment still pending at the bank)
        $latest = collect($this->cashfree->getPayments($recharge->gateway_order_id))->first();
        $payStatus = strtoupper($latest['payment_status'] ?? '');
        if (in_array($payStatus, ['FAILED', 'USER_DROPPED', 'CANCELLED'], true)) {
            $recharge->update(['gateway_status' => $payStatus]);
            return 'failed';
        }

        return 'pending';
    }

    /** Settle a Payment Link recharge: credit once when Cashfree says the link is PAID in full. */
    protected function settleLink(Recharge $recharge): string
    {
        $link = $this->cashfree->getLink($recharge->gateway_order_id);
        if (!$link) {
            return 'pending';
        }

        $status = strtoupper((string) ($link['link_status'] ?? ''));

        if ($status === 'PAID') {
            $paid = (float) ($link['link_amount_paid'] ?? 0);
            if ($paid + 0.009 < (float) $recharge->amount) {
                Log::critical('Cashfree PG link amount mismatch', ['recharge_id' => $recharge->id, 'link' => $link]);
                $recharge->update(['gateway_status' => 'AMOUNT_MISMATCH']);
                return 'failed';
            }

            $paymentId = null;
            foreach ($this->cashfree->getLinkOrders($recharge->gateway_order_id) as $o) {
                if (strtoupper($o['order_status'] ?? '') === 'PAID') {
                    $paymentId = (string) ($o['cf_order_id'] ?? $o['order_id'] ?? '');
                    break;
                }
            }

            DB::transaction(function () use ($recharge, $paymentId) {
                $row = Recharge::whereKey($recharge->id)->lockForUpdate()->first();
                if ($row && (string) $row->status !== '1') {
                    $row->update([
                        'status'             => '1',
                        'gateway_status'     => 'PAID',
                        'gateway_payment_id' => $paymentId,
                        'paid_at'            => now(),
                    ]);
                }
            });

            $recharge->refresh();
            return 'paid';
        }

        if (in_array($status, ['EXPIRED', 'CANCELLED'], true)) {
            $recharge->update(['status' => '0', 'gateway_status' => $status]);
            return 'failed';
        }

        // ACTIVE / PARTIALLY_PAID: seller closed the page or payment still processing
        return 'pending';
    }
}
