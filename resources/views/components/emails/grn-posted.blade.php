{{-- resources/views/components/emails/grn-posted.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GRN Posted: {{ $grn->grn_no }}</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 14px;
            color: #333;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .email-wrapper {
            max-width: 600px;
            margin: 20px auto;
            background: #ffffff;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            overflow: hidden;
        }
        .email-header {
            background: #1a5276;
            color: #ffffff;
            padding: 20px 30px;
            text-align: center;
        }
        .email-header h1 {
            margin: 0;
            font-size: 22px;
            font-weight: 600;
        }
        .email-header p {
            margin: 5px 0 0 0;
            font-size: 13px;
            color: #d6eaf8;
        }
        .email-body {
            padding: 25px 30px;
        }
        .greeting {
            font-size: 15px;
            margin-bottom: 15px;
        }
        .intro-text {
            margin-bottom: 20px;
            color: #555;
        }
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .details-table td {
            padding: 8px 12px;
            border-bottom: 1px solid #f0f0f0;
            vertical-align: top;
        }
        .details-table td:first-child {
            font-weight: 600;
            color: #666;
            width: 160px;
            white-space: nowrap;
        }
        .details-table td:last-child {
            color: #333;
        }
        .details-table tr:last-child td {
            border-bottom: none;
        }
        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            background: #d4edda;
            color: #155724;
        }
        .custom-message {
            background: #f8f9fa;
            border-left: 4px solid #1a5276;
            padding: 12px 15px;
            margin-bottom: 20px;
            color: #555;
            font-style: italic;
        }
        .action-button {
            display: inline-block;
            padding: 12px 30px;
            background: #1a5276;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            font-size: 14px;
            margin: 10px 0;
        }
        .button-wrapper {
            text-align: center;
            margin: 20px 0;
        }
        .note-text {
            font-size: 13px;
            color: #888;
            margin-top: 15px;
        }
        .email-footer {
            background: #f8f9fa;
            padding: 15px 30px;
            text-align: center;
            border-top: 1px solid #e0e0e0;
        }
        .email-footer p {
            margin: 0;
            font-size: 12px;
            color: #999;
        }
        .highlight {
            font-weight: 600;
            color: #1a5276;
        }
    </style>
</head>
<body>
    <div class="email-wrapper">

        {{-- Header --}}
        <div class="email-header">
            <h1>Goods Receipt Note Posted</h1>
            <p>{{ $grn->grn_no }} &mdash; {{ $grn->grn_date ? $grn->grn_date->format('d M Y') : '-' }}</p>
        </div>

        {{-- Body --}}
        <div class="email-body">

            <p class="greeting">Hello,</p>

            <p class="intro-text">
                A Goods Receipt Note has been <strong>posted successfully</strong>.
                Inventory and stock balances have been updated automatically.
            </p>

            {{-- Custom Message (if any) --}}
            @if(!empty($customMessage))
                <div class="custom-message">
                    {{ $customMessage }}
                </div>
            @endif

            {{-- GRN Details --}}
            <table class="details-table">
                <tr>
                    <td>GRN No</td>
                    <td><span class="highlight">{{ $grn->grn_no }}</span></td>
                </tr>
                <tr>
                    <td>GRN Date</td>
                    <td>{{ $grn->grn_date ? $grn->grn_date->format('d M Y') : '-' }}</td>
                </tr>
                <tr>
                    <td>Status</td>
                    <td><span class="status-badge">POSTED</span></td>
                </tr>
                <tr>
                    <td>PO No</td>
                    <td>{{ $grn->purchaseOrder?->po_no ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td>Vendor</td>
                    <td>{{ $grn->vendor?->vendor_name ?? $grn->vendor?->company_name ?? '-' }}</td>
                </tr>
                <tr>
                    <td>Receiving Depot</td>
                    <td>{{ $grn->receivingDepot?->depot_name ?? '-' }}</td>
                </tr>
                <tr>
                    <td>Delivery Note No</td>
                    <td>{{ $grn->delivery_note_no ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td>Total Items</td>
                    <td><span class="highlight">{{ $grn->total_items }}</span></td>
                </tr>
                @php
                    $totalValue = $grn->lines ? $grn->lines->sum('line_total') : 0;
                @endphp
                <tr>
                    <td>Total Value</td>
                    <td><span class="highlight">MYR {{ number_format($totalValue, 2) }}</span></td>
                </tr>
                <tr>
                    <td>Posted By</td>
                    <td>{{ $grn->postedBy?->name ?? '-' }}</td>
                </tr>
                <tr>
                    <td>Posted At</td>
                    <td>{{ $grn->posted_at ? $grn->posted_at->format('d M Y H:i') : '-' }}</td>
                </tr>
            </table>

            {{-- Line Items Summary --}}
            @if($grn->lines && $grn->lines->isNotEmpty())
                <p style="font-weight: 600; color: #1a5276; margin-bottom: 8px;">Line Items:</p>
                <table class="details-table" style="font-size: 13px;">
                    @foreach($grn->lines as $line)
                        <tr>
                            <td style="width: auto;">{{ $line->line_no }}. {{ $line->model?->model_name ?? $line->description ?? '-' }}</td>
                            <td style="text-align: right;">{{ number_format($line->quantity_received, 0) }} {{ $line->unit ?? 'pcs' }} &times; MYR {{ number_format($line->unit_cost, 2) }}</td>
                        </tr>
                    @endforeach
                </table>
            @endif

            {{-- Action Button --}}
            {{-- <div class="button-wrapper">
                <a href="{{ url('admin/grns/' . $grn->id) }}" class="action-button">
                    View GRN Details
                </a>
            </div> --}}

            <p class="note-text">
                Inventory serial numbers and stock balances have been updated in the system.
            </p>

        </div>

        {{-- Footer --}}
        <div class="email-footer">
            <p>Terminal Management System &mdash; GRASP SOFTWARE SOLUTIONS</p>
            <p>This is an automated notification. Please do not reply to this email.</p>
        </div>

    </div>
</body>
</html>
