<?php

namespace App\Exports\Reports;

use App\Models\User;
use App\Services\ReportService;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class RouterMovementReportExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithTitle
{
    protected array $filters;
    protected ?User $user;

    public function __construct(array $filters = [], ?User $user = null)
    {
        $this->filters = $filters;
        $this->user    = $user;
    }

    public function query()
    {
        return (new ReportService())->routerMovementQuery($this->filters, $this->user);
    }

    public function headings(): array
    {
        return [
            'Movement No', 'Date', 'Item Code', 'Item Name',
            'Terminal ID', 'Movement Type', 'Qty',
            'From', 'To', 'Ticket No', 'Condition',
            'Performed By', 'Remarks',
        ];
    }

    public function map($m): array
    {
        return [
            $m->movement_no,
            $m->movement_date,
            $m->inventoryItem?->item_code ?? '',
            $m->inventoryItem?->item_name ?? '',
            $m->inventoryItem?->serial_number ?? '',
            ucfirst(str_replace('_', ' ', $m->movement_type)),
            $m->quantity,
            $m->from_holder_type ? ucfirst($m->from_holder_type) . ($m->fromHolder ? ': ' . $m->fromHolder->name : '') : '',
            $m->to_holder_type ? ucfirst($m->to_holder_type) . ($m->toHolder ? ': ' . $m->toHolder->name : '') : '',
            $m->ticket?->ticket_no ?? '',
            ucfirst($m->item_condition ?? 'good'),
            $m->performer?->name ?? '',
            $m->remarks ?? '',
        ];
    }

    public function title(): string { return 'Router Movement'; }
}
