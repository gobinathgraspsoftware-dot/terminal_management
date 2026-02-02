<?php

namespace App\Exports;

use App\Models\ChargeCatalog;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Charge Catalog Export
 *
 * Export charge catalog to Excel
 */
class ChargeCatalogExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    /**
     * Query for export.
     */
    public function query()
    {
        $query = ChargeCatalog::query()
            ->orderBy('charge_type')
            ->orderBy('charge_name');

        // Apply filters
        if (!empty($this->filters['charge_type'])) {
            $query->where('charge_type', $this->filters['charge_type']);
        }

        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        return $query;
    }

    /**
     * Column headings.
     */
    public function headings(): array
    {
        return [
            'Charge Code',
            'Charge Name',
            'Charge Type',
            'Description',
            'Default Price (RM)',
            'Tax Rate (%)',
            'Taxable',
            'Unit',
            'Status',
            'Created Date',
            'Updated Date',
        ];
    }

    /**
     * Map row data.
     */
    public function map($charge): array
    {
        return [
            $charge->charge_code,
            $charge->charge_name,
            ucfirst($charge->charge_type),
            $charge->description,
            number_format($charge->default_price, 2),
            number_format($charge->tax_rate, 2),
            $charge->is_taxable ? 'Yes' : 'No',
            $charge->unit ?? '-',
            ucfirst($charge->status),
            $charge->created_at ? $charge->created_at->format('Y-m-d H:i:s') : '-',
            $charge->updated_at ? $charge->updated_at->format('Y-m-d H:i:s') : '-',
        ];
    }

    /**
     * Styles for the worksheet.
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Style the header row
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E9ECEF'],
                ],
            ],
        ];
    }
}
