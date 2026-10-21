<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\ShipcluesService;
use Illuminate\Console\Command;

class ShipcluesTrack extends Command
{
    protected $signature = 'shipclues:track {--awb=} {--limit=300}';
    protected $description = 'Refresh shipping status of open Shipclues shipments';

    public function handle(ShipcluesService $shipclues): int
    {
        $query = Order::where('courier_id', ShipcluesService::CODE)
            ->whereNotNull('awb_number')
            ->where(fn ($q) => $q->whereNull('order_status')->orWhere('order_status', '!=', 'cancelled'))
            ->where(fn ($q) => $q->whereNull('shipping_status')
                ->orWhereNotIn('shipping_status', ['delivered', 'rto delivered', 'cancelled', 'lost']));

        if ($awb = $this->option('awb')) {
            $query->where('awb_number', $awb);
        }

        $done = 0;
        foreach ($query->orderBy('updated_at')->limit((int) $this->option('limit'))->get() as $order) {
            try {
                $status = $shipclues->trackOrder($order);
                $this->line($order->awb_number . ' => ' . ($status ?? 'no data'));
                $done++;
            } catch (\Throwable $e) {
                $this->warn($order->awb_number . ': ' . $e->getMessage());
            }
            usleep(200000);
        }

        $this->info("Checked {$done} Shipclues shipments.");
        return self::SUCCESS;
    }
}
