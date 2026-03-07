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

        $query = Vendor::with(['createdBy', 'updatedBy', 'branches']);

        // Apply filters
        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (!empty($this->filters['vendor_type'])) {
            $query->where('vendor_type', $this->filters['vendor_type']);
        }

        if (!empty($this->filters['state'])) {
            $query->whereHas('branches', function ($q) {
                $q->where('state', $this->filters['state']);
            });
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
            'PIC Name',
            'PIC Email',
            'PIC Phone',
            'Bank Name',
            'Bank Account No',
            'Bank Account Name',
            'Payment Terms (Days)',
            'Status',
            'Notes',
            'Branches (Name | Address | City | State | Postcode | Contact | Email | Phone | Primary)',
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
        // Format branches into a readable string
        $branchesStr = '';
        if ($vendor->branches && $vendor->branches->count() > 0) {
            $branchLines = [];
            foreach ($vendor->branches as $branch) {
                $primary = $branch->is_primary ? 'Yes' : 'No';
                $branchLines[] = implode(' | ', [
                    $branch->branch_name ?? '',
                    $branch->address ?? '',
                    $branch->city ?? '',
                    $branch->state ?? '',
                    $branch->postcode ?? '',
                    $branch->contact_person ?? '',
                    $branch->contact_email ?? '',
                    $branch->contact_phone ?? '',
                    $primary,
                ]);
            }
            $branchesStr = implode("\n", $branchLines);
        }

        return [
            $vendor->vendor_code,
            $vendor->vendor_name,
            ucfirst($vendor->vendor_type),
            $vendor->company_name,
            $vendor->registration_no,
            $vendor->tax_id,
            $vendor->pic_name,
            $vendor->pic_email,
            $vendor->pic_phone,
            $vendor->bank_name,
            $vendor->bank_account_no,
            $vendor->bank_account_name,
            $vendor->payment_terms,
            ucfirst($vendor->status),
            $vendor->notes,
            $branchesStr,
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
            'A' => 15,  // Vendor Code
            'B' => 30,  // Vendor Name
            'C' => 15,  // Vendor Type
            'D' => 30,  // Company Name
            'E' => 20,  // Registration No
            'F' => 20,  // Tax ID
            'G' => 25,  // PIC Name
            'H' => 30,  // PIC Email
            'I' => 18,  // PIC Phone
            'J' => 25,  // Bank Name
            'K' => 20,  // Bank Account No
            'L' => 25,  // Bank Account Name
            'M' => 18,  // Payment Terms
            'N' => 12,  // Status
            'O' => 40,  // Notes
            'P' => 80,  // Branches
            'Q' => 20,  // Created At
            'R' => 20,  // Created By
            'S' => 20,  // Updated At
            'T' => 20,  // Updated By
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
                    'color' => ['rgb' => 'FFFFFF'],
                    'bold' => true,
                    'size' => 12,
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '0066CC'],
                ],
            ],
        ];
    }
}
