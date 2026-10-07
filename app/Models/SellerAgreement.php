<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SellerAgreement extends Model
{
    use HasFactory;

  protected $fillable = [
    'seller_id',
    'client_name',
    'client_address',
    'client_pan',
    'agreement_content',
    'pdf_path'
];

    public function seller()
    {
        return $this->belongsTo(SellerList::class);
    }
}
