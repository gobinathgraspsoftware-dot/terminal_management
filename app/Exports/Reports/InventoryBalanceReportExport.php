<?php

namespace App\Exports\Reports;

use App\Models\User;
use App\Services\ReportService;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use App\Exports\Reports\ReportExportStyles;

class InventoryBalanceReportExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithTitle, WithStyles
{
    use ReportExportStyles;

    protected array $filters;
    protected ?User $user;

    public function __construct(array $filters = [], ?User $user = null)
    {
        $this->filters = $filters;
        $this->user    = $user;
    }

    public function query()
    {
        return (new ReportService())->inventoryBalanceQuery($this->filters, $this->user);
    }

    public function headings(): array
    {
        return [
            'Item Code', 'Item Name', 'Type', 'Category',
            'Serial Number', 'Model', 'Warehouse Qty',
            'Technician Qty', 'Total Qty', 'Reorder Level', 'Stock Status',
        ];
    }

    public function map($item): array
    {
        $wQty = 0; $tQty = 0;
        if ($item->relationLoaded('stockBalances')) {
            foreach ($item->stockBalances as $b) {
                if ($b->holder_type === 'warehouse') $wQty += $b->quantity; else $tQty += $b->quantity;
            }
        }

        return [
            $item->item_code,
            $item->item_name,
            ucfirst($item->item_type),
            $item->jobCategory?->category_name ?? '',
            $item->serial_number ?? '',
            $item->model ?? '',
            $wQty,
            $tQty,
            $wQty + $tQty,
            $item->reorder_level,
            $wQty <= $item->reorder_level ? 'Low Stock' : 'OK',
        ];
    }

    public function title(): string { return 'Inventory Balance'; }
}
