<?php

namespace App\Exports\Reports;

use App\Models\Ticket;
use App\Models\User;
use App\Services\ReportService;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use App\Exports\Reports\ReportExportStyles;

class TicketSummaryReportExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithTitle, WithStyles
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
        return (new ReportService())->ticketSummaryQuery($this->filters, $this->user);
    }

    public function headings(): array
    {
        return [
            'Ticket No', 'Vendor', 'Merchant', 'State', 'City',
            'Job Category', 'Job Type', 'Supervisor', 'Technician',
            'Status', 'Priority', 'SLA Hours', 'SLA Status',
            'Created Date', 'Assigned Date', 'Completed Date',
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
            $t->jobCategory?->category_name ?? '',
            $t->jobType?->job_title ?? '',
            $t->supervisor?->name ?? '',
            $t->technician?->name ?? 'Unassigned',
            ucfirst(str_replace('_', ' ', $t->status)),
            ucfirst($t->priority),
            $t->sla_hours,
            ucfirst(str_replace('_', ' ', $t->sla_status ?? '')),
            $t->created_at?->format('d/m/Y'),
            $t->assigned_at?->format('d/m/Y H:i') ?? '',
            $t->completed_at?->format('d/m/Y H:i') ?? '',
        ];
    }

    public function title(): string { return 'Ticket Summary'; }
}
