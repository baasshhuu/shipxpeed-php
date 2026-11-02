<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\B2bOrder;
use App\Services\BigshipService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Admin > Bigship B2B: login details, on/off, markup, courier approval, rate test, orders, API log. */
class BigshipController extends Controller
{
    public function index(Request $request, BigshipService $bigship)
    {
        $migrated = Schema::hasTable('b2b_orders') && Schema::hasTable('bigship_couriers');
        $saved = BigshipService::saved();
        $cred = $bigship->credentials();

        $login = null;
        if ($request->boolean('test') && $bigship->isConfigured()) {
            Cache::forget('bigship_token');
            $login = $bigship->login(true);
        }

        return view('admin.bigship.index', [
            'migrated'   => $migrated,
            'configured' => $bigship->isConfigured(),
            'source'     => $bigship->credentialSource(),
            'username'   => $cred['username'],
            'keyHint'    => $cred['access_key'] !== '' ? '…' . substr($cred['access_key'], -6) : null,
            'enabled'    => (bool) ($saved['enabled'] ?? false),
            'markup'     => $bigship->markupPercent(),
            'gst'        => $bigship->gstPercent(),
            'login'      => $login,
            'couriers'   => $migrated ? DB::table('bigship_couriers')->orderByDesc('approved')->orderBy('name')->get() : collect(),
            'orders'     => $migrated ? B2bOrder::with('seller:id,name,email')->orderByDesc('id')->limit(50)->get() : collect(),
            'logs'       => Schema::hasTable('courier_api_logs')
                ? DB::table('courier_api_logs')->where('courier', BigshipService::CODE)->orderByDesc('id')->limit(50)->get()
                : collect(),
            'rateResult' => session('bigship_rate_result'),
        ]);
    }

    public function saveSettings(Request $request)
    {
        $d = $request->validate([
            'username'       => 'nullable|email|max:150',
            'password'       => 'nullable|string|max:150',
            'access_key'     => 'nullable|string|max:500',
            'enabled'        => 'required|in:0,1',
            'markup_percent' => 'required|numeric|min:0|max:300',
        ]);
        $save = ['enabled' => (bool) $d['enabled'], 'markup_percent' => (float) $d['markup_percent']];
        if (filled($d['username'] ?? null)) {
            $save['username'] = trim($d['username']);
        }
        if (filled($d['password'] ?? null)) {
            $save['password'] = $d['password'];
        }
        if (filled($d['access_key'] ?? null)) {
            $save['access_key'] = preg_replace('/\s+/', '', preg_replace('/^\s*access\s*key\s*:?/i', '', $d['access_key']));
        }
        BigshipService::saveSettings($save);

        return redirect()->route('admin.bigship.index', ['test' => 1])->with('success', 'Bigship settings saved.');
    }

    public function courier(Request $request, int $id)
    {
        $d = $request->validate(['approved' => 'required|in:0,1']);
        abort_unless(Schema::hasTable('bigship_couriers'), 500);
        DB::table('bigship_couriers')->where('id', $id)->update(['approved' => (bool) $d['approved'], 'updated_at' => now()]);
        return redirect()->route('admin.bigship.index')->with('success', 'Courier ' . ($d['approved'] ? 'approved - sellers can now use it.' : 'turned off for sellers.'));
    }

    public function rateTest(Request $request, BigshipService $bigship)
    {
        $d = $request->validate([
            'from'    => 'required|digits:6',
            'to'      => 'required|digits:6',
            'count'   => 'required|integer|min:1|max:999',
            'length'  => 'required|numeric|min:1',
            'breadth' => 'required|numeric|min:1',
            'height'  => 'required|numeric|min:1',
            'weight'  => 'required|numeric|min:0.1',
            'amount'  => 'required|numeric|min:1',
            'payment' => 'required|in:prepaid,cod',
        ]);
        $res = $bigship->quote([
            'from' => $d['from'], 'to' => $d['to'], 'invoice_amount' => $d['amount'], 'payment' => $d['payment'],
            'cod_amount' => $d['payment'] === 'cod' ? max(1, $d['amount'] - 1) : 0, 'risk_type_id' => 2,
            'boxes' => BigshipService::normaliseBoxes([$d]),
        ], false);

        return redirect()->route('admin.bigship.index')->with('bigship_rate_result', ['input' => $d] + $res);
    }

    public function logShow(int $id)
    {
        abort_unless(Schema::hasTable('courier_api_logs'), 404);
        $log = DB::table('courier_api_logs')->where('courier', BigshipService::CODE)->where('id', $id)->first();
        abort_unless($log, 404);
        return response()->json($log, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
