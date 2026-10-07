<?php


namespace App\Exports;

use App\Models\Recharge;
use App\Models\SellerList;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Http\Request;

class RechargeExport implements FromCollection, WithHeadings
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function collection()
    {
        $query = Recharge::select(
                'seller_id',
                DB::raw('SUM(CASE WHEN type = "Credit" AND status = 1 THEN amount ELSE 0 END) as total_credit'),
                DB::raw('SUM(CASE WHEN type = "Debit" THEN amount ELSE 0 END) as total_debit')
            )
            ->groupBy('seller_id')
            ->with('seller');

        // Search by seller name
        if ($this->request->has('search') && !empty($this->request->search)) {
            $query->whereHas('seller', function ($q) {
                $q->where('name', 'like', '%' . $this->request->search . '%');
            });
        }

        // Filter by seller ID
        if ($this->request->has('seller_id') && !empty($this->request->seller_id)) {
            $query->where('seller_id', $this->request->seller_id);
        }

        $brands = $query->get();

        // Calculate wallet balance
        foreach ($brands as $brand) {
            $brand->wallet_balance = $brand->total_credit - $brand->total_debit;
        }

        // Filter by min balance
        if ($this->request->has('min_balance') && !empty($this->request->min_balance)) {
            $brands = $brands->filter(function ($brand) {
                return $brand->wallet_balance >= $this->request->min_balance;
            });
        }

        // Filter by max balance
        if ($this->request->has('max_balance') && !empty($this->request->max_balance)) {
            $brands = $brands->filter(function ($brand) {
                return $brand->wallet_balance <= $this->request->max_balance;
            });
        }

        // Return mapped collection for export
        return $brands->map(function ($brand) {
            return [
                'Seller ID'      => $brand->seller_id,
                'Client Name'    => $brand->seller->name ?? 'N/A',
                'Number'         => $brand->seller->phone_number ?? 'N/A',
                'Email'          => $brand->seller->email ?? 'N/A',
                'Wallet Balance' => $brand->wallet_balance,
            ];
        });
    }

    public function headings(): array
    {
        return ['Seller ID', 'Client Name', 'Number', 'Email', 'Wallet Balance'];
    }
}




// namespace App\Exports;

// use App\Models\Recharge;
// use Illuminate\Support\Facades\DB;
// use Maatwebsite\Excel\Concerns\FromCollection;
// use Maatwebsite\Excel\Concerns\WithHeadings;

// class RechargeExport implements FromCollection, WithHeadings
// {
//     public function collection()
//     {
//         $brands = Recharge::select(
//                 'seller_id',
//                 DB::raw('SUM(CASE WHEN type = "credit" THEN amount ELSE 0 END) as total_credit'),
//                 DB::raw('SUM(CASE WHEN type = "debit" THEN amount ELSE 0 END) as total_debit')
//             )
//             ->groupBy('seller_id')
//             ->with('seller')
//             ->get();

//         return $brands->map(function ($brand) {
//             return [
//                 'Seller ID'     => $brand->seller_id,
//                 'Client Name'   => $brand->seller->name ?? 'N/A',
//                 'Number'        => $brand->seller->phone_number ?? 'N/A',
//                 'Email'         => $brand->seller->email ?? 'N/A',
//                 'Wallet Balance'=> ($brand->total_credit - $brand->total_debit),
//             ];
//         });
//     }

//     public function headings(): array
//     {
//         return ['Seller ID', 'Client Name', 'Number', 'Email', 'Wallet Balance'];
//     }
// }
