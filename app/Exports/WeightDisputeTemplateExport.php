<?php

namespace App\Exports;

use App\Models\WeightDisputes;
use App\Support\WeightDisputeService;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/** Admin upload template: sheet 1 = rows to fill (uploaded back), sheet 2 = how to fill. */
class WeightDisputeTemplateExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [new WeightDisputeTemplateSheet(), new WeightDisputeHelpSheet()];
    }
}

class WeightDisputeTemplateSheet implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    public function array(): array
    {
        return [];
    }

    public function headings(): array
    {
        return WeightDisputeService::templateHeadings();
    }

    public function title(): string
    {
        return 'Upload';
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:H1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F3A68']],
        ]);
        // AWB as text so long numbers are not turned into 1.23E+13
        $sheet->getStyle('A2:A5000')->getNumberFormat()->setFormatCode('@');
        $sheet->freezePane('A2');
        return [];
    }

    public function columnWidths(): array
    {
        return ['A' => 22, 'B' => 20, 'C' => 30, 'D' => 13, 'E' => 13, 'F' => 13, 'G' => 22, 'H' => 34];
    }
}

class WeightDisputeHelpSheet implements FromArray, WithTitle, WithColumnWidths, WithStyles
{
    public function array(): array
    {
        $h = WeightDisputes::windowHours();
        return [
            ['Column', 'What to enter'],
            ['AWB', 'AWB number exactly as booked in the panel. Seller, order and courier are picked up automatically.'],
            ['Charged Weight (kg)', 'Weight the courier billed, in kg (e.g. 1.5). You can also write "1500 g". Leave empty if you fill L × B × H for a volumetric charge.'],
            ['Weight Type', 'Dead  or  Volumetric  — what the courier charged on.'],
            ['Length / Breadth / Height (cm)', 'Optional. Courier-measured box size. Shown to the seller. For Volumetric with no charged weight, weight = L × B × H ÷ 5000.'],
            ['Manual Charge (optional)', 'Leave empty: the panel calculates the excess from the seller\'s rate card (extra 500 g slabs + 18% GST). Fill a ₹ amount only to override.'],
            ['Note (optional)', 'Shown to the seller with the charge.'],
            ['', ''],
            ['What happens on upload', 'For every AWB with weight above the billed slab, the excess is debited from the seller wallet immediately.'],
            ['', 'The same AWB is never charged twice. Rows with no excess are skipped and listed in the upload result.'],
            ['', 'The seller can raise a dispute with photos / videos (max 10 MB each) within ' . $h . ' hours. After that the charge is final.'],
            ['', 'Accepting a dispute refunds the charge to the wallet automatically. Rejecting closes it.'],
        ];
    }

    public function title(): string
    {
        return 'How to fill';
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:B1')->getFont()->setBold(true);
        $sheet->getStyle('A1:A20')->getFont()->setBold(true);
        $sheet->getStyle('B1:B20')->getAlignment()->setWrapText(true);
        return [];
    }

    public function columnWidths(): array
    {
        return ['A' => 30, 'B' => 110];
    }
}
