<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

trait CustomScopes
{
    public function getGuard()
    {
        return $this->guard;
    }

    public function scopeToday(Builder $query)
    {
        return $query->whereDate('created_at', now()->today());
    }

    public function scopeYesterday(Builder $query)
    {
        return $query->whereDate('created_at', now()->yesterday());
    }

    public function scopeActive(Builder $query, int $type = 1)
    {
        return $query->where('status', $type);
    }

    public static function slug($slug, $columns = ['*'])
    {
        $instance = new static;
        return $instance->newQuery()->select($columns)->whereSlug($slug)->first();
    }
}
