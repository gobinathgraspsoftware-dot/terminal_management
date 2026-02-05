<?php

namespace App\Exports;

use App\Services\Inventory\StockValuationService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Illuminate\Support\Collection;

class StockMovementValueExport implements 
    FromCollection,
    WithHeadings,
    WithMapping,
    WithTitle,
    WithStyles,
    WithColumnWidths,
    WithEvents
{
    protected array $filters;
    protected StockValuationService $valuationService;
    protected array $movementTracking;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
        $this->valuationService = app(StockValuationService::class);
        $this->movementTracking = $this->valuationService->getMovementValueTracking($filters);
    }

    /**
     * @return Collection
     */
    public function collection()
    {
        $data = new Collection();

        // Period information
        $data->push([
            'movement_type' => 'PERIOD',
            'description' => 'From',
            'quantity' => '',
            'value' => $this->movementTracking['period']['start_date'],
        ]);

        $data->push([
            'movement_type' => '',
            'description' => 'To',
            'quantity' => '',
            'value' => $this->movementTracking['period']['end_date'],
        ]);

        $data->push([]); // Empty row

        // GRN In
        $data->push([
            'movement_type' => 'GRN IN',
            'description' => 'Goods Received',
            'quantity' => $this->movementTracking['grn_in']['quantity'],
            'value' => $this->movementTracking['grn_in']['value'],
        ]);

        $data->push([]); // Empty row

        // Outbound Movements Header
        $data->push([
            'movement_type' => 'OUTBOUND MOVEMENTS',
            'description' => '',
            'quantity' => '',
            'value' => '',
        ]);

        // Issued
        $data->push([
            'movement_type' => 'Issued to Technicians',
            'description' => 'Stock issued to field technicians',
            'quantity' => $this->movementTracking['issued']['quantity'],
            'value' => $this->movementTracking['issued']['value'],
        ]);

        // Installed
        $data->push([
            'movement_type' => 'Installed',
            'description' => 'Stock installed at sites',
            'quantity' => $this->movementTracking['installed']['quantity'],
            'value' => $this->movementTracking['installed']['value'],
        ]);

        // Wastage
        $data->push([
            'movement_type' => 'Wastage',
            'description' => 'Damaged/Scrapped items',
            'quantity' => $this->movementTracking['wastage']['quantity'],
            'value' => $this->movementTracking['wastage']['value'],
        ]);

        // Returned to Vendor
        $data->push([
            'movement_type' => 'Returned to Vendor',
            'description' => 'Stock returned to suppliers',
            'quantity' => $this->movementTracking['returned_to_vendor']['quantity'],
            'value' => $this->movementTracking['returned_to_vendor']['value'],
        ]);

        $data->push([]); // Empty row

        // Total Outbound
        $data->push([
            'movement_type' => 'TOTAL OUTBOUND',
            'description' => 'Total value of outbound movements',
            'quantity' => $this->movementTracking['issued']['quantity'] + 
                         $this->movementTracking['installed']['quantity'] +
                         $this->movementTracking['wastage']['quantity'] +
                         $this->movementTracking['returned_to_vendor']['quantity'],
            'value' => $this->movementTracking['total_out_value'],
        ]);

        return $data;
    }

    public function headings(): array
    {
        return [
            'Movement Type',
            'Description',
            'Quantity',
            'Value (MYR)',
        ];
    }

    public function map($row): array
    {
        return [
            $row['movement_type'] ?? '',
            $row['description'] ?? '',
            is_numeric($row['quantity'] ?? '') ? number_format($row['quantity'], 0) : ($row['quantity'] ?? ''),
            is_numeric($row['value'] ?? '') ? number_format($row['value'], 2) : ($row['value'] ?? ''),
        ];
    }

    public function title(): string
    {
        return 'Movement Value Tracking';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4']
                ],
                'font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 25,
            'B' => 40,
            'C' => 15,
            'D' => 20,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Bold specific rows (PERIOD, OUTBOUND MOVEMENTS, TOTAL OUTBOUND)
                foreach ($sheet->getRowIterator() as $row) {
                    $cellIterator = $row->getCellIterator();
                    $cellIterator->setIterateOnlyExistingCells(false);
                    
                    foreach ($cellIterator as $cell) {
                        $value = $cell->getValue();
                        if ($cell->getColumn() === 'A' && 
                            in_array($value, ['PERIOD', 'OUTBOUND MOVEMENTS', 'TOTAL OUTBOUND', 'GRN IN'])) {
                            $sheet->getStyle($row->getRowIndex())
                                ->getFont()
                                ->setBold(true);

                            if ($value === 'TOTAL OUTBOUND') {
                                $sheet->getStyle($row->getRowIndex())
                                    ->getFill()
                                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                                    ->getStartColor()->setRGB('FFE699');
                            }
                        }
                    }
                }
            },
        ];
    }
}
