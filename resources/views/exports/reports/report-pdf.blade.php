<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page {
            margin: 15mm 12mm 15mm 12mm;
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 9px;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .header {
            text-align: center;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 2px solid #4472C4;
        }
        .header h1 { font-size: 15px; color: #4472C4; margin: 0 0 3px 0; }
        .header p { font-size: 9px; color: #666; margin: 0; }

        .meta-info {
            margin-bottom: 10px;
            font-size: 8px;
            color: #888;
            overflow: hidden;
        }
        .meta-info .left { float: left; }
        .meta-info .right { float: right; }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
            border: 1px solid #B4C6E7;
        }
        table th {
            background-color: #4472C4;
            color: #fff;
            font-size: 8px;
            font-weight: 600;
            padding: 6px 5px;
            text-align: left;
            white-space: nowrap;
            border: 1px solid #2F5496;
        }
        table td {
            padding: 5px 5px;
            font-size: 8px;
            border: 1px solid #D9E2F3;
            vertical-align: top;
            line-height: 1.4;
        }
        table tr:nth-child(even) td { background-color: #F2F6FC; }
        table tr:nth-child(odd) td { background-color: #FFFFFF; }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .no-data { text-align: center; padding: 30px; color: #999; font-size: 12px; }
        .footer {
            margin-top: 12px;
            padding-top: 6px;
            border-top: 1px solid #B4C6E7;
            text-align: center;
            font-size: 7px;
            color: #999;
        }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $title }}</h1>
        <p>TMS — Terminal Management System</p>
    </div>

    <div class="meta-info">
        <span class="left">Generated: {{ $generatedAt }} &nbsp;&bull;&nbsp; By: {{ $generatedBy }}</span>
        <span class="right">Total Records: {{ $data->count() }}</span>
    </div>

    @if($data->isEmpty())
        <div class="no-data">No data found for the selected filters.</div>
    @else

        {{-- Ticket Summary / Status / SLA Reports --}}
        @if(in_array($reportType, ['ticket-summary', 'status', 'sla']))
        <table>
            <thead>
                <tr>
                    <th>#</th><th>Ticket No</th><th>Vendor</th><th>Merchant</th><th>Job Type</th>
                    <th>Supervisor</th><th>Technician</th><th>Status</th>
                    @if($reportType === 'sla')
                    <th>SLA</th><th>Deadline</th><th>SLA Status</th>
                    @endif
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $i => $t)
                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td>{{ $t->ticket_no }}</td>
                    <td>{{ $t->vendor?->vendor_name ?? '-' }}</td>
                    <td>{{ $t->merchant_name ?? '-' }}</td>
                    <td>{{ $t->jobType?->job_title ?? '-' }}</td>
                    <td>{{ $t->supervisor?->name ?? '-' }}</td>
                    <td>{{ $t->technician?->name ?? 'Unassigned' }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $t->status)) }}</td>
                    @if($reportType === 'sla')
                    <td class="text-center">{{ $t->sla_hours }}h</td>
                    <td>{{ $t->sla_deadline?->format('d/m/Y H:i') ?? '-' }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $t->sla_status ?? 'N/A')) }}</td>
                    @endif
                    <td>{{ $t->created_at?->format('d/m/Y') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        {{-- Supervisor Pricing --}}
        @if($reportType === 'supervisor-pricing')
        <table>
            <thead><tr><th>#</th><th>Supervisor</th><th>Job Category</th><th>Job Type</th><th class="text-right">Price (RM)</th></tr></thead>
            <tbody>
                @foreach($data as $i => $p)
                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td>{{ $p->supervisor?->name ?? '-' }}</td>
                    <td>{{ $p->jobCategory?->category_name ?? '-' }}</td>
                    <td>{{ $p->jobType?->job_title ?? '-' }}</td>
                    <td class="text-right">{{ number_format((float) $p->price, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        {{-- Claim / Payment --}}
        @if(in_array($reportType, ['claim', 'payment']))
        <table>
            <thead>
                <tr>
                    <th>#</th><th>Claim No</th><th>Category</th><th>Ticket No</th><th>Technician</th>
                    <th>Date</th><th class="text-right">Mileage (RM)</th><th class="text-right">Allowance (RM)</th>
                    <th class="text-right">Total (RM)</th><th>Status</th>
                    @if($reportType === 'payment')<th>Paid Date</th>@endif
                </tr>
            </thead>
            <tbody>
                @foreach($data as $i => $c)
                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td>{{ $c->claim_no }}</td>
                    <td>{{ ucfirst($c->claim_category) }}</td>
                    <td>{{ $c->ticket?->ticket_no ?? '-' }}</td>
                    <td>{{ $c->technician?->name ?? '-' }}</td>
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

        {{-- Inventory Balance --}}
        @if($reportType === 'inventory-balance')
        <table>
            <thead>
                <tr><th>#</th><th>Code</th><th>Name</th><th>Type</th><th>Serial</th><th>Model</th>
                <th class="text-right">Warehouse</th><th class="text-right">Technician</th><th class="text-right">Total</th><th>Status</th></tr>
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
                    <td class="text-center">{{ $i + 1 }}</td>
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

        {{-- Router Movement / Accessories Usage --}}
        @if(in_array($reportType, ['router-movement', 'accessories-usage']))
        <table>
            <thead>
                <tr><th>#</th><th>Movement No</th><th>Date</th><th>Item</th><th>Serial/Type</th>
                <th>Movement</th><th class="text-right">Qty</th><th>From</th><th>To</th><th>Ticket</th><th>By</th></tr>
            </thead>
            <tbody>
                @foreach($data as $i => $m)
                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td>{{ $m->movement_no }}</td>
                    <td>{{ $m->movement_date instanceof \Carbon\Carbon ? $m->movement_date->format('d/m/Y') : $m->movement_date }}</td>
                    <td>{{ $m->inventoryItem?->item_name ?? '-' }}</td>
                    <td>{{ $reportType === 'accessories-usage' ? ucfirst(str_replace('_',' ',$m->inventoryItem?->accessory_type ?? '-')) : (!empty($m->router_ids) ? (is_array($m->router_ids) ? implode(', ', $m->router_ids) : implode(', ', json_decode($m->router_ids, true) ?: [])) : ($m->ticket?->router_id ?? '-')) }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $m->movement_type)) }}</td>
                    <td class="text-right">{{ $m->quantity }}</td>
                    <td>{{ $m->fromHolder?->name ?? ($m->from_holder_type ? ucfirst($m->from_holder_type) : '-') }}</td>
                    <td>{{ $m->toHolder?->name ?? ($m->to_holder_type ? ucfirst($m->to_holder_type) : '-') }}</td>
                    <td>{{ $m->ticket?->ticket_no ?? '-' }}</td>
                    <td>{{ $m->performer?->name ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        {{-- Rejected / Rescheduled --}}
        @if($reportType === 'rejected-rescheduled')
        <table>
            <thead>
                <tr><th>#</th><th>Ticket No</th><th>Vendor</th><th>Merchant</th><th>Job Type</th>
                <th>Supervisor</th><th>Technician</th><th>Type</th><th>Reason</th><th>Date</th></tr>
            </thead>
            <tbody>
                @foreach($data as $i => $t)
                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td>{{ $t->ticket_no }}</td>
                    <td>{{ $t->vendor?->vendor_name ?? '-' }}</td>
                    <td>{{ $t->merchant_name ?? '-' }}</td>
                    <td>{{ $t->jobType?->job_title ?? '-' }}</td>
                    <td>{{ $t->supervisor?->name ?? '-' }}</td>
                    <td>{{ $t->technician?->name ?? 'Unassigned' }}</td>
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
        TMS — Terminal Management System &bull; Confidential &bull; {{ $generatedAt }}
    </div>
</body>
</html>
