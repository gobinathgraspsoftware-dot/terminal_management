<?php

namespace App\Exports;

use App\Models\StockAdjustmentLine;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StockVarianceReportExport implements FromCollection, WithHeadings, WithMapping, WithTitle, WithStyles
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = StockAdjustmentLine::with(['stockAdjustment.depot', 'model'])
            ->whereHas('stockAdjustment', function ($q) {
                if (!empty($this->filters['depot_id'])) {
                    $q->where('depot_id', $this->filters['depot_id']);
                }
                if (!empty($this->filters['adjustment_type'])) {
                    $q->where('adjustment_type', $this->filters['adjustment_type']);
                }
                if (!empty($this->filters['date_from'])) {
                    $q->where('adjustment_date', '>=', $this->filters['date_from']);
                }
                if (!empty($this->filters['date_to'])) {
                    $q->where('adjustment_date', '<=', $this->filters['date_to']);
                }
                if (!empty($this->filters['status'])) {
                    $q->where('status', $this->filters['status']);
                }
                if (!empty($this->filters['created_by'])) {
                    $q->where('created_by', $this->filters['created_by']);
                }
            })
            ->whereRaw('physical_quantity != system_quantity')
            ->orderBy('created_at', 'desc');

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'Adjustment No',
            'Date',
            'Type',
            'Depot',
            'Model',
            'Serial No',
            'System Qty',
            'Physical Qty',
            'Variance Qty',
            'Unit Cost',
            'Variance Value',
            'Remarks',
            'Status',
        ];
    }

    public function map($line): array
    {
        $adjustment = $line->stockAdjustment;
        
        return [
            $adjustment->adjustment_no,
            $adjustment->adjustment_date->format('Y-m-d'),
            ucwords(str_replace('_', ' ', $adjustment->adjustment_type)),
            $adjustment->depot ? $adjustment->depot->depot_name : 'N/A',
            $line->model ? $line->model->model_name : 'N/A',
            $line->serial_no ?? '-',
            number_format($line->system_quantity, 2),
            number_format($line->physical_quantity, 2),
            number_format($line->variance_quantity, 2),
            $line->unit_cost ? number_format($line->unit_cost, 2) : '-',
            $line->variance_value ? number_format($line->variance_value, 2) : '-',
            $line->remarks ?? '-',
            ucwords(str_replace('_', ' ', $adjustment->status)),
        ];
    }

    public function title(): string
    {
        return 'Stock Variance Report';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E2E8F0']
                ]
            ],
        ];
    }
}
