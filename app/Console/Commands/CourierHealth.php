<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

/**
 * php artisan couriers:health
 *
 * Read-only check of every configured courier (never books or cancels anything):
 *  1. adapter class loads
 *  2. courier API host reachable (+ Delhivery token valid via the pincode API)
 *  3. serviceability works for recent real orders
 *  4. booking failures in the last N days with top reasons
 *  5. orders stuck without AWB
 */
class CourierHealth extends Command
{
    protected $signature = 'couriers:health {--days=7} {--orders=3}';
    protected $description = 'Read-only health check of all couriers and recent booking failures';

    private const HOSTS = [
        'delhivery_b2c'         => 'https://track.delhivery.com',
        'delhivery_b2c_Express' => 'https://track.delhivery.com',
        'delhivery_zapdeal'     => 'https://track.delhivery.com',
        'xpressbees'            => 'https://shipment.xpressbees.com',
        'shadowfax'             => 'https://dale.shadowfax.in',
        'smartship'             => 'https://api.smartship.in',
        'ekart'                 => 'https://app.elite.ekartlogistics.in',
        'dtdc'                  => 'https://dtdcapi.shipsy.io',
        'shiprocket'            => 'https://apiv2.shiprocket.in',
        'fship'                 => 'https://capi.fship.in',
        'shipclues'             => 'https://api.shipclues.in',
    ];

    public function handle(): int
    {
        $rows = [];
        $codes = array_unique(array_merge(array_keys(config('couriers', [])), ['shipclues', 'fship']));
        $recent = Order::whereNotNull('consignee')->whereNotNull('pickup')->latest('id')->limit((int) $this->option('orders'))->get();

        foreach ($codes as $code) {
            $row = ['courier' => $code, 'class' => 'OK', 'api' => '-', 'serviceability' => '-', 'note' => ''];
            try {
                $svc = app('courier.' . $code);
            } catch (\Throwable $e) {
                $row['class'] = 'FAIL';
                $row['note'] = mb_substr($e->getMessage(), 0, 80);
                $rows[] = $row;
                continue;
            }

            if (isset(self::HOSTS[$code])) {
                try {
                    $t = microtime(true);
                    $r = Http::timeout(8)->withoutRedirecting()->get(self::HOSTS[$code]);
                    $row['api'] = 'UP ' . $r->status() . ' ' . round((microtime(true) - $t) * 1000) . 'ms';
                } catch (\Throwable $e) {
                    $row['api'] = 'DOWN';
                    $row['note'] = mb_substr($e->getMessage(), 0, 80);
                }
            }

            if (str_starts_with($code, 'delhivery')) {
                $token = env('DELHIVERY_B2C_TOKEN');
                if ($token) {
                    try {
                        $r = Http::timeout(8)->withHeaders(['Authorization' => 'Token ' . $token])
                            ->get('https://track.delhivery.com/c/api/pin-codes/json/', ['filter_codes' => '110001']);
                        $row['api'] .= $r->status() === 200 ? ' | token OK' : ' | token ' . $r->status();
                    } catch (\Throwable $e) {
                    }
                } else {
                    $row['note'] = trim($row['note'] . ' DELHIVERY_B2C_TOKEN not in .env (token is hardcoded in code)');
                }
            }

            if (method_exists($svc, 'getServiceability') && $recent->isNotEmpty()) {
                $ok = 0; $empty = 0; $err = 0; $lastErr = '';
                foreach ($recent as $o) {
                    try {
                        if ($seller = \App\Models\SellerList::find($o->seller_id)) {
                            Auth::guard('seller')->setUser($seller);
                        }
                        $res = $svc->getServiceability(['order_id' => $o->id]);
                        empty($res) ? $empty++ : $ok++;
                    } catch (\Throwable $e) {
                        $err++;
                        $lastErr = mb_substr($e->getMessage(), 0, 70);
                    }
                }
                $row['serviceability'] = "ok {$ok} / none {$empty} / err {$err}";
                if ($lastErr) $row['note'] = trim($row['note'] . ' ' . $lastErr);
            }
            $rows[] = $row;
        }

        $this->table(['Courier', 'Adapter', 'API', 'Serviceability (recent orders)', 'Note'], $rows);

        $days = (int) $this->option('days');
        if (Schema::hasTable('courier_booking_failures')) {
            $this->info("Booking failures in the last {$days} days:");
            $f = DB::table('courier_booking_failures')->where('created_at', '>=', now()->subDays($days))
                ->select('courier_code', DB::raw('COUNT(*) as n'), DB::raw('MAX(created_at) as last'))
                ->groupBy('courier_code')->orderByDesc('n')->get();
            $this->table(['Courier', 'Failures', 'Last'], $f->map(fn ($x) => [(string) $x->courier_code, $x->n, $x->last])->all());
            $top = DB::table('courier_booking_failures')->where('created_at', '>=', now()->subDays($days))
                ->select('courier_code', 'reason', DB::raw('COUNT(*) as n'))->groupBy('courier_code', 'reason')->orderByDesc('n')->limit(10)->get();
            $this->table(['Courier', 'Count', 'Reason'], $top->map(fn ($x) => [(string) $x->courier_code, $x->n, mb_substr($x->reason, 0, 110)])->all());
        } else {
            $this->warn('Run php artisan migrate to enable the booking failure log.');
        }

        $stuck = Order::whereNull('awb_number')->whereNotNull('seller_amount_walate')->where('seller_amount_walate', '>', 0)
            ->where('updated_at', '>=', now()->subDays($days))->count();
        $this->line("Orders with a courier charge set but no AWB (last {$days} days): {$stuck}");

        return self::SUCCESS;
    }
}
