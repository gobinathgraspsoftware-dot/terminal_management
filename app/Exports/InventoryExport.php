<?php

namespace App\Exports;

use App\Models\InventoryItem;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InventoryExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected ?string $itemType;
    protected ?string $status;
    protected ?int $categoryId;

    public function __construct(?string $itemType = null, ?string $status = null, ?int $categoryId = null)
    {
        $this->itemType   = $itemType;
        $this->status     = $status;
        $this->categoryId = $categoryId;
    }

    public function query()
    {
        $query = InventoryItem::with(['jobCategory', 'stockBalances'])
            ->select('inventory_items.*');

        if ($this->itemType) {
            $query->where('item_type', $this->itemType);
        }
        if ($this->status) {
            $query->where('status', $this->status);
        }
        if ($this->categoryId) {
            $query->where('job_category_id', $this->categoryId);
        }

        return $query->orderBy('item_code');
    }

    public function headings(): array
    {
        return [
            'Item Code',
            'Item Name',
            'Category',
            'Type',
            'Terminal ID',
            'Brand',
            'Model',
            'Unit',
            'Warehouse Stock',
            'Total Stock',
            'Reorder Level',
            'Status',
            'Created At',
        ];
    }

    public function map($item): array
    {
        return [
            $item->item_code,
            $item->item_name,
            $item->jobCategory->category_name ?? 'N/A',
            ucfirst($item->item_type),
            $item->serial_number ?? '-',
            $item->brand ?? '-',
            $item->model ?? '-',
            $item->unit,
            $item->warehouse_stock,
            $item->total_stock,
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
