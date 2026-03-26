<?php

namespace App\Exports\Reports;

use App\Models\User;
use App\Services\ReportService;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class SlaReportExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithTitle
{
    protected array $filters;
    protected ?User $user;

    public function __construct(array $filters = [], ?User $user = null)
    {
        $this->filters = $filters;
        $this->user    = $user;
    }

    public function query()
    {
        return (new ReportService())->slaReportQuery($this->filters, $this->user);
    }

    public function headings(): array
    {
        return [
            'Ticket No', 'Vendor', 'Merchant', 'Job Type',
            'Supervisor', 'Technician', 'Status',
            'SLA Hours', 'SLA Deadline', 'SLA Status',
            'Rescheduled', 'Created Date', 'Completed Date',
        ];
    }

    public function map($t): array
    {
        return [
            $t->ticket_no,
            $t->vendor?->vendor_name ?? '',
            $t->merchant_name ?? '',
            $t->jobType?->job_title ?? '',
            $t->supervisor?->name ?? '',
            $t->technician?->name ?? 'Unassigned',
            ucfirst(str_replace('_', ' ', $t->status)),
            $t->sla_hours,
            $t->sla_deadline?->format('d/m/Y H:i') ?? '',
            ucfirst(str_replace('_', ' ', $t->sla_status ?? 'N/A')),
            $t->rescheduled_at ? 'Yes' : 'No',
            $t->created_at?->format('d/m/Y'),
            $t->completed_at?->format('d/m/Y H:i') ?? '',
        ];
    }

    public function title(): string { return 'SLA Report'; }
}
