<?php

namespace App\Exports;

use App\Models\Ledger;

use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Events\BeforeSheet;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\BeforeExport;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\BeforeWriting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithProperties;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;

class LedgerExport implements
    FromQuery,
    WithHeadings,
    WithStyles,
    ShouldAutoSize,
    WithColumnFormatting,
    WithMapping,
    WithProperties,
    WithEvents
{
    use Exportable, RegistersEventListeners;

    protected $where = [];

    public function query()
    {
        return Ledger::query()
            ->select('voucher_no', 'date', 'payment_type', 'amount', 'updated_balance', 'particulars')
            ->where($this->where)
            ->orderBy('created_at', 'desc');
    }

    public function where(array $where = [])
    {
        $this->where = $where;
        return $this;
    }

    public function prepareRows($rows)
    {

        $credit = $rows->where('payment_type', 1)->sum('amount');
        $debit = $rows->where('payment_type', 2)->sum('amount');
        $data = $rows->toArray();
        $data[] = [
            'voucher_no'        => "",
            'date'              => "",
            'payment_type'      => "3",
            'amount'            => $credit - $debit,
            'updated_balance'   => "",
            'particulars'       => "",
        ];

        return $rows;
    }

    public function properties(): array
    {
        return [
            'creator'        => 'Lucky Solanki',
            'lastModifiedBy' => 'Lucky Solanki',
            'title'          => 'Invoices Export',
            'description'    => 'Latest Ledger',
            'subject'        => 'Ledger'
        ];
    }

    public function headings(): array
    {
        return [
            ["Ledger Account Balance"],
            ["Voucher No", "Date", "Payment Type", "Amount", "Balance", "Particulars"]
        ];
    }

    public function map($ledger): array
    {
        return [
            $ledger['voucher_no'],
            Carbon::parse($ledger['date'])->format('d M, Y h:m A'),
            $ledger['payment_type'] == 1 ? "Credit" : "Debit",
            ($ledger['payment_type'] == 1 ? "+" : "-") . $ledger['amount'],
            $ledger['updated_balance'],
            $ledger['particulars'],
        ];
    }

    public function columnFormats(): array
    {
        return [
            'B' => NumberFormat::FORMAT_DATE_DATETIME,
            'D' => '"₹" #,##0.00_-',
            'E' => '"₹" #,##0.00_-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1     => [
                'font' => [
                    'name'      =>  'Calibri',
                    'size'      =>  16,
                    'bold'      =>  true,
                    'color'     =>  ['argb' => '4361ee'],
                ],
                'borders' => [
                    'outline' => [
                        'borderStyle' => Border::BORDER_HAIR,
                        'color' => ['argb' => '4361ee'],
                    ],

                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => [
                        'rgb' => 'eceffe',
                    ]
                ],
            ],
            2     => [
                'font' => [
                    'name'      =>  'Calibri',
                    'bold'      =>  true,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => [
                        'rgb' => 'a6a6a6',
                    ]
                ],
            ],
            'D'     => ['font' => ['bold' => true]],
            'E'     => ['font' => ['bold' => true]],

        ];
    }

    public static function afterSheet(AfterSheet $event)
    {
        $event->sheet->getDelegate()->mergeCells('A1:F1');
        $event->sheet->getDelegate()->getRowDimension('1')->setRowHeight(30);
        $event->sheet->getDelegate()->getStyle('A1:F1')->getAlignment()->setHorizontal(Alignment::VERTICAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
    }

    public static function beforeExport(BeforeExport $event)
    {
        //
    }

    public static function beforeWriting(BeforeWriting $event)
    {
        //
    }

    public static function beforeSheet(BeforeSheet $event)
    {
        //
    }
}
