<?php

namespace App\Http\Controllers\admin;

use App\Exports\WeightDiscrepancyExport;
use App\Exports\WeightDisputeTemplateExport;
use App\Http\Controllers\Controller;
use App\Models\SellerList;
use App\Models\WeightDisputes;
use App\Support\WeightDisputeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Admin > Weight Disputes
 *  upload courier weights (Excel) -> auto debit -> review seller disputes -> accept (auto refund) / reject (closed)
 */
class WeightDispatchingController extends Controller
{
    private const TABS = [
        'all' => 'All',
        'disputed' => 'Needs review',
        'charged' => 'Open (seller can dispute)',
        'accepted' => 'Accepted · refunded',
        'rejected' => 'Rejected',
        'closed' => 'Closed',
    ];

    public function index(Request $request)
    {
        $tab = array_key_exists($request->get('tab'), self::TABS) ? $request->get('tab') : 'all';
        $query = $this->filtered($request, $tab);
        $disputes = $query->orderByRaw("CASE WHEN dispute_status = 'disputed' THEN 0 ELSE 1 END")
            ->orderByDesc('id')->paginate(25)->withQueryString();

        $sellerNames = SellerList::whereIn('id', $disputes->pluck('seller_id')->filter()->unique())->pluck('name', 'id');
        $rows = $disputes->getCollection()->map(function (WeightDisputes $d) use ($sellerNames) {
            return $d->detail(true) + [
                'seller' => $sellerNames[$d->seller_id] ?? '—',
                'seller_id' => $d->seller_id,
                'calc_method' => $d->calc_method,
                'raised_at' => optional($d->raised_at)->format('d M Y, h:i A'),
            ];
        });

        $now = now();
        $base = WeightDisputes::where('dispute_status', '!=', WeightDisputes::VOID);
        $counts = [
            'all' => (clone $base)->count(),
            'disputed' => (clone $base)->where('dispute_status', WeightDisputes::DISPUTED)->count(),
            'charged' => (clone $base)->where('dispute_status', WeightDisputes::CHARGED)->where('dispute_deadline', '>', $now)->count(),
            'accepted' => (clone $base)->where('dispute_status', WeightDisputes::ACCEPTED)->count(),
            'rejected' => (clone $base)->where('dispute_status', WeightDisputes::REJECTED)->count(),
            'closed' => (clone $base)->where(function ($q) use ($now) {
                $q->where('dispute_status', WeightDisputes::CLOSED)
                  ->orWhere(fn ($q2) => $q2->where('dispute_status', WeightDisputes::CHARGED)->where('dispute_deadline', '<=', $now));
            })->count(),
        ];
        $totals = [
            'charged' => (float) (clone $base)->whereNotNull('deducted_at')->sum('excess_charge'),
            'refunded' => (float) (clone $base)->where('dispute_status', WeightDisputes::ACCEPTED)->sum('excess_charge'),
        ];

        return view('WeightDispatching.index', [
            'rows' => $rows,
            'disputes' => $disputes,
            'tabs' => self::TABS,
            'tab' => $tab,
            'counts' => $counts,
            'totals' => $totals,
            'windowHours' => WeightDisputes::windowHours(),
        ]);
    }

    private function filtered(Request $request, string $tab)
    {
        $now = now();
        $q = WeightDisputes::query()->where('dispute_status', '!=', WeightDisputes::VOID);
        switch ($tab) {
            case 'disputed':
            case 'accepted':
            case 'rejected':
                $q->where('dispute_status', $tab);
                break;
            case 'charged':
                $q->where('dispute_status', WeightDisputes::CHARGED)->where('dispute_deadline', '>', $now);
                break;
            case 'closed':
                $q->where(function ($w) use ($now) {
                    $w->where('dispute_status', WeightDisputes::CLOSED)
                      ->orWhere(fn ($w2) => $w2->where('dispute_status', WeightDisputes::CHARGED)->where('dispute_deadline', '<=', $now));
                });
                break;
        }
        if ($s = trim((string) $request->get('q'))) {
            $sellerIds = SellerList::where('name', 'like', "%$s%")->orWhere('email', 'like', "%$s%")->pluck('id');
            $q->where(function ($w) use ($s, $sellerIds) {
                $w->where('awb', 'like', "%$s%")->orWhere('order_number', 'like', "%$s%")->orWhereIn('seller_id', $sellerIds);
            });
        }
        if ($request->filled('from')) {
            $q->whereDate('created_at', '>=', $request->get('from'));
        }
        if ($request->filled('to')) {
            $q->whereDate('created_at', '<=', $request->get('to'));
        }
        return $q;
    }

    /** Old "Add" page now lives on the list page. */
    public function create()
    {
        return redirect()->route('weight.dispatching.index');
    }

    public function template()
    {
        return Excel::download(new WeightDisputeTemplateExport(), 'weight_dispute_upload_template.xlsx');
    }

    public function importWeightDisputes(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ], [
            'excel_file.required' => 'Choose the filled template to upload.',
            'excel_file.mimes' => 'Upload the Excel template (.xlsx, .xls or .csv).',
        ]);

        try {
            $sheets = Excel::toArray(new \stdClass(), $request->file('excel_file'));
            $rows = $sheets[0] ?? [];
            if (count($rows) < 2) {
                return back()->with('error', 'The file has no AWB rows.');
            }
            if (count($rows) > 5001) {
                return back()->with('error', 'Upload at most 5000 AWBs per file.');
            }
            $result = WeightDisputeService::importRows($rows, Auth::id());
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        $msg = $result['charged']
            ? $result['charged'] . ' AWB(s) charged — ₹' . number_format($result['amount'], 2) . ' debited from seller wallets.'
            : 'No AWB was charged. See the row results below.';
        return back()->with($result['charged'] ? 'success' : 'error', $msg)->with('wd_result', $result);
    }

    public function review(Request $request, $id)
    {
        $request->validate([
            'decision' => 'required|in:accept,reject',
            'admin_remark' => 'nullable|string|max:1000',
        ]);
        if ($request->decision === 'reject' && !trim((string) $request->admin_remark)) {
            return back()->with('error', 'Write a reason for the seller when rejecting.');
        }
        try {
            $d = WeightDisputeService::review((int) $id, $request->decision === 'accept', $request->admin_remark, Auth::id());
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
        return back()->with('success', $request->decision === 'accept'
            ? 'Accepted — ₹' . number_format((float) $d->excess_charge, 2) . ' refunded to the seller wallet (AWB ' . $d->awb . ').'
            : 'Rejected — dispute closed (AWB ' . $d->awb . ').');
    }

    public function proof($id, $n)
    {
        $d = WeightDisputes::findOrFail($id);
        return self::streamProof($d, (int) $n);
    }

    public static function streamProof(WeightDisputes $d, int $n)
    {
        $p = ((array) $d->seller_proofs)[$n] ?? null;
        if (!$p || empty($p['path']) || !Storage::disk('local')->exists($p['path'])) {
            abort(404);
        }
        return response()->file(Storage::disk('local')->path($p['path']), [
            'Content-Type' => $p['mime'] ?? 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="' . addslashes($p['name'] ?? basename($p['path'])) . '"',
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function downloadExcel(Request $request)
    {
        $tab = array_key_exists($request->get('tab'), self::TABS) ? $request->get('tab') : 'all';
        $list = $this->filtered($request, $tab)->orderByDesc('id')->limit(20000)->get();
        $names = SellerList::whereIn('id', $list->pluck('seller_id')->filter()->unique())->pluck('name', 'id');
        $data = $list->map(function (WeightDisputes $d) use ($names) {
            $x = $d->detail(true);
            return [
                $d->awb, $d->courier, $x['applied_kg'], $x['charged_kg'], $x['diff_kg'], $x['excess_charge'],
                $names[$d->seller_id] ?? '', optional($d->deducted_at ?? $d->created_at)->format('Y-m-d'),
                $x['status_label'], ucfirst((string) $d->weight_type),
            ];
        });
        return Excel::download(new WeightDiscrepancyExport($data, [
            'AWB Number', 'Courier', 'Billed Weight (kg)', 'Courier Charged (kg)', 'Difference (kg)', 'Excess Charge (₹)',
            'Seller', 'Charged On', 'Status', 'Weight Type',
        ]), 'weight_disputes_' . date('Y_m_d_His') . '.xlsx');
    }

    /** Old bulk "Submit All" button: charging now happens on upload. */
    public function submit()
    {
        return redirect()->route('weight.dispatching.index')->with('success', 'Charges are now debited automatically when you upload the Excel.');
    }
}
