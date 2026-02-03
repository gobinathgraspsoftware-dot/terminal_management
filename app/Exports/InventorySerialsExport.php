<?php

namespace App\Exports;

use App\Models\InventorySerial;
use App\Models\Depot;
use App\Models\User;
use App\Models\Site;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Carbon\Carbon;

class InventorySerialsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, ShouldAutoSize, WithTitle
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    /**
     * Get the collection to export.
     */
    public function collection()
    {
        $query = InventorySerial::with([
            'model.category',
            'grn',
            'purchaseOrder',
        ]);

        // ── Status filter ──
        if (!empty($this->filters['status'])) {
            $query->where('current_status', $this->filters['status']);
        }

        // ── Location type filter ──
        if (!empty($this->filters['location_type'])) {
            $query->where('current_location_type', $this->filters['location_type']);
        }

        // ── Depot filter ──
        if (!empty($this->filters['depot_id'])) {
            $query->where('current_location_type', 'depot')
                  ->where('current_location_id', $this->filters['depot_id']);
        }

        // ── Model / Category filter ──
        if (!empty($this->filters['model_id'])) {
            $query->where('model_id', $this->filters['model_id']);
        }

        if (!empty($this->filters['category_id'])) {
            $query->whereHas('model', function ($q) {
                $q->where('category_id', $this->filters['category_id']);
            });
        }

        // ── Warranty filter ──
        if (!empty($this->filters['warranty_status'])) {
            $today = Carbon::today();
            if ($this->filters['warranty_status'] === 'active') {
                $query->whereNotNull('warranty_end')
                      ->where('warranty_end', '>=', $today);
            } elseif ($this->filters['warranty_status'] === 'expired') {
                $query->where(function ($q) use ($today) {
                    $q->whereNull('warranty_end')
                      ->orWhere('warranty_end', '<', $today);
                });
            }
        }

        // ── Search filter ──
        if (!empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('serial_no', 'like', "%{$search}%")
                  ->orWhereHas('model', function ($mq) use ($search) {
                      $mq->where('model_name', 'like', "%{$search}%")
                         ->orWhere('brand', 'like', "%{$search}%");
                  });
            });
        }

        return $query->orderBy('serial_no')->get();
    }

    /**
     * Sheet title.
     */
    public function title(): string
    {
        return 'Inventory Serials';
    }

    /**
     * Define the headings for the export.
     */
    public function headings(): array
    {
        return [
            'Serial No',
            'Category',
            'Model',
            'Brand',
            'Hardware Type',
            'Device Type',
            'Telco',
            'SIM Quota',
            'Status',
            'Location Type',
            'Location Name',
            'GRN No',
            'GRN Date',
            'PO No',
            'Warranty Start',
            'Warranty End',
            'Warranty Status',
            'Purchase Price (MYR)',
            'Remarks',
            'Created At',
            'Updated At',
        ];
    }

    /**
     * Map the data for each row.
     */
    public function map($serial): array
    {
        return [
            $serial->serial_no,
            $serial->model->category->category_name ?? '-',
            $serial->model->model_name ?? '-',
            $serial->model->brand ?? '-',
            $serial->hardware_type ?? '-',
            $serial->device_type ?? '-',
            $serial->telco ?? '-',
            $serial->sim_quota ?? '-',
            $this->formatStatus($serial->current_status),
            ucfirst($serial->current_location_type ?? '-'),
            $this->resolveLocationName($serial),
            $serial->grn->grn_no ?? '-',
            $serial->grn_date ? $serial->grn_date->format('Y-m-d') : '-',
            $serial->purchaseOrder->po_no ?? '-',
            $serial->warranty_start ? $serial->warranty_start->format('Y-m-d') : '-',
            $serial->warranty_end ? $serial->warranty_end->format('Y-m-d') : '-',
            $this->warrantyStatus($serial),
            $serial->purchase_price ? number_format($serial->purchase_price, 2) : '-',
            $serial->remarks ?? '',
            $serial->created_at ? $serial->created_at->format('Y-m-d H:i:s') : '',
            $serial->updated_at ? $serial->updated_at->format('Y-m-d H:i:s') : '',
        ];
    }

    /**
     * Define column widths.
     */
    public function columnWidths(): array
    {
        return [
            'A' => 22, // Serial No
            'B' => 18, // Category
            'C' => 25, // Model
            'D' => 18, // Brand
            'E' => 16, // Hardware Type
            'F' => 16, // Device Type
            'G' => 14, // Telco
            'H' => 14, // SIM Quota
            'I' => 18, // Status
            'J' => 16, // Location Type
            'K' => 28, // Location Name
            'L' => 16, // GRN No
            'M' => 14, // GRN Date
            'N' => 16, // PO No
            'O' => 16, // Warranty Start
            'P' => 16, // Warranty End
            'Q' => 16, // Warranty Status
            'R' => 18, // Purchase Price
            'S' => 30, // Remarks
            'T' => 20, // Created At
            'U' => 20, // Updated At
        ];
    }

    /**
     * Apply styles to the worksheet.
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            // Header row styling
            1 => [
                'font' => [
                    'bold'  => true,
                    'size'  => 11,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '198754'], // Bootstrap success green
                ],
            ],
        ];
    }

    // ─── Helper methods ───────────────────────────────────────

    /**
     * Human-readable status.
     */
    protected function formatStatus(string $status): string
    {
        $map = [
            'in_stock'           => 'In Stock',
            'issued_to_tech'     => 'Issued to Tech',
            'installed'          => 'Installed',
            'under_service'      => 'Under Service',
            'returned_to_vendor' => 'Returned to Vendor',
            'wasted'             => 'Wasted',
            'reserved'           => 'Reserved',
        ];

        return $map[$status] ?? ucfirst(str_replace('_', ' ', $status));
    }

    /**
     * Resolve actual location name from polymorphic type + id.
     */
    protected function resolveLocationName(InventorySerial $serial): string
    {
        if (!$serial->current_location_type || !$serial->current_location_id) {
            return '-';
        }

        try {
            switch ($serial->current_location_type) {
                case 'depot':
                    $depot = Depot::find($serial->current_location_id);
                    return $depot ? "{$depot->depot_name} ({$depot->depot_code})" : 'Unknown Depot';

                case 'technician':
                    $user = User::find($serial->current_location_id);
                    return $user ? $user->name : 'Unknown Technician';

                case 'site':
                    $site = Site::find($serial->current_location_id);
                    return $site ? "{$site->site_name}" : 'Unknown Site';

                case 'vendor':
                    $vendor = \App\Models\Vendor::find($serial->current_location_id);
                    return $vendor ? $vendor->vendor_name : 'Unknown Vendor';

                default:
                    return '-';
            }
        } catch (\Exception $e) {
            return '-';
        }
    }

    /**
     * Determine warranty status label.
     */
    protected function warrantyStatus(InventorySerial $serial): string
    {
        if (!$serial->warranty_end) {
            return 'N/A';
        }

        $today = Carbon::today();

        if ($serial->warranty_end->lt($today)) {
            return 'Expired';
        }

        $daysLeft = $today->diffInDays($serial->warranty_end);
        if ($daysLeft <= 30) {
            return "Expiring ({$daysLeft}d)";
        }

        return 'Active';
    }
}
