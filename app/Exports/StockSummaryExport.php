<?php

namespace App\Exports;

use App\Services\StockReportService;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class StockSummaryExport implements WithMultipleSheets
{
    protected array $filters;
    protected StockReportService $stockReportService;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
        $this->stockReportService = app(StockReportService::class);
    }

    /**
     * Return multiple sheets
     */
    public function sheets(): array
    {
        $sheets = [];

        // Sheet 1: Summary Statistics
        $sheets[] = new SummaryStatisticsSheet($this->filters);

        // Sheet 2: By Location
        $sheets[] = new ByLocationSheet($this->filters);

        // Sheet 3: By Model
        $sheets[] = new ByModelSheet($this->filters);

        // Sheet 4: Top Movers
        $sheets[] = new TopMoversSheet($this->filters);

        return $sheets;
    }
}

// ============================================================================
// Summary Statistics Sheet
// ============================================================================

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SummaryStatisticsSheet implements FromArray, WithTitle, WithStyles
{
    protected array $filters;
    protected StockReportService $stockReportService;

    public function __construct(array $filters)
    {
        $this->filters = $filters;
        $this->stockReportService = app(StockReportService::class);
    }

    public function array(): array
    {
        $summary = $this->stockReportService->getMovementSummary($this->filters);

        $data = [
            ['STOCK MOVEMENT SUMMARY'],
            [''],
            ['Metric', 'Value'],
            ['Total Movements', $summary['total_movements']],
            ['Total Quantity In', $summary['total_in']],
            ['Total Quantity Out', abs($summary['total_out'])],
            ['Net Movement', $summary['total_in'] + $summary['total_out']],
            [''],
            ['MOVEMENTS BY TYPE'],
            ['Type', 'Count'],
        ];

        foreach ($summary['by_type'] as $type => $count) {
            $data[] = [\App\Models\StockLedger::TYPE_OPTIONS[$type] ?? $type, $count];
        }

        return $data;
    }

    public function title(): string
    {
        return 'Summary';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            3 => ['font' => ['bold' => true]],
            9 => ['font' => ['bold' => true]],
            10 => ['font' => ['bold' => true]],
        ];
    }
}

// ============================================================================
// By Location Sheet
// ============================================================================

class ByLocationSheet implements FromArray, WithTitle, WithStyles
{
    protected array $filters;
    protected StockReportService $stockReportService;

    public function __construct(array $filters)
    {
        $this->filters = $filters;
        $this->stockReportService = app(StockReportService::class);
    }

    public function array(): array
    {
        $byLocation = $this->stockReportService->getMovementByLocation($this->filters);

        $data = [
            ['MOVEMENTS BY LOCATION'],
            [''],
            ['From', 'To', 'Type', 'Count', 'Quantity'],
        ];

        foreach ($byLocation as $movement) {
            $data[] = [
                $movement['from'],
                $movement['to'],
                $movement['type'],
                $movement['count'],
                $movement['quantity'],
            ];
        }

        return $data;
    }

    public function title(): string
    {
        return 'By Location';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            3 => ['font' => ['bold' => true]],
        ];
    }
}

// ============================================================================
// By Model Sheet
// ============================================================================

class ByModelSheet implements FromArray, WithTitle, WithStyles
{
    protected array $filters;
    protected StockReportService $stockReportService;

    public function __construct(array $filters)
    {
        $this->filters = $filters;
        $this->stockReportService = app(StockReportService::class);
    }

    public function array(): array
    {
        $byModel = $this->stockReportService->getMovementByModel($this->filters);

        $data = [
            ['MOVEMENTS BY MODEL'],
            [''],
            ['Model', 'Category', 'Total Movements', 'Total In', 'Total Out'],
        ];

        foreach ($byModel as $model) {
            $data[] = [
                $model['model'],
                $model['category'],
                $model['movement_count'],
                $model['total_in'],
                $model['total_out'],
            ];
        }

        return $data;
    }

    public function title(): string
    {
        return 'By Model';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            3 => ['font' => ['bold' => true]],
        ];
    }
}

// ============================================================================
// Top Movers Sheet
// ============================================================================

class TopMoversSheet implements FromArray, WithTitle, WithStyles
{
    protected array $filters;
    protected StockReportService $stockReportService;

    public function __construct(array $filters)
    {
        $this->filters = $filters;
        $this->stockReportService = app(StockReportService::class);
    }

    public function array(): array
    {
        $topMovers = $this->stockReportService->getTopMovers(10, $this->filters);

        $data = [
            ['TOP 10 MOVING MODELS'],
            [''],
            ['Rank', 'Model', 'Category', 'Movement Count', 'Unique Serials'],
        ];

        $rank = 1;
        foreach ($topMovers as $mover) {
            $data[] = [
                $rank++,
                $mover['model_name'],
                $mover['category'],
                $mover['movement_count'],
                $mover['unique_serials'],
            ];
        }

        return $data;
    }

    public function title(): string
    {
        return 'Top Movers';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            3 => ['font' => ['bold' => true]],
        ];
    }
}
