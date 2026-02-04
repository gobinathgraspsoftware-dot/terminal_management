<?php

namespace App\Exports;

use App\Models\InventorySerial;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class SerialLabelsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, ShouldAutoSize
{
    protected array $serialIds;

    public function __construct(array $serialIds)
    {
        $this->serialIds = $serialIds;
    }

    /**
     * Get the collection to export
     */
    public function collection()
    {
        return InventorySerial::with(['terminalModel', 'terminalModel.category'])
            ->whereIn('id', $this->serialIds)
            ->get();
    }

    /**
     * Define the headings
     */
    public function headings(): array
    {
        return [
            'Serial No',
            'Model Code',
            'Model Name',
            'Category',
            'Hardware Type',
            'Device Type',
            'Status',
            'Current Location',
            'Location Type',
        ];
    }

    /**
     * Map the data for each row
     */
    public function map($serial): array
    {
        return [
            $serial->serial_no,
            $serial->terminalModel->model_code ?? '',
            $serial->terminalModel->model_name ?? '',
            $serial->terminalModel->category->category_name ?? '',
            $serial->hardware_type ?? '',
            $serial->device_type ?? '',
            ucfirst(str_replace('_', ' ', $serial->current_status)),
            $serial->location_name,
            ucfirst($serial->current_location_type),
        ];
    }

    /**
     * Apply styles to the worksheet
     */
    public function styles(Worksheet $sheet): array
    {
        // Header styling
        $sheet->getStyle('A1:I1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0070C0'],
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

        // Auto-filter
        $sheet->setAutoFilter('A1:I1');

        // Freeze header row
        $sheet->freezePane('A2');

        return [];
    }

    /**
     * Define column widths
     */
    public function columnWidths(): array
    {
        return [
            'A' => 18,  // Serial No
            'B' => 15,  // Model Code
            'C' => 25,  // Model Name
            'D' => 20,  // Category
            'E' => 15,  // Hardware Type
            'F' => 15,  // Device Type
            'G' => 18,  // Status
            'H' => 25,  // Current Location
            'I' => 15,  // Location Type
        ];
    }
}
