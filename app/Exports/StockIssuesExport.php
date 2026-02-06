<?php

namespace App\Exports;

use App\Models\StockIssue;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class StockIssuesExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    use Exportable;

    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    /**
     * @return \Illuminate\Database\Query\Builder
     */
    public function query()
    {
        return $this->query->with([
            'fromDepot:id,depot_name',
            'toDepot:id,depot_name',
            'toTechnician:id,name',
            'fromTechnician:id,name',
            'lines.model:id,model_name',
        ]);
    }

    /**
     * Define the headings for the Excel export
     */
    public function headings(): array
    {
        return [
            'Issue No',
            'Issue Date',
            'Issue Type',
            'From Location',
            'To Location',
            'Total Items',
            'Status',
            'Posted At',
            'Remarks',
            // Line details
            'Line No',
            'Model',
            'Serial No',
            'Quantity',
        ];
    }

    /**
     * Map data for each row
     */
    public function map($stockIssue): array
    {
        $rows = [];

        if ($stockIssue->lines->isEmpty()) {
            // If no lines, export header only
            $rows[] = [
                $stockIssue->issue_no,
                $stockIssue->issue_date->format('Y-m-d'),
                ucwords(str_replace('_', ' ', $stockIssue->issue_type)),
                $this->getFromLocation($stockIssue),
                $this->getToLocation($stockIssue),
                $stockIssue->total_items,
                strtoupper($stockIssue->status),
                $stockIssue->posted_at ? $stockIssue->posted_at->format('Y-m-d H:i') : '-',
                $stockIssue->remarks ?? '-',
                '',
                '',
                '',
                '',
            ];
        } else {
            // Export each line as a separate row
            foreach ($stockIssue->lines as $index => $line) {
                $rows[] = [
                    $index === 0 ? $stockIssue->issue_no : '',
                    $index === 0 ? $stockIssue->issue_date->format('Y-m-d') : '',
                    $index === 0 ? ucwords(str_replace('_', ' ', $stockIssue->issue_type)) : '',
                    $index === 0 ? $this->getFromLocation($stockIssue) : '',
                    $index === 0 ? $this->getToLocation($stockIssue) : '',
                    $index === 0 ? $stockIssue->total_items : '',
                    $index === 0 ? strtoupper($stockIssue->status) : '',
                    $index === 0 ? ($stockIssue->posted_at ? $stockIssue->posted_at->format('Y-m-d H:i') : '-') : '',
                    $index === 0 ? ($stockIssue->remarks ?? '-') : '',
                    $line->line_no,
                    $line->model->model_name ?? '-',
                    $line->serial_no ?? '-',
                    $line->quantity,
                ];
            }
        }

        return $rows;
    }

    /**
     * Get from location text
     */
    protected function getFromLocation($stockIssue): string
    {
        if ($stockIssue->issue_type === StockIssue::TYPE_ISSUE_TO_TECH) {
            return 'Depot: ' . ($stockIssue->fromDepot->depot_name ?? '-');
        } else {
            return 'Technician: ' . ($stockIssue->fromTechnician->name ?? '-');
        }
    }

    /**
     * Get to location text
     */
    protected function getToLocation($stockIssue): string
    {
        if ($stockIssue->issue_type === StockIssue::TYPE_ISSUE_TO_TECH) {
            return 'Technician: ' . ($stockIssue->toTechnician->name ?? '-');
        } else {
            return 'Depot: ' . ($stockIssue->toDepot->depot_name ?? '-');
        }
    }

    /**
     * Apply styles to the worksheet
     */
    public function styles(Worksheet $sheet)
    {
        // Header row styling
        $sheet->getStyle('A1:M1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0d6efd'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Auto-size columns
        foreach (range('A', 'M') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return [];
    }

    /**
     * Define column widths
     */
    public function columnWidths(): array
    {
        return [
            'A' => 15, // Issue No
            'B' => 12, // Issue Date
            'C' => 20, // Issue Type
            'D' => 25, // From Location
            'E' => 25, // To Location
            'F' => 12, // Total Items
            'G' => 12, // Status
            'H' => 18, // Posted At
            'I' => 30, // Remarks
            'J' => 10, // Line No
            'K' => 25, // Model
            'L' => 20, // Serial No
            'M' => 12, // Quantity
        ];
    }
}
