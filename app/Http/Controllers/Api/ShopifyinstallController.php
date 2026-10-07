<?php
namespace App\Http\Controllers\Api;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Order;
use App\Models\Recharge;
use App\Models\ShippingNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\LogisticProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Maatwebsite\Excel\Facades\Excel;

use Illuminate\Support\Facades\Schema;
class ShopifyinstallController extends Controller
{

// public function shopifyInstall(Request $request)
// {
//     $shop = $request->get('shop');

//     $scopes = env('SHOPIFY_SCOPES');
//     $clientId = env('SHOPIFY_CLIENT_ID');
//     $redirect = env('SHOPIFY_REDIRECT_URI');

//     $url = "https://{$shop}/admin/oauth/authorize?"
//         . "client_id={$clientId}"
//         . "&scope={$scopes}"
//         . "&redirect_uri={$redirect}";

//     return redirect($url);
// }



public function shopifyInstall(Request $request)
{
    // dd($request->all());
    // If no shop parameter, return error
    if (!$request->has('shop')) {
        return response()->json([
            'error' => 'Shop parameter is required',
            'message' => 'Please provide a shop domain (e.g., yourstore.myshopify.com)'
        ], 400);
    }

    $shop = trim($request->shop);

    // Validate shop domain
    if (!$shop || !str_contains($shop, '.myshopify.com')) {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Invalid shop domain',
                'message' => 'Please enter a valid Shopify store domain (e.g., yourstore.myshopify.com)'
            ], 400);
        }
        return redirect()->route('seller.shopify.integration')
            ->with('error', 'Please enter a valid Shopify store domain (e.g., yourstore.myshopify.com)');
    }

    $scopes = env('SHOPIFY_SCOPES');
    $clientId = env('SHOPIFY_CLIENT_ID');
    $redirect = env('SHOPIFY_REDIRECT_URI');

    // Generate the installation URL
    $installUrl = "https://{$shop}/admin/oauth/authorize?" . http_build_query([
        'client_id' => $clientId,
        'scope' => $scopes,
        'redirect_uri' => $redirect,
    ]);

    // If this is an AJAX request or API call, return the URL
    if ($request->expectsJson()) {
        return response()->json([
            'success' => true,
            'shop' => $shop,
            'install_url' => $installUrl,
            'message' => 'Installation link generated successfully!'
        ]);
    }

    // For web requests, redirect directly to Shopify for installation
    return redirect($installUrl);
}


public function shopifyCallback(Request $request)
{
    try {
        $shop = $request->shop;
        $code = $request->code;

        // Validate required parameters
        if (!$shop || !$code) {
            Log::error('Shopify callback missing required parameters', [
                'shop' => $shop,
                'code' => $code ? 'present' : 'missing'
            ]);
            return redirect()->route('seller.shopify.integration')
                ->with('error', 'Invalid callback parameters. Please try again.');
        }

        // Exchange authorization code for access token
        $response = Http::post("https://{$shop}/admin/oauth/access_token", [
            'client_id' => env('SHOPIFY_CLIENT_ID'),
            'client_secret' => env('SHOPIFY_CLIENT_SECRET'),
            'code' => $code,
        ]);

        if (!$response->successful()) {
            Log::error('Shopify OAuth token exchange failed', [
                'shop' => $shop,
                'status' => $response->status(),
                'response' => $response->body()
            ]);
            return redirect()->route('seller.shopify.integration')
                ->with('error', 'Failed to connect to Shopify. Please try again.');
        }

        $data = $response->json();
        
        if (!isset($data['access_token'])) {
            Log::error('Shopify OAuth response missing access token', [
                'shop' => $shop,
                'response_data' => $data
            ]);
            return redirect()->route('seller.shopify.integration')
                ->with('error', 'Invalid response from Shopify. Please try again.');
        }

        $accessToken = $data['access_token'];

        // Save shop and token to database
        \DB::table('shopify_stores')->updateOrInsert(
            ['shop' => $shop],
            [
                'access_token' => $accessToken,
                'installed_at' => now(),
                'updated_at' => now()
            ]
        );

        Log::info('Shopify store connected successfully', ['shop' => $shop]);

        // Redirect to success page or integration page with success message
        return redirect()->route('seller.shopify.integration')
            ->with('success', "Shop '{$shop}' connected successfully! You can now sync your orders.");

    } catch (\Exception $e) {
        Log::error('Shopify callback error: ' . $e->getMessage(), [
            'shop' => $request->shop ?? 'unknown',
            'exception' => $e->getTraceAsString()
        ]);
        
        return redirect()->route('seller.shopify.integration')
            ->with('error', 'An error occurred while connecting your shop. Please try again.');
    }
}

    /**
     * Handle direct installation redirect for a specific shop
     */
    public function shopifyInstallDirect(Request $request, $shop = null)
    {
        // Get shop from parameter or request
        $shopDomain = $shop ?? $request->shop;
        
        if (!$shopDomain) {
            return redirect()->route('seller.shopify.integration')
                ->with('error', 'Shop domain is required.');
        }

        $shopDomain = trim($shopDomain);

        // Validate shop domain
        if (!str_contains($shopDomain, '.myshopify.com')) {
            return redirect()->route('seller.shopify.integration')
                ->with('error', 'Please enter a valid Shopify store domain (e.g., yourstore.myshopify.com)');
        }

        $scopes = env('SHOPIFY_SCOPES');
        $clientId = env('SHOPIFY_CLIENT_ID');
        $redirect = env('SHOPIFY_REDIRECT_URI');

        // Generate the installation URL and redirect directly
        $installUrl = "https://{$shopDomain}/admin/oauth/authorize?" . http_build_query([
            'client_id' => $clientId,
            'scope' => $scopes,
            'redirect_uri' => $redirect,
        ]);

        return redirect($installUrl);
    }

    /**
     * Get installation status for a shop
     */
    public function getInstallationStatus(Request $request)
    {
        $shop = $request->shop;
        
        if (!$shop) {
            return response()->json(['error' => 'Shop parameter is required'], 400);
        }
        
        $shopRecord = \DB::table('shopify_stores')
            ->where('shop', $shop)
            ->first();
            
        return response()->json([
            'shop' => $shop,
            'installed' => $shopRecord ? true : false,
            'installed_at' => $shopRecord ? $shopRecord->installed_at : null
        ]);
    }


}







