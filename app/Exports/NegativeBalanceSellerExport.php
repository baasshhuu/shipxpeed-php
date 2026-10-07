<?php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class NegativeBalanceSellerExport implements FromCollection, WithHeadings
{
    protected $data;

    public function __construct($data)
    {
        $this->data = collect($data);
    }

    public function collection()
    {
        return $this->data->map(function ($order) {
            // Seller bank details fetch
            $bankDetails = \DB::table('seller_bank_details')
                ->where('seller_id', $order->seller_id)
                ->first();

            // Determine payment method - Wallet or Bank
            // You can customize this logic based on your business requirements
            $paymentMethod = 'Bank'; // Default to Bank
            
            // Check if order has wallet payment indicator
            if (isset($order->payment_method) && $order->payment_method == 'wallet') {
                $paymentMethod = 'Wallet';
            } elseif (isset($order->wallet_used) && $order->wallet_used > 0) {
                $paymentMethod = 'Wallet';
            } elseif (!empty($bankDetails)) {
                $paymentMethod = 'Bank';
            }

            return [
                'awb_number'       => $order->awb_number,
                'order_number'     => $order->order_number,
                'status'           => ucfirst($order->shipping_status),
                'collectable_amount' => $order->collectable_amount,
                'courier'          => $order->courier_id,
                'delivered_date'   => $order->delivered_date ?? 'N/A',
                'remittance_date'  => $order->delivered_date
                    ? \Carbon\Carbon::parse($order->delivered_date)->addDays(7)->format('Y-m-d')
                    : 'N/A',
                'payment_status'   => $order->payment_status ?? 'N/A',
                'payment_method'   => $paymentMethod, // New column - Wallet or Bank

                // Seller bank details
                'account_number'   => $bankDetails->account_number ?? 'N/A',
                'ifsc_code'        => $bankDetails->ifsc_code ?? 'N/A',
                'account_holder_name' => $bankDetails->account_holder_name ?? 'N/A',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'AWB Number',
            'Order Number',
            'Status',
            'Collectable Amount',
            'Courier',
            'Delivered Date',
            'Remittance Date',
            'Payment Status',
            'Payment Method', // New column - Wallet/Bank
            'Account Number',
            'IFSC Code',
            'Account Holder Name',
        ];
    }
}
