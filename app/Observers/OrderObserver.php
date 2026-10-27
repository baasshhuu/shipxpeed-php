<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\WhatsApp\OrderStatusNotifier;

/**
 * One place that sees every shipment status change, whichever courier sync,
 * webhook, cron or admin action saved it, and triggers the buyer WhatsApp alert.
 */
class OrderObserver
{
    public function created(Order $order): void
    {
        if (filled($order->awb_number)) {
            app(OrderStatusNotifier::class)->notify($order, 'booked');
        }
    }

    public function updated(Order $order): void
    {
        if ($order->wasChanged(['shipping_status', 'order_status', 'awb_number'])) {
            app(OrderStatusNotifier::class)->handleOrderChange($order);
        }
    }
}
