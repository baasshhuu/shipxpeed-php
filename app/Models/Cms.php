<?php

namespace App\Models;

use App\Traits\CustomScopes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cms extends Model
{
    use HasFactory, SoftDeletes, CustomScopes;

    protected $fillable = [
        'title',
        'description',
        'status',
        'image'
    ];
}
