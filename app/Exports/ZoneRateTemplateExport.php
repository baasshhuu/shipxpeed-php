<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/** Zone rate template for Admin > Zone Price Setting > Bulk upload. Column names match the importer. */
class ZoneRateTemplateExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles
{
    public function __construct(private array $rows)
    {
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'zone',
            'logistic_provider',
            'courier_name',
            'prepaid_price',
            'cod_price',
            'prepaid_fix_price',
            'cod_fix_price',
            'cod_charge_parsent',
            'rto_credit',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->freezePane('D2');
        return [1 => ['font' => ['bold' => true]]];
    }
}
