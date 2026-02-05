<?php

namespace App\Exports;

use App\Models\StockBalance;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StockBalanceExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
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
        $query = StockBalance::with(['model.category']);

        // Apply filters
        if (isset($this->filters['model_id']) && !empty($this->filters['model_id'])) {
            $query->where('model_id', $this->filters['model_id']);
        }

        if (isset($this->filters['category_id']) && !empty($this->filters['category_id'])) {
            $query->whereHas('model', function($q) {
                $q->where('category_id', $this->filters['category_id']);
            });
        }

        if (isset($this->filters['location_type']) && !empty($this->filters['location_type'])) {
            $query->where('location_type', $this->filters['location_type']);
        }

        if (isset($this->filters['location_id']) && !empty($this->filters['location_id'])) {
            $query->where('location_id', $this->filters['location_id']);
        }

        if (isset($this->filters['stock_status'])) {
            if ($this->filters['stock_status'] === 'low_stock') {
                $query->where('quantity_available', '>', 0)
                    ->whereHas('model', function($q) {
                        $q->whereRaw('stock_balances.quantity_available <= terminal_models.min_stock_level');
                    });
            } elseif ($this->filters['stock_status'] === 'out_of_stock') {
                $query->where('quantity_available', '<=', 0);
            }
        }

        return $query->orderBy('location_type')
            ->orderBy('location_id')
            ->orderBy('model_id')
            ->get();
    }

    /**
     * Define column headings
     */
    public function headings(): array
    {
        return [
            'Location Type',
            'Location Name',
            'Category',
            'Model Name',
            'Model Code',
            'Quantity On Hand',
            'Quantity Reserved',
            'Quantity Available',
            'Min Stock Level',
            'Stock Status',
            'Last Movement Date',
            'Last Updated',
        ];
    }

    /**
     * Map each row
     */
    public function map($balance): array
    {
        $model = $balance->model;
        $minStock = $model->min_stock_level ?? 5;

        // Determine stock status
        $stockStatus = 'Normal';
        if ($balance->quantity_available <= 0) {
            $stockStatus = 'Out of Stock';
        } elseif ($balance->quantity_available <= $minStock) {
            $stockStatus = 'Low Stock';
        }

        return [
            ucfirst($balance->location_type),
            $this->getLocationName($balance->location_type, $balance->location_id),
            $model->category->category_name ?? '-',
            $model->model_name ?? '-',
            $model->model_code ?? '-',
            $balance->quantity_on_hand,
            $balance->quantity_reserved,
            $balance->quantity_available,
            $minStock,
            $stockStatus,
            $balance->last_movement_date ? $balance->last_movement_date->format('Y-m-d') : '-',
            $balance->updated_at->format('Y-m-d H:i:s'),
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
                    'startColor' => ['rgb' => '70AD47']
                ],
                'font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true],
            ],
        ];
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
