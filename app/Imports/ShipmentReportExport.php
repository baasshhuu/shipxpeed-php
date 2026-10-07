<?php
namespace App\Exports;


use App\Models\Order;
use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Illuminate\Http\Request;
use Auth;

class ShipmentReportExport implements FromCollection
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function collection()
    {
        $seller = Auth::guard('seller')->user();

        $query = Order::where('seller_id', $seller->id)
                      ->whereNotNull('awb_number');

        if ($this->request->filled('awb')) {
            $query->where('awb_number', 'LIKE', '%' . $this->request->awb . '%');
        }

        if ($this->request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $this->request->start_date);
        }

        if ($this->request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $this->request->end_date);
        }

        return $query->get()->map(function ($order) {
            $amount = $order->seller_amount_walate ?? 0;
            $gst = round($amount * 0.18, 2);
            $total = $amount + $gst;

            return [
                'AWB Number'      => $order->awb_number,
                'Seller Name'     => $order->seller->name ?? 'N/A',
                'Shipping Status' => ucfirst($order->shipping_status ?? 'N/A'),
                'Amount'          => $amount,
                'GST (18%)'       => $gst,
                'Total'           => $total,
            ];
        });
    }
}
