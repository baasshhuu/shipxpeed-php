<?php

namespace App\Support\Cod;

use App\Models\Order;
use App\Support\Xpeed\Kit;
use App\Support\Xpeed\Protection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * COD remittance engine.
 *
 * Flow
 *  1. generate():     every delivered COD order gets one cod_remittances row, dated on the day it was
 *                     marked delivered, with the seller's plan at that moment:
 *                       D+7 standard (no fee) · D+4 early (0.99%) · D+2 early (1.29%)  — fee % of COD amount
 *                     due_on = delivered_on + plan days; net_payable = COD − fee.
 *  2. settleWallets(): when a row is due and the seller's wallet is below the minimum (₹100), the COD is
 *                     credited to the wallet first until it reaches the minimum; only the rest stays payable.
 *                     Rows fully used this way become "adjusted" and never show as payable.
 *  3. markPaid():     admin pays the bank transfers outside the panel, then marks sellers paid; due rows
 *                     become "paid", a cod_payouts row is stored and the seller gets an email.
 */
class CodRemittance
{
    public const MIN_WALLET = 100.0;

    public const STATUS = [
        'upcoming' => ['Upcoming', 'blue'],
        'due'      => ['Due to pay', 'warn'],
        'adjusted' => ['Adjusted to wallet', 'muted'],
        'paid'     => ['Paid', 'ok'],
    ];

    // ------------------------------------------------------------------ plans

    /** code => [label, days, fee %]. D+7 is the default for every seller. */
    public static function plans(): array
    {
        return [
            'd7' => ['label' => 'D+7 Standard', 'days' => 7, 'pct' => 0.0],
            'd4' => ['label' => 'D+4 Early COD', 'days' => 4, 'pct' => Kit::num('early_cod_d4_pct') ?: 0.99],
            'd2' => ['label' => 'D+2 Early COD', 'days' => 2, 'pct' => Kit::num('early_cod_d2_pct') ?: 1.29],
        ];
    }

    /** The seller's current plan code (d7 unless an early plan is active). */
    public static function sellerPlan(int $sellerId): string
    {
        try {
            $svc = Protection::service($sellerId);
        } catch (\Throwable $e) {
            return 'd7';
        }
        return ($svc->early_cod_status ?? null) === 'active' && in_array($svc->early_cod_plan, ['d4', 'd2'], true)
            ? $svc->early_cod_plan : 'd7';
    }

    public static function setPlan(int $sellerId, string $plan): void
    {
        $values = $plan === 'd7'
            ? ['early_cod_status' => 'stopped', 'early_cod_decided_at' => now()]
            : ['early_cod_plan' => $plan, 'early_cod_status' => 'active', 'early_cod_requested_at' => now(),
               'early_cod_decided_at' => now(), 'early_cod_note' => null];
        Protection::saveService($sellerId, $values);
    }

    public static function pct(float $p): string
    {
        return rtrim(rtrim(number_format($p, 2), '0'), '.') . '%';
    }

    // ------------------------------------------------------------------ status

    /** Display status of a row: upcoming / due / adjusted / paid. */
    public static function state($row, ?string $today = null): string
    {
        if ($row->status === 'paid' || $row->status === 'adjusted') {
            return $row->status;
        }
        $today ??= now()->toDateString();
        return Carbon::parse($row->due_on)->toDateString() <= $today ? 'due' : 'upcoming';
    }

    // ------------------------------------------------------------------ 1. generate

    /** Create rows for delivered COD orders that don't have one yet. Returns number created. */
    public static function generate(int $limit = 5000, ?int $sellerId = null): int
    {
        if (!Kit::ready('cod_remittances', 'orders')) {
            return 0;
        }
        $hasDelivered = Kit::hasCol('orders', 'delivered_date');
        $hasPayStatus = Kit::hasCol('orders', 'payment_status');

        $q = Order::query()
            ->whereRaw('LOWER(payment_type) = ?', ['cod'])
            ->whereNotNull('awb_number')->where('awb_number', '!=', '')
            ->whereNotExists(fn ($s) => $s->select(DB::raw(1))->from('cod_remittances')->whereColumn('cod_remittances.order_id', 'orders.id'))
            ->where(function ($w) use ($hasDelivered) {
                $w->whereRaw("LOWER(shipping_status) LIKE '%deliver%'");
                if ($hasDelivered) {
                    $w->orWhereNotNull('delivered_date');
                }
            })
            ->when($sellerId, fn ($w) => $w->where('seller_id', $sellerId))
            ->orderBy('id')
            ->limit($limit);

        $created = 0;
        $plans = self::plans();
        $planCache = [];

        foreach ($q->get() as $o) {
            // Only real deliveries (not "undelivered", RTO-delivered, cancelled …)
            if (Kit::bucket($o) !== 'delivered') {
                continue;
            }
            $cod = round((float) ($o->collectable_amount ?: 0), 2);
            if ($cod <= 0) {
                $cod = round((float) ($o->order_amount ?: 0), 2);
            }
            if ($cod <= 0) {
                continue;
            }

            $deliveredOn = null;
            if ($hasDelivered && !empty($o->delivered_date)) {
                try {
                    $deliveredOn = Carbon::parse($o->delivered_date)->startOfDay();
                } catch (\Throwable $e) {
                    $deliveredOn = null;
                }
            }
            // Courier marked it delivered but no date was stored: today is the day we saw it delivered
            $deliveredOn ??= now()->startOfDay();

            $sid = (int) $o->seller_id;
            $legacyPaid = $hasPayStatus && strcasecmp((string) $o->payment_status, 'Paid') === 0;

            $code = $legacyPaid ? 'd7' : ($planCache[$sid] ??= self::sellerPlan($sid));
            $p = $plans[$code];
            $fee = $legacyPaid ? 0.0 : round($cod * $p['pct'] / 100, 2);

            try {
                DB::table('cod_remittances')->insert([
                    'seller_id'    => $sid,
                    'order_id'     => $o->id,
                    'order_number' => mb_substr((string) Kit::ref($o), 0, 80),
                    'awb'          => mb_substr((string) $o->awb_number, 0, 80),
                    'courier'      => mb_substr(Kit::courierLabel($o), 0, 120),
                    'cod_amount'   => $cod,
                    'delivered_on' => $deliveredOn->toDateString(),
                    'plan'         => $code,
                    'plan_days'    => $p['days'],
                    'fee_pct'      => $legacyPaid ? 0 : $p['pct'],
                    'fee_amount'   => $fee,
                    'wallet_adjusted' => 0,
                    'net_payable'  => round($cod - $fee, 2),
                    'due_on'       => $deliveredOn->copy()->addDays($p['days'])->toDateString(),
                    'status'       => $legacyPaid ? 'paid' : 'pending',
                    'paid_at'      => $legacyPaid ? now() : null,
                    'note'         => $legacyPaid ? 'Paid before the COD module' : null,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
                $created++;
            } catch (\Illuminate\Database\QueryException $e) {
                // unique(order_id): another run created it first
                if (!str_contains($e->getMessage(), 'Duplicate')) {
                    Log::warning('COD generate failed', ['order' => $o->id, 'error' => $e->getMessage()]);
                }
            }
        }

        return $created;
    }

    // ------------------------------------------------------------------ 2. wallet settle

    /**
     * Sellers with due COD and a wallet below the minimum: credit COD into the wallet until it reaches
     * the minimum. Returns ['sellers' => n, 'amount' => total credited].
     */
    public static function settleWallets(?array $sellerIds = null, ?string $upTo = null): array
    {
        $out = ['sellers' => 0, 'amount' => 0.0];
        if (!Kit::ready('cod_remittances')) {
            return $out;
        }
        $upTo ??= now()->toDateString();

        $ids = DB::table('cod_remittances')->where('status', 'pending')->where('due_on', '<=', $upTo)
            ->where('net_payable', '>', 0)
            ->when($sellerIds, fn ($q) => $q->whereIn('seller_id', $sellerIds))
            ->distinct()->pluck('seller_id');

        foreach ($ids as $sid) {
            $sid = (int) $sid;
            $credited = DB::transaction(function () use ($sid, $upTo) {
                // lock this seller's due rows so two runs can't credit twice
                $rows = DB::table('cod_remittances')->where('seller_id', $sid)->where('status', 'pending')
                    ->where('due_on', '<=', $upTo)->where('net_payable', '>', 0)
                    ->orderBy('due_on')->orderBy('id')->lockForUpdate()->get();

                $balance = Kit::balance($sid);
                if ($balance >= self::MIN_WALLET) {
                    return 0.0;
                }
                $total = 0.0;
                foreach ($rows as $r) {
                    $need = round(self::MIN_WALLET - $balance, 2);
                    if ($need <= 0) {
                        break;
                    }
                    $take = round(min((float) $r->net_payable, $need), 2);
                    if ($take <= 0) {
                        continue;
                    }
                    $net = round((float) $r->net_payable - $take, 2);
                    Kit::credit($sid, $take, 'COD adjusted against wallet (minimum ₹' . (int) self::MIN_WALLET . ') · AWB ' . $r->awb);
                    DB::table('cod_remittances')->where('id', $r->id)->update([
                        'wallet_adjusted' => round((float) $r->wallet_adjusted + $take, 2),
                        'net_payable'     => $net,
                        'status'          => $net <= 0 ? 'adjusted' : 'pending',
                        'updated_at'      => now(),
                    ]);
                    $balance = round($balance + $take, 2);
                    $total += $take;
                }
                return $total;
            });
            if ($credited > 0) {
                $out['sellers']++;
                $out['amount'] = round($out['amount'] + $credited, 2);
            }
        }

        return $out;
    }

    /** Generate new rows and settle wallets. Used by the scheduled job and on page loads. */
    public static function refresh(?int $sellerId = null): array
    {
        $made = 0;
        $settled = ['sellers' => 0, 'amount' => 0.0];
        try {
            $made = self::generate(5000, $sellerId);
        } catch (\Throwable $e) {
            Log::error('COD generate error', ['error' => $e->getMessage()]);
        }
        try {
            $settled = self::settleWallets($sellerId ? [$sellerId] : null);
        } catch (\Throwable $e) {
            Log::error('COD wallet settle error', ['error' => $e->getMessage()]);
        }
        return ['created' => $made] + $settled;
    }

    // ------------------------------------------------------------------ bank details

    /** seller_id => [holder, bank, account, ifsc, verified] */
    public static function banks(array $sellerIds): array
    {
        $out = [];
        if (!$sellerIds) {
            return $out;
        }
        $sellers = DB::table('seller_lists')->whereIn('id', $sellerIds)->get()->keyBy('id');
        $rows = Kit::ready('seller_bank_details')
            ? DB::table('seller_bank_details')->whereIn('seller_id', $sellerIds)
                ->when(Kit::hasCol('seller_bank_details', 'deleted_at'), fn ($q) => $q->whereNull('deleted_at'))
                ->orderByDesc('id')->get()->unique('seller_id')->keyBy('seller_id')
            : collect();

        foreach ($sellerIds as $sid) {
            $s = $sellers->get($sid);
            $b = $rows->get($sid);
            $acct = trim((string) ($b->account_number ?? '')) ?: trim((string) ($s->account_number ?? ''));
            $out[$sid] = [
                'holder'   => trim((string) ($b->account_holder_name ?? '')) ?: trim((string) ($s->pan_verified_name ?? '')) ?: (string) ($s->name ?? ''),
                'bank'     => trim((string) ($b->bank_name ?? '')) ?: trim((string) ($s->bank_name ?? '')),
                'account'  => $acct,
                'ifsc'     => strtoupper(trim((string) ($b->ifsc_code ?? '')) ?: trim((string) ($s->ifsc_code ?? ''))),
                'verified' => (string) ($s->bank_account_verified ?? '') === '1',
                'name'     => (string) ($s->name ?? ('Seller #' . $sid)),
                'email'    => (string) ($s->email ?? ''),
                'phone'    => (string) ($s->phone_number ?? ''),
                'company'  => (string) ($s->company_name ?? ''),
            ];
        }
        return $out;
    }

    // ------------------------------------------------------------------ admin summaries

    /** Base query for payable rows (pending, net > 0) with due date in [from, to]. */
    public static function payableQuery(?string $from, string $to, ?int $sellerId = null)
    {
        return DB::table('cod_remittances')->where('status', 'pending')->where('net_payable', '>', 0)
            ->where('due_on', '<=', $to)
            ->when($from, fn ($q) => $q->where('due_on', '>=', $from))
            ->when($sellerId, fn ($q) => $q->where('seller_id', $sellerId));
    }

    /** One line per seller of what is payable. */
    public static function payableBySeller(?string $from, string $to, ?int $sellerId = null): array
    {
        $rows = self::payableQuery($from, $to, $sellerId)
            ->selectRaw('seller_id, COUNT(*) AS orders, SUM(cod_amount) AS cod, SUM(fee_amount) AS fee, ' .
                'SUM(wallet_adjusted) AS adj, SUM(net_payable) AS net, MIN(due_on) AS first_due, MAX(due_on) AS last_due')
            ->groupBy('seller_id')->orderByDesc('net')->get();
        $banks = self::banks($rows->pluck('seller_id')->map(fn ($v) => (int) $v)->all());
        return $rows->map(fn ($r) => [
            'seller_id' => (int) $r->seller_id,
            'orders'    => (int) $r->orders,
            'cod'       => round((float) $r->cod, 2),
            'fee'       => round((float) $r->fee, 2),
            'adj'       => round((float) $r->adj, 2),
            'net'       => round((float) $r->net, 2),
            'first_due' => $r->first_due,
            'last_due'  => $r->last_due,
            'bank'      => $banks[(int) $r->seller_id] ?? null,
        ])->all();
    }

    // ------------------------------------------------------------------ 3. mark paid

    /**
     * Mark everything payable up to $upTo as paid for the given sellers.
     * Returns list of ['seller_id', 'payout_id', 'amount', 'orders', 'mailed', 'error'].
     */
    public static function markPaid(array $sellerIds, string $upTo, string $paidOn, ?string $reference, ?int $adminId, ?string $from = null): array
    {
        // last chance to top up low wallets before money leaves
        self::settleWallets($sellerIds, $upTo);

        $banks = self::banks($sellerIds);
        $results = [];
        foreach ($sellerIds as $sid) {
            $sid = (int) $sid;
            $payout = DB::transaction(function () use ($sid, $upTo, $from, $paidOn, $reference, $adminId, $banks) {
                $rows = self::payableQuery($from, $upTo, $sid)->lockForUpdate()->get();
                if ($rows->isEmpty()) {
                    return null;
                }
                $b = $banks[$sid] ?? [];
                $pid = DB::table('cod_payouts')->insertGetId([
                    'seller_id'       => $sid,
                    'orders'          => $rows->count(),
                    'cod_amount'      => round((float) $rows->sum('cod_amount'), 2),
                    'fee_amount'      => round((float) $rows->sum('fee_amount'), 2),
                    'wallet_adjusted' => round((float) $rows->sum('wallet_adjusted'), 2),
                    'amount'          => round((float) $rows->sum('net_payable'), 2),
                    'reference'       => $reference ? mb_substr($reference, 0, 120) : null,
                    'paid_on'         => $paidOn,
                    'account_holder'  => mb_substr($b['holder'] ?? '', 0, 150) ?: null,
                    'bank_name'       => mb_substr($b['bank'] ?? '', 0, 150) ?: null,
                    'account_number'  => mb_substr($b['account'] ?? '', 0, 40) ?: null,
                    'ifsc'            => mb_substr($b['ifsc'] ?? '', 0, 20) ?: null,
                    'paid_by'         => $adminId,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
                DB::table('cod_remittances')->whereIn('id', $rows->pluck('id'))->update([
                    'status' => 'paid', 'payout_id' => $pid, 'paid_at' => now(), 'updated_at' => now(),
                ]);
                if (Kit::hasCol('orders', 'payment_status')) {
                    // keeps the older screens (which read orders.payment_status) in step
                    Order::whereIn('id', $rows->pluck('order_id'))->update(['payment_status' => 'Paid']);
                }
                return DB::table('cod_payouts')->where('id', $pid)->first();
            });

            if (!$payout) {
                continue;
            }
            [$mailed, $err] = self::mailPayout($payout);
            $results[] = ['seller_id' => $sid, 'payout_id' => $payout->id, 'amount' => (float) $payout->amount,
                'orders' => (int) $payout->orders, 'mailed' => $mailed, 'error' => $err];
        }
        return $results;
    }

    /** Email the seller about a payout. Returns [sent?, error]. */
    public static function mailPayout($payout): array
    {
        $seller = DB::table('seller_lists')->where('id', $payout->seller_id)->first();
        $email = trim((string) ($seller->email ?? ''));
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            DB::table('cod_payouts')->where('id', $payout->id)->update(['mail_error' => 'No valid email on the account']);
            return [false, 'No valid email on the account'];
        }
        $lines = DB::table('cod_remittances')->where('payout_id', $payout->id)->orderBy('delivered_on')->limit(300)->get();
        try {
            Mail::send('email.cod_paid', [
                'seller' => $seller, 'payout' => $payout, 'lines' => $lines,
                'more' => max(0, (int) $payout->orders - $lines->count()),
                'url' => url('/seller/cod-remittance'),
            ], function ($m) use ($email, $seller, $payout) {
                $m->to($email, $seller->name ?? null)
                  ->subject('COD paid: ₹' . number_format((float) $payout->amount, 2) . ' sent to your bank · Shipxpeed');
            });
            DB::table('cod_payouts')->where('id', $payout->id)->update(['mailed_at' => now(), 'mail_error' => null]);
            return [true, null];
        } catch (\Throwable $e) {
            $msg = mb_substr($e->getMessage(), 0, 250);
            DB::table('cod_payouts')->where('id', $payout->id)->update(['mail_error' => $msg]);
            Log::warning('COD paid mail failed', ['payout' => $payout->id, 'error' => $msg]);
            return [false, $msg];
        }
    }
}
