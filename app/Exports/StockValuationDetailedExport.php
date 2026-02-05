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

class StockValuationDetailedExport implements 
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

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
        $this->valuationService = app(StockValuationService::class);
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->valuationService->getDetailedValuation($this->filters);
    }

    public function headings(): array
    {
        return [
            'Location Type',
            'Location Name',
            'Category',
            'Model',
            'Quantity',
            'Average Cost (MYR)',
            'Total Value (MYR)',
            'Last Movement Date',
        ];
    }

    public function map($row): array
    {
        return [
            $row['location_type'],
            $row['location_name'],
            $row['category_name'],
            $row['model_name'],
            $row['quantity_on_hand'],
            number_format($row['average_cost'], 2),
            number_format($row['total_value'], 2),
            $row['last_movement_date'] ?? 'N/A',
        ];
    }

    public function title(): string
    {
        return 'Detailed Valuation';
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
            'A' => 15,
            'B' => 30,
            'C' => 20,
            'D' => 30,
            'E' => 12,
            'F' => 18,
            'G' => 18,
            'H' => 18,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Format quantity column
                $highestRow = $sheet->getHighestRow();
                $sheet->getStyle('E2:E' . $highestRow)
                    ->getNumberFormat()
                    ->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

                // Add totals row
                $totalRow = $highestRow + 2;
                $sheet->setCellValue('D' . $totalRow, 'TOTAL:');
                $sheet->setCellValue('E' . $totalRow, '=SUM(E2:E' . $highestRow . ')');
                $sheet->setCellValue('G' . $totalRow, '=SUM(G2:G' . $highestRow . ')');

                $sheet->getStyle('D' . $totalRow . ':G' . $totalRow)
                    ->getFont()
                    ->setBold(true);

                $sheet->getStyle('D' . $totalRow . ':G' . $totalRow)
                    ->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('E7E6E6');
            },
        ];
    }
}
