<?php

namespace App\Exports;

use App\Models\StockLedger;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;

class StockLedgerExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    /**
     * Get the data collection
     */
    public function collection()
    {
        $query = StockLedger::with(['model.category', 'serial', 'createdBy', 'reversedByUser'])
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc');

        // Apply filters
        if (isset($this->filters['model_id']) && !empty($this->filters['model_id'])) {
            $query->where('model_id', $this->filters['model_id']);
        }

        if (isset($this->filters['transaction_type']) && !empty($this->filters['transaction_type'])) {
            $query->where('transaction_type', $this->filters['transaction_type']);
        }

        if (isset($this->filters['location_type']) && !empty($this->filters['location_type'])) {
            $query->where(function($q) use ($filters) {
                $q->where('from_location_type', $this->filters['location_type'])
                  ->orWhere('to_location_type', $this->filters['location_type']);
            });
        }

        if (isset($this->filters['location_id']) && !empty($this->filters['location_id'])) {
            $query->where(function($q) use ($filters) {
                $q->where('from_location_id', $this->filters['location_id'])
                  ->orWhere('to_location_id', $this->filters['location_id']);
            });
        }

        if (isset($this->filters['from_date']) && !empty($this->filters['from_date'])) {
            $query->whereDate('transaction_date', '>=', $this->filters['from_date']);
        }

        if (isset($this->filters['to_date']) && !empty($this->filters['to_date'])) {
            $query->whereDate('transaction_date', '<=', $this->filters['to_date']);
        }

        if (isset($this->filters['serial_no']) && !empty($this->filters['serial_no'])) {
            $query->where('serial_no', 'like', '%' . $this->filters['serial_no'] . '%');
        }

        if (isset($this->filters['show_reversed']) && $this->filters['show_reversed'] === 'exclude') {
            $query->where('is_reversed', false);
        }

        return $query->get();
    }

    /**
     * Define column headings
     */
    public function headings(): array
    {
        return [
            'Transaction Date',
            'Transaction No',
            'Transaction Type',
            'Category',
            'Model',
            'Model Code',
            'Serial Number',
            'Quantity',
            'Unit Cost',
            'Total Cost',
            'From Location Type',
            'From Location',
            'To Location Type',
            'To Location',
            'Reference Type',
            'Reference No',
            'Created By',
            'Created At',
            'Is Reversed',
            'Reversed At',
            'Reversed By',
            'Remarks',
        ];
    }

    /**
     * Map each row
     */
    public function map($ledger): array
    {
        return [
            $ledger->transaction_date->format('Y-m-d'),
            $ledger->transaction_no,
            $this->getTransactionTypeLabel($ledger->transaction_type),
            $ledger->model->category->category_name ?? '-',
            $ledger->model->model_name ?? '-',
            $ledger->model->model_code ?? '-',
            $ledger->serial_no ?? '-',
            $ledger->quantity,
            $ledger->unit_cost ?? 0,
            $ledger->total_cost ?? 0,
            ucfirst($ledger->from_location_type ?? '-'),
            $this->getLocationName($ledger->from_location_type, $ledger->from_location_id),
            ucfirst($ledger->to_location_type ?? '-'),
            $this->getLocationName($ledger->to_location_type, $ledger->to_location_id),
            $ledger->reference_type ?? '-',
            $ledger->reference_no ?? '-',
            $ledger->createdBy->name ?? '-',
            $ledger->created_at->format('Y-m-d H:i:s'),
            $ledger->is_reversed ? 'Yes' : 'No',
            $ledger->reversed_at ? $ledger->reversed_at->format('Y-m-d H:i:s') : '-',
            $ledger->reversedByUser->name ?? '-',
            $ledger->remarks ?? '-',
        ];
    }

    /**
     * Apply styles to the spreadsheet
     */
    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4']
                ],
                'font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true],
            ],
        ];
    }

    /**
     * Get transaction type label
     */
    protected function getTransactionTypeLabel($type): string
    {
        $labels = [
            'grn_in' => 'GRN In',
            'purchase_return' => 'Purchase Return',
            'adjustment_in' => 'Adjustment In',
            'adjustment_out' => 'Adjustment Out',
            'transfer_out' => 'Transfer Out',
            'transfer_in' => 'Transfer In',
            'issue_to_tech' => 'Issue to Technician',
            'return_from_tech' => 'Return from Technician',
            'install' => 'Installation',
            'replacement_out' => 'Replacement Out',
            'replacement_in' => 'Replacement In',
            'sale_out' => 'Sale Out',
        ];

        return $labels[$type] ?? ucfirst(str_replace('_', ' ', $type));
    }

    /**
     * Get location name
     */
    protected function getLocationName($type, $id): string
    {
        if (!$type || !$id) {
            return '-';
        }

        switch ($type) {
            case 'depot':
                $depot = \App\Models\Depot::find($id);
                return $depot ? $depot->depot_name : "Depot #{$id}";

            case 'technician':
                $tech = \App\Models\User::find($id);
                return $tech ? $tech->name : "Technician #{$id}";

            case 'site':
                $site = \App\Models\Site::find($id);
                return $site ? $site->site_name : "Site #{$id}";

            case 'vendor':
                $vendor = \App\Models\Vendor::find($id);
                return $vendor ? $vendor->vendor_name : "Vendor #{$id}";

            case 'client':
                $client = \App\Models\Client::find($id);
                return $client ? $client->client_name : "Client #{$id}";

            default:
                return ucfirst($type) . " #{$id}";
        }
    }
}
