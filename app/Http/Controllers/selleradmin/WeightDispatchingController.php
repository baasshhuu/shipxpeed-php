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
 *  every charge with full details; inside the dispute window (24 h) the seller either
 *  Accepts the charge (closed) or Rejects it with proof (up to 5 photos + 1 video) for admin review.
 */
class WeightDispatchingController extends Controller
{
    public const MAX_IMAGES = 5;
    public const MAX_VIDEOS = 1;
    public const MAX_KB = 10240; // 10 MB per file
    public const IMAGE_TYPES = 'jpg,jpeg,png,webp,heic,heif';
    public const VIDEO_TYPES = 'mp4,mov,webm,3gp,mkv';

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
            case 'seller_accepted':
                $q->where('dispute_status', WeightDisputes::SELLER_ACCEPTED);
                break;
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
            'seller_accepted' => (clone $base)->where('dispute_status', WeightDisputes::SELLER_ACCEPTED)->count(),
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
            'maxImages' => self::MAX_IMAGES,
            'maxVideos' => self::MAX_VIDEOS,
            'imageTypes' => self::IMAGE_TYPES,
            'videoTypes' => self::VIDEO_TYPES,
            'maxMb' => (int) (self::MAX_KB / 1024),
            // kept for any old partial that still reads it
            'totalDiscrepancies' => $stats['total'],
        ]);
    }

    /** Seller agrees with the courier weight. */
    public function accept($id)
    {
        $d = WeightDisputes::where('seller_id', $this->sellerId())->findOrFail($id);
        try {
            WeightDisputeService::acceptCharge($d);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
        return back()->with('success', 'You accepted the weight charge for AWB ' . $d->awb . '. This case is closed.');
    }

    /** Seller rejects the charge and sends proof (max 5 photos + 1 video). */
    public function raise(Request $request, $id)
    {
        $d = WeightDisputes::where('seller_id', $this->sellerId())->findOrFail($id);
        if (!$d->canRaise()) {
            return $this->reply($request, false, $d->lockedReason());
        }

        $request->validate([
            'remark' => 'required|string|min:10|max:1000',
            'images' => 'nullable|array|max:' . self::MAX_IMAGES,
            'images.*' => 'file|max:' . self::MAX_KB . '|mimes:' . self::IMAGE_TYPES,
            'video' => 'nullable|file|max:' . self::MAX_KB . '|mimes:' . self::VIDEO_TYPES,
        ], [
            'remark.required' => 'Tell us why the courier weight is wrong.',
            'remark.min' => 'Please describe the issue in a little more detail (at least 10 characters).',
            'images.max' => 'You can upload at most ' . self::MAX_IMAGES . ' photos.',
            'images.*.max' => 'Each photo must be 10 MB or smaller.',
            'images.*.mimes' => 'Photos must be JPG, PNG, WEBP or HEIC.',
            'images.*.file' => 'A photo did not upload completely. Please try again.',
            'video.max' => 'The video must be 10 MB or smaller.',
            'video.mimes' => 'The video must be MP4, MOV, WEBM, 3GP or MKV.',
            'video.file' => 'The video did not upload completely. Please try again.',
        ]);

        $files = array_values(array_filter((array) $request->file('images', [])));
        if ($request->hasFile('video')) {
            $files[] = $request->file('video');
        }
        if (!$files) {
            return $this->reply($request, false, 'Add at least one photo or the video as proof.');
        }

        try {
            WeightDisputeService::raise($d, $request->remark, $files);
        } catch (\Throwable $e) {
            return $this->reply($request, false, $e->getMessage());
        }
        return $this->reply($request, true, 'Charge rejected and dispute raised for AWB ' . $d->awb . '. Our team will review your proof; if accepted, ₹' . number_format((float) $d->excess_charge, 2) . ' is refunded to your wallet automatically.');
    }

    /** Page upload (JSON, with progress bar) or plain form post. */
    private function reply(Request $request, bool $ok, string $message)
    {
        if ($request->expectsJson()) {
            if ($ok) {
                session()->flash('success', $message); // shown when the page reloads
            }
            return response()->json(['ok' => $ok, 'message' => $message], $ok ? 200 : 422);
        }
        return back()->with($ok ? 'success' : 'error', $message);
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
