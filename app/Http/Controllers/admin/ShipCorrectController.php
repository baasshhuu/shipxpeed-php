<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Services\ShipCorrectService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Admin > ShipCorrect: key, connection test, rate test, tracking test, API log. */
class ShipCorrectController extends Controller
{
    public function index(Request $request, ShipCorrectService $sc)
    {
        if ($request->boolean('refresh')) {
            Cache::forget('shipcorrect_unauthorized_' . md5($sc->apiKey()));
        }

        // Connection check = a tiny live rate call (cached 10 min unless refreshed)
        $conn = null;
        if ($sc->isConfigured()) {
            $ck = 'shipcorrect_conn_' . md5($sc->apiKey());
            if ($request->boolean('refresh')) {
                Cache::forget($ck);
            }
            $conn = Cache::remember($ck, now()->addMinutes(10), function () use ($sc) {
                $r = $sc->rateCalculator(['pickup_pincode' => '110053', 'delivery_pincode' => '500001', 'actual_weight' => 0.5,
                    'length' => 10, 'breadth' => 10, 'height' => 10, 'payment_type' => 'prepaid', 'shipment_value' => 500]);
                return ['ok' => $r['ok'], 'error' => $r['error'], 'count' => count($r['data']['rates'] ?? [])];
            });
        }

        $logs = Schema::hasTable('courier_api_logs')
            ? DB::table('courier_api_logs')->where('courier', ShipCorrectService::CODE)->orderByDesc('id')->limit(50)->get()
            : collect();
        $provider = Schema::hasTable('logistic_providers')
            ? DB::table('logistic_providers')->where('code', ShipCorrectService::CODE)->first()
            : null;

        return view('admin.shipcorrect.index', [
            'configured'  => $sc->isConfigured(),
            'keyHint'     => $sc->isConfigured() ? strlen($sc->apiKey()) . ' chars, ends …' . substr($sc->apiKey(), -4) : null,
            'keySource'   => config('shipcorrect.api_key') ? 'env' : 'admin',
            'baseUrl'     => config('shipcorrect.base_url'),
            'markup'      => config('shipcorrect.markup_percent'),
            'conn'        => $conn,
            'couriers'    => ShipCorrectService::rateCardKeys(),
            'logs'        => $logs,
            'provider'    => $provider,
            'rateResult'  => session('shipcorrect_rate_result'),
            'trackResult' => session('shipcorrect_track_result'),
        ]);
    }

    public function saveKey(Request $request)
    {
        $data = $request->validate([
            'api_key' => ['nullable', 'string', 'max:255'],
            'status'  => ['required', 'in:0,1'],
        ]);
        $row = Schema::hasTable('logistic_providers')
            ? DB::table('logistic_providers')->where('code', ShipCorrectService::CODE)->first()
            : null;
        if (!$row) {
            return redirect()->route('admin.shipcorrect.index')->with('error', 'ShipCorrect courier row missing - run php artisan migrate.');
        }
        $update = ['status' => $data['status'] === '1' ? 'active' : 'inactive', 'updated_at' => now()];
        if (filled($data['api_key'] ?? null)) {
            if (!Schema::hasColumn('logistic_providers', 'api_key')) {
                return redirect()->route('admin.shipcorrect.index')->with('error', 'logistic_providers has no api_key column - put SHIPCORRECT_API_KEY in .env instead.');
            }
            $update['api_key'] = preg_replace('/\s+/', '', (string) $data['api_key']);
        }
        DB::table('logistic_providers')->where('id', $row->id)->update($update);
        Cache::forget('shipcorrect_api_key');

        return redirect()->route('admin.shipcorrect.index', ['refresh' => 1])->with('success', 'ShipCorrect settings saved.');
    }

    public function rateTest(Request $request, ShipCorrectService $sc)
    {
        $data = $request->validate([
            'from'    => 'required|digits:6',
            'to'      => 'required|digits:6',
            'weight'  => 'required|numeric|min:0.01|max:100',
            'payment' => 'required|in:cod,prepaid',
            'amount'  => 'required|numeric|min:1',
        ]);
        $rates = $sc->rateCalculator([
            'pickup_pincode'   => $data['from'],
            'delivery_pincode' => $data['to'],
            'actual_weight'    => (float) $data['weight'],
            'length'           => 10, 'breadth' => 10, 'height' => 10,
            'payment_type'     => $data['payment'],
            'shipment_value'   => (float) $data['amount'],
        ]);
        return redirect()->route('admin.shipcorrect.index')->with('shipcorrect_rate_result', ['input' => $data, 'rates' => $rates]);
    }

    public function trackTest(Request $request, ShipCorrectService $sc)
    {
        $data = $request->validate(['awb' => 'required|string|max:60']);
        return redirect()->route('admin.shipcorrect.index')->with('shipcorrect_track_result', ['awb' => $data['awb'], 'track' => $sc->track(trim($data['awb']))]);
    }

    public function logShow(int $id)
    {
        abort_unless(Schema::hasTable('courier_api_logs'), 404);
        $log = DB::table('courier_api_logs')->where('id', $id)->where('courier', ShipCorrectService::CODE)->first();
        abort_unless($log, 404);
        return response()->json($log, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
