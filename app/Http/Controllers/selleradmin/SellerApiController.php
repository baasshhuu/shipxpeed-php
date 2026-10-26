<?php

namespace App\Http\Controllers\selleradmin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Shipxpeedapi\AuthController;
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

        $seller->api_token = AuthController::newToken();
        $seller->save();

        return redirect()->to(route('seller.api') . '#credentials')
            ->with('api_token_status', 'New API token generated. Update it in every integration that uses this account.');
    }
}
