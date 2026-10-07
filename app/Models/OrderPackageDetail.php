<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderPackageDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'package_type',
        'total_weight',
        'length',
        'width',
        'height',
        'volumetric_weight',
    ];

    protected $casts = [
        'total_weight' => 'decimal:2',
        'length' => 'decimal:2',
        'width' => 'decimal:2',
        'height' => 'decimal:2',
        'volumetric_weight' => 'decimal:2',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    protected static function booted()
    {
        static::saving(function ($package) {
            if ($package->length && $package->width && $package->height) {
                $package->volumetric_weight = ($package->length * $package->width * $package->height) / 5000;
            }
        });
    }
}
