<?php

namespace App\Imports;

use App\Models\WeightDisputes;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class WeightDisputeImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        // dd($row);/
        return new WeightDisputes([
            'awb'            => $row['awb'] ?? null,
            'courier'        => $row['courier'] ?? null,
            'Mentionedweight'  => $row['mentionedweight'] ?? null,
            'chargedweight'  => $row['chargedweight'] ?? null,
            'weightmissmatched' => $row['weightmissmatched'] ?? null,
            'weightdisputecharges' => $row['weightdisputecharges'] ?? null,

        ]);
    }


    
}
