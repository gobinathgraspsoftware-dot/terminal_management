<?php

namespace App\Exports;

use App\Models\Partner;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PartnersExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected array $filters;
    protected bool $isTemplate;

    public function __construct(array $filters = [], bool $isTemplate = false)
    {
        $this->filters = $filters;
        $this->isTemplate = $isTemplate;
    }

    /**
     * Get the collection of partners to export
     */
    public function collection()
    {
        // Return empty collection for template
        if ($this->isTemplate) {
            return collect([]);
        }

        $query = Partner::with(['createdBy', 'updatedBy'])
            ->withCount(['clients', 'jobOrders']);

        // Apply filters
        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (!empty($this->filters['job_intake_method'])) {
            $query->where('job_intake_method', $this->filters['job_intake_method']);
        }

        if (!empty($this->filters['state'])) {
            $query->where('state', $this->filters['state']);
        }

        if (!empty($this->filters['show_trashed']) && $this->filters['show_trashed'] === 'true') {
            $query->withTrashed();
        }

        return $query->orderBy('partner_name')->get();
    }

    /**
     * Define the headings for the export
     */
    public function headings(): array
    {
        return [
            'Partner Code',
            'Partner Name',
            'PIC Name',
            'PIC Email',
            'PIC Phone',
            'Address',
            'City',
            'State',
            'Postcode',
            'Country',
            'Job Intake Method',
            'Status',
            'Total Clients',
            'Total Jobs',
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
    public function map($partner): array
    {
        return [
            $partner->partner_code,
            $partner->partner_name,
            $partner->pic_name ?? '',
            $partner->pic_email ?? '',
            $partner->pic_phone ?? '',
            $partner->address ?? '',
            $partner->city ?? '',
            $partner->state ?? '',
            $partner->postcode ?? '',
            $partner->country ?? 'Malaysia',
            ucfirst($partner->job_intake_method),
            ucfirst($partner->status),
            $partner->clients_count ?? 0,
            $partner->job_orders_count ?? 0,
            $partner->notes ?? '',
            $partner->created_at ? $partner->created_at->format('Y-m-d H:i:s') : '',
            $partner->createdBy ? $partner->createdBy->name : '',
            $partner->updated_at ? $partner->updated_at->format('Y-m-d H:i:s') : '',
            $partner->updatedBy ? $partner->updatedBy->name : '',
        ];
    }

    /**
     * Apply styles to the worksheet
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            // Style the header row
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4'],
                ],
            ],
        ];
    }
}
