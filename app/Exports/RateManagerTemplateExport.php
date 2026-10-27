<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Rate Manager template: sheet "Rates" (one row per courier × zone, columns match the importer),
 * sheet "Zones" (what each zone covers) and sheet "How to fill".
 */
class RateManagerTemplateExport implements WithMultipleSheets
{
    public const HEADINGS = [
        'zone', 'courier_key', 'courier_name', 'live_by_default',
        'first_500g', 'additional_500g', 'cod_charge', 'cod_percent',
    ];

    public function __construct(private array $rows, private array $zones, private string $forWho)
    {
    }

    public function sheets(): array
    {
        $help = [
            ['Rates for', $this->forWho],
            ['', ''],
            ['Column', 'Meaning'],
            ['zone', 'Zone letter (see the Zones sheet). Do not change.'],
            ['courier_key', 'Courier code used by the panel. Do not change.'],
            ['courier_name', 'For your reference only.'],
            ['live_by_default', 'Whether the courier is live for all sellers (change it on Courier Status, not here).'],
            ['first_500g', 'Freight price (₹) for the first 500 g. Required to offer this courier in this zone.'],
            ['additional_500g', 'Freight price (₹) for each additional 500 g. Empty = same as first_500g.'],
            ['cod_charge', 'COD charge (₹) per COD order.'],
            ['cod_percent', 'COD % of order value. The higher of cod_charge and cod_percent is charged.'],
            ['', ''],
            ['How the seller price is worked out', ''],
            ['Freight', 'first_500g + (number of 500 g slabs - 1) × additional_500g'],
            ['COD orders', '+ higher of cod_charge and (order value × cod_percent / 100)'],
            ['GST', '+ 18% GST on the total'],
            ['Example', '1.2 kg prepaid, first 40, additional 35: 40 + 2×35 = 110, + 18% GST = ₹129.80'],
            ['', ''],
            ['Rows left empty', 'are skipped (nothing changes for that courier + zone).'],
            ['Seller rates', 'A seller without their own rate for a courier + zone pays the default rate card.'],
        ];

        return [
            new class($this->rows) implements FromArray, WithHeadings, WithTitle, ShouldAutoSize, WithStyles {
                public function __construct(private array $rows) {}
                public function array(): array { return $this->rows; }
                public function headings(): array { return RateManagerTemplateExport::HEADINGS; }
                public function title(): string { return 'Rates'; }
                public function styles(Worksheet $sheet)
                {
                    $sheet->freezePane('E2');
                    return [1 => ['font' => ['bold' => true]]];
                }
            },
            new class($this->zones) implements FromArray, WithHeadings, WithTitle, ShouldAutoSize, WithStyles {
                public function __construct(private array $rows) {}
                public function array(): array { return $this->rows; }
                public function headings(): array { return ['zone', 'routes (pickup state → delivery state)', 'examples']; }
                public function title(): string { return 'Zones'; }
                public function styles(Worksheet $sheet) { return [1 => ['font' => ['bold' => true]]]; }
            },
            new class($help) implements FromArray, WithTitle, ShouldAutoSize, WithStyles {
                public function __construct(private array $rows) {}
                public function array(): array { return $this->rows; }
                public function title(): string { return 'How to fill'; }
                public function styles(Worksheet $sheet) { return [1 => ['font' => ['bold' => true]], 3 => ['font' => ['bold' => true]], 13 => ['font' => ['bold' => true]]]; }
            },
        ];
    }
}
