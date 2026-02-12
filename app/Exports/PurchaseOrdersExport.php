<?php

namespace App\Exports;

use App\Models\PurchaseOrder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PurchaseOrdersExport implements FromQuery, WithHeadings, WithMapping, WithStyles
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        $query = PurchaseOrder::with(['vendor', 'receivingDepot', 'createdBy']);

        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (!empty($this->filters['vendor_id'])) {
            $query->where('vendor_id', $this->filters['vendor_id']);
        }

        if (!empty($this->filters['date_from'])) {
            $query->whereDate('po_date', '>=', $this->filters['date_from']);
        }

        if (!empty($this->filters['date_to'])) {
            $query->whereDate('po_date', '<=', $this->filters['date_to']);
        }

        return $query->latest('po_date');
    }

    public function headings(): array
    {
        return [
            'PO No',
            'PO Date',
            'Vendor',
            'Reference',
            'Delivery Date',
            'Receiving Depot',
            'Subtotal',
            'Tax',
            'Total Amount',
            'Currency',
            'Status',
            'Created By',
            'Created At',
        ];
    }

    public function map($po): array
    {
        return [
            $po->po_no,
            $po->po_date->format('Y-m-d'),
            $po->vendor->vendor_name ?? 'N/A',
            $po->reference,
            $po->delivery_date?->format('Y-m-d'),
            $po->receivingDepot->depot_name ?? 'N/A',
            number_format($po->subtotal, 2),
            number_format($po->tax_amount, 2),
            number_format($po->total_amount, 2),
            $po->currency,
            ucwords(str_replace('_', ' ', $po->status)),
            $po->createdBy->name ?? 'N/A',
            $po->created_at->format('Y-m-d H:i:s'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
