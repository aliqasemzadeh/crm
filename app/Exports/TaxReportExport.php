<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;

class TaxReportExport implements FromCollection, WithHeadings, WithMapping, WithEvents
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            __('app.economic_code'),
            __('app.identification_code'),
            __('app.customer_title'),
            __('app.invoices_count'),
            __('app.total_sales'),
            __('app.total_tax'),
            __('app.net_amount'),
        ];
    }

    /**
     * @return array
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $event->sheet->getDelegate()->setRightToLeft(true);
            },
        ];
    }

    public function map($row): array
    {
        return [
            $row->EconomicCode,
            $row->IdentificationCode,
            $row->Name . ' ' . $row->LastName,
            $row->invoices_count,
            $row->total_price,
            $row->total_tax,
            $row->total_net_price,
        ];
    }
}
