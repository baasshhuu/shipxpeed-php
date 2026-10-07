<?php

namespace App\Models;

use App\Traits\CustomScopes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class SendWhatsAppMessage extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'SendWhatsAppMessages';

    protected $fillable = [
        'number',
    ];

}
