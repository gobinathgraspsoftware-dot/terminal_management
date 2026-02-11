<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Quotation - {{ $quotation->quotation_no }}</title>
    <style>
        @page {
            margin: 10mm;
            size: A4 portrait;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 9pt;
            color: #333;
            line-height: 1.3;
        }

        .container {
            width: 190mm;
            margin: 0 auto;
        }

        /* Header Section */
        .header {
            border-bottom: 3px solid #2c3e50;
            padding-bottom: 10px;
            margin-bottom: 15px;
            overflow: hidden;
        }

        .logo-section {
            float: left;
            width: 30%;
        }

        .logo {
            max-width: 120px;
            max-height: 60px;
        }

        .company-info {
            float: right;
            width: 65%;
            text-align: right;
        }

        .company-name {
            font-size: 16pt;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 4px;
        }

        .company-details {
            font-size: 8pt;
            color: #666;
            line-height: 1.4;
        }

        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }

        /* Document Title */
        .document-title {
            text-align: center;
            margin: 15px 0;
            padding: 8px;
            background-color: #3498db;
            color: white;
        }

        .document-title h1 {
            font-size: 18pt;
            margin: 0;
            letter-spacing: 1px;
        }

        .document-subtitle {
            font-size: 9pt;
            margin-top: 3px;
        }

        /* Quotation Details */
        .quotation-details {
            margin: 15px 0;
            overflow: hidden;
        }

        .details-column {
            width: 48%;
            float: left;
            vertical-align: top;
        }

        .details-column.left {
            margin-right: 4%;
        }

        .detail-row {
            margin-bottom: 6px;
            font-size: 8.5pt;
        }

        .detail-label {
            font-weight: bold;
            color: #555;
            display: inline-block;
            width: 100px;
        }

        .detail-value {
            color: #333;
        }

        .party-box {
            background-color: #f8f9fa;
            padding: 10px;
            border: 1px solid #dee2e6;
            min-height: 120px;
        }

        .party-title {
            font-weight: bold;
            font-size: 10pt;
            color: #2c3e50;
            margin-bottom: 8px;
            border-bottom: 2px solid #3498db;
            padding-bottom: 4px;
        }

        /* Line Items Table */
        .line-items {
            margin: 15px 0;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        .table thead {
            background-color: #34495e;
            color: white;
        }

        .table th {
            padding: 6px 4px;
            text-align: left;
            font-weight: bold;
            font-size: 8pt;
            border: 1px solid #2c3e50;
        }

        .table td {
            padding: 6px 4px;
            border: 1px solid #ddd;
            font-size: 8pt;
            word-wrap: break-word;
        }

        .table tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        /* Totals Section */
        .totals-section {
            margin-top: 15px;
            float: right;
            width: 45%;
        }

        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }

        .totals-table td {
            padding: 6px;
            border-bottom: 1px solid #ddd;
            font-size: 8.5pt;
        }

        .totals-table tr.total-row {
            background-color: #34495e;
            color: white;
            font-weight: bold;
            font-size: 10pt;
        }

        .totals-table tr.total-row td {
            border: none;
            padding: 10px 6px;
        }

        /* Terms & Conditions */
        .terms-section {
            clear: both;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 2px solid #ddd;
        }

        .section-title {
            font-size: 11pt;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 8px;
            border-bottom: 2px solid #3498db;
            padding-bottom: 4px;
        }

        .terms-content {
            font-size: 8pt;
            line-height: 1.5;
            white-space: pre-line;
        }

        /* Footer */
        .footer {
            margin-top: 20px;
            padding-top: 12px;
            border-top: 2px solid #2c3e50;
            font-size: 8pt;
            color: #666;
            overflow: hidden;
        }

        .footer-column {
            width: 48%;
            float: left;
        }

        .footer-column.right {
            float: right;
            text-align: right;
        }

        .validity-note {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            padding: 8px;
            margin-top: 12px;
            font-weight: bold;
            color: #856404;
            font-size: 8.5pt;
        }

        /* Signature Section */
        .signature-section {
            margin-top: 30px;
            overflow: hidden;
        }

        .signature-box {
            width: 48%;
            float: left;
            text-align: center;
            padding: 8px;
        }

        .signature-line {
            border-top: 1px solid #333;
            margin-top: 50px;
            padding-top: 4px;
            font-weight: bold;
            font-size: 8pt;
        }

        /* Watermark */
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 80pt;
            color: rgba(0, 0, 0, 0.05);
            font-weight: bold;
            z-index: -1;
        }

        /* Status Badge */
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 3px;
            font-size: 8pt;
            font-weight: bold;
        }

        .status-draft { background-color: #6c757d; color: white; }
        .status-pending_approval { background-color: #ffc107; color: #000; }
        .status-approved { background-color: #28a745; color: white; }
        .status-sent { background-color: #17a2b8; color: white; }
        .status-accepted { background-color: #28a745; color: white; }
        .status-rejected { background-color: #dc3545; color: white; }
        .status-expired { background-color: #dc3545; color: white; }
        .status-cancelled { background-color: #6c757d; color: white; }

        /* Item Description */
        .item-description {
            font-size: 7pt;
            color: #666;
            font-style: italic;
            margin-top: 2px;
        }

        small {
            font-size: 7pt;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Watermark -->
        @if($watermark ?? false)
            <div class="watermark">{{ $watermark }}</div>
        @endif

        <!-- Header -->
        <div class="header clearfix">
            <div class="logo-section">
                @if(isset($company['logo_path']) && $company['logo_path'])
                    <img src="{{ public_path('storage/' . $company['logo_path']) }}" alt="Logo" class="logo">
                @endif
            </div>
            <div class="company-info">
                <div class="company-name">{{ $company['name'] ?? 'COMPANY NAME' }}</div>
                <div class="company-details">
                    @if(isset($company['registration_no']) && $company['registration_no'])
                        {{ $company['registration_no'] }}<br>
                    @endif
                    @if(isset($company['tax_id']) && $company['tax_id'])
                        {{ $company['tax_id'] }}<br>
                    @endif
                    @if(isset($company['address']) && $company['address'])
                        {{ $company['address'] }}<br>
                    @endif
                    @if(isset($company['phone']) && $company['phone'])
                        Tel: {{ $company['phone'] }}
                    @endif
                    @if(isset($company['email']) && $company['email'])
                        | Email: {{ $company['email'] }}
                    @endif
                    @if(isset($company['website']) && $company['website'])
                        <br>{{ $company['website'] }}
                    @endif
                </div>
            </div>
        </div>

        <!-- Document Title -->
        <div class="document-title">
            <h1>{{ strtoupper($quotation->getTypeLabel()) }} QUOTATION</h1>
            <div class="document-subtitle">{{ $quotation->quotation_no }}</div>
        </div>

        <!-- Quotation Details -->
        <div class="quotation-details clearfix">
            <!-- Left Column -->
            <div class="details-column left">
                <div class="detail-row">
                    <span class="detail-label">Quotation No:</span>
                    <span class="detail-value">{{ $quotation->quotation_no }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Date:</span>
                    <span class="detail-value">{{ $quotation->quotation_date->format('d M Y') }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Valid Until:</span>
                    <span class="detail-value">
                        @if($quotation->valid_until)
                            {{ $quotation->valid_until->format('d M Y') }}
                        @else
                            N/A
                        @endif
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Reference:</span>
                    <span class="detail-value">{{ $quotation->reference ?? '-' }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Status:</span>
                    <span class="status-badge status-{{ strtolower(str_replace('_', '-', $quotation->status)) }}">
                        {{ $quotation->getStatusLabel() }}
                    </span>
                </div>
            </div>

            <!-- Right Column -->
            <div class="details-column">
                <div class="party-box">
                    <div class="party-title">
                        @if($quotation->quotation_type === 'customer')
                            QUOTATION TO
                        @else
                            QUOTATION FROM VENDOR
                        @endif
                    </div>

                    @if($quotation->quotation_type === 'customer' && $quotation->client)
                        <strong>{{ $quotation->client->client_name }}</strong><br>
                        @if($quotation->client->company_name)
                            {{ $quotation->client->company_name }}<br>
                        @endif
                        @if($quotation->client->registration_no)
                            Reg: {{ $quotation->client->registration_no }}<br>
                        @endif
                        @if($quotation->client->address)
                            {{ $quotation->client->address }}<br>
                        @endif
                        @if($quotation->client->city || $quotation->client->postcode)
                            {{ $quotation->client->city ?? '' }} {{ $quotation->client->postcode ?? '' }}<br>
                        @endif
                        @if($quotation->client->state || $quotation->client->country)
                            {{ $quotation->client->state ?? '' }}@if($quotation->client->state && $quotation->client->country), @endif{{ $quotation->client->country ?? '' }}<br>
                        @endif
                        @if($quotation->client->pic_name)
                            Attn: {{ $quotation->client->pic_name }}<br>
                        @endif
                        @if($quotation->client->pic_phone)
                            Tel: {{ $quotation->client->pic_phone }}
                        @endif
                        @if($quotation->client->pic_email)
                            <br>Email: {{ $quotation->client->pic_email }}
                        @endif
                    @elseif($quotation->quotation_type === 'vendor' && $quotation->vendor)
                        <strong>{{ $quotation->vendor->vendor_name }}</strong><br>
                        @if($quotation->vendor->company_name)
                            {{ $quotation->vendor->company_name }}<br>
                        @endif
                        @if($quotation->vendor->address)
                            {{ $quotation->vendor->address }}<br>
                        @endif
                        @if($quotation->vendor->city || $quotation->vendor->postcode)
                            {{ $quotation->vendor->city ?? '' }} {{ $quotation->vendor->postcode ?? '' }}<br>
                        @endif
                        @if($quotation->vendor->state || $quotation->vendor->country)
                            {{ $quotation->vendor->state ?? '' }}@if($quotation->vendor->state && $quotation->vendor->country), @endif{{ $quotation->vendor->country ?? '' }}<br>
                        @endif
                        @if($quotation->vendor->pic_name)
                            Attn: {{ $quotation->vendor->pic_name }}<br>
                        @endif
                        @if($quotation->vendor->pic_phone)
                            Tel: {{ $quotation->vendor->pic_phone }}
                        @endif
                        @if($quotation->vendor->pic_email)
                            <br>Email: {{ $quotation->vendor->pic_email }}
                        @endif
                    @endif
                </div>
            </div>
        </div>

        <!-- Line Items -->
        <div class="line-items">
            <h3 class="section-title">ITEMS / SERVICES</h3>

            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 5%;" class="text-center">#</th>
                        <th style="width: 35%;">Description</th>
                        <th style="width: 10%;" class="text-center">Qty</th>
                        <th style="width: 13%;" class="text-right">Unit Price</th>
                        @if($showTax ?? true)
                            <th style="width: 8%;" class="text-right">Tax</th>
                        @endif
                        <th style="width: 13%;" class="text-right">Discount</th>
                        <th style="width: 16%;" class="text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($quotation->lines->sortBy('line_no') as $line)
                        <tr>
                            <td class="text-center">{{ $line->line_no }}</td>
                            <td>
                                <strong>{{ $line->description }}</strong>
                                @if($line->item_type === 'model' && $line->model)
                                    <div class="item-description">
                                        Model: {{ $line->model->model_name }}
                                        @if($line->model->category)
                                            ({{ $line->model->category->category_name }})
                                        @endif
                                    </div>
                                @elseif($line->item_type === 'charge' && $line->charge)
                                    <div class="item-description">
                                        Charge: {{ $line->charge->charge_name }}
                                    </div>
                                @endif
                                @if($line->remarks)
                                    <div class="item-description">Note: {{ $line->remarks }}</div>
                                @endif
                            </td>
                            <td class="text-center">{{ number_format($line->quantity, 2) }} {{ $line->unit ?? 'pcs' }}</td>
                            <td class="text-right">{{ $quotation->currency }} {{ number_format($line->unit_price, 2) }}</td>
                            @if($showTax ?? true)
                                <td class="text-right">{{ number_format($line->tax_rate, 0) }}%</td>
                            @endif
                            <td class="text-right">
                                @if($line->discount_amount > 0)
                                    {{ $quotation->currency }} {{ number_format($line->discount_amount, 2) }}
                                    @if($line->discount_percent > 0)
                                        <br><small>({{ number_format($line->discount_percent, 1) }}%)</small>
                                    @endif
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-right"><strong>{{ $quotation->currency }} {{ number_format($line->line_total, 2) }}</strong></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Totals -->
        <div class="totals-section">
            <table class="totals-table">
                <tr>
                    <td><strong>Subtotal:</strong></td>
                    <td class="text-right">{{ $quotation->currency }} {{ number_format($quotation->subtotal, 2) }}</td>
                </tr>
                @if($quotation->discount_amount > 0)
                    <tr>
                        <td><strong>Discount:</strong></td>
                        <td class="text-right">({{ $quotation->currency }} {{ number_format($quotation->discount_amount, 2) }})</td>
                    </tr>
                @endif
                @if(($showTax ?? true) && $quotation->tax_amount > 0)
                    <tr>
                        <td><strong>Tax:</strong></td>
                        <td class="text-right">{{ $quotation->currency }} {{ number_format($quotation->tax_amount, 2) }}</td>
                    </tr>
                @endif
                <tr class="total-row">
                    <td><strong>TOTAL:</strong></td>
                    <td class="text-right"><strong>{{ $quotation->currency }} {{ number_format($quotation->total_amount, 2) }}</strong></td>
                </tr>
            </table>
        </div>

        <!-- Terms & Conditions -->
        <div class="terms-section">
            <h3 class="section-title">TERMS & CONDITIONS</h3>
            <div class="terms-content">
                @if($quotation->terms_conditions)
                    {{ $quotation->terms_conditions }}
                @else
1. This quotation is valid for {{ $quotation->valid_until ? $quotation->valid_until->diffInDays($quotation->quotation_date) : 30 }} days from the date of issue.
2. Prices are quoted in {{ $quotation->currency }} and are subject to change without prior notice.
3. Payment terms: As per agreement or Net 30 days from invoice date.
4. Delivery timeline will be confirmed upon order confirmation.
5. All disputes are subject to local jurisdiction.
                @endif
            </div>

            @if($quotation->notes)
                <h3 class="section-title" style="margin-top: 15px;">NOTES</h3>
                <div class="terms-content">{{ $quotation->notes }}</div>
            @endif
        </div>

        <!-- Validity Notice -->
        @if($quotation->valid_until)
            <div class="validity-note">
                ⚠ This quotation is valid until {{ $quotation->valid_until->format('d M Y') }}
            </div>
        @endif

        <!-- Signature Section -->
        <div class="signature-section clearfix">
            <div class="signature-box">
                <div class="signature-line">
                    Prepared By<br>
                    {{ $quotation->createdBy->name ?? 'N/A' }}<br>
                    {{ $quotation->created_at->format('d M Y') }}
                </div>
            </div>
            @if($quotation->approvedBy)
                <div class="signature-box">
                    <div class="signature-line">
                        Approved By<br>
                        {{ $quotation->approvedBy->name }}<br>
                        {{ $quotation->approved_at ? $quotation->approved_at->format('d M Y') : '' }}
                    </div>
                </div>
            @else
                <div class="signature-box">
                    <div class="signature-line">
                        Customer Acceptance<br>
                        Name & Signature<br>
                        Date: _______________
                    </div>
                </div>
            @endif
        </div>

        <!-- Footer -->
        <div class="footer clearfix">
            <div class="footer-column">
                <strong>Bank Details for Payment:</strong><br>
                @if(isset($company['bank_name']) && $company['bank_name'])
                    Bank: {{ $company['bank_name'] }}<br>
                    Account No: {{ $company['bank_account_no'] ?? 'N/A' }}<br>
                    Account Name: {{ $company['bank_account_name'] ?? 'N/A' }}
                @else
                    Please contact us for payment details.
                @endif
            </div>
            <div class="footer-column right">
                <strong>For inquiries:</strong><br>
                @if(isset($company['email']) && $company['email'])
                    Email: {{ $company['email'] }}<br>
                @endif
                @if(isset($company['phone']) && $company['phone'])
                    Phone: {{ $company['phone'] }}<br>
                @endif
                @if(isset($company['website']) && $company['website'])
                    Website: {{ $company['website'] }}
                @endif
            </div>
        </div>

        <div style="clear: both; text-align: center; margin-top: 10px; padding-top: 8px; border-top: 1px solid #ddd;">
            <small>This is a computer-generated document. No signature is required.</small>
        </div>
    </div>
</body>
</html>
