<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StockReturnsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function query()
    {
        return $this->query->with([
            'fromTechnician:id,name',
            'toDepot:id,depot_name',
            'lines.model:id,model_name'
        ]);
    }

    public function headings(): array
    {
        return [
            'Return No',
            'Return Date',
            'From Technician',
            'To Depot',
            'Total Items',
            'Status',
            'Has Damaged Items',
            'Remarks',
            'Created By',
            'Posted Date',
        ];
    }

    public function map($stockReturn): array
    {
        // Check if has damaged/defective items
        $hasDamaged = $stockReturn->lines->contains(function($line) {
            return in_array($line->condition, ['damaged', 'defective']);
        });

        return [
            $stockReturn->issue_no,
            $stockReturn->issue_date->format('Y-m-d'),
            $stockReturn->fromTechnician->name ?? '-',
            $stockReturn->toDepot->depot_name ?? '-',
            $stockReturn->total_items,
            ucfirst($stockReturn->status),
            $hasDamaged ? 'Yes' : 'No',
            $stockReturn->remarks ?? '-',
            $stockReturn->creator->name ?? '-',
            $stockReturn->posted_at ? $stockReturn->posted_at->format('Y-m-d') : '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E2E8F0']
                ]
            ],
        ];
    }
}
