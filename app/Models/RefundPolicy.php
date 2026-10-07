<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\CustomScopes;


class RefundPolicy extends Model
{
    use HasFactory, SoftDeletes, CustomScopes;

    protected $fillable = [
        'title',
        'description',
        'status'
    ];
}
