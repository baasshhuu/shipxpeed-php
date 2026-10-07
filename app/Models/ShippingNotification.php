<?php

namespace App\Models;

use App\Traits\CustomScopes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;

class ShippingNotification extends Authenticatable
{
    use HasFactory, SoftDeletes;


protected $table = 'shippingnotifications';


    protected $fillable = [
        'order_status',
        'notification_type', // 'email' or 'whatsapp'
        'enabled', // boolean
        'template',
        'updated_at',
        'seller_id',
    ];

    // Scope for notification type
    public function scopeType($query, $type)
    {
        return $query->where('notification_type', $type);
    }

    // Scope for enabled notifications
    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }
}
