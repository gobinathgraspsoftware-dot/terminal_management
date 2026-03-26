<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 9px; color: #333; }
        .header { text-align: center; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #667eea; }
        .header h1 { font-size: 16px; color: #667eea; margin-bottom: 3px; }
        .header p { font-size: 9px; color: #666; }
        .meta-info { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 8px; color: #888; }
        .meta-info span { display: inline-block; margin-right: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 5px; }
        table th { background-color: #667eea; color: #fff; font-size: 8px; font-weight: 600; padding: 5px 4px; text-align: left; white-space: nowrap; }
        table td { padding: 4px; font-size: 8px; border-bottom: 1px solid #eee; vertical-align: top; }
        table tr:nth-child(even) { background-color: #f8f9fa; }
        table tr:hover { background-color: #e8ecf1; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 3px; font-size: 7px; font-weight: 600; color: #fff; }
        .badge-success { background-color: #28a745; }
        .badge-danger { background-color: #dc3545; }
        .badge-warning { background-color: #ffc107; color: #333; }
        .badge-info { background-color: #17a2b8; }
        .badge-secondary { background-color: #6c757d; }
        .badge-primary { background-color: #0d6efd; }
        .footer { margin-top: 15px; padding-top: 8px; border-top: 1px solid #ddd; text-align: center; font-size: 8px; color: #999; }
        .page-break { page-break-after: always; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .no-data { text-align: center; padding: 30px; color: #999; font-size: 12px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>TMS — {{ $title }}</h1>
        <p>Terminal Management System</p>
    </div>

    <div class="meta-info">
        <span>Generated: {{ $generatedAt }}</span>
        <span>By: {{ $generatedBy }}</span>
        <span>Records: {{ $data->count() }}</span>
    </div>

    @if($data->isEmpty())
        <div class="no-data">No data found for the selected filters.</div>
    @else

        {{-- Ticket Summary / Status / SLA Reports --}}
        @if(in_array($reportType, ['ticket-summary', 'status', 'sla']))
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Ticket No</th>
                    <th>Vendor</th>
                    <th>Merchant</th>
                    <th>Job Type</th>
                    <th>Supervisor</th>
                    <th>Technician</th>
                    <th>Status</th>
                    @if($reportType === 'sla')
                    <th>SLA</th>
                    <th>Deadline</th>
                    <th>SLA Status</th>
                    @endif
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $i => $t)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $t->ticket_no }}</td>
                    <td>{{ $t->vendor->vendor_name ?? '-' }}</td>
                    <td>{{ $t->merchant_name ?? '-' }}</td>
                    <td>{{ $t->jobType->job_title ?? '-' }}</td>
                    <td>{{ $t->supervisor->name ?? '-' }}</td>
                    <td>{{ $t->technician->name ?? 'Unassigned' }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $t->status)) }}</td>
                    @if($reportType === 'sla')
                    <td>{{ $t->sla_hours }}h</td>
                    <td>{{ $t->sla_deadline?->format('d/m/Y H:i') ?? '-' }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $t->sla_status ?? 'N/A')) }}</td>
                    @endif
                    <td>{{ $t->created_at?->format('d/m/Y') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        {{-- Supervisor Pricing Report --}}
        @if($reportType === 'supervisor-pricing')
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Supervisor</th>
                    <th>Job Category</th>
                    <th>Job Type</th>
                    <th class="text-right">Price (RM)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $i => $p)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $p->supervisor->name ?? '-' }}</td>
                    <td>{{ $p->jobCategory->category_name ?? '-' }}</td>
                    <td>{{ $p->jobType->job_title ?? '-' }}</td>
                    <td class="text-right">{{ number_format((float) $p->price, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        {{-- Claim / Payment Reports --}}
        @if(in_array($reportType, ['claim', 'payment']))
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Claim No</th>
                    <th>Category</th>
                    <th>Ticket No</th>
                    <th>Technician</th>
                    <th>Claim Date</th>
                    <th class="text-right">Mileage (RM)</th>
                    <th class="text-right">Allowance (RM)</th>
                    <th class="text-right">Total (RM)</th>
                    <th>Status</th>
                    @if($reportType === 'payment')
                    <th>Paid Date</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach($data as $i => $c)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $c->claim_no }}</td>
                    <td>{{ ucfirst($c->claim_category) }}</td>
                    <td>{{ $c->ticket->ticket_no ?? '-' }}</td>
                    <td>{{ $c->technician->name ?? '-' }}</td>
                    <td>{{ $c->claim_date?->format('d/m/Y') }}</td>
                    <td class="text-right">{{ number_format((float) $c->total_mileage_amount, 2) }}</td>
                    <td class="text-right">{{ number_format((float) $c->total_allowance_amount, 2) }}</td>
                    <td class="text-right">{{ number_format((float) $c->total_amount, 2) }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $c->status)) }}</td>
                    @if($reportType === 'payment')
                    <td>{{ $c->paid_at?->format('d/m/Y') ?? '-' }}</td>
                    @endif
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        {{-- Inventory Balance Report --}}
        @if($reportType === 'inventory-balance')
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Serial</th>
                    <th>Model</th>
                    <th class="text-right">Warehouse</th>
                    <th class="text-right">Technician</th>
                    <th class="text-right">Total</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $i => $item)
                @php
                    $wQty = 0; $tQty = 0;
                    if ($item->relationLoaded('stockBalances')) {
                        foreach ($item->stockBalances as $b) {
                            if ($b->holder_type === 'warehouse') $wQty += $b->quantity; else $tQty += $b->quantity;
                        }
                    }
                @endphp
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $item->item_code }}</td>
                    <td>{{ $item->item_name }}</td>
                    <td>{{ ucfirst($item->item_type) }}</td>
                    <td>{{ $item->serial_number ?? '-' }}</td>
                    <td>{{ $item->model ?? '-' }}</td>
                    <td class="text-right">{{ $wQty }}</td>
                    <td class="text-right">{{ $tQty }}</td>
                    <td class="text-right">{{ $wQty + $tQty }}</td>
                    <td>{{ $wQty <= $item->reorder_level ? 'LOW' : 'OK' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        {{-- Router Movement / Accessories Usage Reports --}}
        @if(in_array($reportType, ['router-movement', 'accessories-usage']))
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Movement No</th>
                    <th>Date</th>
                    <th>Item</th>
                    <th>Serial/Type</th>
                    <th>Movement</th>
                    <th class="text-right">Qty</th>
                    <th>From</th>
                    <th>To</th>
                    <th>Ticket</th>
                    <th>By</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $i => $m)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $m->movement_no }}</td>
                    <td>{{ $m->movement_date }}</td>
                    <td>{{ $m->inventoryItem->item_name ?? '-' }}</td>
                    <td>{{ $m->inventoryItem->serial_number ?? ucfirst(str_replace('_',' ',$m->inventoryItem->accessory_type ?? '')) }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $m->movement_type)) }}</td>
                    <td class="text-right">{{ $m->quantity }}</td>
                    <td>{{ $m->fromHolder->name ?? ($m->from_holder_type ? ucfirst($m->from_holder_type) : '-') }}</td>
                    <td>{{ $m->toHolder->name ?? ($m->to_holder_type ? ucfirst($m->to_holder_type) : '-') }}</td>
                    <td>{{ $m->ticket->ticket_no ?? '-' }}</td>
                    <td>{{ $m->performer->name ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        {{-- Rejected / Rescheduled Report --}}
        @if($reportType === 'rejected-rescheduled')
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Ticket No</th>
                    <th>Vendor</th>
                    <th>Merchant</th>
                    <th>Job Type</th>
                    <th>Supervisor</th>
                    <th>Technician</th>
                    <th>Type</th>
                    <th>Reason</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $i => $t)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $t->ticket_no }}</td>
                    <td>{{ $t->vendor->vendor_name ?? '-' }}</td>
                    <td>{{ $t->merchant_name ?? '-' }}</td>
                    <td>{{ $t->jobType->job_title ?? '-' }}</td>
                    <td>{{ $t->supervisor->name ?? '-' }}</td>
                    <td>{{ $t->technician->name ?? 'Unassigned' }}</td>
                    <td>{{ $t->status === 'rejected' ? 'Rejected' : 'Rescheduled' }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($t->reschedule_reason ?? '-', 50) }}</td>
                    <td>{{ $t->created_at?->format('d/m/Y') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

    @endif

    <div class="footer">
        TMS — Terminal Management System &bull; Confidential &bull; Page generated {{ $generatedAt }}
    </div>
</body>
</html>
