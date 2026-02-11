<?php

namespace App\Exports;

use App\Models\Quotation;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Facades\Auth;

class QuotationsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithTitle, ShouldAutoSize
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    /**
     * Query for quotations with filters.
     */
    public function query()
    {
        $query = Quotation::with(['client', 'vendor', 'createdBy', 'approvedBy']);

        $user = Auth::user();

        // Apply role-based scoping
        if ($user->hasRole('supervisor')) {
            $teamMemberIds = \App\Models\User::where('supervisor_id', $user->id)->pluck('id')->toArray();
            $teamMemberIds[] = $user->id;
            $query->whereIn('created_by', $teamMemberIds);
        } elseif ($user->hasRole('technician')) {
            $query->where('created_by', $user->id);
        }

        // Apply filters
        if (!empty($this->filters['quotation_type'])) {
            $query->where('quotation_type', $this->filters['quotation_type']);
        }

        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (!empty($this->filters['client_id'])) {
            $query->where('client_id', $this->filters['client_id']);
        }

        if (!empty($this->filters['vendor_id'])) {
            $query->where('vendor_id', $this->filters['vendor_id']);
        }

        if (!empty($this->filters['date_from'])) {
            $query->where('quotation_date', '>=', $this->filters['date_from']);
        }

        if (!empty($this->filters['date_to'])) {
            $query->where('quotation_date', '<=', $this->filters['date_to']);
        }

        if (!empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('quotation_no', 'like', "%{$search}%")
                  ->orWhere('reference', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('quotation_date', 'desc');
    }

    /**
     * Define column headings.
     */
    public function headings(): array
    {
        return [
            'Quotation No',
            'Date',
            'Type',
            'Client/Vendor',
            'Reference',
            'Valid Until',
            'Subtotal',
            'Tax Amount',
            'Discount',
            'Total Amount',
            'Currency',
            'Status',
            'Sent At',
            'Approved By',
            'Approved At',
            'Created By',
            'Created At',
            'Updated At',
        ];
    }

    /**
     * Map data for each row.
     */
    public function map($quotation): array
    {
        return [
            $quotation->quotation_no,
            $quotation->quotation_date->format('Y-m-d'),
            $quotation->getTypeLabel(),
            $quotation->party_name,
            $quotation->reference ?? '',
            $quotation->valid_until ? $quotation->valid_until->format('Y-m-d') : '',
            number_format($quotation->subtotal, 2),
            number_format($quotation->tax_amount, 2),
            number_format($quotation->discount_amount, 2),
            number_format($quotation->total_amount, 2),
            $quotation->currency,
            $quotation->getStatusLabel(),
            $quotation->sent_at ? $quotation->sent_at->format('Y-m-d H:i') : '',
            $quotation->approvedBy ? $quotation->approvedBy->name : '',
            $quotation->approved_at ? $quotation->approved_at->format('Y-m-d H:i') : '',
            $quotation->createdBy ? $quotation->createdBy->name : '',
            $quotation->created_at->format('Y-m-d H:i'),
            $quotation->updated_at->format('Y-m-d H:i'),
        ];
    }

    /**
     * Apply styles to the worksheet.
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Header row styling
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 12,
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4'],
                ],
                'font' => [
                    'color' => ['rgb' => 'FFFFFF'],
                    'bold' => true,
                ],
            ],
        ];
    }

    /**
     * Set the sheet title.
     */
    public function title(): string
    {
        return 'Quotations';
    }
}
