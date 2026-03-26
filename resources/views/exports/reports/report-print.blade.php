<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} — TMS Print</title>
    <style>
        @page {
            size: landscape;
            margin: 10mm;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 11px;
            color: #333;
            padding: 15px;
            background: #fff;
        }

        /* Header */
        .print-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            border-bottom: 3px solid #4472C4;
            padding-bottom: 10px;
            margin-bottom: 12px;
        }
        .print-header .title-section h1 { font-size: 18px; color: #4472C4; margin-bottom: 2px; }
        .print-header .title-section p { font-size: 10px; color: #888; }
        .print-header .meta-section { text-align: right; font-size: 9px; color: #666; line-height: 1.6; }

        /* Toolbar — hidden during print */
        .print-toolbar {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            margin-bottom: 12px;
            padding: 8px 0;
        }
        .print-toolbar button {
            padding: 6px 16px;
            border: 1px solid #ccc;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 500;
        }
        .btn-print { background: #4472C4; color: #fff; border-color: #4472C4; }
        .btn-print:hover { background: #3461a8; }
        .btn-close-tab { background: #f8f9fa; color: #333; }
        .btn-close-tab:hover { background: #e2e6ea; }

        /* Table */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
            border: 1px solid #B4C6E7;
            font-size: 10px;
        }
        table th {
            background-color: #4472C4;
            color: #fff;
            font-weight: 600;
            padding: 7px 6px;
            text-align: left;
            white-space: nowrap;
            border: 1px solid #2F5496;
            font-size: 10px;
        }
        table td {
            padding: 5px 6px;
            border: 1px solid #D9E2F3;
            vertical-align: top;
            line-height: 1.4;
        }
        table tr:nth-child(even) td { background-color: #F2F6FC; }
        table tr:nth-child(odd) td { background-color: #fff; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }

        /* Summary row */
        table tfoot td {
            background-color: #E8EEF7 !important;
            font-weight: 600;
            border-top: 2px solid #4472C4;
        }

        /* Footer */
        .print-footer {
            margin-top: 15px;
            padding-top: 8px;
            border-top: 1px solid #B4C6E7;
            text-align: center;
            font-size: 8px;
            color: #aaa;
        }

        .no-data { text-align: center; padding: 40px; color: #999; font-size: 14px; }

        /* Print-specific */
        @media print {
            .print-toolbar { display: none !important; }
            body { padding: 0; font-size: 9px; }
            table { font-size: 9px; }
            table th { font-size: 9px; padding: 5px 4px; }
            table td { padding: 4px; }
        }
    </style>
</head>
<body>

    <!-- Toolbar (visible on screen, hidden during print) -->
    <div class="print-toolbar">
        <button class="btn-print" onclick="window.print();">🖨️ Print Now</button>
        <button class="btn-close-tab" onclick="window.close();">✕ Close</button>
    </div>

    <!-- Header -->
    <div class="print-header">
        <div class="title-section">
            <h1>{{ $title }}</h1>
            <p>TMS — Terminal Management System</p>
        </div>
        <div class="meta-section">
            Generated: {{ $generatedAt }}<br>
            By: {{ $generatedBy }}<br>
            Records: {{ $data->count() }}
        </div>
    </div>

    @if($data->isEmpty())
        <div class="no-data">No data found for the selected filters.</div>
    @else

        {{-- ─────────────────────────────────────── --}}
        {{-- Ticket Summary / Status / SLA           --}}
        {{-- ─────────────────────────────────────── --}}
        @if(in_array($reportType, ['ticket-summary', 'status', 'sla']))
        <table>
            <thead><tr>
                <th>#</th><th>Ticket No</th><th>Vendor</th><th>Merchant</th><th>State</th><th>City</th>
                <th>Job Category</th><th>Job Type</th><th>Supervisor</th><th>Technician</th><th>Status</th><th>Priority</th>
                @if($reportType === 'sla')<th>SLA</th><th>SLA Status</th>@endif
                <th>Created</th>
            </tr></thead>
            <tbody>
                @foreach($data as $i => $t)
                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td>{{ $t->ticket_no }}</td>
                    <td>{{ $t->vendor?->vendor_name ?? '-' }}</td>
                    <td>{{ $t->merchant_name ?? '-' }}</td>
                    <td>{{ $t->state?->name ?? '-' }}</td>
                    <td>{{ $t->city?->name ?? '-' }}</td>
                    <td>{{ $t->jobCategory?->category_name ?? '-' }}</td>
                    <td>{{ $t->jobType?->job_title ?? '-' }}</td>
                    <td>{{ $t->supervisor?->name ?? '-' }}</td>
                    <td>{{ $t->technician?->name ?? 'Unassigned' }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $t->status)) }}</td>
                    <td>{{ ucfirst($t->priority) }}</td>
                    @if($reportType === 'sla')
                    <td class="text-center">{{ $t->sla_hours }}h</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $t->sla_status ?? 'N/A')) }}</td>
                    @endif
                    <td>{{ $t->created_at?->format('d/m/Y') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        {{-- ─────────────────────────────────────── --}}
        {{-- Supervisor Pricing                      --}}
        {{-- ─────────────────────────────────────── --}}
        @if($reportType === 'supervisor-pricing')
        <table>
            <thead><tr><th>#</th><th>Supervisor</th><th>Job Category</th><th>Job Type</th><th class="text-right">Price (RM)</th></tr></thead>
            <tbody>
                @php $totalPrice = 0; @endphp
                @foreach($data as $i => $p)
                @php $totalPrice += (float) $p->price; @endphp
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

        {{-- ─────────────────────────────────────── --}}
        {{-- Claim / Payment                          --}}
        {{-- ─────────────────────────────────────── --}}
        @if(in_array($reportType, ['claim', 'payment']))
        <table>
            <thead><tr>
                <th>#</th><th>Claim No</th><th>Category</th><th>Ticket No</th><th>Technician</th>
                <th>Vendor</th><th>Date</th><th class="text-right">Mileage</th><th class="text-right">Allowance</th>
                <th class="text-right">Total</th><th>Status</th>
                @if($reportType === 'payment')<th>Paid Date</th><th>Batch</th>@endif
            </tr></thead>
            <tbody>
                @php $sumMileage = 0; $sumAllow = 0; $sumTotal = 0; @endphp
                @foreach($data as $i => $c)
                @php $sumMileage += (float)$c->total_mileage_amount; $sumAllow += (float)$c->total_allowance_amount; $sumTotal += (float)$c->total_amount; @endphp
                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td>{{ $c->claim_no }}</td>
                    <td>{{ ucfirst($c->claim_category) }}</td>
                    <td>{{ $c->ticket?->ticket_no ?? '-' }}</td>
                    <td>{{ $c->technician?->name ?? '-' }}</td>
                    <td>{{ $c->ticket?->vendor?->vendor_name ?? '-' }}</td>
                    <td>{{ $c->claim_date?->format('d/m/Y') }}</td>
                    <td class="text-right">{{ number_format((float)$c->total_mileage_amount, 2) }}</td>
                    <td class="text-right">{{ number_format((float)$c->total_allowance_amount, 2) }}</td>
                    <td class="text-right">{{ number_format((float)$c->total_amount, 2) }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $c->status)) }}</td>
                    @if($reportType === 'payment')
                    <td>{{ $c->paid_at?->format('d/m/Y') ?? '-' }}</td>
                    <td>{{ $c->payoutBatch?->batch_no ?? '-' }}</td>
                    @endif
                </tr>
                @endforeach
            </tbody>
            <tfoot><tr>
                <td colspan="{{ $reportType === 'payment' ? 7 : 7 }}" class="text-right"><strong>Totals:</strong></td>
                <td class="text-right"><strong>{{ number_format($sumMileage, 2) }}</strong></td>
                <td class="text-right"><strong>{{ number_format($sumAllow, 2) }}</strong></td>
                <td class="text-right"><strong>{{ number_format($sumTotal, 2) }}</strong></td>
                <td colspan="{{ $reportType === 'payment' ? 3 : 1 }}"></td>
            </tr></tfoot>
        </table>
        @endif

        {{-- ─────────────────────────────────────── --}}
        {{-- Inventory Balance                        --}}
        {{-- ─────────────────────────────────────── --}}
        @if($reportType === 'inventory-balance')
        <table>
            <thead><tr>
                <th>#</th><th>Code</th><th>Name</th><th>Type</th><th>Category</th><th>Serial</th><th>Model</th>
                <th class="text-right">Warehouse</th><th class="text-right">Technician</th><th class="text-right">Total</th>
                <th>Reorder</th><th>Status</th>
            </tr></thead>
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
                    <td>{{ \App\Models\JobCategory::find($item->job_category_id)?->category_name ?? '-' }}</td>
                    <td>{{ $item->serial_number ?? '-' }}</td>
                    <td>{{ $item->model ?? '-' }}</td>
                    <td class="text-right">{{ $wQty }}</td>
                    <td class="text-right">{{ $tQty }}</td>
                    <td class="text-right">{{ $wQty + $tQty }}</td>
                    <td class="text-center">{{ $item->reorder_level }}</td>
                    <td>{{ $wQty <= $item->reorder_level ? 'LOW' : 'OK' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        {{-- ─────────────────────────────────────── --}}
        {{-- Router Movement / Accessories Usage      --}}
        {{-- ─────────────────────────────────────── --}}
        @if(in_array($reportType, ['router-movement', 'accessories-usage']))
        <table>
            <thead><tr>
                <th>#</th><th>Movement No</th><th>Date</th><th>Item</th><th>{{ $reportType === 'accessories-usage' ? 'Accessory Type' : 'Terminal ID' }}</th>
                <th>Movement</th><th class="text-right">Qty</th><th>From</th><th>To</th><th>Ticket</th><th>Condition</th><th>By</th>
            </tr></thead>
            <tbody>
                @foreach($data as $i => $m)
                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td>{{ $m->movement_no }}</td>
                    <td>{{ $m->movement_date instanceof \Carbon\Carbon ? $m->movement_date->format('d/m/Y') : $m->movement_date }}</td>
                    <td>{{ $m->inventoryItem?->item_name ?? '-' }}</td>
                    <td>{{ $reportType === 'accessories-usage' ? ucfirst(str_replace('_',' ',$m->inventoryItem?->accessory_type ?? '-')) : ($m->inventoryItem?->serial_number ?? '-') }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $m->movement_type)) }}</td>
                    <td class="text-right">{{ $m->quantity }}</td>
                    <td>{{ $m->fromHolder?->name ?? ($m->from_holder_type ? ucfirst($m->from_holder_type) : '-') }}</td>
                    <td>{{ $m->toHolder?->name ?? ($m->to_holder_type ? ucfirst($m->to_holder_type) : '-') }}</td>
                    <td>{{ $m->ticket?->ticket_no ?? '-' }}</td>
                    <td>{{ ucfirst($m->item_condition ?? 'good') }}</td>
                    <td>{{ $m->performer?->name ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        {{-- ─────────────────────────────────────── --}}
        {{-- Rejected / Rescheduled                   --}}
        {{-- ─────────────────────────────────────── --}}
        @if($reportType === 'rejected-rescheduled')
        <table>
            <thead><tr>
                <th>#</th><th>Ticket No</th><th>Vendor</th><th>Merchant</th><th>Job Type</th>
                <th>Supervisor</th><th>Technician</th><th>Type</th><th>Reason</th><th>Created</th>
            </tr></thead>
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
                    <td>{{ \Illuminate\Support\Str::limit($t->reschedule_reason ?? '-', 60) }}</td>
                    <td>{{ $t->created_at?->format('d/m/Y') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

    @endif

    <div class="print-footer">
        TMS — Terminal Management System &bull; Confidential &bull; {{ $generatedAt }}
    </div>

    <script>
        // Auto-trigger print dialog after page loads
        window.addEventListener('load', function() {
            setTimeout(function() { window.print(); }, 500);
        });
    </script>
</body>
</html>
