<?php

namespace App\Models;

use App\Traits\CustomScopes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends Model
{
    use HasFactory, SoftDeletes, CustomScopes;

    public const STATUSES = ['Pending', 'In Progress', 'Resolved', 'Rejected'];

    public const SUBJECTS = [
        'Pickup issue',
        'Delivery delay',
        'NDR / RTO',
        'Weight dispute',
        'COD remittance',
        'Wallet / billing',
        'Rate / pricing',
        'Lost or damaged shipment',
        'Technical / API',
        'Other',
    ];

    protected $fillable = [
        'ticket_id',
        'seller_id',
        'order_id',
        'category_id',
        'subject',
        'message',
        'status',
        'admin_reply',
        'replied_at',
        'mail_sent_at',
    ];

    protected $casts = [
        'replied_at'   => 'datetime',
        'mail_sent_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // Ticket number like SXT-00042, assigned right after the row is saved.
        static::created(function (Ticket $ticket) {
            if (empty($ticket->ticket_id)) {
                $ticket->ticket_id = 'SXT-' . str_pad($ticket->id, 5, '0', STR_PAD_LEFT);
                $ticket->saveQuietly();
            }
        });
    }

    public function seller()
    {
        return $this->belongsTo(SellerList::class, 'seller_id');
    }
}
