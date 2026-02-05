<?php

namespace App\Exports;

use App\Services\StockReportService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StockMovementReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithTitle
{
    protected array $filters;
    protected StockReportService $stockReportService;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
        $this->stockReportService = app(StockReportService::class);
    }

    /**
     * Get data collection
     */
    public function collection()
    {
        return $this->stockReportService
            ->getMovementReport($this->filters)
            ->get();
    }

    /**
     * Define headings
     */
    public function headings(): array
    {
        return [
            'Date',
            'Transaction No',
            'Type',
            'Serial No',
            'Model',
            'Category',
            'Quantity',
            'From Location',
            'To Location',
            'Reference',
            'Unit Cost',
            'Total Cost',
            'Created By',
            'Remarks',
        ];
    }

    /**
     * Map data rows
     */
    public function map($movement): array
    {
        return [
            $movement->transaction_date ? $movement->transaction_date->format('Y-m-d') : '',
            $movement->transaction_no,
            $movement->type_label,
            $movement->serial_no ?? '-',
            $movement->model ? $movement->model->model_name : '-',
            $movement->model && $movement->model->category ? $movement->model->category->category_name : '-',
            $movement->quantity,
            $movement->from_location_name,
            $movement->to_location_name,
            $movement->reference_label,
            $movement->unit_cost ? number_format($movement->unit_cost, 2) : '-',
            $movement->total_cost ? number_format($movement->total_cost, 2) : '-',
            $movement->createdBy ? $movement->createdBy->name : '-',
            $movement->remarks ?? '',
        ];
    }

    /**
     * Apply styles
     */
    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4']
                ],
                'font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true],
            ],
        ];
    }

    /**
     * Define column widths
     */
    public function columnWidths(): array
    {
        return [
            'A' => 12,  // Date
            'B' => 18,  // Transaction No
            'C' => 25,  // Type
            'D' => 20,  // Serial No
            'E' => 25,  // Model
            'F' => 20,  // Category
            'G' => 10,  // Quantity
            'H' => 20,  // From Location
            'I' => 20,  // To Location
            'J' => 20,  // Reference
            'K' => 12,  // Unit Cost
            'L' => 12,  // Total Cost
            'M' => 20,  // Created By
            'N' => 30,  // Remarks
        ];
    }

    /**
     * Sheet title
     */
    public function title(): string
    {
        return 'Stock Movement Report';
    }
}
