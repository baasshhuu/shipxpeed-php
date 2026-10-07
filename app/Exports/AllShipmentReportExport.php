<?php

namespace App\Exports;

use Illuminate\Http\Request;
use App\Models\Order;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AllShipmentReportExport implements FromCollection, WithHeadings
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function collection()
    {
        $query = Order::with('seller')->whereNotNull('awb_number');

        if ($this->request->filled('seller_id')) {
            $query->where('seller_id', $this->request->seller_id);
        }

        if ($this->request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $this->request->start_date);
        }

        if ($this->request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $this->request->end_date);
        }

        return $query->get()->map(function ($order) {
            // $amount = $order->seller_amount_walate ?? 0;
                    $amountIncludingGST = $order->seller_amount_walate ?? 0;
                                 $amount = round($amountIncludingGST / 1.18, 2); // ✅ GST removed amount

            $gst = round($amount * 0.18, 2);
            $total = $amount + $gst;

            // Consignee details decode
            $consignee = is_string($order->consignee) ? json_decode($order->consignee, true) : ($order->consignee ?? []);
            $order_items = is_string($order->order_items) ? json_decode($order->order_items, true) : ($order->order_items ?? []);

            return [
                $order->awb_number,
                $order->seller->name ?? 'N/A',
                $order->seller->email ?? 'N/A',
                $order->seller->phone_number ?? 'N/A',
                ucfirst($order->shipping_status ?? 'N/A'),
                $order->payment_type ?? 'N/A',
                $order_items['name'] ?? 'N/A',

                $consignee['name'] ?? 'N/A',
                $consignee['phone'] ?? 'N/A',
                $consignee['address'] ?? 'N/A',
                $consignee['address_2'] ?? 'N/A',
                $consignee['pincode'] ?? 'N/A',
                $consignee['city'] ?? 'N/A',
                $consignee['state'] ?? 'N/A',
                $order->package_weight ?? 'N/A',
                $order->package_length ?? 'N/A',
                $order->package_breadth ?? 'N/A',
                $order->package_height ?? 'N/A',
                $amount,
                $gst,
                $total,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'AWB Number',
            'Seller Name',
            'Seller Email',
            'Seller Phone',
            'Shipping Status',
            'Payment Type',
            'Product name',
            'Consignee Name',
            'Consignee Phone',
            'Consignee Address',
            'Address 2',
            'Pincode',
            'City',
            'State',
            'Package Weight',
            'Package Length',
            'Package Breadth',
            'Package Height',
            'Amount',
            'GST (18%)',
            'Total Amount',
        ];
    }
}
