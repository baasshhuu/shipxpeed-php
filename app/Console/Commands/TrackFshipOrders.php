<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\FshipService;
use Illuminate\Console\Command;

class TrackFshipOrders extends Command
{
    protected $signature = 'fship:track {--limit=300 : Max orders per run}';
    protected $description = 'Pull latest Fship tracking status for open Fship shipments';

    public function handle(FshipService $fship): int
    {
        if (!$fship->isConfigured()) {
            $this->warn('FSHIP_API_KEY not set - skipping.');
            return self::SUCCESS;
        }

        $orders = Order::where('courier_id', FshipService::CODE)
            ->whereNotNull('awb_number')
            ->where(fn ($q) => $q->whereNull('order_status')->orWhere('order_status', '!=', 'cancelled'))
            ->where(fn ($q) => $q->whereNull('shipping_status')->orWhereNotIn('shipping_status', ['delivered', 'rto', 'cancelled', 'lost']))
            ->orderBy('updated_at')
            ->limit((int) $this->option('limit'))
            ->get();

        $changed = 0;
        foreach ($orders as $order) {
            $before = $order->shipping_status;
            $after = $fship->syncOrderStatus($order);
            if ($after !== null && $after !== $before) {
                $changed++;
            }
        }

        $this->info("Fship: checked {$orders->count()} orders, {$changed} status changes.");
        return self::SUCCESS;
    }
}
