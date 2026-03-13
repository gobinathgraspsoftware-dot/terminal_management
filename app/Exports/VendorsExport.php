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

    public function collection()
    {
        if ($this->isTemplate) {
            return collect([]);
        }

        $query = Vendor::with(['createdBy', 'updatedBy', 'branches', 'vendorType']);

        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        // Support both vendor_type_id (new) and vendor_type (legacy)
        if (!empty($this->filters['vendor_type'])) {
            if (is_numeric($this->filters['vendor_type'])) {
                $query->where('vendor_type_id', $this->filters['vendor_type']);
            } else {
                $query->where('vendor_type', $this->filters['vendor_type']);
            }
        }

        if (!empty($this->filters['state'])) {
            $query->whereHas('branches', function ($q) {
                $q->where('state_id', $this->filters['state']);
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

    public function map($vendor): array
    {
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

        // Use VendorType relationship name, fallback to legacy enum
        $vendorTypeName = $vendor->vendorType?->title ?? ucfirst($vendor->vendor_type ?? '');

        return [
            $vendor->vendor_code,
            $vendor->vendor_name,
            $vendorTypeName,
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
            ucfirst($vendor->status ?? ''),
            $vendor->notes,
            $branchesStr,
            $vendor->created_at ? $vendor->created_at->format('Y-m-d H:i:s') : '',
            $vendor->createdBy ? $vendor->createdBy->name : '',
            $vendor->updated_at ? $vendor->updated_at->format('Y-m-d H:i:s') : '',
            $vendor->updatedBy ? $vendor->updatedBy->name : '',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,
            'B' => 30,
            'C' => 15,
            'D' => 30,
            'E' => 20,
            'F' => 20,
            'G' => 25,
            'H' => 30,
            'I' => 18,
            'J' => 25,
            'K' => 20,
            'L' => 25,
            'M' => 18,
            'N' => 12,
            'O' => 40,
            'P' => 80,
            'Q' => 20,
            'R' => 20,
            'S' => 20,
            'T' => 20,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
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
