<?php

namespace App\Exports\Reports;

use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Shared styling trait for all Report Exports.
 *
 * Usage: Add this trait to any export class and implement WithStyles:
 *
 *   use ReportExportStyles;
 *
 *   class MyExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithTitle, WithStyles
 *   {
 *       use ReportExportStyles;
 *       ...
 *   }
 */
trait ReportExportStyles
{
    /**
     * Apply styles to the worksheet.
     * Implements Maatwebsite\Excel\Concerns\WithStyles
     */
    public function styles(Worksheet $sheet): array
    {
        // Determine the last column letter based on headings count
        $lastCol = $this->getLastColumnLetter();

        // Style header row
        $headerRange = 'A1:' . $lastCol . '1';

        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 10,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4'], // TMS Blue
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '2F5496'],
                ],
            ],
        ]);

        // Set header row height
        $sheet->getRowDimension(1)->setRowHeight(25);

        // Apply light borders to all data cells
        $lastRow = $sheet->getHighestRow();
        if ($lastRow > 1) {
            $dataRange = 'A2:' . $lastCol . $lastRow;
            $sheet->getStyle($dataRange)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'D9E2F3'],
                    ],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);

            // Alternate row shading
            for ($row = 2; $row <= $lastRow; $row++) {
                if ($row % 2 === 0) {
                    $sheet->getStyle('A' . $row . ':' . $lastCol . $row)->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'F2F6FC'],
                        ],
                    ]);
                }
            }
        }

        // Freeze header row
        $sheet->freezePane('A2');

        return [];
    }

    /**
     * Get the last column letter based on headings count.
     */
    protected function getLastColumnLetter(): string
    {
        $count = count($this->headings());
        $letter = '';

        while ($count > 0) {
            $count--;
            $letter = chr(65 + ($count % 26)) . $letter;
            $count = intdiv($count, 26);
        }

        return $letter;
    }
}
