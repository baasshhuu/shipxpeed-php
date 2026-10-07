<?php



namespace App\Exports;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ShipmentReportExport implements FromCollection, WithHeadings
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

        return $query->get()->map(function ($order) use ($seller) {
            // $amount = $order->seller_amount_walate ?? 0;
                    $amountIncludingGST = $order->seller_amount_walate ?? 0;
                                 $amount = round($amountIncludingGST / 1.18, 2); // ✅ GST removed amount

            $gst = round($amount * 0.18, 2);
            $total = $amount + $gst;

            // Order Items
            $orderItems = $order->order_items;
            $productName = $orderItems['name'] ?? 'N/A';

            // Pickup Details
            $pickup = $order->pickup;
            $pickupAddress = $pickup['warehouse_name'] ?? 'N/A';
            $pickupPincode = $pickup['pincode'] ?? 'N/A';
            $pickupPhone = $pickup['phone'] ?? 'N/A';

            // Consignee Details
            $consignee = $order->consignee;
            $consigneeName = $consignee['name'] ?? 'N/A';
            $consigneeAddress = $consignee['address'] ?? 'N/A';
            $consigneeAddress2 = $consignee['address_2'] ?? 'N/A';
            $consigneeCity = $consignee['city'] ?? 'N/A';
            $consigneeState = $consignee['state'] ?? 'N/A';
            $consigneePincode = $consignee['pincode'] ?? 'N/A';
            $consigneePhone = $consignee['phone'] ?? 'N/A';

            return [
                $productName,
                $order->collectable_amount ?? 'N/A',
                $order->payment_type ?? 'N/A',
                $seller->email ?? 'N/A',               // Seller Email
                $seller->phone ?? 'N/A',               // Seller Number
                $pickupAddress,
                $pickupPincode,
                $pickupPhone,
                $consigneeName,
                $consigneeAddress,
                $consigneeAddress2,
                $consigneeCity,
                $consigneeState,
                $consigneePincode,
                $consigneePhone,
                $order->awb_number ?? 'N/A',
                $order->order_number ?? 'N/A',
                $order->customer_order_id ?? 'N/A',
                $order->all_courier_name ?? 'N/A',
                $order->package_weight ?? 'N/A',
                ucfirst($order->shipping_status ?? 'N/A'),
                ucfirst($order->order_status ?? 'N/A'),

                number_format($amount, 2),
                number_format($gst, 2),
                number_format($total, 2),
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Product Name',
            'Collectable Amount',
            'Payment Type',
            'Seller Email',
            'Seller Number',
            'Pickup Address',
            'Pickup Pincode',
            'Pickup Phone',
            'Consignee Name',
            'Consignee Address',
            'Consignee Address 2',
            'Consignee City',
            'Consignee State',
            'Consignee Pincode',
            'Consignee Phone',
            'AWB Number',
            'Order Number',
            'Customer Order ID',
            'Courier ID',
            'Package Weight',
            'Shipping Status',
            'Order Status',

            'Shipment Amount (₹)',
            'GST 18% (₹)',
            'Total Amount (₹)',
        ];
    }
}

