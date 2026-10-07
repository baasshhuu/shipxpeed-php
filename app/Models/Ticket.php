<?php

namespace App\Models;

use App\Traits\CustomScopes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends Model
{
    use HasFactory, SoftDeletes, CustomScopes;

    protected $fillable = [
        'ticket_id',
        'seller_id',
        'order_id',
        'category_id',
        'message',
        'status'
    ];

    public function seller()
    {
        return $this->belongsTo(SellerList::class);
    }

}
