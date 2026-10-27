<?php

namespace App\Exports;

use App\Support\ShipmentReport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromGenerator;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Admin › Shipment Report › Export Excel. Same filters and the same numbers
 * as the page (both use App\Support\ShipmentReport), with every field.
 */
class AllShipmentReportExport implements FromGenerator, WithHeadings, ShouldAutoSize, WithStyles, WithEvents
{
    /** column heading => row key */
    public const COLUMNS = [
        'Order Date'                 => 'order_date',
        'Shipped Date'               => 'shipped_date',
        'Delivered Date'             => 'delivered_date',
        'Order No.'                  => 'order_number',
        'Channel Order No.'          => 'channel_order',
        'AWB Number'                 => 'awb',
        'Seller ID'                  => 'seller_id',
        'Seller Name'                => 'seller_name',
        'Seller Email'               => 'seller_email',
        'Seller Phone'               => 'seller_phone',
        'Courier'                    => 'courier',
        'Courier Account'            => 'courier_account',
        'Zone'                       => 'zone',
        'Payment Type'               => 'payment',
        'Order Value (₹)'            => 'order_value',
        'COD to Collect (₹)'         => 'cod_amount',
        'COD Remittance'             => 'cod_remittance',
        'Status'                     => 'status',
        'Courier Status (raw)'       => 'courier_status',
        'RTO Reason'                 => 'rto_reason',
        'Buyer Name'                 => 'buyer_name',
        'Buyer Phone'                => 'buyer_phone',
        'Buyer Alt. Phone'           => 'buyer_alt_phone',
        'Buyer Email'                => 'buyer_email',
        'Buyer Address'              => 'buyer_address',
        'Buyer Landmark'             => 'buyer_landmark',
        'Buyer City'                 => 'buyer_city',
        'Buyer State'                => 'buyer_state',
        'Buyer Pincode'              => 'buyer_pincode',
        'Pickup Location'            => 'pickup_name',
        'Pickup Contact'             => 'pickup_contact',
        'Pickup Phone'               => 'pickup_phone',
        'Pickup Address'             => 'pickup_address',
        'Pickup City'                => 'pickup_city',
        'Pickup State'               => 'pickup_state',
        'Pickup Pincode'             => 'pickup_pincode',
        'Products'                   => 'products',
        'SKUs'                       => 'skus',
        'Total Qty'                  => 'qty',
        'Dead Weight (kg)'           => 'weight_kg',
        'Length (cm)'                => 'length_cm',
        'Breadth (cm)'               => 'breadth_cm',
        'Height (cm)'                => 'height_cm',
        'Volumetric Weight (kg)'     => 'vol_weight_kg',
        'Shipping Charge excl. GST (₹)' => 'freight_taxable',
        'GST 18% (₹)'                => 'gst',
        'Shipping Charged incl. GST (₹)' => 'shipping_charged',
        'RTO Charge (₹)'             => 'rto_charge',
        'Cancellation Refund (₹)'    => 'refund',
        'Weight Dispute Charge (₹)'  => 'weight_charge',
        'WhatsApp Alerts (₹)'        => 'whatsapp_charge',
        'Net Billed (₹)'             => 'net',
    ];

    public function __construct(protected Request $request)
    {
    }

    public function generator(): \Generator
    {
        $f = ShipmentReport::filters($this->request);
        $keys = array_values(self::COLUMNS);
        // newest first, streamed in chunks so big date ranges don't run out of memory
        foreach (ShipmentReport::query($f)->lazy(500)->chunk(500) as $chunk) {
            $chunk = $chunk->collect();
            $extras = ShipmentReport::extras($chunk);
            foreach ($chunk as $order) {
                $row = ShipmentReport::row($order, $extras);
                yield array_map(fn ($k) => $row[$k] ?? null, $keys);
            }
        }
    }

    public function headings(): array
    {
        return array_keys(self::COLUMNS);
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }

    public function registerEvents(): array
    {
        return [AfterSheet::class => function (AfterSheet $e) {
            $s = $e->sheet->getDelegate();
            $s->freezePane('A2');
            $s->setAutoFilter($s->calculateWorksheetDimension());
            // long numbers (AWB, phone, pincode) shown in full instead of 3.85E+13
            foreach (['AWB Number', 'Buyer Phone', 'Buyer Alt. Phone', 'Seller Phone', 'Pickup Phone', 'Buyer Pincode', 'Pickup Pincode', 'Order No.', 'Channel Order No.'] as $h) {
                $i = array_search($h, array_keys(self::COLUMNS), true);
                if ($i === false) {
                    continue;
                }
                $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
                $s->getStyle($col . '2:' . $col . $s->getHighestRow())->getNumberFormat()->setFormatCode('0');
            }
        }];
    }
}
