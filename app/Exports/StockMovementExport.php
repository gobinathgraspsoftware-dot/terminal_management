<?php

namespace App\Exports;

use App\Models\StockMovement;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StockMovementExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = StockMovement::with(['inventoryItem', 'performer', 'ticket']);

        if (!empty($this->filters['movement_type'])) {
            $query->ofType($this->filters['movement_type']);
        }
        if (!empty($this->filters['date_from'])) {
            $query->where('movement_date', '>=', $this->filters['date_from']);
        }
        if (!empty($this->filters['date_to'])) {
            $query->where('movement_date', '<=', $this->filters['date_to']);
        }
        if (!empty($this->filters['inventory_item_id'])) {
            $query->forItem($this->filters['inventory_item_id']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function headings(): array
    {
        return [
            '#',
            'Movement No',
            'Date',
            'Type',
            'Item Code',
            'Item Name',
            'Router IDs',
            'Quantity',
            'From',
            'To',
            'Ticket No',
            'Condition',
            'Reason',
            'Remarks',
            'Performed By',
            'Created At',
        ];
    }

    public function map($movement): array
    {
        static $row = 0;
        $row++;

        // Get router IDs display
        $routerIds = $movement->router_ids;
        if (empty($routerIds)) {
            $routerIdsDisplay = '-';
        } else {
            if (is_string($routerIds)) {
                $routerIds = json_decode($routerIds, true) ?? [];
            }
            $routerIdsDisplay = is_array($routerIds) ? implode(', ', $routerIds) : '-';
        }

        return [
            $row,
            $movement->movement_no,
            $movement->movement_date?->format('d M Y'),
            $movement->getTypeLabel(),
            $movement->inventoryItem->item_code ?? 'N/A',
            $movement->inventoryItem->item_name ?? 'N/A',
            $routerIdsDisplay,
            $movement->quantity,
            $movement->getFromLocation(),
            $movement->getToLocation(),
            $movement->ticket->ticket_no ?? '-',
            ucfirst($movement->item_condition ?? '-'),
            $movement->reason ?? '-',
            $movement->remarks ?? '-',
            $movement->performer->name ?? 'N/A',
            $movement->created_at?->format('d M Y H:i'),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 11]],
        ];
    }
}
