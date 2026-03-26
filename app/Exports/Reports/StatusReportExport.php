<?php

namespace App\Exports\Reports;

use App\Models\User;
use App\Services\ReportService;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use App\Exports\Reports\ReportExportStyles;

class StatusReportExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithTitle, WithStyles
{
    use ReportExportStyles;

    protected array $filters;
    protected ?User $user;

    public function __construct(array $filters = [], ?User $user = null)
    {
        $this->filters = $filters;
        $this->user    = $user;
    }

    public function query()
    {
        return (new ReportService())->statusReportQuery($this->filters, $this->user);
    }

    public function headings(): array
    {
        return [
            'Ticket No', 'Vendor', 'Merchant', 'State', 'City',
            'Job Type', 'Supervisor', 'Technician', 'Status',
            'SLA Status', 'Created Date',
        ];
    }

    public function map($t): array
    {
        return [
            $t->ticket_no,
            $t->vendor?->vendor_name ?? '',
            $t->merchant_name ?? '',
            $t->state?->name ?? '',
            $t->city?->name ?? '',
            $t->jobType?->job_title ?? '',
            $t->supervisor?->name ?? '',
            $t->technician?->name ?? 'Unassigned',
            ucfirst(str_replace('_', ' ', $t->status)),
            ucfirst(str_replace('_', ' ', $t->sla_status ?? '')),
            $t->created_at?->format('d/m/Y'),
        ];
    }

    public function title(): string { return 'Status Report'; }
}
