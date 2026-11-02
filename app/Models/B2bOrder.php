<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Domestic B2B shipment (booked through Bigship). */
class B2bOrder extends Model
{
    protected $table = 'b2b_orders';

    protected $guarded = ['id'];

    protected $casts = [
        'pickup'          => 'array',
        'return_address'  => 'array',
        'consignee'       => 'array',
        'boxes'           => 'array',
        'quote'           => 'array',
        'tracking'        => 'array',
        'invoice_date'    => 'date',
        'last_tracked_at' => 'datetime',
        'booked_at'       => 'datetime',
        'cancelled_at'    => 'datetime',
    ];

    public const STATUS_LABELS = [
        'draft'      => 'Draft',
        'booked'     => 'Booked',
        'in_transit' => 'In transit',
        'delivered'  => 'Delivered',
        'rto'        => 'RTO',
        'cancelled'  => 'Cancelled',
    ];

    public function seller()
    {
        return $this->belongsTo(SellerList::class, 'seller_id');
    }

    public function isCod(): bool
    {
        return $this->payment_mode === 'cod';
    }

    public function canCancel(): bool
    {
        return $this->status === 'booked';
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst(str_replace('_', ' ', (string) $this->status));
    }
}
