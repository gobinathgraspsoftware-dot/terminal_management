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

class StockValuationSummaryExport implements 
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
    protected array $summary;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
        $this->valuationService = app(StockValuationService::class);
        $this->summary = $this->valuationService->getValuationSummary($filters);
    }

    /**
     * @return Collection
     */
    public function collection()
    {
        // Combine all sections
        $collection = new Collection();

        // Summary Header
        $collection->push([
            'section' => 'TOTAL SUMMARY',
            'name' => 'Total Inventory Value',
            'quantity' => $this->summary['total_quantity'],
            'value' => $this->summary['total_value'],
        ]);

        $collection->push([]); // Empty row

        // By Depot
        $collection->push([
            'section' => 'BY DEPOT',
            'name' => '',
            'quantity' => '',
            'value' => '',
        ]);

        foreach ($this->summary['by_depot'] as $depot) {
            $collection->push([
                'section' => '',
                'name' => $depot['depot_name'],
                'quantity' => $depot['quantity'],
                'value' => $depot['value'],
            ]);
        }

        $collection->push([]); // Empty row

        // By Category
        $collection->push([
            'section' => 'BY CATEGORY',
            'name' => '',
            'quantity' => '',
            'value' => '',
        ]);

        foreach ($this->summary['by_category'] as $category) {
            $collection->push([
                'section' => '',
                'name' => $category['category_name'],
                'quantity' => $category['quantity'],
                'value' => $category['value'],
            ]);
        }

        $collection->push([]); // Empty row

        // By Model
        $collection->push([
            'section' => 'BY MODEL',
            'name' => 'Model',
            'quantity' => 'Quantity',
            'value' => 'Total Value',
            'avg_cost' => 'Avg Cost',
            'category' => 'Category',
        ]);

        foreach ($this->summary['by_model'] as $model) {
            $collection->push([
                'section' => '',
                'name' => $model['model_name'],
                'quantity' => $model['quantity'],
                'value' => $model['value'],
                'avg_cost' => $model['avg_cost'],
                'category' => $model['category_name'],
            ]);
        }

        return $collection;
    }

    public function headings(): array
    {
        return [
            'Section',
            'Description',
            'Quantity',
            'Total Value (MYR)',
            'Avg Cost (MYR)',
            'Category',
        ];
    }

    public function map($row): array
    {
        return [
            $row['section'] ?? '',
            $row['name'] ?? '',
            $row['quantity'] ?? '',
            $row['value'] ?? '',
            $row['avg_cost'] ?? '',
            $row['category'] ?? '',
        ];
    }

    public function title(): string
    {
        return 'Stock Valuation Summary';
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
            'A' => 20,
            'B' => 35,
            'C' => 15,
            'D' => 20,
            'E' => 20,
            'F' => 25,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Format currency columns
                $highestRow = $sheet->getHighestRow();
                $sheet->getStyle('D2:E' . $highestRow)
                    ->getNumberFormat()
                    ->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

                // Bold section headers
                foreach ($sheet->getRowIterator() as $row) {
                    $cellIterator = $row->getCellIterator();
                    $cellIterator->setIterateOnlyExistingCells(false);
                    
                    foreach ($cellIterator as $cell) {
                        if ($cell->getColumn() === 'A' && !empty($cell->getValue())) {
                            $sheet->getStyle($row->getRowIndex())
                                ->getFont()
                                ->setBold(true);
                        }
                    }
                }
            },
        ];
    }
}
