<?php

namespace App\Support\Xpeed;

use App\Models\Order;
use App\Models\Recharge;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Seller reports: the same builders feed instant CSV downloads and scheduled e-mails.
 */
class Reports
{
    public const TYPES = [
        'orders' => ['All shipments', 'Every order with courier, status, charges and buyer location'],
        'ndr'    => ['NDR / failed deliveries', 'Shipments whose delivery failed, with the action taken'],
        'rto'    => ['RTO', 'Shipments returned to origin'],
        'cod'    => ['COD remittance', 'Delivered COD shipments with remittance status'],
        'wallet' => ['Wallet ledger', 'Every recharge, debit and refund on your wallet'],
    ];

    public const FREQUENCIES = ['daily' => 'Daily (yesterday)', 'weekly' => 'Weekly (last 7 days, Mondays)', 'monthly' => 'Monthly (last month, on the 1st)'];

    /** @return array{0: array, 1: \Generator} headings + rows */
    public static function build(int $sellerId, string $type, Carbon $from, Carbon $to): array
    {
        $to = $to->copy()->endOfDay();
        $from = $from->copy()->startOfDay();

        if ($type === 'wallet') {
            $head = ['Date', 'Type', 'Amount', 'Description', 'Status'];
            $rows = (function () use ($sellerId, $from, $to) {
                foreach (Recharge::where('seller_id', $sellerId)->whereBetween('created_at', [$from, $to])->orderBy('id')->cursor() as $r) {
                    yield [optional($r->created_at)->format('Y-m-d H:i'), $r->type, $r->amount, $r->description, (int) $r->status === 1 ? 'Success' : 'Pending/Failed'];
                }
            })();
            return [$head, $rows];
        }

        $head = ['Order ID', 'Order No.', 'AWB', 'Courier', 'Status', 'Payment', 'Order value', 'COD amount', 'Shipping charge', 'Weight', 'Buyer', 'Buyer phone', 'City', 'State', 'Pincode', 'Created', 'Delivered on'];
        if ($type === 'cod') {
            $head[] = 'Remittance';
        }
        if ($type === 'ndr') {
            $head[] = 'Last NDR action';
            $head[] = 'Action status';
        }

        $q = Order::where('seller_id', $sellerId)->whereBetween('created_at', [$from, $to])->orderBy('id');
        if ($type === 'cod') {
            $q->where('payment_type', 'cod');
        }
        $hasDelivered = Kit::hasCol('orders', 'delivered_date');
        $ndrActions = $type === 'ndr' && Kit::ready('ndr_responses');

        $rows = (function () use ($q, $type, $hasDelivered, $ndrActions) {
            foreach ($q->cursor() as $o) {
                $b = Kit::bucket($o);
                if ($type === 'ndr' && $b !== 'ndr' && !($ndrActions && DB::table('ndr_responses')->where('order_id', $o->id)->whereNotNull('action')->exists())) {
                    continue;
                }
                if ($type === 'rto' && $b !== 'rto') {
                    continue;
                }
                if ($type === 'cod' && $b !== 'delivered') {
                    continue;
                }
                $c = Kit::consignee($o);
                $row = [$o->id, Kit::ref($o), $o->awb_number, $o->awb_number ? Kit::courierLabel($o) : '', Kit::BUCKET_LABELS[$b] ?? $b,
                    strtoupper((string) $o->payment_type), $o->order_amount, $o->collectable_amount, $o->shipping_charges, $o->package_weight,
                    $c['name'] ?? '', $c['phone'] ?? '', $c['city'] ?? '', $c['state'] ?? '', $c['pincode'] ?? '',
                    optional($o->created_at)->format('Y-m-d H:i'), $hasDelivered ? ($o->delivered_date ?? '') : ''];
                if ($type === 'cod') {
                    $row[] = strtolower((string) $o->payment_status) === 'paid' ? 'Remitted' : 'Pending';
                }
                if ($type === 'ndr') {
                    $a = $ndrActions ? DB::table('ndr_responses')->where('order_id', $o->id)->whereNotNull('action')->orderByDesc('id')->first() : null;
                    $row[] = $a->action ?? '';
                    $row[] = $a->status ?? '';
                }
                yield $row;
            }
        })();

        return [$head, $rows];
    }

    /** Window a schedule covers when it runs now. */
    public static function window(string $frequency): array
    {
        return match ($frequency) {
            'weekly'  => [now()->subDays(7)->startOfDay(), now()->subDay()->endOfDay()],
            'monthly' => [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()],
            default   => [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()],
        };
    }

    public static function due(object $s): bool
    {
        $last = $s->last_sent_at ? Carbon::parse($s->last_sent_at) : null;
        return match ($s->frequency) {
            'weekly'  => now()->isMonday() && (!$last || $last->lt(now()->startOfDay())),
            'monthly' => now()->day === 1 && (!$last || $last->lt(now()->startOfDay())),
            default   => !$last || $last->lt(now()->startOfDay()),
        };
    }

    /** Builds the CSV for a schedule and e-mails it. Returns a short result line. */
    public static function send(object $s): string
    {
        [$from, $to] = self::window($s->frequency);
        [$head, $rows] = self::build((int) $s->seller_id, $s->report, $from, $to);

        $tmp = tempnam(sys_get_temp_dir(), 'xprep');
        $fh = fopen($tmp, 'w');
        fwrite($fh, "\xEF\xBB\xBF");
        fputcsv($fh, $head);
        $count = 0;
        foreach ($rows as $r) {
            fputcsv($fh, $r);
            $count++;
        }
        fclose($fh);

        $emails = array_values(array_filter(Kit::tokens($s->emails, 10), fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL)));
        if (!$emails) {
            @unlink($tmp);
            return 'No valid e-mail address';
        }
        $title = self::TYPES[$s->report][0] ?? ucfirst($s->report);
        $period = $from->format('d M Y') . ($from->isSameDay($to) ? '' : ' – ' . $to->format('d M Y'));
        $file = 'shipxpeed-' . $s->report . '-' . $from->format('Ymd') . '.csv';

        Mail::raw("Hello,\n\nAttached is your Shipxpeed {$title} report for {$period} ({$count} rows).\n\nYou can change or stop this e-mail any time from Seller panel › Reports.\n\nTeam Shipxpeed",
            function ($m) use ($emails, $title, $period, $tmp, $file) {
                $m->to($emails)->subject("Shipxpeed {$title} report · {$period}")->attach($tmp, ['as' => $file, 'mime' => 'text/csv']);
            });
        @unlink($tmp);
        return "Sent {$count} rows to " . implode(', ', $emails);
    }
}
