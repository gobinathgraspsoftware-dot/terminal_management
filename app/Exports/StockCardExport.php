<?php

namespace App\Exports;

use App\Services\StockReportService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StockCardExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithTitle, WithEvents
{
    protected int $serialId;
    protected StockReportService $stockReportService;
    protected $stockCardData;

    public function __construct(int $serialId)
    {
        $this->serialId = $serialId;
        $this->stockReportService = app(StockReportService::class);
        $this->stockCardData = $this->stockReportService->getStockCard($serialId);
    }

    /**
     * Get movements collection
     */
    public function collection()
    {
        return collect($this->stockCardData['movements']);
    }

    /**
     * Define headings
     */
    public function headings(): array
    {
        return [
            'Date',
            'Transaction No',
            'Type',
            'Quantity In',
            'Quantity Out',
            'Running Balance',
            'From Location',
            'To Location',
            'Reference',
            'Created By',
            'Remarks',
        ];
    }

    /**
     * Map data rows
     */
    public function map($movement): array
    {
        $quantityIn = $movement->quantity > 0 ? $movement->quantity : '';
        $quantityOut = $movement->quantity < 0 ? abs($movement->quantity) : '';

        return [
            $movement->transaction_date ? $movement->transaction_date->format('Y-m-d') : '',
            $movement->transaction_no,
            $movement->type_label,
            $quantityIn,
            $quantityOut,
            $movement->running_balance ?? 0,
            $movement->from_location_name,
            $movement->to_location_name,
            $movement->reference_label,
            $movement->createdBy ? $movement->createdBy->name : '-',
            $movement->remarks ?? '',
        ];
    }

    /**
     * Apply styles
     */
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

    /**
     * Define column widths
     */
    public function columnWidths(): array
    {
        return [
            'A' => 12,  // Date
            'B' => 18,  // Transaction No
            'C' => 25,  // Type
            'D' => 12,  // Quantity In
            'E' => 12,  // Quantity Out
            'F' => 15,  // Running Balance
            'G' => 20,  // From Location
            'H' => 20,  // To Location
            'I' => 20,  // Reference
            'J' => 20,  // Created By
            'K' => 30,  // Remarks
        ];
    }

    /**
     * Sheet title
     */
    public function title(): string
    {
        return 'Stock Card';
    }

    /**
     * Register events
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $serial = $this->stockCardData['serial'];

                // Add serial information at the top
                $sheet->insertNewRowBefore(1, 5);
                
                $sheet->setCellValue('A1', 'STOCK CARD');
                $sheet->setCellValue('A2', 'Serial Number:');
                $sheet->setCellValue('B2', $serial->serial_no);
                $sheet->setCellValue('A3', 'Model:');
                $sheet->setCellValue('B3', $serial->model ? $serial->model->model_name : 'N/A');
                $sheet->setCellValue('A4', 'Current Status:');
                $sheet->setCellValue('B4', ucfirst(str_replace('_', ' ', $serial->current_status)));
                $sheet->setCellValue('A5', 'Current Location:');
                $sheet->setCellValue('B5', $serial->current_location_name ?? 'N/A');

                // Style the header
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A2:A5')->getFont()->setBold(true);

                // Add summary at the bottom
                $lastRow = $sheet->getHighestRow() + 2;
                $sheet->setCellValue('A' . $lastRow, 'SUMMARY');
                $sheet->setCellValue('A' . ($lastRow + 1), 'Total Movements:');
                $sheet->setCellValue('B' . ($lastRow + 1), $this->stockCardData['movement_count']);
                $sheet->setCellValue('A' . ($lastRow + 2), 'Total In:');
                $sheet->setCellValue('B' . ($lastRow + 2), $this->stockCardData['total_in']);
                $sheet->setCellValue('A' . ($lastRow + 3), 'Total Out:');
                $sheet->setCellValue('B' . ($lastRow + 3), $this->stockCardData['total_out']);
                $sheet->setCellValue('A' . ($lastRow + 4), 'Current Balance:');
                $sheet->setCellValue('B' . ($lastRow + 4), $this->stockCardData['current_balance']);

                $sheet->getStyle('A' . $lastRow)->getFont()->setBold(true);
                $sheet->getStyle('A' . ($lastRow + 1) . ':A' . ($lastRow + 4))->getFont()->setBold(true);
            },
        ];
    }
}
