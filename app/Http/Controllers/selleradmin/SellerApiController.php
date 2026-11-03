<?php

namespace App\Http\Controllers\selleradmin;

use App\Http\Controllers\Controller;
use App\Models\SellerList;
use Illuminate\Support\Facades\Auth;

class SellerApiController extends Controller
{
    /**
     * Generate (or regenerate) the logged-in seller's API token from the panel.
     * Any integration still using the previous token stops working immediately.
     */
    public function token()
    {
        $seller = Auth::guard('seller')->user();
        abort_unless($seller, 403);

        // Random, unique per account: "sxp_" + 28 characters. The key alone identifies this account on the API.
        do {
            $token = 'sxp_' . \Illuminate\Support\Str::random(28);
        } while (SellerList::where('api_token', $token)->exists());
        $seller->api_token = $token;
        $seller->save();

        return redirect()->to(route('seller.api') . '#credentials')
            ->with('api_token_status', 'New API token generated. Update it in every integration that uses this account.');
    }
}
