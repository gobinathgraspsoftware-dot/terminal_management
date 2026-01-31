<?php

namespace App\Exports;

use App\Models\TerminalModel;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TerminalModelsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    /**
     * Query for export
     */
    public function query()
    {
        $query = TerminalModel::with(['category'])
            ->select('terminal_models.*');

        // Apply filters
        if (!empty($this->filters['category_id'])) {
            $query->where('category_id', $this->filters['category_id']);
        }

        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (!empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('model_code', 'like', "%{$search}%")
                    ->orWhere('model_name', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('model_code');
    }

    /**
     * Headings for the export
     */
    public function headings(): array
    {
        return [
            'Model Code',
            'Model Name',
            'Category',
            'Brand',
            'Description',
            'Serial Tracked',
            'Warranty (Months)',
            'Status',
            'Sort Order',
            'Created At',
        ];
    }

    /**
     * Map data for each row
     */
    public function map($terminalModel): array
    {
        return [
            $terminalModel->model_code,
            $terminalModel->model_name,
            $terminalModel->category ? $terminalModel->category->category_name : '',
            $terminalModel->brand ?? '',
            $terminalModel->description ?? '',
            $terminalModel->is_serial_tracked ? 'Yes' : 'No',
            $terminalModel->warranty_months,
            ucfirst($terminalModel->status),
            $terminalModel->sort_order,
            $terminalModel->created_at ? $terminalModel->created_at->format('Y-m-d H:i:s') : '',
        ];
    }

    /**
     * Apply styles to the worksheet
     */
    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E9ECEF']
                ]
            ],
        ];
    }
}
