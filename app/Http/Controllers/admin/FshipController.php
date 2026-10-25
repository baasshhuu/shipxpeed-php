<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Services\FshipService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Admin > Fship: connection status, live courier list, rate test, API logs. */
class FshipController extends Controller
{
    public function index(Request $request, FshipService $fship)
    {
        $couriers = $fship->isConfigured() ? $fship->getCouriers((bool) $request->boolean('refresh')) : [];

        $logs = Schema::hasTable('courier_api_logs')
            ? DB::table('courier_api_logs')->where('courier', FshipService::CODE)->orderByDesc('id')->limit(50)->get()
            : collect();

        $provider = Schema::hasTable('logistic_providers')
            ? DB::table('logistic_providers')->where('code', FshipService::CODE)->first()
            : null;

        $warehouses = Schema::hasTable('fship_warehouses')
            ? DB::table('fship_warehouses')->orderByDesc('id')->limit(50)->get()
            : collect();

        $rateResult = session('fship_rate_result');

        return view('admin.fship.index', [
            'configured' => $fship->isConfigured(),
            'env'        => config('fship.env'),
            'baseUrl'    => config('fship.base_url'),
            'markup'     => config('fship.markup_percent'),
            'couriers'   => $couriers,
            'logs'       => $logs,
            'provider'   => $provider,
            'warehouses' => $warehouses,
            'rateResult' => $rateResult,
            'migrated'   => Schema::hasTable('courier_api_logs') && Schema::hasTable('fship_warehouses'),
        ]);
    }

    public function rateTest(Request $request, FshipService $fship)
    {
        $data = $request->validate([
            'from'    => 'required|digits:6',
            'to'      => 'required|digits:6',
            'weight'  => 'required|numeric|min:0.01|max:100',
            'payment' => 'required|in:COD,P',
            'amount'  => 'required|numeric|min:1',
        ]);

        $serviceable = $fship->pincodeServiceability($data['from'], $data['to']);
        $rates = $fship->rateCalculator([
            'source_Pincode'      => $data['from'],
            'destination_Pincode' => $data['to'],
            'payment_Mode'        => $data['payment'],
            'amount'              => (float) $data['amount'],
            'express_Type'        => 'surface',
            'shipment_Weight'     => (float) $data['weight'],
            'shipment_Length'     => 10,
            'shipment_Width'      => 10,
            'shipment_Height'     => 10,
            'volumetric_Weight'   => 0.2,
        ]);

        return redirect()->route('admin.fship.index')->with('fship_rate_result', [
            'input'       => $data,
            'serviceable' => $serviceable,
            'rates'       => $rates,
        ]);
    }

    public function logShow(int $id)
    {
        abort_unless(Schema::hasTable('courier_api_logs'), 404);
        $log = DB::table('courier_api_logs')->where('id', $id)->first();
        abort_unless($log, 404);
        return response()->json($log, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
