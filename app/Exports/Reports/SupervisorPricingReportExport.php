<?php

namespace App\Exports\Reports;

use App\Services\ReportService;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use App\Exports\Reports\ReportExportStyles;

class SupervisorPricingReportExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithTitle, WithStyles
{
    use ReportExportStyles;

    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        return (new ReportService())->supervisorPricingQuery($this->filters);
    }

    public function headings(): array
    {
        return ['Supervisor', 'Job Category', 'Job Type', 'Price (RM)'];
    }

    public function map($p): array
    {
        return [
            $p->supervisor?->name ?? '',
            $p->jobCategory?->category_name ?? '',
            $p->jobType?->job_title ?? '',
            number_format((float) $p->price, 2),
        ];
    }

    public function title(): string { return 'Supervisor Pricing'; }
}
