<?php

namespace App\Http\Controllers\admin;

use App\Exports\RateManagerTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\RateManagerSheetImport;
use App\Models\SellerList;
use App\Models\ZonePriceSetting;
use App\Support\CourierAccess;
use App\Support\RateCard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Admin > Rate Manager.
 * Default rate card (all sellers) or one seller's own rates, per courier × zone:
 * first 500 g, each additional 500 g, COD ₹, COD %. Excel template download/upload.
 * The courier list is the Courier Status list, so new integrations appear here automatically.
 */
class RateManagerController extends Controller
{
    private const FIELDS = ['first' => 'prepaid_price', 'additional' => 'additional_price', 'cod' => 'cod_price', 'cod_percent' => 'cod_charge_parsent'];

    public function index(Request $request)
    {
        if ($request->query('refresh')) {
            Cache::forget('ratecard_keys_shipclues');
            Cache::forget('ratecard_keys_fship');
        }
        $sellers = SellerList::orderBy('name')->get();
        $defaultId = RateCard::defaultSellerId();
        $scope = (string) $request->query('seller', 'default');
        if ($scope !== 'default' && !$sellers->firstWhere('id', (int) $scope)) {
            $scope = 'default';
        }
        $sellerId = $scope === 'default' ? $defaultId : (int) $scope;

        $groups = CourierAccess::groups();
        $zones = RateCard::zones();
        $own = $this->ratesFor($sellerId);
        $def = $scope === 'default' ? $own : $this->ratesFor($defaultId);

        return view('admin.ratemanager.index', [
            'sellers' => $sellers,
            'scope' => $scope,
            'groups' => $groups,
            'zones' => $zones,
            'own' => $own,
            'def' => $def,
            'importResult' => session('rate_import_result'),
        ]);
    }

    /** Save all zones of one courier. Empty first 500 g = remove (seller then pays the default rate). */
    public function save(Request $request): JsonResponse
    {
        $data = $request->validate([
            'seller' => ['required'],
            'key' => ['required', 'string', 'max:120'],
            'rows' => ['array'],
            'rows.*.first' => ['nullable', 'numeric', 'min:0'],
            'rows.*.additional' => ['nullable', 'numeric', 'min:0'],
            'rows.*.cod' => ['nullable', 'numeric', 'min:0'],
            'rows.*.cod_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);
        if (!in_array($data['key'], CourierAccess::keys(), true)) {
            return response()->json(['success' => false, 'message' => 'This courier is not in the panel.'], 422);
        }
        $sellerId = $data['seller'] === 'default' ? RateCard::defaultSellerId() : (int) $data['seller'];
        if ($data['seller'] !== 'default' && !SellerList::find($sellerId)) {
            return response()->json(['success' => false, 'message' => 'Seller not found.'], 404);
        }

        $saved = 0;
        $cleared = 0;
        foreach ($data['rows'] ?? [] as $zone => $row) {
            $zone = RateCard::normalizeZone($zone);
            if (!$zone) {
                continue;
            }
            $vals = [];
            foreach (self::FIELDS as $in => $col) {
                $v = $row[$in] ?? null;
                $vals[$col] = ($v === null || $v === '') ? null : (float) $v;
            }
            $match = ['seller_id' => $sellerId, 'zone' => $zone, 'LogisticProvider' => $data['key']];
            if (!$vals['prepaid_price']) {
                if (array_filter($vals, fn ($v) => $v !== null)) {
                    return response()->json(['success' => false, 'message' => "Zone {$zone}: enter the first 500 g price."], 422);
                }
                $cleared += ZonePriceSetting::where($match)->delete();
                continue;
            }
            $this->store($match, $vals);
            $saved++;
        }

        $msg = "Saved {$saved} zone" . ($saved === 1 ? '' : 's');
        if ($cleared) {
            $msg .= $data['seller'] === 'default' ? ", removed {$cleared}" : ", {$cleared} now use the default rate";
        }
        return response()->json(['success' => true, 'message' => $msg . '.']);
    }

    /** Excel template: every courier in the panel × every zone, pre-filled with current rates. */
    public function template(Request $request)
    {
        $scope = (string) $request->query('seller', 'default');
        $defaultId = RateCard::defaultSellerId();
        $seller = $scope !== 'default' ? SellerList::find((int) $scope) : null;
        $sellerId = $seller ? $seller->id : $defaultId;
        $own = $this->ratesFor($sellerId);
        $def = $seller ? $this->ratesFor($defaultId) : $own;

        $rows = [];
        foreach (CourierAccess::groups() as $g) {
            foreach ($g['couriers'] as $key => $c) {
                foreach (RateCard::zones() as $z) {
                    $r = $own[$key][$z] ?? $def[$key][$z] ?? [];
                    $rows[] = [
                        $z, $key, $c['label'], $c['default'] ? 'yes' : 'no',
                        $r['first'] ?? '', $r['additional'] ?? '', $r['cod'] ?? '', $r['cod_percent'] ?? '',
                    ];
                }
            }
        }

        $zoneRows = [];
        try {
            $pairs = DB::table('pincode_zones')->get(['zone', 'pickup_state', 'deliver_state']);
            foreach ($pairs->groupBy(fn ($p) => RateCard::normalizeZone($p->zone)) as $z => $list) {
                $zoneRows[] = [$z, $list->count(), $list->take(4)->map(fn ($p) => $p->pickup_state . ' → ' . $p->deliver_state)->implode('; ')];
            }
            usort($zoneRows, fn ($a, $b) => strcmp((string) $a[0], (string) $b[0]));
        } catch (\Throwable $e) {
        }

        $who = $seller ? $seller->name : 'Default rate card (all sellers)';
        $file = 'rates-' . ($seller ? 'seller-' . $seller->id : 'default') . '-' . now()->format('Ymd') . '.xlsx';
        return Excel::download(new RateManagerTemplateExport($rows, $zoneRows, $who), $file);
    }

    /** Upload the filled template. Bad rows are skipped and listed; good rows are saved. */
    public function upload(Request $request): RedirectResponse
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
            $sheets = Excel::toArray(new RateManagerSheetImport, $request->file('file'));
            $sheet = $sheets[0] ?? reset($sheets) ?: [];
        } catch (\Throwable $e) {
            return back()->withError('Could not read the file: ' . $e->getMessage());
        }

        $keys = [];
        foreach (CourierAccess::keys() as $k) {
            $keys[strtolower($k)] = $k;
        }
        $num = function ($v) {
            if ($v === null || $v === '') {
                return null;
            }
            $v = str_replace([',', '₹', ' ', '%'], '', (string) $v);
            return is_numeric($v) ? (float) $v : false;
        };

        $saved = 0;
        $skipped = [];
        foreach ($sheet as $i => $row) {
            $line = $i + 2;
            $zone = RateCard::normalizeZone($row['zone'] ?? null);
            $rawKey = trim((string) ($row['courier_key'] ?? $row['logistic_provider'] ?? ''));
            if (!$zone && $rawKey === '') {
                continue;
            }
            if (!$zone || strlen($zone) > 3) {
                $skipped[] = "Row {$line}: zone \"" . ($row['zone'] ?? '') . '" is not valid';
                continue;
            }
            $key = $keys[strtolower($rawKey)] ?? null;
            if (!$key) {
                $skipped[] = "Row {$line}: courier \"{$rawKey}\" is not in the panel";
                continue;
            }
            $vals = [
                'prepaid_price' => $num($row['first_500g'] ?? null),
                'additional_price' => $num($row['additional_500g'] ?? null),
                'cod_price' => $num($row['cod_charge'] ?? null),
                'cod_charge_parsent' => $num($row['cod_percent'] ?? null),
            ];
            $bad = array_keys(array_filter($vals, fn ($v) => $v === false || ($v !== null && $v < 0)));
            if ($bad) {
                $skipped[] = "Row {$line}: " . implode(', ', $bad) . ' must be a number of 0 or more';
                continue;
            }
            if (!array_filter($vals, fn ($v) => $v !== null)) {
                continue; // nothing filled in
            }
            if (!$vals['prepaid_price']) {
                $skipped[] = "Row {$line}: first_500g is required";
                continue;
            }
            if (($vals['cod_charge_parsent'] ?? 0) > 100) {
                $skipped[] = "Row {$line}: cod_percent cannot be more than 100";
                continue;
            }
            foreach ($sellerIds as $sid) {
                $this->store(['seller_id' => $sid, 'zone' => $zone, 'LogisticProvider' => $key], $vals);
            }
            $saved++;
        }

        $who = $target === 'default' ? 'the default rate card'
            : ($target === 'every' ? 'all ' . count($sellerIds) . ' sellers' : (SellerList::find($sellerIds[0])->name ?? 'the seller'));
        return redirect()->route('rate.manager', ['seller' => in_array($target, ['default', 'every'], true) ? 'default' : $target])
            ->with('rate_import_result', ['saved' => $saved, 'skipped' => $skipped, 'who' => $who]);
    }

    /** courier key => zone => [first, additional, cod, cod_percent] for one seller id. */
    private function ratesFor(int $sellerId): array
    {
        $out = [];
        foreach (ZonePriceSetting::where('seller_id', $sellerId)->where('status', 1)->get() as $r) {
            $z = RateCard::normalizeZone($r->zone);
            if (!$z || (float) $r->prepaid_price <= 0) {
                continue;
            }
            $out[$r->LogisticProvider][$z] = [
                'first' => (float) $r->prepaid_price,
                'additional' => $r->additional_price !== null ? (float) $r->additional_price : null,
                'cod' => (float) $r->cod_price,
                'cod_percent' => (float) $r->cod_charge_parsent,
            ];
        }
        return $out;
    }

    private function store(array $match, array $vals): void
    {
        $row = ZonePriceSetting::firstOrNew($match);
        $row->fill([
            'prepaid_price' => $vals['prepaid_price'],
            'additional_price' => $vals['additional_price'],
            'cod_price' => $vals['cod_price'] ?? 0,
            'cod_charge_parsent' => $vals['cod_charge_parsent'] ?? 0,
            // the new formula replaces fixed prices for anything set here
            'prepaid_fix_price' => 0,
            'cod_fix_price' => 0,
            'status' => 1,
        ]);
        if (!$row->exists) {
            $row->rto_credit = 0;
        }
        $row->save();
    }
}
