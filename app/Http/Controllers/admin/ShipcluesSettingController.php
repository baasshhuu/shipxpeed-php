<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\LogisticProvider;
use App\Services\ShipcluesService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Admin > Logistics > Shipclues settings.
 * Stores the API key in logistic_providers.api_key and the markup in logistic_providers.api_secret (JSON).
 */
class ShipcluesSettingController extends Controller
{
    public function index(Request $request)
    {
        $provider = LogisticProvider::where('code', ShipcluesService::CODE)->first();
        $settings = ShipcluesService::settingsFrom($provider);
        $test = null;

        if ($request->boolean('test')) {
            $test = app(ShipcluesService::class)->getRatesForParams([
                'origin_pincode' => $request->input('from', '110053'),
                'destination_pincode' => $request->input('to', '500001'),
                'package_weight' => $request->input('weight', 500),
                'payment_type' => 'prepaid',
            ]);
        }

        return view('logistics.shipclues', [
            'provider' => $provider,
            'settings' => $settings,
            'hasKey' => $provider && filled($provider->api_key),
            'envKey' => (bool) env('SHIPCLUES_API_KEY'),
            'test' => $test,
        ]);
    }

    public function save(Request $request)
    {
        $data = $request->validate([
            'api_key' => ['nullable', 'string', 'max:255'],
            'markup_percent' => ['required', 'numeric', 'min:0', 'max:500'],
            'markup_flat' => ['required', 'numeric', 'min:0', 'max:10000'],
            'status' => ['required', 'in:0,1'],
        ]);

        $provider = LogisticProvider::withTrashed()->where('code', ShipcluesService::CODE)->first();
        if ($provider && method_exists($provider, 'trashed') && $provider->trashed()) {
            $provider->restore();
        }

        if (!$provider) {
            $email = 'shipclues@shipxpeed.com';
            if (LogisticProvider::withTrashed()->where('email', $email)->exists()) {
                $email = 'shipclues+' . Str::lower(Str::random(6)) . '@shipxpeed.com';
            }
            $provider = new LogisticProvider();
            $provider->name = 'Shipclues';
            $provider->code = ShipcluesService::CODE;
            $provider->email = $email;
            $provider->password = bcrypt(Str::random(32));
            $provider->logo = 'brands/image.png';
        }

        if (filled($data['api_key'] ?? null)) {
            $provider->api_key = trim($data['api_key']);
        }
        $provider->api_secret = json_encode([
            'markup_percent' => (float) $data['markup_percent'],
            'markup_flat' => (float) $data['markup_flat'],
        ]);
        $provider->status = (int) $data['status'];
        $provider->save();

        foreach (['shipclues_api_key', 'shipclues_settings', 'shipclues_provider_linked'] as $key) {
            Cache::forget($key);
        }

        return redirect()->route('shipclues.settings', ['test' => 1])
            ->with('success', 'Shipclues settings saved.');
    }
}
