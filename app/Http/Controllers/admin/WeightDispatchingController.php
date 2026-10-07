<?php

namespace App\Http\Controllers\admin;

use Illuminate\View\View;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Models\SellerList;
use App\Models\WeightDisputes;
 use App\Imports\WeightDisputeImport;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use App\Models\Recharge;

use App\Exports\WeightDispatchingExport;

class WeightDispatchingController extends Controller
{
    // public function __construct()
    // {
    //     $this->middleware('auth');
    // }

    // public function index(Request $request)
    // {

    //     $data = WeightDisputes::all();
    //     dd($data);

    //     return view('WeightDispatching.index',compact('data'));
      
    // }

public function index()
{
    $disputes = WeightDisputes::where('status','0')->paginate(15);
    $mappedData = [];

    foreach ($disputes as $dispute) {
        $order = Order::where('awb_number', $dispute->awb)->first();

        if ($order) {
            $seller = SellerList::find($order->seller_id);
 
            if ($seller) {

                $mappedData[] = [
             'id' => $dispute->id,
            'awb' => $dispute->awb,
            'courier' => $dispute->courier,
            'mentionedweight' => $dispute->Mentionedweight,
            'chargedweight' => $dispute->chargedweight,
            'weightmissmatched' => $dispute->weightmissmatched,
            'weightdisputecharges' => $dispute->weightdisputecharges,
            'seller_id' => $seller->id ?? null,
            'seller_name' => $seller->name ?? 'N/A',
        ];

      
            }
        }
    }

    return view('WeightDispatching.index', [
        'data' => $mappedData,
        'disputes' => $disputes
    ]);
}




public function downloadExcel()
{
    return Excel::download(new WeightDispatchingExport, 'weight_dispatching_list.xlsx');
}



        public function create()
    {
        return view('WeightDispatching.add');
      
    }





public function importWeightDisputes(Request $request)
{
    // dd($request);
    $request->validate([
        'excel_file' => 'required|mimes:xlsx,xls'
    ]);

    Excel::import(new WeightDisputeImport, $request->file('excel_file'));

    return back()->with('success', 'Weight Dispute Data Imported Successfully!');
}





public function submit(Request $request)
{
    $sellers = $request->input('sellers');
    // dd($sellers);

    foreach ($sellers as $sellerData) {
        // dd($sellerData);
        if (!empty($sellerData['amount'])) {
            // dd($sellerData);
            // echo 'dwdada';die;
            // 1. Recharge Entry
            Recharge::create([
                'seller_id'                  => $sellerData['seller_id'],
                'type'                       => 'Debit',
                'amount'                     => $sellerData['amount'],
                'status'                     => 1,
                'description'                => 'Weight Dispute Adjustment',
                'weight_id'                  => $sellerData['weight_id'],
                'weight_awb'                 => $sellerData['weight_awb'],
                'weight_courier'             => $sellerData['weight_courier'],
                'weight_mentionedweight'     => $sellerData['weight_mentionedweight'],
                'weight_chargedweight'       => $sellerData['weight_chargedweight'],
                'weight_weightmissmatched'   => $sellerData['weight_weightmissmatched'],
                'weight_seller_name'         => $sellerData['weight_seller_name'],
            ]);

            // 2. Update WeightDisputes Status
            WeightDisputes::where('id', $sellerData['weight_id'])->update([
                'status' => 1,
            ]);
        }
    }

    return back()->with('success', 'All seller amounts have been debited and disputes updated.');
}




// public function submit(Request $request)
// {

//     dd($request);
//     $sellers = $request->input('sellers');

//     foreach ($sellers as $sellerData) {
//         if (!empty($sellerData['amount']) && $sellerData['amount'] > 0) {
//             Recharge::create([
//                 'seller_id' => $sellerData['seller_id'],
//                 'type'      => 'Debit',
//                 'amount'    => $sellerData['amount'],
//                 'status'    => 1,


//                 'description'    =>"cjkkc",
//                 'weight_id'    => $sellerData['weight_id'],
//                 'weight_awb'    => $sellerData['weight_awb'],
//                 'weight_courier'    => $sellerData['weight_courier'],
//                 'weight_mentionedweight'    => $sellerData['weight_mentionedweight'],
//                 'weight_chargedweight'    => $sellerData['weight_chargedweight'],
//                 'weight_weightmissmatched'    => $sellerData['weight_weightmissmatched'],
//                 'weight_seller_name'    => $sellerData['weight_seller_name'],
              
//             ]);
//         }
//     }

//     return back()->with('success', 'All seller amounts have been debited successfully.');
// }



// public function submit(Request $request)
// {
//     dd($request);
//     $sellers = $request->input('sellers');

//     foreach ($sellers as $sellerData) {
//         Recharge::create([
//             'seller_id' => $sellerData['seller_id'],
//             'type'      => 'Debit',
//             'amount'    => $sellerData['amount'],
//             'status'    => 1,
//             'description'    => "amounts have been debited",

//         ]);
//     }

//     return back()->with('success', 'All seller amounts have been debited successfully.');
// }









}
