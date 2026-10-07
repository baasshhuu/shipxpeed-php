<?php

namespace App\Models;

use App\Traits\CustomScopes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class RateCard extends Model
{
    use HasFactory, SoftDeletes, CustomScopes;

    protected $fillable = [
        'seller_id',
        'rate_pdf_1',
        'rate_pdf_2',
        'rate_pdf_3',
        'status',
    ];
}
