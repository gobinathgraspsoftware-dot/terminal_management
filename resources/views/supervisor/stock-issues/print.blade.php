<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Stock Issue - {{ $stockIssue->issue_no }}</title>
    <style>
        @media print {
            @page { margin: 20mm; }
            body { margin: 0; }
            .no-print { display: none; }
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.5;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #000;
            padding-bottom: 15px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .info-table {
            width: 100%;
            margin-bottom: 20px;
        }
        .info-table td {
            padding: 5px;
        }
        .info-table td:first-child {
            font-weight: bold;
            width: 150px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .items-table th, .items-table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        .items-table th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        .items-table td:nth-child(1),
        .items-table td:nth-child(4) {
            text-align: center;
        }
        .signature-section {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
        }
        .signature-box {
            width: 45%;
        }
        .signature-line {
            border-top: 1px solid #000;
            margin-top: 60px;
            padding-top: 5px;
        }
        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border: 1px solid #000;
            font-weight: bold;
        }
        .footer {
            margin-top: 30px;
            font-size: 10px;
            text-align: center;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px; text-align: right;">
        <button onclick="window.print()" style="padding: 10px 20px; font-size: 14px;">
            Print Document
        </button>
        <button onclick="window.close()" style="padding: 10px 20px; font-size: 14px;">
            Close
        </button>
    </div>

    <div class="header">
        <h1>STOCK ISSUE</h1>
        <p style="margin: 5px 0;">Terminal Management System</p>
        <p style="margin: 0; font-size: 14px;"><strong>{{ $stockIssue->issue_no }}</strong></p>
    </div>

    <table class="info-table">
        <tr>
            <td>Issue Date:</td>
            <td>{{ $stockIssue->issue_date->format('d F Y') }}</td>
            <td>Issue Type:</td>
            <td>
                @if($stockIssue->issue_type === 'issue_to_tech')
                    <span class="status-badge">ISSUE TO TECHNICIAN</span>
                @else
                    <span class="status-badge">RETURN FROM TECHNICIAN</span>
                @endif
            </td>
        </tr>
        <tr>
            <td>Status:</td>
            <td>
                <span class="status-badge">{{ strtoupper($stockIssue->status) }}</span>
            </td>
            <td>Total Items:</td>
            <td><strong>{{ $stockIssue->total_items }}</strong></td>
        </tr>
        @if($stockIssue->issue_type === 'issue_to_tech')
        <tr>
            <td>From Depot:</td>
            <td>{{ $stockIssue->fromDepot->name ?? '-' }}</td>
            <td>To Technician:</td>
            <td>{{ $stockIssue->toTechnician->name ?? '-' }}</td>
        </tr>
        @else
        <tr>
            <td>From Technician:</td>
            <td>{{ $stockIssue->fromTechnician->name ?? '-' }}</td>
            <td>To Depot:</td>
            <td>{{ $stockIssue->toDepot->name ?? '-' }}</td>
        </tr>
        @endif
        @if($stockIssue->remarks)
        <tr>
            <td>Remarks:</td>
            <td colspan="3">{{ $stockIssue->remarks }}</td>
        </tr>
        @endif
    </table>

    <h3 style="margin-top: 30px; margin-bottom: 10px; border-bottom: 1px solid #000; padding-bottom: 5px;">
        LINE ITEMS
    </h3>
    
    <table class="items-table">
        <thead>
            <tr>
                <th width="5%">#</th>
                <th width="35%">Model</th>
                <th width="30%">Serial Number</th>
                <th width="15%">Quantity</th>
                <th width="15%">Remarks</th>
            </tr>
        </thead>
        <tbody>
            @foreach($stockIssue->lines as $line)
            <tr>
                <td>{{ $line->line_no }}</td>
                <td>
                    <strong>{{ $line->model->model_name ?? '-' }}</strong>
                    @if($line->model && $line->model->category)
                        <br><small>{{ $line->model->category->name }}</small>
                    @endif
                </td>
                <td>{{ $line->serial_no ?? 'Non-Serialized' }}</td>
                <td>{{ number_format($line->quantity, 2) }}</td>
                <td>{{ $line->remarks ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="3" style="text-align: right;">Total Items:</th>
                <th>{{ $stockIssue->total_items }}</th>
                <th></th>
            </tr>
        </tfoot>
    </table>

    <div class="signature-section">
        <div class="signature-box">
            <strong>Issued By:</strong>
            <div class="signature-line">
                <div>Name: _________________________</div>
                <div>Date: _________________________</div>
            </div>
        </div>
        <div class="signature-box">
            <strong>Received By:</strong>
            <div class="signature-line">
                <div>Name: _________________________</div>
                <div>Date: _________________________</div>
            </div>
        </div>
    </div>

    <div class="footer">
        <p>This is a computer-generated document. No signature is required.</p>
        <p>Printed on: {{ now()->format('d F Y H:i:s') }}</p>
        @if($stockIssue->posted_at)
            <p>Posted on: {{ $stockIssue->posted_at->format('d F Y H:i:s') }}</p>
        @endif
    </div>

    <script>
        window.onload = function() {
            // Auto-print after 1 second (optional)
            // setTimeout(function() { window.print(); }, 1000);
        };
    </script>
</body>
</html>
