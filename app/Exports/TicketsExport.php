<?php

namespace App\Exports;

use App\Models\Ticket;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class TicketsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithTitle
{
    protected $user;
    protected array $filters;

    public function __construct($user, array $filters = [])
    {
        $this->user = $user;
        $this->filters = $filters;
    }

    public function query()
    {
        $query = Ticket::with(['vendor', 'vendorBranch', 'state', 'city', 'supervisor', 'technician', 'jobCategory', 'jobType', 'accessoryItem'])
            ->visibleTo($this->user);

        if (!empty($this->filters['status']))         $query->where('status', $this->filters['status']);
        if (!empty($this->filters['priority']))       $query->where('priority', $this->filters['priority']);
        if (!empty($this->filters['vendor_id']))      $query->where('vendor_id', $this->filters['vendor_id']);
        if (!empty($this->filters['supervisor_id']))  $query->where('supervisor_id', $this->filters['supervisor_id']);
        if (!empty($this->filters['date_from']))      $query->whereDate('created_at', '>=', $this->filters['date_from']);
        if (!empty($this->filters['date_to']))        $query->whereDate('created_at', '<=', $this->filters['date_to']);

        return $query->orderBy('created_at', 'desc');
    }

    public function headings(): array
    {
        return [
            'Ticket No',
            'Vendor Ref',
            'Vendor',
            'Branch',
            'State',
            'City',
            'TID',
            'Terminal ID',
            'Router ID',
            'Old Router ID',
            'Accessory Type',
            'Accessory Item',
            'Accessory Qty',
            'Merchant Name',
            'Contact',
            'Job Category',
            'Job Type',
            'Price (RM)',
            'Status',
            'Priority',
            'Supervisor',
            'Supervisor Type',
            'Technician',
            'Expected Start',
            'Expected End',
            'SLA Deadline',
            'SLA Status',
            'Mileage',
            'Mileage Rate',
            'Mileage Amount',
            'Toll',
            'Standby/Meal',
            'Total Claim',
            'Created At',
            'Assigned At',
            'Accepted At',
            'Completed At',
        ];
    }

    public function map($ticket): array
    {
        return [
            $ticket->ticket_no,
            $ticket->vendor_ticket_ref_no ?? '',
            $ticket->vendor?->vendor_name ?? '',
            $ticket->vendorBranch?->branch_name ?? '',
            $ticket->state?->name ?? '',
            $ticket->city?->name ?? '',
            $ticket->tid ?? '',
            $ticket->terminal_id ?? '',
            $ticket->router_id ?? '',
            $ticket->old_terminal_id ?? '',
            $ticket->getAccessoryTypeLabel(),
            $ticket->accessoryItem?->item_name ?? '',
            $ticket->accessory_qty ?? '',
            $ticket->merchant_name ?? '',
            $ticket->contact_number ?? '',
            $ticket->jobCategory?->category_name ?? '',
            $ticket->jobType?->job_title ?? '',
            number_format($ticket->price ?? 0, 2),
            ucfirst(str_replace('_', ' ', $ticket->status)),
            ucfirst($ticket->priority),
            $ticket->supervisor?->name ?? '',
            ucfirst($ticket->supervisor?->supervisor_type ?? ''),
            $ticket->technician?->name ?? 'Unassigned',
            $ticket->expected_start_date?->format('d/m/Y') ?? '',
            $ticket->expected_end_date?->format('d/m/Y') ?? '',
            $ticket->sla_deadline?->format('d/m/Y H:i') ?? '',
            ucfirst(str_replace('_', ' ', $ticket->sla_status ?? '')),
            $ticket->mileage ?? 0,
            $ticket->mileage_rate ?? 0,
            $ticket->mileage_amount ?? 0,
            $ticket->toll ?? 0,
            $ticket->standby_meal ?? 0,
            $ticket->total_claim_amount ?? 0,
            $ticket->created_at?->format('d/m/Y H:i'),
            $ticket->assigned_at?->format('d/m/Y H:i') ?? '',
            $ticket->accepted_at?->format('d/m/Y H:i') ?? '',
            $ticket->completed_at?->format('d/m/Y H:i') ?? '',
        ];
    }

    public function title(): string
    {
        return 'Tickets';
    }
}
