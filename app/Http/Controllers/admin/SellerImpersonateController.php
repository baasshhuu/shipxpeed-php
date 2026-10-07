<?php

// app/Http/Controllers/Admin/SellerImpersonateController.php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\SellerList;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class SellerImpersonateController extends Controller
{
    public function loginAsSeller($id)
    {
        $seller = SellerList::findOrFail($id);

        // Save current admin ID to session
        Session::put('admin_impersonator_id', Auth::id());

        // Use seller guard to log in
        Auth::guard('seller')->login($seller);

        return redirect()->route('seller.dashboard')->with('success', 'Now logged in as seller.');
    }
}
