<?php

namespace App\Exports;

use App\Models\Grn;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GrnsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        $query = Grn::with(['vendor', 'receivingDepot', 'purchaseOrder', 'createdBy', 'postedBy']);

        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (!empty($this->filters['vendor_id'])) {
            $query->where('vendor_id', $this->filters['vendor_id']);
        }

        if (!empty($this->filters['purchase_order_id'])) {
            $query->where('purchase_order_id', $this->filters['purchase_order_id']);
        }

        if (!empty($this->filters['receiving_depot_id'])) {
            $query->where('receiving_depot_id', $this->filters['receiving_depot_id']);
        }

        if (!empty($this->filters['date_from'])) {
            $query->whereDate('grn_date', '>=', $this->filters['date_from']);
        }

        if (!empty($this->filters['date_to'])) {
            $query->whereDate('grn_date', '<=', $this->filters['date_to']);
        }

        if (!empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('grn_no', 'like', "%{$search}%")
                  ->orWhere('delivery_note_no', 'like', "%{$search}%");
            });
        }

        // Role-based scoping
        if (!empty($this->filters['user']) && !empty($this->filters['role'])) {
            $user = $this->filters['user'];
            $role = $this->filters['role'];

            if ($role === 'supervisor') {
                $teamUserIds = \App\Models\User::where('team_id', $user->team_id)->pluck('id')->toArray();
                $query->whereIn('created_by', $teamUserIds);
            } elseif ($role === 'technician') {
                $query->where('created_by', $user->id);
            }
        }

        return $query->latest('grn_date');
    }

    public function headings(): array
    {
        return [
            'GRN No',
            'GRN Date',
            'Vendor',
            'PO No',
            'Receiving Depot',
            'Delivery Note No',
            'Total Items',
            'Total Value',
            'Status',
            'Posted At',
            'Posted By',
            'Created By',
            'Created At',
            'Remarks',
        ];
    }

    public function map($grn): array
    {
        $totalValue = $grn->lines ? $grn->lines->sum('line_total') : 0;

        return [
            $grn->grn_no,
            $grn->grn_date->format('Y-m-d'),
            $grn->vendor->vendor_name ?? $grn->vendor->company_name ?? 'N/A',
            $grn->purchaseOrder->po_no ?? 'N/A',
            $grn->receivingDepot->depot_name ?? 'N/A',
            $grn->delivery_note_no ?? '-',
            $grn->total_items,
            number_format($totalValue, 2),
            ucfirst($grn->status),
            $grn->posted_at ? $grn->posted_at->format('Y-m-d H:i') : '-',
            $grn->postedBy->name ?? '-',
            $grn->createdBy->name ?? 'N/A',
            $grn->created_at->format('Y-m-d H:i'),
            $grn->remarks ?? '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function title(): string
    {
        return 'GRN List';
    }
}
