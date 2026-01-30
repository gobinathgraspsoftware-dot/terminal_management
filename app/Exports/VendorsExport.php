<?php

namespace App\Exports;

use App\Models\Vendor;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class VendorsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, ShouldAutoSize
{
    protected array $filters;
    protected bool $isTemplate;

    public function __construct(array $filters = [], bool $isTemplate = false)
    {
        $this->filters = $filters;
        $this->isTemplate = $isTemplate;
    }

    /**
     * Get the collection to export
     */
    public function collection()
    {
        // If template, return empty collection
        if ($this->isTemplate) {
            return collect([]);
        }

        $query = Vendor::with(['createdBy', 'updatedBy']);

        // Apply filters
        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (!empty($this->filters['vendor_type'])) {
            $query->where('vendor_type', $this->filters['vendor_type']);
        }

        if (!empty($this->filters['state'])) {
            $query->where('state', $this->filters['state']);
        }

        if (!empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('vendor_code', 'like', "%{$search}%")
                    ->orWhere('vendor_name', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('vendor_name')->get();
    }

    /**
     * Define the headings for the export
     */
    public function headings(): array
    {
        return [
            'Vendor Code',
            'Vendor Name',
            'Vendor Type',
            'Company Name',
            'Registration No',
            'Tax ID',
            'Address',
            'City',
            'State',
            'Postcode',
            'Country',
            'PIC Name',
            'PIC Email',
            'PIC Phone',
            'Bank Name',
            'Bank Account No',
            'Bank Account Name',
            'Payment Terms (Days)',
            'Status',
            'Notes',
            'Created At',
            'Created By',
            'Updated At',
            'Updated By',
        ];
    }

    /**
     * Map the data for each row
     */
    public function map($vendor): array
    {
        return [
            $vendor->vendor_code,
            $vendor->vendor_name,
            ucfirst($vendor->vendor_type),
            $vendor->company_name,
            $vendor->registration_no,
            $vendor->tax_id,
            $vendor->address,
            $vendor->city,
            $vendor->state,
            $vendor->postcode,
            $vendor->country,
            $vendor->pic_name,
            $vendor->pic_email,
            $vendor->pic_phone,
            $vendor->bank_name,
            $vendor->bank_account_no,
            $vendor->bank_account_name,
            $vendor->payment_terms,
            ucfirst($vendor->status),
            $vendor->notes,
            $vendor->created_at ? $vendor->created_at->format('Y-m-d H:i:s') : '',
            $vendor->createdBy ? $vendor->createdBy->name : '',
            $vendor->updated_at ? $vendor->updated_at->format('Y-m-d H:i:s') : '',
            $vendor->updatedBy ? $vendor->updatedBy->name : '',
        ];
    }

    /**
     * Define column widths
     */
    public function columnWidths(): array
    {
        return [
            'A' => 15, // Vendor Code
            'B' => 30, // Vendor Name
            'C' => 15, // Vendor Type
            'D' => 30, // Company Name
            'E' => 20, // Registration No
            'F' => 20, // Tax ID
            'G' => 40, // Address
            'H' => 15, // City
            'I' => 15, // State
            'J' => 12, // Postcode
            'K' => 15, // Country
            'L' => 25, // PIC Name
            'M' => 30, // PIC Email
            'N' => 18, // PIC Phone
            'O' => 25, // Bank Name
            'P' => 20, // Bank Account No
            'Q' => 25, // Bank Account Name
            'R' => 18, // Payment Terms
            'S' => 12, // Status
            'T' => 40, // Notes
            'U' => 20, // Created At
            'V' => 20, // Created By
            'W' => 20, // Updated At
            'X' => 20, // Updated By
        ];
    }

    /**
     * Apply styles to the worksheet
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Style the header row
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 12,
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '0066CC'],
                ],
                'font' => [
                    'color' => ['rgb' => 'FFFFFF'],
                    'bold' => true,
                ],
            ],
        ];
    }
}
