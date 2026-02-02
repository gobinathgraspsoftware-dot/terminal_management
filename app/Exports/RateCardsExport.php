<?php

namespace App\Exports;

use App\Models\RateCard;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RateCardsExport implements 
    FromCollection, 
    WithHeadings, 
    WithMapping, 
    WithStyles, 
    WithColumnWidths, 
    WithTitle
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $query = RateCard::with('model');

        // Apply filters
        if (!empty($this->filters['job_type'])) {
            $query->where('job_type', $this->filters['job_type']);
        }

        if (!empty($this->filters['model_id'])) {
            $query->where('model_id', $this->filters['model_id']);
        }

        if (!empty($this->filters['state'])) {
            $query->where('state', $this->filters['state']);
        }

        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (!empty($this->filters['search'])) {
            $searchValue = $this->filters['search'];
            $query->where(function($q) use ($searchValue) {
                $q->where('rate_card_code', 'like', "%{$searchValue}%")
                  ->orWhere('rate_card_name', 'like', "%{$searchValue}%")
                  ->orWhere('description', 'like', "%{$searchValue}%");
            });
        }

        return $query->orderBy('rate_card_code')->get();
    }

    /**
     * Define column headings
     */
    public function headings(): array
    {
        return [
            'Rate Card Code',
            'Rate Card Name',
            'Description',
            'Job Type',
            'Terminal Model',
            'State',
            'Calculation Type',
            'Rate Amount',
            'Min Amount',
            'Max Amount',
            'Effective From',
            'Effective To',
            'Status',
            'Is Effective',
            'Created Date',
        ];
    }

    /**
     * Map data for each row
     */
    public function map($rateCard): array
    {
        return [
            $rateCard->rate_card_code,
            $rateCard->rate_card_name,
            $rateCard->description,
            $rateCard->job_type_display,
            $rateCard->model ? $rateCard->model->model_name : 'All Models',
            $rateCard->state ?? 'All States',
            $rateCard->calculation_type_display,
            $rateCard->rate_amount,
            $rateCard->min_amount,
            $rateCard->max_amount,
            $rateCard->effective_from->format('d M Y'),
            $rateCard->effective_to ? $rateCard->effective_to->format('d M Y') : 'No End Date',
            ucfirst($rateCard->status),
            $rateCard->is_effective ? 'Yes' : 'No',
            $rateCard->created_at->format('d M Y H:i'),
        ];
    }

    /**
     * Apply styles to the worksheet
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Style the header row
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4'],
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    /**
     * Define column widths
     */
    public function columnWidths(): array
    {
        return [
            'A' => 18, // Rate Card Code
            'B' => 30, // Rate Card Name
            'C' => 40, // Description
            'D' => 15, // Job Type
            'E' => 25, // Terminal Model
            'F' => 20, // State
            'G' => 18, // Calculation Type
            'H' => 15, // Rate Amount
            'I' => 15, // Min Amount
            'J' => 15, // Max Amount
            'K' => 15, // Effective From
            'L' => 15, // Effective To
            'M' => 12, // Status
            'N' => 15, // Is Effective
            'O' => 18, // Created Date
        ];
    }

    /**
     * Set worksheet title
     */
    public function title(): string
    {
        return 'Rate Cards';
    }
}
