<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class BulkSerialsTemplateExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths, ShouldAutoSize
{
    /**
     * Return sample data for template
     */
    public function array(): array
    {
        return [
            // Sample row 1
            [
                'SERIAL001',
                'TM001',
                'Terminal Model A',
                'EDC',
                'Android',
                'Maxis',
                '50GB',
                'in_stock',
                'depot',
                'DEP001',
                'Main Depot',
                'Sample remark'
            ],
            // Sample row 2
            [
                'SERIAL002',
                'TM002',
                'Terminal Model B',
                'SIM',
                '',
                'Digi',
                '30GB',
                'issued_to_tech',
                'technician',
                'TECH001',
                'John Doe',
                ''
            ],
        ];
    }

    /**
     * Define the headings for the template
     */
    public function headings(): array
    {
        return [
            'Serial No *',
            'Model Code',
            'Model Name',
            'Hardware Type',
            'Device Type',
            'Telco',
            'SIM Quota',
            'Status *',
            'Location Type *',
            'Location Code',
            'Location Name',
            'Remarks'
        ];
    }

    /**
     * Apply styles to the worksheet
     */
    public function styles(Worksheet $sheet): array
    {
        // Header styling
        $sheet->getStyle('A1:L1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4'],
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

        // Sample data styling
        $sheet->getStyle('A2:L3')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E7E6E6'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CCCCCC'],
                ],
            ],
        ]);

        // Freeze header row
        $sheet->freezePane('A2');

        // Add instructions in a separate sheet would be ideal, but for now add a note
        $sheet->getComment('A1')->getText()->createTextRun(
            "INSTRUCTIONS:\n" .
            "1. Serial No is required and must be unique\n" .
            "2. Model Code OR Model Name is required\n" .
            "3. Status values: in_stock, issued_to_tech, installed, under_service, returned_to_vendor, wasted, reserved\n" .
            "4. Location Type values: depot, technician, site, vendor\n" .
            "5. Location Code or Location Name is required\n" .
            "6. Delete sample rows before importing\n" .
            "7. Fields marked with * are required"
        );

        return [];
    }

    /**
     * Define column widths
     */
    public function columnWidths(): array
    {
        return [
            'A' => 15,  // Serial No
            'B' => 12,  // Model Code
            'C' => 20,  // Model Name
            'D' => 15,  // Hardware Type
            'E' => 12,  // Device Type
            'F' => 10,  // Telco
            'G' => 12,  // SIM Quota
            'H' => 15,  // Status
            'I' => 15,  // Location Type
            'J' => 15,  // Location Code
            'K' => 20,  // Location Name
            'L' => 25,  // Remarks
        ];
    }
}
