<?php

namespace App\Exports;

use App\Models\InventoryItem;
use App\Models\StockBalance;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InventoryExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = InventoryItem::with(['jobCategory', 'creator']);

        if (!empty($this->filters['item_type'])) {
            $query->where('item_type', $this->filters['item_type']);
        }
        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        return $query->orderBy('item_code')->get();
    }

    public function headings(): array
    {
        return [
            '#',
            'Item Code',
            'Item Name',
            'Item Type',
            'Accessory Type',
            'Terminal ID',
            'Brand',
            'Model',
            'Category',
            'Unit',
            'Warehouse Stock',
            'Total Stock',
            'Reorder Level',
            'Status',
            'Created Date',
        ];
    }

    public function map($item): array
    {
        static $row = 0;
        $row++;

        $accessoryLabel = match ($item->accessory_type) {
            'sim_card' => 'SIM Card',
            'antenna' => 'Antenna',
            default => '-',
        };

        return [
            $row,
            $item->item_code,
            $item->item_name,
            ucfirst($item->item_type),
            $accessoryLabel,
            $item->serial_number ?? '-',
            $item->brand ?? '-',
            $item->model ?? '-',
            $item->jobCategory->category_name ?? 'N/A',
            $item->unit ?? 'unit',
            $item->getWarehouseStock(),
            $item->getTotalStock(),
            $item->reorder_level,
            ucfirst($item->status),
            $item->created_at?->format('d M Y'),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 11]],
        ];
    }
}
