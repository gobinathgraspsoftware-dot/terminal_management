<?php

namespace App\Exports;

use App\Services\GrnReportService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReceivingSummaryByVendorExport implements FromCollection, WithHeadings, WithStyles, WithTitle
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $service = app(GrnReportService::class);
        $data = $service->getReceivingSummaryByVendor($this->filters);

        return $data->map(function ($row) {
            return [
                'vendor'         => $row->vendor_name ?? $row->company_name ?? 'N/A',
                'grn_count'      => $row->grn_count,
                'model_count'    => $row->model_count,
                'total_qty'      => number_format($row->total_qty_received, 0),
                'total_value'    => number_format($row->total_value, 2),
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Vendor',
            'No. of GRNs',
            'No. of Models',
            'Total Qty Received',
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
        return 'Receiving By Vendor';
    }
}
