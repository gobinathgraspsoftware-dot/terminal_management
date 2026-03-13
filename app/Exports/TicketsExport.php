<?php

namespace App\Exports;

use App\Models\Ticket;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TicketsExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    protected $user;
    protected $filters;

    public function __construct($user, array $filters = [])
    {
        $this->user = $user;
        $this->filters = $filters;
    }

    public function query()
    {
        $query = Ticket::with(['vendor', 'vendorBranch', 'state', 'city', 'supervisor', 'technician', 'jobType'])
            ->visibleTo($this->user);

        if (!empty($this->filters['status'])) $query->where('status', $this->filters['status']);
        if (!empty($this->filters['vendor_id'])) $query->where('vendor_id', $this->filters['vendor_id']);

        return $query->orderBy('created_at', 'desc');
    }

    public function headings(): array
    {
        return [
            'Ticket #', 'Vendor Ref', 'Vendor', 'Branch', 'State', 'District',
            'TID', 'Merchant', 'Contact', 'Job Type', 'Status', 'Priority',
            'Supervisor', 'Technician', 'SLA Hours', 'SLA Deadline',
            'Mileage', 'Mileage Rate', 'Mileage Claim', 'Toll', 'Standby/Meal', 'Total Claim',
            'Created At',
        ];
    }

    public function map($ticket): array
    {
        return [
            $ticket->ticket_no,
            $ticket->vendor_ticket_ref_no,
            $ticket->vendor?->vendor_name,
            $ticket->vendorBranch?->branch_name,
            $ticket->state?->name,
            $ticket->city?->name,
            $ticket->tid,
            $ticket->merchant_name,
            $ticket->contact_number,
            $ticket->jobType?->job_title,
            $ticket->status,
            $ticket->priority,
            $ticket->supervisor?->name,
            $ticket->technician?->name,
            $ticket->sla_hours,
            $ticket->sla_deadline?->format('Y-m-d H:i'),
            $ticket->mileage,
            $ticket->mileage_rate,
            $ticket->mileage_amount,
            $ticket->toll,
            $ticket->standby_meal,
            $ticket->total_claim_amount,
            $ticket->created_at->format('Y-m-d H:i'),
        ];
    }
}
