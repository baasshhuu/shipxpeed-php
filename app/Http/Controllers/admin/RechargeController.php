<?php

namespace App\Http\Controllers\admin;

use App\Models\Recharge;
use App\Helper\Helper;
use App\Models\SellerList;
use Illuminate\View\View;
use Illuminate\Http\Request;
use \Yajra\Datatables\Datatables;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use App\Http\Controllers\Controller;
use App\Models\Cms;
use Illuminate\Support\Facades\DB;
use App\Exports\RechargeExport;
use Maatwebsite\Excel\Facades\Excel;

class RechargeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }


public function exportRecharge(Request $request)
{
    return Excel::download(new RechargeExport($request), 'recharge_balance.xlsx');
}



// public function exportRecharge()
// {
//     return Excel::download(new RechargeExport, 'recharge-report.xlsx');
// }




public function indexRecharge(Request $request)
{
    $query = Recharge::select(
            'seller_id',
            DB::raw('SUM(CASE WHEN type = "Credit" AND status = 1 THEN amount ELSE 0 END) as total_credit'),
            DB::raw('SUM(CASE WHEN type = "Debit" THEN amount ELSE 0 END) as total_debit')
        )
        ->groupBy('seller_id')
        ->with('seller');

    // Search by seller name
    if ($request->has('search') && !empty($request->search)) {
        $query->whereHas('seller', function ($q) use ($request) {
            $q->where('name', 'like', '%' . $request->search . '%');
        });
    }

    // Filter by specific seller
    if ($request->has('seller_id') && !empty($request->seller_id)) {
        $query->where('seller_id', $request->seller_id);
    }

    $brands = $query->get();

    // Calculate wallet balance (same logic as your working calculation)
    foreach ($brands as $brand) {
        $brand->wallet_balance = $brand->total_credit - $brand->total_debit;
    }

    // Filter by balance range after calculation
    if ($request->has('min_balance') && !empty($request->min_balance)) {
        $brands = $brands->filter(function ($brand) use ($request) {
            return $brand->wallet_balance >= $request->min_balance;
        });
    }

    if ($request->has('max_balance') && !empty($request->max_balance)) {
        $brands = $brands->filter(function ($brand) use ($request) {
            return $brand->wallet_balance <= $request->max_balance;
        });
    }

    // Convert to paginated collection
    $perPage = 10;
    $currentPage = $request->get('page', 1);
    $offset = ($currentPage - 1) * $perPage;
    
    $paginatedBrands = new \Illuminate\Pagination\LengthAwarePaginator(
        $brands->slice($offset, $perPage)->values(),
        $brands->count(),
        $perPage,
        $currentPage,
        [
            'path' => $request->url(),
            'pageName' => 'page',
        ]
    );

    // Preserve query parameters for pagination links
    $paginatedBrands->appends($request->query());

    // Get all sellers for the dropdown filter
    $allSellers = SellerList::select('id', 'name')
                            ->orderBy('name')
                            ->get();

    return view('recharge.balance', [
        'brands' => $paginatedBrands,
        'allSellers' => $allSellers
    ]);
}





    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {
            // $data = Recharge::select('id', 'amount', 'seller_id', 'code', 'status', 'created_at');

            $data = Recharge::with('seller')
           ->select('id', 'amount', 'seller_id', 'code', 'status', 'created_at');

            // return Datatables::of($data)
            //     ->editColumn('seller_id', function ($row) {
            //         return $row->seller->name ?? 'N/A'; 
            //     })

            return Datatables::of($data)
                ->editColumn('image', function ($row) {
                    $btn = '<div class="img-group"><img class="" src="' . asset('storage/' . $row['image']) . '" alt=""></div>';
                    return $btn;
                })
                ->editColumn('created_at', function ($row) {
                    return $row['created_at']->format('d M, Y');
                })

                    ->editColumn('seller_id', function ($row) {
                    return $row->seller->name ?? 'N/A';
                })
                // ->editColumn('status', function ($row) {
                //     return $row['status'] == 1 ? '<small class="badge fw-semi-bold rounded-pill status badge-light-success"> Active</small>' : '<small class="badge fw-semi-bold rounded-pill status badge-light-danger"> Inactive</small>';
                // })


                ->editColumn('status', function ($row) {
                    $statusText = $row->status == 1 ? 'Approved' : 'Pending';
                    $badgeClass = $row->status == 1 ? 'badge-light-success' : 'badge-light-warning';
                    
                    return '<small class="badge fw-semi-bold rounded-pill status '.$badgeClass.' change-status" 
                                style="cursor:pointer" 
                                data-id="'.$row->id.'" 
                                data-status="'.$row->status.'">'
                                . $statusText .
                        '</small>';
                })

                ->addColumn('action', function ($row) {

                    $btn = '<button class="text-600 btn-reveal dropdown-toggle btn btn-link btn-sm" id="drop" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span class="fas fa-ellipsis-h fs--1"></span></button><div class="dropdown-menu" aria-labelledby="drop">';
                    if (Helper::userCan(107, 'can_edit')) {
                        $btn .= '<a class="dropdown-item" href="' . route('cms.edit', $row['id']) . '">Edit</a>';
                    }
                    if (Helper::userAllowed(107)) {
                        return $btn;
                    } else {
                        return '';
                    }
                })
                ->orderColumn('created_at', function ($query, $order) {
                    $query->orderBy('created_at', $order);
                })
                ->rawColumns(['action', 'image', 'status'])
                ->make(true);
        }
        return view('recharge.index');
    }

    public function add(): View
    {
        $sellers = SellerList::all();
        return view('recharge.add', compact('sellers'));
    }



    public function save(Request $request)
{
    $validated = $request->validate([
        'amount'    => ['required', 'numeric'],
        'seller_id' => ['required'], 
        'type'      => ['required'], 
    ]);

    $validated['status'] = 1;

    // ✅ Description set karna
    if ($request->type === 'Debit') {
        $validated['description'] = 'Amount debited from Admin';
    } elseif ($request->type === 'Credit') {
        $validated['description'] = 'Amount credited by Admin';
    }

    Recharge::create($validated);

    return to_route('recharges')->withSuccess('Recharge Successfully..!!');
}


//     public function save(Request $request)
// {
//     $validated = $request->validate([
//         'amount'     => ['required', 'numeric'],
//         'seller_id'  => ['required'], 
//         'type'       => ['required'], 
//     ]);
//     $validated['status'] = 1;

//     Recharge::create($validated);

//     return to_route('recharges')->withSuccess('Recharge Successfully..!!');
// }

   

    public function edit($id): View|RedirectResponse
    {
        $cms = Cms::find($id);
        if (!$cms) {
            return to_route('cms')->withError('Cms Not Found..!!');
        }
        return view('cms.edit', compact('cms'));
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $cms = Cms::find($id);
        if (!$cms) {
            return to_route('cms')->withError('Cms Not Found..!!');
        }

        $data = $request->validate([
            'title'         => ['required', 'string', 'max:200'],
            'description'   => ['required', 'string', 'max:10000'],
            'status'        => ['required', 'integer'],
            'image'         => ['image', 'mimes:jpg,png,jpeg', 'max:5048']
        ]);

        if ($request->file('image')) {
            Helper::deleteFile($cms->image);
            $data['image'] = Helper::saveFile($request->file('image'), 'cms');
        }

        $cms->update($data);
        return to_route('cms')->withSuccess('Cms Updated Successfully..!!');
    }

    public function delete(Request $request): JsonResponse
    {
        return Helper::deleteRecord(new Cms, $request->id);
    }


public function changeStatus(Request $request)
{

    //     if (!auth()->check()) {
    //     return response()->json(['success' => false, 'message' => 'User not authenticated']);
    // }
    // dd($request);
    $recharge = Recharge::find($request->id);

    if (!$recharge) {
        return response()->json(['success' => false, 'message' => 'Recharge not found.']);
    }

    // Toggle status: if 1 then 0, if 0 then 1
    $recharge->status = $recharge->status == 1 ? 0 : 1;
    $recharge->save();

    return response()->json([
        'success' => true,
        'message' => 'Status updated successfully.'
    ]);
}



}
