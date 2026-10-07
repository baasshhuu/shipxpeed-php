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
        // Sample data for template
        return [
            [
                'Zone A',
                'Delhivery',
                50.00,
                25.00,
                45.00,
                20.00,
                2.50
            ],
            [
                'Zone B',
                'Blue Dart',
                60.00,
                30.00,
                55.00,
                25.00,
                3.00
            ],
            [
                'Metro',
                'DTDC',
                40.00,
                20.00,
                35.00,
                15.00,
                2.00
            ]
        ];
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
            'cod_charge_parsent'
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