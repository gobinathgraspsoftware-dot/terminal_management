<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Purchase Order - {{ $po->po_no }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.4;
            color: #333;
        }
        .container {
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #667eea;
        }
        .company-name {
            font-size: 24pt;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }
        .company-details {
            font-size: 9pt;
            color: #666;
        }
        .po-title {
            font-size: 18pt;
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
            color: #333;
        }
        .section {
            margin-bottom: 20px;
        }
        .section-title {
            background: #f0f0f0;
            padding: 8px;
            font-weight: bold;
            margin-bottom: 10px;
            border-left: 4px solid #667eea;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .info-table td {
            padding: 6px;
            border: none;
        }
        .info-table td:first-child {
            width: 30%;
            font-weight: bold;
            color: #666;
        }
        .lines-table {
            margin-top: 10px;
        }
        .lines-table th {
            background: #667eea;
            color: white;
            padding: 8px;
            text-align: left;
            font-weight: bold;
        }
        .lines-table td {
            padding: 6px;
            border-bottom: 1px solid #ddd;
        }
        .lines-table tbody tr:nth-child(even) {
            background: #f9f9f9;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .totals-table {
            width: 50%;
            margin-left: auto;
            margin-top: 10px;
        }
        .totals-table td {
            padding: 5px 10px;
            border: none;
        }
        .totals-table td:first-child {
            text-align: right;
            font-weight: bold;
        }
        .totals-table td:last-child {
            text-align: right;
            width: 120px;
        }
        .grand-total {
            background: #f0f0f0;
            font-size: 12pt;
            font-weight: bold;
            color: #667eea;
        }
        .signature-section {
            margin-top: 50px;
            page-break-inside: avoid;
        }
        .signature-box {
            width: 45%;
            display: inline-block;
            vertical-align: top;
        }
        .signature-line {
            border-top: 1px solid #333;
            margin-top: 50px;
            padding-top: 5px;
        }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8pt;
            color: #999;
            padding: 10px;
            border-top: 1px solid #ddd;
        }
        .page-number:before {
            content: "Page " counter(page);
        }
        .terms-section {
            margin-top: 20px;
            padding: 10px;
            background: #f9f9f9;
            border-left: 3px solid #667eea;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 9pt;
            font-weight: bold;
        }
        .badge-approved {
            background: #28a745;
            color: white;
        }
        .badge-sent {
            background: #007bff;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="company-name">{{ $company['name'] }}</div>
            <div class="company-details">
                {{ $company['address'] }}<br>
                Tel: {{ $company['phone'] }} | Email: {{ $company['email'] }}<br>
                {{ $company['registration'] ?? '' }}
            </div>
        </div>

        <div class="po-title">PURCHASE ORDER</div>

        <!-- PO Information -->
        <table class="info-table">
            <tr>
                <td colspan="2">
                    <table style="width: 100%;">
                        <tr>
                            <td style="width: 50%; vertical-align: top;">
                                <strong style="font-size: 11pt;">Vendor:</strong><br>
                                <strong>{{ $po->vendor->vendor_name }}</strong><br>
                                @if($po->vendor->address)
                                    {{ $po->vendor->address }}<br>
                                @endif
                                @if($po->vendor->pic_name)
                                    Attn: {{ $po->vendor->pic_name }}<br>
                                @endif
                                @if($po->vendor->pic_phone)
                                    Tel: {{ $po->vendor->pic_phone }}<br>
                                @endif
                                @if($po->vendor->pic_email)
                                    Email: {{ $po->vendor->pic_email }}
                                @endif
                            </td>
                            <td style="width: 50%; vertical-align: top;">
                                <table class="info-table">
                                    <tr>
                                        <td>PO Number:</td>
                                        <td><strong>{{ $po->po_no }}</strong></td>
                                    </tr>
                                    <tr>
                                        <td>PO Date:</td>
                                        <td>{{ $po->po_date->format('d M Y') }}</td>
                                    </tr>
                                    @if($po->reference)
                                    <tr>
                                        <td>Reference:</td>
                                        <td>{{ $po->reference }}</td>
                                    </tr>
                                    @endif
                                    <tr>
                                        <td>Delivery Date:</td>
                                        <td>{{ $po->delivery_date?->format('d M Y') ?? 'TBD' }}</td>
                                    </tr>
                                    <tr>
                                        <td>Currency:</td>
                                        <td>{{ $po->currency }}</td>
                                    </tr>
                                    @if(in_array($po->status, ['approved', 'sent']))
                                    <tr>
                                        <td>Status:</td>
                                        <td><span class="badge badge-{{ $po->status === 'approved' ? 'approved' : 'sent' }}">{{ strtoupper($po->status) }}</span></td>
                                    </tr>
                                    @endif
                                </table>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <!-- Delivery Address -->
        @if($po->delivery_address)
        <div class="section">
            <div class="section-title">Delivery Address</div>
            <div style="padding: 5px;">
                {{ $po->delivery_address }}<br>
                @if($po->receivingDepot)
                    <strong>Receiving Depot:</strong> {{ $po->receivingDepot->depot_name }}
                @endif
            </div>
        </div>
        @endif

        <!-- Line Items -->
        <div class="section">
            <div class="section-title">Order Items</div>
            <table class="lines-table">
                <thead>
                    <tr>
                        <th style="width: 5%;">#</th>
                        <th style="width: 30%;">Terminal Model</th>
                        <th style="width: 25%;">Description</th>
                        <th style="width: 8%; text-align: center;">Qty</th>
                        <th style="width: 5%;">Unit</th>
                        <th style="width: 12%; text-align: right;">Unit Price</th>
                        <th style="width: 15%; text-align: right;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($po->lines as $line)
                    <tr>
                        <td>{{ $line->line_no }}</td>
                        <td>{{ $line->model->model_name ?? '-' }}</td>
                        <td>{{ $line->description ?? '-' }}</td>
                        <td class="text-center">{{ number_format($line->quantity_ordered, 0) }}</td>
                        <td>{{ $line->unit }}</td>
                        <td class="text-right">{{ number_format($line->unit_price, 2) }}</td>
                        <td class="text-right">{{ number_format($line->line_total, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- Totals -->
            <table class="totals-table">
                <tr>
                    <td>Subtotal:</td>
                    <td>{{ $po->currency }} {{ number_format($po->subtotal, 2) }}</td>
                </tr>
                <tr>
                    <td>Tax Amount:</td>
                    <td>{{ $po->currency }} {{ number_format($po->tax_amount, 2) }}</td>
                </tr>
                @if($po->discount_amount > 0)
                <tr>
                    <td>Discount:</td>
                    <td>{{ $po->currency }} {{ number_format($po->discount_amount, 2) }}</td>
                </tr>
                @endif
                <tr class="grand-total">
                    <td>GRAND TOTAL:</td>
                    <td>{{ $po->currency }} {{ number_format($po->total_amount, 2) }}</td>
                </tr>
            </table>
        </div>

        <!-- Payment Terms -->
        @if($po->payment_terms)
        <div class="section">
            <div class="section-title">Payment Terms</div>
            <div style="padding: 5px;">
                {{ $po->payment_terms }}
            </div>
        </div>
        @endif

        <!-- Terms & Conditions -->
        @if($po->terms_conditions)
        <div class="terms-section">
            <strong>Terms & Conditions:</strong><br>
            {{ $po->terms_conditions }}
        </div>
        @endif

        <!-- Signatures -->
        <div class="signature-section">
            <table style="width: 100%;">
                <tr>
                    <td style="width: 48%; vertical-align: top;">
                        <div class="signature-box">
                            <strong>Prepared By:</strong>
                            <div class="signature-line">
                                {{ $po->createdBy->name ?? '' }}<br>
                                {{ $po->created_at->format('d M Y') }}
                            </div>
                        </div>
                    </td>
                    <td style="width: 4%;"></td>
                    <td style="width: 48%; vertical-align: top;">
                        <div class="signature-box">
                            <strong>Approved By:</strong>
                            <div class="signature-line">
                                @if($po->approvedBy)
                                    {{ $po->approvedBy->name }}<br>
                                    {{ $po->approved_at->format('d M Y') }}
                                @else
                                    <br><br>
                                @endif
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="footer">
        <div class="page-number"></div>
        Generated on {{ now()->format('d M Y, h:i A') }} | This is a computer-generated document
    </div>
</body>
</html>
