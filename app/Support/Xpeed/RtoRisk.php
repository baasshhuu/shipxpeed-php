<?php

namespace App\Support\Xpeed;

use App\Models\Order;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Simple, explainable RTO risk score (0-100) for an order, from the panel's own history:
 * COD, the buyer's past deliveries / returns (by phone), the delivery pincode's RTO rate,
 * address quality and order value. No outside data, every point comes with a reason.
 */
class RtoRisk
{
    /** @return array{score:int, level:string, reasons:array<int,string>} */
    public static function score(Order $order): array
    {
        $c = Kit::consignee($order);
        $score = 0;
        $why = [];
        $cod = strtolower((string) $order->payment_type) === 'cod';

        if ($cod) {
            $score += 25;
            $why[] = 'Cash on delivery (+25)';
        }

        $phone = substr(preg_replace('/\D/', '', (string) ($c['phone'] ?? '')), -10);
        if (strlen($phone) !== 10 || !preg_match('/^[6-9]/', $phone) || preg_match('/^(\d)\1{9}$/', $phone)) {
            $score += 15;
            $why[] = 'Buyer mobile looks invalid (+15)';
        } else {
            $h = self::phoneHistory($phone, (int) $order->id);
            if ($h['rto'] > 0) {
                $p = min(30, 15 * $h['rto']);
                $score += $p;
                $why[] = "Buyer had {$h['rto']} earlier return(s) to origin (+{$p})";
            }
            if ($h['delivered'] > 0) {
                $score -= 10;
                $why[] = "Buyer accepted {$h['delivered']} earlier delivery(ies) (−10)";
            }
            if ($h['open_cod'] >= 3) {
                $score += 10;
                $why[] = "Buyer has {$h['open_cod']} other COD orders open (+10)";
            }
        }

        $pin = (string) ($c['pincode'] ?? '');
        if (preg_match('/^[1-9][0-9]{5}$/', $pin)) {
            $p = self::pincodeHistory($pin);
            if ($p['total'] >= 5) {
                $rate = $p['rto'] / max(1, $p['total']);
                if ($rate >= 0.15) {
                    $pts = (int) min(25, round($rate * 60));
                    $score += $pts;
                    $why[] = 'Pincode ' . $pin . ' RTO rate ' . round($rate * 100) . "% (+{$pts})";
                }
            }
        } else {
            $score += 10;
            $why[] = 'Delivery pincode missing/invalid (+10)';
        }

        $addr = trim((string) ($c['address'] ?? ''));
        if (mb_strlen($addr) < 20) {
            $score += 10;
            $why[] = 'Very short address (+10)';
        } elseif (!preg_match('/\d/', $addr) && empty($c['landmark'])) {
            $score += 5;
            $why[] = 'No house/flat number or landmark (+5)';
        }

        if ($cod && (float) $order->order_amount >= 3000) {
            $score += 10;
            $why[] = 'High-value COD order (+10)';
        }

        $score = max(0, min(100, $score));
        return ['score' => $score, 'level' => $score >= 60 ? 'high' : ($score >= 35 ? 'medium' : 'low'), 'reasons' => $why];
    }

    private static function phoneHistory(string $phone, int $exceptId): array
    {
        return Cache::remember('xp_rto_phone_' . $phone, now()->addHours(3), function () use ($phone, $exceptId) {
            $out = ['rto' => 0, 'delivered' => 0, 'open_cod' => 0];
            try {
                $rows = DB::table('orders')
                    ->whereRaw("RIGHT(REGEXP_REPLACE(JSON_UNQUOTE(JSON_EXTRACT(consignee, '$.phone')), '[^0-9]', ''), 10) = ?", [$phone])
                    ->where('id', '!=', $exceptId)
                    ->where('created_at', '>=', now()->subYear())
                    ->limit(200)
                    ->get(['shipping_status', 'order_status', 'payment_type', 'awb_number']);
                foreach ($rows as $r) {
                    $b = Kit::bucket($r);
                    if ($b === 'rto') {
                        $out['rto']++;
                    } elseif ($b === 'delivered') {
                        $out['delivered']++;
                    } elseif (in_array($b, ['new', 'booked'], true) && strtolower((string) $r->payment_type) === 'cod') {
                        $out['open_cod']++;
                    }
                }
            } catch (\Throwable $e) {
            }
            return $out;
        });
    }

    private static function pincodeHistory(string $pin): array
    {
        return Cache::remember('xp_rto_pin_' . $pin, now()->addHours(6), function () use ($pin) {
            $out = ['total' => 0, 'rto' => 0];
            try {
                $rows = DB::table('orders')
                    ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(consignee, '$.pincode')) = ?", [$pin])
                    ->whereNotNull('awb_number')
                    ->where('created_at', '>=', now()->subMonths(6))
                    ->limit(1000)
                    ->get(['shipping_status', 'order_status', 'awb_number']);
                foreach ($rows as $r) {
                    $b = Kit::bucket($r);
                    if (in_array($b, ['delivered', 'rto'], true)) {
                        $out['total']++;
                        $out['rto'] += $b === 'rto' ? 1 : 0;
                    }
                }
            } catch (\Throwable $e) {
            }
            return $out;
        });
    }
}
