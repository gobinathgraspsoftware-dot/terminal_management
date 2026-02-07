<?php

namespace App\Exports;

use App\Models\StockAdjustment;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StockAdjustmentsExport implements FromCollection, WithHeadings, WithMapping, WithTitle, WithStyles
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = StockAdjustment::with(['depot', 'creator', 'approver'])
            ->orderBy('adjustment_date', 'desc');

        // Apply filters
        if (!empty($this->filters['depot_id'])) {
            $query->where('depot_id', $this->filters['depot_id']);
        }

        if (!empty($this->filters['adjustment_type'])) {
            $query->where('adjustment_type', $this->filters['adjustment_type']);
        }

        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (!empty($this->filters['date_from'])) {
            $query->where('adjustment_date', '>=', $this->filters['date_from']);
        }

        if (!empty($this->filters['date_to'])) {
            $query->where('adjustment_date', '<=', $this->filters['date_to']);
        }

        if (!empty($this->filters['created_by'])) {
            $query->where('created_by', $this->filters['created_by']);
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'Adjustment No',
            'Date',
            'Type',
            'Depot',
            'Reason',
            'Total Items',
            'Status',
            'Created By',
            'Created At',
            'Approved By',
            'Approved At',
        ];
    }

    public function map($adjustment): array
    {
        return [
            $adjustment->adjustment_no,
            $adjustment->adjustment_date->format('Y-m-d'),
            ucwords(str_replace('_', ' ', $adjustment->adjustment_type)),
            $adjustment->depot ? $adjustment->depot->depot_name : 'N/A',
            $adjustment->reason,
            $adjustment->total_items,
            ucwords(str_replace('_', ' ', $adjustment->status)),
            $adjustment->creator ? $adjustment->creator->name : 'N/A',
            $adjustment->created_at->format('Y-m-d H:i:s'),
            $adjustment->approver ? $adjustment->approver->name : '-',
            $adjustment->approved_at ? $adjustment->approved_at->format('Y-m-d H:i:s') : '-',
        ];
    }

    public function title(): string
    {
        return 'Stock Adjustments';
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
