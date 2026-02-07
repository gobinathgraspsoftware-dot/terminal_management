<?php

namespace App\Exports;

use App\Models\StockTransfer;
use Maatwebskie\Excel\Concerns\FromCollection;
use Maatwebskie\Excel\Concerns\WithHeadings;
use Maatwebskie\Excel\Concerns\WithMapping;
use Maatwebskie\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StockTransfersExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = StockTransfer::with(['fromDepot', 'toDepot', 'lines.model']);

        if (!empty($this->filters['from_depot_id'])) {
            $query->where('from_depot_id', $this->filters['from_depot_id']);
        }

        if (!empty($this->filters['to_depot_id'])) {
            $query->where('to_depot_id', $this->filters['to_depot_id']);
        }

        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (!empty($this->filters['date_from'])) {
            $query->whereDate('transfer_date', '>=', $this->filters['date_from']);
        }

        if (!empty($this->filters['date_to'])) {
            $query->whereDate('transfer_date', '<=', $this->filters['date_to']);
        }

        return $query->orderBy('transfer_date', 'desc')->get();
    }

    public function headings(): array
    {
        return [
            'Transfer No',
            'Transfer Date',
            'From Depot',
            'To Depot',
            'Total Items',
            'Status',
            'Approved At',
            'Dispatched At',
            'Received At',
            'Remarks',
        ];
    }

    public function map($transfer): array
    {
        return [
            $transfer->transfer_no,
            $transfer->transfer_date->format('Y-m-d'),
            $transfer->fromDepot->depot_name ?? 'N/A',
            $transfer->toDepot->depot_name ?? 'N/A',
            $transfer->total_items,
            ucfirst(str_replace('_', ' ', $transfer->status)),
            $transfer->approved_at ? $transfer->approved_at->format('Y-m-d H:i') : '-',
            $transfer->dispatched_at ? $transfer->dispatched_at->format('Y-m-d H:i') : '-',
            $transfer->received_at ? $transfer->received_at->format('Y-m-d H:i') : '-',
            $transfer->remarks ?? '',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
