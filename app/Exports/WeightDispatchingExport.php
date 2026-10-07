<?php
namespace App\Exports;

use Illuminate\Support\Collection;

class WeightDispatchingExport implements \Maatwebsite\Excel\Concerns\FromCollection, 
                                         \Maatwebsite\Excel\Concerns\WithHeadings
{
    public function collection()
    {
        // Replace with actual query or pass via DI
        return collect(session('export_data') ?? []);
    }

    public function headings(): array
    {
        return [
            'awb',
            'Mentionedweight',
            'chargedweight',
            'weightmissmatched',
            'weightdisputecharges',
          
        ];
    }
}
