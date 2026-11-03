<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Generic sheet for COD exports. Columns listed in $textCols (by heading) are written as text,
 * so bank account numbers and AWBs never turn into 1.23E+15.
 */
class CodPayoutExport extends DefaultValueBinder implements FromArray, WithHeadings, ShouldAutoSize, WithCustomValueBinder, WithStyles, WithTitle
{
    private array $textLetters = [];

    public function __construct(private array $headings, private array $rows, array $textCols = [], private string $title = 'COD')
    {
        foreach ($headings as $i => $h) {
            if (in_array($h, $textCols, true)) {
                $this->textLetters[] = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
            }
        }
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function title(): string
    {
        return mb_substr($this->title, 0, 31);
    }

    public function styles(Worksheet $sheet)
    {
        return [1 => ['font' => ['bold' => true]]];
    }

    public function bindValue(Cell $cell, $value): bool
    {
        if ($cell->getRow() > 1 && in_array($cell->getColumn(), $this->textLetters, true)) {
            $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);
            return true;
        }
        return parent::bindValue($cell, $value);
    }
}
