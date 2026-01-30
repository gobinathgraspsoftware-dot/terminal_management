<?php

namespace App\Exports;

use App\Models\Site;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SitesExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    /**
     * Get the collection to export
     */
    public function collection()
    {
        $query = Site::with(['client:id,client_name'])
            ->select('sites.*');

        // Apply role-based scoping
        $user = Auth::user();
        if ($user->hasRole('supervisor')) {
            $coverageStates = json_decode($user->coverage_states, true) ?? [];
            if (!empty($coverageStates)) {
                $query->whereIn('state', $coverageStates);
            }
        }

        // Apply filters
        if (!empty($this->filters['client_id'])) {
            $query->where('client_id', $this->filters['client_id']);
        }

        if (!empty($this->filters['state'])) {
            $query->where('state', $this->filters['state']);
        }

        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        return $query->orderBy('site_code')->get();
    }

    /**
     * Define the headings
     */
    public function headings(): array
    {
        return [
            'Site Code',
            'Site Name',
            'Client',
            'Address',
            'City',
            'State',
            'Postcode',
            'Country',
            'Latitude',
            'Longitude',
            'PIC Name',
            'PIC Phone',
            'PIC Email',
            'Operating Hours',
            'Status',
            'Assets Count',
            'Created At',
        ];
    }

    /**
     * Map the data for each row
     */
    public function map($site): array
    {
        return [
            $site->site_code,
            $site->site_name,
            $site->client ? $site->client->client_name : '',
            $site->address,
            $site->city,
            $site->state,
            $site->postcode,
            $site->country,
            $site->latitude,
            $site->longitude,
            $site->pic_name,
            $site->pic_phone,
            $site->pic_email,
            $site->operating_hours,
            ucfirst($site->status),
            $site->siteAssets()->count(),
            $site->created_at?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Apply styles to the worksheet
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as header
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E2E8F0']
                ]
            ],
        ];
    }
}
