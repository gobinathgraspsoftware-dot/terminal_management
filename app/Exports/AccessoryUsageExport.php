<?php

namespace App\Exports;

use App\Models\AccessoryUsage;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class AccessoryUsageExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        $query = AccessoryUsage::with(['ticket', 'model.category', 'serial', 'createdBy', 'returnDepot']);

        if (!empty($this->filters['accessory_type'])) {
            $query->where('accessory_type', $this->filters['accessory_type']);
        }
        if (!empty($this->filters['action'])) {
            $query->where('action', $this->filters['action']);
        }
        if (!empty($this->filters['date_from'])) {
            $query->whereDate('created_at', '>=', $this->filters['date_from']);
        }
        if (!empty($this->filters['date_to'])) {
            $query->whereDate('created_at', '<=', $this->filters['date_to']);
        }
        if (!empty($this->filters['ticket_id'])) {
            $query->where('ticket_id', $this->filters['ticket_id']);
        }

        return $query->orderBy('created_at', 'desc');
    }

    public function headings(): array
    {
        return [
            'ID', 'Ticket No', 'Accessory Type', 'Model', 'Category',
            'Serial No', 'Quantity', 'Action', 'Condition',
            'Return Date', 'Return Condition', 'Return Depot',
            'Remarks', 'Created By', 'Created At',
        ];
    }

    public function map($usage): array
    {
        return [
            $usage->id,
            $usage->ticket->ticket_no ?? 'N/A',
            AccessoryUsage::ACCESSORY_TYPE_OPTIONS[$usage->accessory_type] ?? $usage->accessory_type,
            $usage->model->model_name ?? 'N/A',
            $usage->model->category->category_name ?? 'N/A',
            $usage->serial_no ?? '-',
            $usage->quantity,
            ucfirst($usage->action),
            ucfirst($usage->condition),
            $usage->return_date ? $usage->return_date->format('d M Y') : '-',
            $usage->return_condition ? ucfirst($usage->return_condition) : '-',
            $usage->returnDepot->depot_name ?? '-',
            $usage->remarks ?? '-',
            $usage->createdBy->name ?? 'System',
            $usage->created_at ? $usage->created_at->format('d M Y H:i') : '',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '0D6EFD'],
                ],
            ],
        ];
    }
}
