<?php

namespace App\Observers;

use App\Models\User;
use App\Helper\Helper;
use Illuminate\Support\Str;

class UserObserver
{
    public function creating(User $user)
    {
        $user->slug      = Str::uuid();
        $user->image     = $user->image ?? 'admin/avatar.png';
    }

    public function created(User $user)
    {
        // Code after save
        $user->userId = Helper::orderId($user->id, 'A', 6);
        $user->saveQuietly();
    }
}
