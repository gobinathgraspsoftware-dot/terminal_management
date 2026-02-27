<?php

namespace App\Exports;

use App\Models\Grn;
use App\Models\GrnLine;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GrnRegisterExport implements FromCollection, WithHeadings, WithStyles, WithTitle
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Grn::with(['vendor', 'receivingDepot', 'purchaseOrder', 'createdBy', 'postedBy', 'lines.model'])
            ->select('grns.*');

        $this->applyFilters($query);

        $grns = $query->orderBy('grn_date', 'desc')->get();

        $rows = collect();

        foreach ($grns as $grn) {
            foreach ($grn->lines as $line) {
                $rows->push([
                    'grn_no'          => $grn->grn_no,
                    'grn_date'        => $grn->grn_date->format('Y-m-d'),
                    'vendor'          => $grn->vendor->vendor_name ?? $grn->vendor->company_name ?? 'N/A',
                    'po_no'           => $grn->purchaseOrder->po_no ?? 'N/A',
                    'depot'           => $grn->receivingDepot->depot_name ?? 'N/A',
                    'delivery_note'   => $grn->delivery_note_no ?? '-',
                    'model'           => $line->model->model_name ?? 'N/A',
                    'description'     => $line->description ?? '-',
                    'qty_received'    => number_format($line->quantity_received, 0),
                    'unit_cost'       => number_format($line->unit_cost, 2),
                    'line_total'      => number_format($line->line_total, 2),
                    'status'          => ucfirst($grn->status),
                    'posted_at'       => $grn->posted_at ? $grn->posted_at->format('Y-m-d H:i') : '-',
                    'posted_by'       => $grn->postedBy->name ?? '-',
                    'created_by'      => $grn->createdBy->name ?? 'N/A',
                ]);
            }
        }

        return $rows;
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
            'Model',
            'Description',
            'Qty Received',
            'Unit Cost',
            'Line Total',
            'Status',
            'Posted At',
            'Posted By',
            'Created By',
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
        return 'GRN Register';
    }

    protected function applyFilters($query): void
    {
        if (!empty($this->filters['date_from'])) {
            $query->whereDate('grns.grn_date', '>=', $this->filters['date_from']);
        }

        if (!empty($this->filters['date_to'])) {
            $query->whereDate('grns.grn_date', '<=', $this->filters['date_to']);
        }

        if (!empty($this->filters['vendor_id'])) {
            $query->where('grns.vendor_id', $this->filters['vendor_id']);
        }

        if (!empty($this->filters['purchase_order_id'])) {
            $query->where('grns.purchase_order_id', $this->filters['purchase_order_id']);
        }

        if (!empty($this->filters['receiving_depot_id'])) {
            $query->where('grns.receiving_depot_id', $this->filters['receiving_depot_id']);
        }

        if (!empty($this->filters['status'])) {
            $query->where('grns.status', $this->filters['status']);
        }

        // Role-based scoping
        if (!empty($this->filters['user']) && !empty($this->filters['role'])) {
            $user = $this->filters['user'];
            $role = $this->filters['role'];

            if ($role === 'supervisor') {
                $teamUserIds = \App\Models\User::where('team_id', $user->team_id)->pluck('id')->toArray();
                $query->whereIn('grns.created_by', $teamUserIds);
            } elseif ($role === 'technician') {
                $query->where('grns.created_by', $user->id);
            }
        }
    }
}
