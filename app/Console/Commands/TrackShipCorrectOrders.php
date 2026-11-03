<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\ShipCorrectService;
use Illuminate\Console\Command;

class TrackShipCorrectOrders extends Command
{
    protected $signature = 'shipcorrect:track {--limit=300 : Max orders per run}';
    protected $description = 'Pull latest ShipCorrect tracking status for open ShipCorrect shipments';

    public function handle(ShipCorrectService $sc): int
    {
        if (!$sc->isConfigured()) {
            $this->warn('SHIPCORRECT_API_KEY not set - skipping.');
            return self::SUCCESS;
        }

        $orders = Order::where('courier_id', ShipCorrectService::CODE)
            ->whereNotNull('awb_number')
            ->where(fn ($q) => $q->whereNull('order_status')->orWhere('order_status', '!=', 'cancelled'))
            ->where(fn ($q) => $q->whereNull('shipping_status')->orWhereNotIn('shipping_status', ['delivered', 'rto', 'cancelled', 'lost']))
            ->orderBy('updated_at')
            ->limit((int) $this->option('limit'))
            ->get();

        $changed = 0;
        foreach ($orders as $order) {
            $before = $order->shipping_status;
            $after = $sc->syncOrderStatus($order);
            if ($after !== null && $after !== $before) {
                $changed++;
            }
        }

        $this->info("ShipCorrect: checked {$orders->count()} orders, {$changed} status changes.");
        return self::SUCCESS;
    }
}
