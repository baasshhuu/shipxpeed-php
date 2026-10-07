<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ZonePriceTemplateExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles
{
    public function array(): array
    {
        // Get all logistic providers from controller
        $LogisticProviders = [
         // Delhivery
"Delhivery",
"Delhivery_Air",
// Delhivery
// Shadowfax
"Shadowfax",
// Shadowfax
// selloship
"Ekart2KG_selloship",
// selloship
// DTDC
"DTDC_Surface_500gm",
"DTDC_Surface_1kg",
"DTDC_Air",
// DTDC
// tekipost
'tekipost_Delhivery_5kg', 
'tekipost_Delhivery_10kg', 
'tekipost_Delhivery_1_KG', 
'tekipost_Ekart_2_KG_Fixed', 
'tekipost_Amazon_2_kg', 
'tekipost_Amazon_500_GM',
// tekipost
// parcelx
"Parcel_X_Delhivery",
"Parcel_X_Amazon",
"Parcel_X_Amazon_1KG",
"Parcel_X_Amazon_2KG",
// parcelx
// Shiprocket
"Shiprocket_Xpressbee",
"Shiprocket_Delhivery",
"Shiprocket_Bluedart",
"shiprocket_Bluedart_1kg",
// Shiprocket
"Ekart500gm",
"Ekart500gm_boxd",
"boxd_bluedart_500gm",
"parcel_x_Xpressbee",
"parcel_x_Shreemaruti",



        ];

        $zones = ['A', 'B', 'C', 'D', 'E'];
        $data = [];

        // Sample pricing data for different zones
        $zonePricing = [
            'A' => ['cod_price' => 60.00, 'cod_fix_price' => '0.00', 'prepaid_price' => 60.00, 'prepaid_fix_price' => '0.00', 'cod_charge_parsent' => 2.00, 'rto_credit' => '0.00'],
            'B' => ['cod_price' => 70.00, 'cod_fix_price' => '0.00', 'prepaid_price' => 75.00, 'prepaid_fix_price' => '0.00', 'cod_charge_parsent' => 2.00, 'rto_credit' => '0.00'],
            'C' => ['cod_price' => 80.00, 'cod_fix_price' => '0.00', 'prepaid_price' => 75.00, 'prepaid_fix_price' => '0.00', 'cod_charge_parsent' => 2.00, 'rto_credit' => '0.00'],
            'D' => ['cod_price' => 70.00, 'cod_fix_price' => '0.00', 'prepaid_price' => 75.00, 'prepaid_fix_price' => '0.00', 'cod_charge_parsent' => 2.00, 'rto_credit' => '0.00'],
            'E' => ['cod_price' => 80.00, 'cod_fix_price' => '0.00', 'prepaid_price' => 75.00, 'prepaid_fix_price' => '0.00', 'cod_charge_parsent' => 2.00, 'rto_credit' => '0.00']
        ];

        // Create rows for each zone and each logistic provider
        foreach ($zones as $zone) {
            foreach ($LogisticProviders as $provider) {
                $pricing = $zonePricing[$zone];
                $data[] = [
                    $zone,
                    $provider,
                    $pricing['cod_price'],
                    $pricing['cod_fix_price'],
                    $pricing['prepaid_price'],
                    $pricing['prepaid_fix_price'],
                    $pricing['cod_charge_parsent'],
                    $pricing['rto_credit']
                ];
            }
        }

        return $data;
    }

    public function headings(): array
    {
        return [
            'zone',
            'logistic_provider',
            'cod_price',
            'cod_fix_price',
            'prepaid_price',
            'prepaid_fix_price',
            'cod_charge_parsent',
            'rto_credit'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text
            1 => ['font' => ['bold' => true]],
            
            // Style the header row with background color
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFE2E2E2'],
                ],
            ],
        ];
    }
}