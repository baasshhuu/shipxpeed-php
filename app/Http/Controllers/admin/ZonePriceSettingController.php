<?php

namespace App\Http\Controllers\admin;

use App\Models\Brand;
use App\Models\Cms;
use App\Helper\Helper;
use App\Models\Testimonial;
use Illuminate\View\View;
use Illuminate\Http\Request;
use \Yajra\Datatables\Datatables;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use App\Http\Controllers\Controller;
use App\Models\PriceSetting;
use App\Models\ZonePriceSetting;
use App\Models\SellerList;
use App\Models\LogisticProvider;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ZonePriceImport;
use App\Exports\ZonePriceTemplateExport;
use App\Exports\ZoneRateTemplateExport;
use App\Imports\ZoneRateSheetImport;
use App\Models\ActicvSleb;
use App\Support\RateCard;

class ZonePriceSettingController extends Controller
{
    /**
     * Zone Price Setting: one page with three tabs.
     *  - Couriers: switch each courier on/off for one seller or every seller
     *  - Rates:    zone-wise rates per courier for the default rate card or one seller
     *  - Bulk:     download a pre-filled template, upload it back
     */
    public function add(Request $request)
    {
        $sellers = SellerList::orderBy('name')->get(['id', 'name', 'email']);
        $defaultId = RateCard::defaultSellerId();
        $scope = (string) $request->query('seller', 'default');
        if ($scope !== 'default' && !$sellers->firstWhere('id', (int) $scope)) {
            $scope = 'default';
        }
        $sellerId = $scope === 'default' ? $defaultId : (int) $scope;

        $couriers = RateCard::couriers();
        $zones = RateCard::zones();
        $routeZones = RateCard::routeZones();

        $fields = ['prepaid_price', 'cod_price', 'prepaid_fix_price', 'cod_fix_price', 'cod_charge_parsent', 'rto_credit'];
        $pack = function ($rows) use ($fields) {
            $out = [];
            foreach ($rows as $r) {
                $z = RateCard::normalizeZone($r->zone);
                if (!$z) {
                    continue;
                }
                foreach ($fields as $f) {
                    $out[$r->LogisticProvider][$z][$f] = (float) $r->{$f};
                }
            }
            return $out;
        };
        $ownRates = $pack(ZonePriceSetting::where('seller_id', $sellerId)->where('status', 1)->get());
        $defaultRates = $scope === 'default' ? $ownRates : $pack(ZonePriceSetting::where('seller_id', $defaultId)->where('status', 1)->get());

        // On/off state per courier
        $switches = ActicvSleb::whereIn('LogisticProvider', array_keys($couriers))
            ->get(['seller_id', 'LogisticProvider', 'status']);
        $sellerCount = $sellers->count();
        $state = [];
        foreach ($couriers as $key => $c) {
            $rows = $switches->where('LogisticProvider', $key);
            if ($scope === 'default') {
                $on = $c['mode'] === 'optin'
                    ? $rows->whereIn('status', [1, '1'])->pluck('seller_id')->unique()->count()
                    : $sellerCount - $rows->whereIn('status', [0, '0'])->pluck('seller_id')->unique()->count();
                $state[$key] = ['on' => $on, 'of' => $sellerCount, 'checked' => $sellerCount > 0 && $on === $sellerCount];
            } else {
                $row = $rows->firstWhere('seller_id', $sellerId);
                $checked = $c['mode'] === 'optin'
                    ? ($row && (string) $row->status === '1')
                    : !($row && (string) $row->status === '0');
                $state[$key] = ['checked' => $checked];
            }
        }

        return view('admin.zonepricesetting.manager', [
            'sellers' => $sellers,
            'scope' => $scope,
            'sellerId' => $sellerId,
            'defaultId' => $defaultId,
            'couriers' => $couriers,
            'zones' => $zones,
            'routeZones' => $routeZones,
            'ownRates' => $ownRates,
            'defaultRates' => $defaultRates,
            'state' => $state,
            'tab' => in_array($request->query('tab'), ['couriers', 'rates', 'bulk'], true) ? $request->query('tab') : 'couriers',
            'importResult' => session('import_result'),
        ]);
    }

    /** Switch one courier on/off for one seller or for every seller. */
    public function toggleCourier(Request $request): JsonResponse
    {
        $data = $request->validate([
            'seller' => ['required'],
            'key' => ['required', 'string', 'max:191'],
            'on' => ['required', 'boolean'],
        ]);
        $couriers = RateCard::couriers(false);
        if (!isset($couriers[$data['key']])) {
            $couriers = RateCard::couriers();
        }
        if (!isset($couriers[$data['key']])) {
            return response()->json(['status' => false, 'message' => 'Unknown courier.'], 422);
        }
        $status = $request->boolean('on') ? '1' : '0';
        $ids = $data['seller'] === 'default'
            ? SellerList::pluck('id')->all()
            : [(int) $data['seller']];

        foreach ($ids as $id) {
            ActicvSleb::updateOrCreate(
                ['seller_id' => $id, 'LogisticProvider' => $data['key']],
                ['status' => $status]
            );
        }

        $who = $data['seller'] === 'default' ? 'all ' . count($ids) . ' sellers' : (SellerList::find($ids[0])->name ?? 'seller');
        return response()->json([
            'status' => true,
            'message' => $couriers[$data['key']]['label'] . ($status === '1' ? ' switched on' : ' switched off') . ' for ' . $who . '.',
        ]);
    }

    /** Save all zones of one courier for the default rate card or one seller. Empty zone = remove own rate. */
    public function saveGrid(Request $request): JsonResponse
    {
        $data = $request->validate([
            'seller' => ['required'],
            'key' => ['required', 'string', 'max:191'],
            'rows' => ['array'],
            'rows.*.prepaid_price' => ['nullable', 'numeric', 'min:0'],
            'rows.*.cod_price' => ['nullable', 'numeric', 'min:0'],
            'rows.*.prepaid_fix_price' => ['nullable', 'numeric', 'min:0'],
            'rows.*.cod_fix_price' => ['nullable', 'numeric', 'min:0'],
            'rows.*.cod_charge_parsent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'rows.*.rto_credit' => ['nullable', 'numeric', 'min:0'],
        ]);
        $sellerId = $data['seller'] === 'default' ? RateCard::defaultSellerId() : (int) $data['seller'];
        if ($data['seller'] !== 'default' && !SellerList::find($sellerId)) {
            return response()->json(['status' => false, 'message' => 'Seller not found.'], 404);
        }

        $saved = 0;
        $cleared = 0;
        foreach ($data['rows'] ?? [] as $zone => $row) {
            $zone = RateCard::normalizeZone($zone);
            if (!$zone) {
                continue;
            }
            $values = array_map(fn ($v) => ($v === null || $v === '') ? null : (float) $v, $row);
            $match = ['seller_id' => $sellerId, 'zone' => $zone, 'LogisticProvider' => $data['key']];

            if (count(array_filter($values, fn ($v) => $v !== null)) === 0) {
                $cleared += ZonePriceSetting::where($match)->delete();
                continue;
            }
            ZonePriceSetting::updateOrCreate($match, [
                'prepaid_price' => $values['prepaid_price'] ?? 0,
                'cod_price' => $values['cod_price'] ?? 0,
                'prepaid_fix_price' => $values['prepaid_fix_price'] ?? 0,
                'cod_fix_price' => $values['cod_fix_price'] ?? 0,
                'cod_charge_parsent' => $values['cod_charge_parsent'] ?? 0,
                'rto_credit' => $values['rto_credit'] ?? 0,
                'status' => 1,
            ]);
            $saved++;
        }

        $msg = "Saved {$saved} zone" . ($saved === 1 ? '' : 's');
        if ($cleared) {
            $msg .= $data['seller'] === 'default' ? ", removed {$cleared}" : ", {$cleared} now use the default rate card";
        }
        return response()->json(['status' => true, 'message' => $msg . '.']);
    }

    /** Pre-filled Excel template: one row per courier × zone. */
    public function downloadRateTemplate(Request $request)
    {
        $scope = (string) $request->query('seller', 'default');
        $which = (string) $request->query('couriers', 'live');
        $sellerId = $scope === 'default' ? RateCard::defaultSellerId() : (int) $scope;
        $defaultId = RateCard::defaultSellerId();

        $couriers = array_filter(RateCard::couriers(), fn ($c) => match ($which) {
            'all' => true,
            'integrated' => $c['integrated'],
            default => $c['live'],
        });
        if (!$couriers) {
            $couriers = RateCard::couriers();
        }

        $index = function ($sid) {
            $out = [];
            foreach (ZonePriceSetting::where('seller_id', $sid)->where('status', 1)->get() as $r) {
                $out[$r->LogisticProvider . '|' . RateCard::normalizeZone($r->zone)] = $r;
            }
            return $out;
        };
        $own = $index($sellerId);
        $def = $sellerId === $defaultId ? $own : $index($defaultId);

        $rows = [];
        foreach ($couriers as $key => $c) {
            foreach (RateCard::zones() as $zone) {
                $r = $own[$key . '|' . $zone] ?? $def[$key . '|' . $zone] ?? null;
                $rows[] = [
                    $zone, $key, $c['label'] . ' · ' . $c['provider_name'],
                    $r ? (float) $r->prepaid_price : '',
                    $r ? (float) $r->cod_price : '',
                    $r ? (float) $r->prepaid_fix_price : '',
                    $r ? (float) $r->cod_fix_price : '',
                    $r ? (float) $r->cod_charge_parsent : '',
                    $r ? (float) $r->rto_credit : '',
                ];
            }
        }

        $name = $scope === 'default' ? 'default' : 'seller-' . $sellerId;
        return Excel::download(new ZoneRateTemplateExport($rows), "zone-rates-{$name}-" . now()->format('Ymd') . '.xlsx');
    }

    /** Upload the template back. Bad rows are skipped and listed; good rows are saved. */
    public function uploadRates(Request $request): RedirectResponse
    {
        $request->validate([
            'target' => ['required'],
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
        ]);
        $target = (string) $request->input('target');
        $sellerIds = match (true) {
            $target === 'default' => [RateCard::defaultSellerId()],
            $target === 'every' => SellerList::pluck('id')->all(),
            default => array_filter([SellerList::find((int) $target)?->id]),
        };
        if (!$sellerIds) {
            return back()->withError('Choose who these rates are for.');
        }

        try {
            $sheet = Excel::toArray(new ZoneRateSheetImport, $request->file('file'))[0] ?? [];
        } catch (\Throwable $e) {
            return back()->withError('Could not read the file: ' . $e->getMessage());
        }

        $keys = [];
        foreach (RateCard::couriers() as $k => $c) {
            $keys[strtolower($k)] = $k;
        }
        $num = function ($v) {
            if ($v === null || $v === '') {
                return null;
            }
            $v = str_replace([',', '₹', ' '], '', (string) $v);
            return is_numeric($v) ? (float) $v : false;
        };

        $saved = 0;
        $skipped = [];
        foreach ($sheet as $i => $row) {
            $line = $i + 2; // heading row is line 1
            $zone = RateCard::normalizeZone($row['zone'] ?? null);
            $rawKey = trim((string) ($row['logistic_provider'] ?? $row['courier_key'] ?? $row['courier'] ?? ''));
            if (!$zone && $rawKey === '') {
                continue; // empty line
            }
            if (!$zone || strlen($zone) > 3) {
                $skipped[] = "Row {$line}: zone \"" . ($row['zone'] ?? '') . '" is not valid';
                continue;
            }
            $key = $keys[strtolower($rawKey)] ?? null;
            if (!$key) {
                $skipped[] = "Row {$line}: courier \"{$rawKey}\" is not on the courier list";
                continue;
            }
            $vals = [
                'prepaid_price' => $num($row['prepaid_price'] ?? null),
                'cod_price' => $num($row['cod_price'] ?? null),
                'prepaid_fix_price' => $num($row['prepaid_fix_price'] ?? null),
                'cod_fix_price' => $num($row['cod_fix_price'] ?? null),
                'cod_charge_parsent' => $num($row['cod_charge_parsent'] ?? $row['cod_charge_percent'] ?? null),
                'rto_credit' => $num($row['rto_credit'] ?? null),
            ];
            $bad = array_keys(array_filter($vals, fn ($v) => $v === false || ($v !== null && $v < 0)));
            if ($bad) {
                $skipped[] = "Row {$line}: " . implode(', ', $bad) . ' must be a number of 0 or more';
                continue;
            }
            if (($vals['cod_charge_parsent'] ?? 0) > 100) {
                $skipped[] = "Row {$line}: COD % cannot be more than 100";
                continue;
            }
            if (count(array_filter($vals, fn ($v) => $v !== null)) === 0) {
                continue; // nothing filled in for this courier/zone
            }
            foreach ($sellerIds as $sid) {
                ZonePriceSetting::updateOrCreate(
                    ['seller_id' => $sid, 'zone' => $zone, 'LogisticProvider' => $key],
                    array_map(fn ($v) => $v ?? 0, $vals) + ['status' => 1]
                );
            }
            $saved++;
        }

        $who = $target === 'default' ? 'the default rate card' : ($target === 'every' ? 'all ' . count($sellerIds) . ' sellers' : (SellerList::find($sellerIds[0])->name ?? 'the seller'));
        return redirect()->route('zone.pricesetting.add', ['tab' => 'bulk', 'seller' => $target === 'every' ? 'default' : $target])
            ->with('import_result', ['saved' => $saved, 'skipped' => $skipped, 'who' => $who]);
    }

    public function save(Request $request)
    {
        $validated = $request->validate([
            'seller_id' => ['required'],
            'zone' => ['required', 'string'],
            'LogisticProvider' => ['required'],
            'cod_price' => ['required', 'numeric', 'min:0'],
            'cod_fix_price' => ['required', 'numeric', 'min:0'],
            'prepaid_price' => ['required', 'numeric', 'min:0'],
            'prepaid_fix_price' => ['required', 'numeric', 'min:0'],
            'cod_charge_parsent' => ['required', 'numeric', 'min:0', 'max:100'],
                        'rto_credit' => ['nullable', 'numeric', 'min:0'],

        ]);
    //   dd($validated);
        // Check if record exists
        $existing = ZonePriceSetting::where('seller_id', $validated['seller_id'])
            ->where('zone', $validated['zone'])
            ->where('LogisticProvider', $validated['LogisticProvider'])
            ->first();

        $data = [
            'seller_id' => $validated['seller_id'],
            'zone' => $validated['zone'],
            'LogisticProvider' => $validated['LogisticProvider'],
            'cod_price' => $validated['cod_price'],
            'cod_fix_price' => $validated['cod_fix_price'],
            'prepaid_price' => $validated['prepaid_price'],
            'prepaid_fix_price' => $validated['prepaid_fix_price'],
            'cod_charge_parsent' => $validated['cod_charge_parsent'],
            'rto_credit' => $validated['rto_credit'],
            'status' => 1,
        ];

        if ($existing) {
            // Update existing record
            $existing->update($data);
            $message = 'Zone price setting updated successfully!';
        } else {
            // Create new record
            ZonePriceSetting::create($data);
            $message = 'Zone price setting added successfully!';
        }

        return to_route('zone.pricesetting.add')->withSuccess($message);
    }

    public function uploadExcel(Request $request)
    {
        $validated = $request->validate([
            'seller_id' => ['required'],
            'excel_file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:2048'],
        ]);

     try {
            Excel::import(new ZonePriceImport($validated['seller_id']), $validated['excel_file']);
            return back()->withSuccess('Excel data uploaded successfully!');
        } catch (\Exception $e) {
            return back()->withError('Error uploading file: ' . $e->getMessage());
        }
    }

    public function index(Request $request)
    {
        return redirect()->route('zone.pricesetting.add', $request->only('seller', 'tab'));
    }

    public function edit($id)
    {
        $zonePriceSetting = ZonePriceSetting::findOrFail($id);
        $SellerList = SellerList::all();
        // $LogisticProviders = LogisticProvider::all();
         $LogisticProviders = [
// Delhivery
"Delhivery",
"Delhivery_Air",
// Delhivery
// Shadowfax
"Shadowfax",
// Shadowfax
// selloship
"Ekart2KG_selloship",
// selloship
// DTDC
"DTDC_Surface_500gm",
"DTDC_Surface_1kg",
"DTDC_Air",
// DTDC
// tekipost
'tekipost_Delhivery_5kg', 
'tekipost_Delhivery_10kg', 
'tekipost_Delhivery_1_KG', 
'tekipost_Ekart_2_KG_Fixed', 
'tekipost_Amazon_2_kg', 
'tekipost_Amazon_500_GM',
// tekipost
// parcelx
"Parcel_X_Delhivery",
"Parcel_X_Amazon",
"Parcel_X_Amazon_1KG",
"Parcel_X_Amazon_2KG",
"Parcel_X_Delhivery_250gm",
// parcelx
// Shiprocket
"Shiprocket_Xpressbee",
"Shiprocket_Delhivery",
"Shiprocket_Bluedart",
"shiprocket_Bluedart_1kg",
// Shiprocket
"Ekart500gm",
'Ekart500gm_boxd',

    ];
        // Fship couriers (live list from the Fship account)
        $LogisticProviders = array_merge($LogisticProviders, array_keys(RateCard::aggregatorKeys('fship')));

        return view('admin.zonepricesetting.zone_price_edit', compact('zonePriceSetting', 'SellerList', 'LogisticProviders'));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'seller_id' => ['required'],
            'zone' => ['required', 'string'],
            'LogisticProvider' => ['required'],
            'cod_price' => ['required', 'numeric', 'min:0'],
            'cod_fix_price' => ['required', 'numeric', 'min:0'],
            'prepaid_price' => ['required', 'numeric', 'min:0'],
            'prepaid_fix_price' => ['required', 'numeric', 'min:0'],
            'cod_charge_parsent' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $zonePriceSetting = ZonePriceSetting::findOrFail($id);
        $zonePriceSetting->update($validated);

        return to_route('zone.price.index')->withSuccess('Zone price setting updated successfully!');
    }

    public function delete($id)
    {
        $zonePriceSetting = ZonePriceSetting::findOrFail($id);
        $zonePriceSetting->delete();

        return back()->withSuccess('Zone price setting deleted successfully!');
    }

    public function downloadTemplate()
    {
        return Excel::download(new ZonePriceTemplateExport, 'zone_price_template.xlsx');
    }


}
