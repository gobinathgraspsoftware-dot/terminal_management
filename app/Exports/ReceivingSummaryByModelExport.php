<?php

namespace App\Exports;

use App\Services\GrnReportService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReceivingSummaryByModelExport implements FromCollection, WithHeadings, WithStyles, WithTitle
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $service = app(GrnReportService::class);
        $data = $service->getReceivingSummaryByModel($this->filters);

        return $data->map(function ($row) {
            return [
                'model'          => $row->model_name ?? 'N/A',
                'category'       => $row->category_name ?? 'N/A',
                'grn_count'      => $row->grn_count,
                'vendor_count'   => $row->vendor_count,
                'total_qty'      => number_format($row->total_qty_received, 0),
                'avg_unit_cost'  => number_format($row->avg_unit_cost, 2),
                'total_value'    => number_format($row->total_value, 2),
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Model',
            'Category',
            'No. of GRNs',
            'No. of Vendors',
            'Total Qty Received',
            'Avg Unit Cost',
            'Total Value',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function title(): string
    {
        return 'Receiving By Model';
    }
}
