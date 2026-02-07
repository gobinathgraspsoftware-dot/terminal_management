<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Transfer - {{ $stockTransfer->transfer_no }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none !important; }
            .page-break { page-break-after: always; }
            body { font-size: 11px; }
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            padding: 20px;
            background: white;
        }
        .company-header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px double #000;
            padding-bottom: 15px;
        }
        .company-header h2 {
            margin: 0;
            font-size: 24px;
            font-weight: bold;
        }
        .company-header p {
            margin: 5px 0 0 0;
            font-size: 14px;
            color: #666;
        }
        .document-title {
            text-align: center;
            margin: 20px 0;
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .info-table {
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
            margin: 20px 0;
        }
        .items-table th,
        .items-table td {
            border: 1px solid #000;
            padding: 8px;
        }
        .items-table th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }
        .items-table tfoot td {
            font-weight: bold;
        }
        .signature-section {
            margin-top: 60px;
        }
        .signature-box {
            border: 1px solid #ddd;
            padding: 15px;
            min-height: 100px;
            margin-bottom: 20px;
        }
        .signature-line {
            border-top: 1px solid #000;
            margin-top: 60px;
            padding-top: 5px;
            text-align: center;
        }
        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 10px;
            color: #999;
            padding: 10px 0;
            border-top: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <!-- Print/Close Buttons -->
    <div class="no-print mb-3">
        <button onclick="window.print()" class="btn btn-primary">
            <i class="bi bi-printer"></i> Print
        </button>
        <button onclick="window.close()" class="btn btn-secondary">
            <i class="bi bi-x-circle"></i> Close
        </button>
    </div>

    <!-- Company Header -->
    <div class="company-header">
        <h2>TERMINAL MANAGEMENT SYSTEM</h2>
        <p>Stock Transfer Document</p>
    </div>

    <!-- Document Title -->
    <div class="document-title">
        STOCK TRANSFER SLIP
    </div>

    <!-- Transfer Information -->
    <table class="table table-bordered info-table">
        <tr>
            <td>Transfer No:</td>
            <td><strong>{{ $stockTransfer->transfer_no }}</strong></td>
            <td>Transfer Date:</td>
            <td>{{ $stockTransfer->transfer_date->format('d M Y') }}</td>
        </tr>
        <tr>
            <td>From Depot:</td>
            <td>{{ $stockTransfer->fromDepot->depot_name }}</td>
            <td>To Depot:</td>
            <td>{{ $stockTransfer->toDepot->depot_name }}</td>
        </tr>
        <tr>
            <td>Status:</td>
            <td colspan="3">
                <strong>{{ ucfirst(str_replace('_', ' ', $stockTransfer->status)) }}</strong>
                @if($stockTransfer->dispatched_at)
                | Dispatched: {{ $stockTransfer->dispatched_at->format('d M Y H:i') }}
                @endif
            </td>
        </tr>
    </table>

    <!-- Items Details -->
    <h5>ITEMS TO BE TRANSFERRED</h5>
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 35%;">Model</th>
                <th style="width: 25%;">Serial No</th>
                <th style="width: 15%;">Qty Requested</th>
                <th style="width: 20%;">Qty Received</th>
            </tr>
        </thead>
        <tbody>
            @foreach($stockTransfer->lines as $line)
            <tr>
                <td style="text-align: center;">{{ $line->line_no }}</td>
                <td>{{ $line->model->category->category_name }} - {{ $line->model->model_name }}</td>
                <td style="text-align: center;">{{ $line->serial_no ?? '-' }}</td>
                <td style="text-align: center;">{{ number_format($line->quantity_requested, 2) }}</td>
                <td style="text-align: center;"></td>
            </tr>
            @if($line->remarks)
            <tr>
                <td colspan="5" style="font-size: 10px; font-style: italic;">
                    <strong>Note:</strong> {{ $line->remarks }}
                </td>
            </tr>
            @endif
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" style="text-align: right;"><strong>TOTAL ITEMS:</strong></td>
                <td style="text-align: center;"><strong>{{ $stockTransfer->total_items }}</strong></td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <!-- Remarks -->
    @if($stockTransfer->remarks)
    <div style="margin: 20px 0; padding: 10px; border: 1px solid #ddd; background-color: #f9f9f9;">
        <strong>Remarks:</strong>
        <p style="margin: 5px 0;">{{ $stockTransfer->remarks }}</p>
    </div>
    @endif

    <!-- Signature Section -->
    <div class="signature-section">
        <div class="row">
            <div class="col-6">
                <div class="signature-box">
                    <p><strong>PREPARED BY (SENDER):</strong></p>
                    <div class="signature-line">
                        <p class="mb-0">Name & Signature</p>
                        <p class="small">Date: _____________________</p>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="signature-box">
                    <p><strong>RECEIVED BY:</strong></p>
                    <div class="signature-line">
                        <p class="mb-0">Name & Signature</p>
                        <p class="small">Date: _____________________</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-6">
                <div class="signature-box">
                    <p><strong>APPROVED BY:</strong></p>
                    @if($stockTransfer->approved_at && $stockTransfer->approvedBy)
                    <p>{{ $stockTransfer->approvedBy->name }}</p>
                    <p class="small">Date: {{ $stockTransfer->approved_at->format('d M Y H:i') }}</p>
                    @else
                    <div class="signature-line">
                        <p class="mb-0">Name & Signature</p>
                        <p class="small">Date: _____________________</p>
                    </div>
                    @endif
                </div>
            </div>
            <div class="col-6">
                <div class="signature-box">
                    <p><strong>VERIFIED BY (Warehouse):</strong></p>
                    <div class="signature-line">
                        <p class="mb-0">Name & Signature</p>
                        <p class="small">Date: _____________________</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer no-print">
        <p>This is a computer-generated document. No signature is required unless specified.</p>
        <p>Printed on: {{ now()->format('d M Y H:i:s') }} | Generated by Terminal Management System</p>
    </div>

    <script>
        // Auto-print on load (optional)
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>
