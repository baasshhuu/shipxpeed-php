<?php

namespace App\Http\Controllers\selleradmin;

use App\Exports\WeightDiscrepancyExport;
use App\Http\Controllers\admin\WeightDispatchingController as AdminWeight;
use App\Http\Controllers\Controller;
use App\Models\Recharge;
use App\Models\WeightDisputes;
use App\Support\WeightDisputeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Seller > Weight Discrepancy
 *  every charge with full details, raise a dispute with photo/video proof inside the dispute window,
 *  follow the review until refund or closure.
 */
class WeightDispatchingController extends Controller
{
    public const MAX_FILES = 5;
    public const MAX_KB = 10240; // 10 MB per file

    private function sellerId(): int
    {
        return (int) Auth::guard('seller')->id();
    }

    private function query(Request $request)
    {
        $now = now();
        $q = WeightDisputes::where('seller_id', $this->sellerId())
            ->where('dispute_status', '!=', WeightDisputes::VOID);

        switch ($request->get('status')) {
            case 'open':
                $q->where('dispute_status', WeightDisputes::CHARGED)->where('dispute_deadline', '>', $now);
                break;
            case 'review':
                $q->where('dispute_status', WeightDisputes::DISPUTED);
                break;
            case 'accepted':
                $q->where('dispute_status', WeightDisputes::ACCEPTED);
                break;
            case 'rejected':
                $q->where('dispute_status', WeightDisputes::REJECTED);
                break;
            case 'closed':
                $q->where(function ($w) use ($now) {
                    $w->where('dispute_status', WeightDisputes::CLOSED)
                      ->orWhere(fn ($w2) => $w2->where('dispute_status', WeightDisputes::CHARGED)->where('dispute_deadline', '<=', $now));
                });
                break;
        }
        if ($request->filled('start_date')) {
            $q->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $q->whereDate('created_at', '<=', $request->end_date);
        }
        if ($request->filled('awb')) {
            $s = trim($request->awb);
            $q->where(fn ($w) => $w->where('awb', 'like', "%$s%")->orWhere('order_number', 'like', "%$s%"));
        }
        return $q;
    }

    public function index(Request $request)
    {
        $seller = Auth::guard('seller')->user();
        $sid = $this->sellerId();

        $wallet = Recharge::where('seller_id', $sid)->selectRaw('
            SUM(CASE WHEN status = 1 AND type = "Credit" THEN amount ELSE 0 END) as credit_amount,
            SUM(CASE WHEN type = "Debit" THEN amount ELSE 0 END) as debit_amount')->first();
        $totalAmount = ($wallet->credit_amount ?? 0) - ($wallet->debit_amount ?? 0);

        $disputes = $this->query($request)
            ->orderByRaw("CASE WHEN dispute_status = 'charged' AND dispute_deadline > ? THEN 0 ELSE 1 END", [now()])
            ->orderByDesc('id')->paginate(20)->withQueryString();
        $rows = $disputes->getCollection()->map(fn (WeightDisputes $d) => $d->detail(false))->all();

        $now = now();
        $base = WeightDisputes::where('seller_id', $sid)->where('dispute_status', '!=', WeightDisputes::VOID);
        $stats = [
            'total' => (clone $base)->count(),
            'open' => (clone $base)->where('dispute_status', WeightDisputes::CHARGED)->where('dispute_deadline', '>', $now)->count(),
            'review' => (clone $base)->where('dispute_status', WeightDisputes::DISPUTED)->count(),
            'accepted' => (clone $base)->where('dispute_status', WeightDisputes::ACCEPTED)->count(),
            'rejected' => (clone $base)->where('dispute_status', WeightDisputes::REJECTED)->count(),
            'charged_amount' => (float) (clone $base)->whereNotNull('deducted_at')->sum('excess_charge'),
            'refunded_amount' => (float) (clone $base)->where('dispute_status', WeightDisputes::ACCEPTED)->sum('excess_charge'),
        ];

        return view('sellerdashboard.WeightDispatching.index', [
            'rows' => $rows,
            'disputes' => $disputes,
            'stats' => $stats,
            'seller' => $seller,
            'totalAmount' => $totalAmount,
            'windowHours' => WeightDisputes::windowHours(),
            'maxFiles' => self::MAX_FILES,
            'maxMb' => (int) (self::MAX_KB / 1024),
            // kept for any old partial that still reads it
            'totalDiscrepancies' => $stats['total'],
        ]);
    }

    public function raise(Request $request, $id)
    {
        $d = WeightDisputes::where('seller_id', $this->sellerId())->findOrFail($id);
        if (!$d->canRaise()) {
            return back()->with('error', 'You can raise a dispute only within ' . WeightDisputes::windowHours() . ' hours of the charge. This one is ' . strtolower(WeightDisputes::statusLabel($d->liveStatus())) . '.');
        }

        $request->validate([
            'remark' => 'required|string|min:10|max:1000',
            'proofs' => 'required|array|min:1|max:' . self::MAX_FILES,
            'proofs.*' => 'file|max:' . self::MAX_KB . '|mimes:jpg,jpeg,png,webp,heic,mp4,mov,webm,3gp,mkv',
        ], [
            'remark.required' => 'Tell us why the courier weight is wrong.',
            'remark.min' => 'Please describe the issue in a little more detail (at least 10 characters).',
            'proofs.required' => 'Add at least one photo or video as proof.',
            'proofs.max' => 'You can upload at most ' . self::MAX_FILES . ' files.',
            'proofs.*.max' => 'Each file must be 10 MB or smaller.',
            'proofs.*.mimes' => 'Only photos (JPG, PNG, WEBP, HEIC) and videos (MP4, MOV, WEBM, 3GP, MKV) are allowed.',
            'proofs.*.file' => 'A file did not upload completely. Please try again.',
        ]);

        try {
            WeightDisputeService::raise($d, $request->remark, $request->file('proofs', []));
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
        return back()->with('success', 'Dispute raised for AWB ' . $d->awb . '. Our team will review your proof; if accepted, ₹' . number_format((float) $d->excess_charge, 2) . ' is refunded to your wallet automatically.');
    }

    public function proof($id, $n)
    {
        $d = WeightDisputes::where('seller_id', $this->sellerId())->findOrFail($id);
        return AdminWeight::streamProof($d, (int) $n);
    }

    public function downloadExcel(Request $request)
    {
        $list = $this->query($request)->orderByDesc('id')->limit(20000)->get();
        if ($list->isEmpty()) {
            return back()->with('error', 'No data found to export.');
        }
        $data = $list->map(function (WeightDisputes $d) {
            $x = $d->detail(false);
            return [
                $d->awb, $d->courier, $x['applied_kg'], $x['charged_kg'], $x['diff_kg'], $x['excess_charge'],
                ucfirst((string) $d->weight_type), optional($d->deducted_at ?? $d->created_at)->format('Y-m-d'),
                $x['status_label'], $x['deadline'],
            ];
        });
        return Excel::download(new WeightDiscrepancyExport($data, [
            'AWB Number', 'Courier', 'Your Weight (kg)', 'Courier Charged (kg)', 'Difference (kg)', 'Charged (₹)',
            'Weight Type', 'Date', 'Status', 'Dispute By',
        ]), 'weight_discrepancy_' . date('Y_m_d_His') . '.xlsx');
    }

    /** Old placeholder route. */
    public function submit()
    {
        return redirect()->route('seller.weight.discrepancy');
    }
}
