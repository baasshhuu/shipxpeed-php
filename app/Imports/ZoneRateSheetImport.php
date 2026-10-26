<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithHeadingRow;

/** Reads the zone rate sheet as rows keyed by heading (zone, logistic_provider, prepaid_price, ...). */
class ZoneRateSheetImport implements WithHeadingRow
{
}
