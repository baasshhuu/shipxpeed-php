<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/** Reads only the first sheet ("Rates") of the Rate Manager template, rows keyed by heading. */
class RateManagerSheetImport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [0 => new class implements WithHeadingRow {}];
    }
}
