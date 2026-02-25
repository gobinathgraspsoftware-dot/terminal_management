{{-- resources/views/components/pdfs/grn.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>GRN - {{ $grn->grn_no }}</title>
    <style>
        /* ============================================================
           BASE STYLES
           ============================================================ */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', Arial, Helvetica, sans-serif;
            font-size: 10px;
            color: #333;
            line-height: 1.4;
        }
        .page {
            padding: 15px 25px;
        }

        /* ============================================================
           WATERMARK
           ============================================================ */
        @if(!empty($watermark))
        .watermark {
            position: fixed;
            top: 35%;
            left: 15%;
            font-size: 100px;
            color: rgba(200, 200, 200, 0.25);
            transform: rotate(-35deg);
            z-index: -1;
            font-weight: bold;
            letter-spacing: 15px;
            pointer-events: none;
        }
        @endif

        /* ============================================================
           HEADER
           ============================================================ */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .header-table td {
            vertical-align: top;
            padding: 0;
        }
        .company-name {
            font-size: 16px;
            font-weight: bold;
            color: #1a5276;
            margin-bottom: 3px;
        }
        .company-details {
            font-size: 8.5px;
            color: #666;
            line-height: 1.5;
        }
        .document-title {
            font-size: 20px;
            font-weight: bold;
            color: #1a5276;
            text-align: right;
        }
        .document-subtitle {
            font-size: 9px;
            color: #888;
            text-align: right;
            margin-top: 2px;
        }

        /* ============================================================
           DIVIDER
           ============================================================ */
        .divider {
            border-top: 2px solid #1a5276;
            margin: 8px 0 12px 0;
        }
        .divider-light {
            border-top: 1px solid #ddd;
            margin: 8px 0;
        }

        /* ============================================================
           INFO GRID (2-column)
           ============================================================ */
        .info-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .info-grid td {
            vertical-align: top;
            padding: 0;
            width: 50%;
        }
        .info-box {
            padding: 8px 10px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            margin: 0 4px 8px 0;
            background: #fafafa;
        }
        .info-box:last-child {
            margin-right: 0;
            margin-left: 4px;
        }
        .info-box-title {
            font-size: 8px;
            font-weight: bold;
            color: #1a5276;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
            border-bottom: 1px solid #e0e0e0;
            padding-bottom: 3px;
        }
        .info-row {
            margin-bottom: 2px;
        }
        .info-label {
            font-size: 8.5px;
            color: #888;
            display: inline-block;
            width: 100px;
        }
        .info-value {
            font-size: 9px;
            color: #333;
            font-weight: 600;
        }

        /* ============================================================
           STATUS BADGE
           ============================================================ */
        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .status-posted {
            background: #d4edda;
            color: #155724;
        }
        .status-draft {
            background: #e2e3e5;
            color: #383d41;
        }
        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        /* ============================================================
           LINE ITEMS TABLE
           ============================================================ */
        .section-title {
            font-size: 10px;
            font-weight: bold;
            color: #1a5276;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .items-table thead th {
            background: #1a5276;
            color: #fff;
            padding: 6px 8px;
            font-size: 8.5px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            text-align: left;
            border: 1px solid #1a5276;
        }
        .items-table thead th.text-center {
            text-align: center;
        }
        .items-table thead th.text-right {
            text-align: right;
        }
        .items-table tbody td {
            padding: 5px 8px;
            font-size: 9px;
            border: 1px solid #e0e0e0;
            vertical-align: top;
        }
        .items-table tbody tr:nth-child(even) {
            background: #f8f9fa;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }

        /* ============================================================
           SERIALS SUB-TABLE
           ============================================================ */
        .serials-row td {
            padding: 0 8px 5px 8px !important;
            border-top: none !important;
            background: #f0f4f8;
        }
        .serials-label {
            font-size: 7.5px;
            font-weight: bold;
            color: #1a5276;
            margin-bottom: 2px;
        }
        .serials-list {
            font-size: 8px;
            color: #555;
            line-height: 1.6;
        }
        .serial-chip {
            display: inline-block;
            background: #fff;
            border: 1px solid #d0d0d0;
            border-radius: 3px;
            padding: 1px 5px;
            margin: 1px 2px;
            font-size: 7.5px;
            font-family: 'DejaVu Sans Mono', monospace;
        }

        /* ============================================================
           TOTALS
           ============================================================ */
        .totals-table {
            width: 280px;
            border-collapse: collapse;
            margin-left: auto;
            margin-bottom: 15px;
        }
        .totals-table td {
            padding: 4px 8px;
            font-size: 9px;
        }
        .totals-table .total-label {
            text-align: right;
            color: #666;
            font-weight: 600;
        }
        .totals-table .total-value {
            text-align: right;
            font-weight: 600;
            width: 100px;
        }
        .totals-table .grand-total td {
            border-top: 2px solid #1a5276;
            font-size: 11px;
            font-weight: bold;
            color: #1a5276;
            padding-top: 6px;
        }

        /* ============================================================
           REMARKS
           ============================================================ */
        .remarks-box {
            padding: 8px 10px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            background: #fafafa;
            margin-bottom: 15px;
        }
        .remarks-title {
            font-size: 8px;
            font-weight: bold;
            color: #1a5276;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        .remarks-text {
            font-size: 9px;
            color: #555;
            white-space: pre-line;
        }

        /* ============================================================
           FOOTER
           ============================================================ */
        .footer {
            margin-top: 20px;
            border-top: 1px solid #e0e0e0;
            padding-top: 8px;
        }
        .footer-grid {
            width: 100%;
            border-collapse: collapse;
        }
        .footer-grid td {
            width: 33.33%;
            vertical-align: top;
            padding: 0 5px;
        }
        .signature-block {
            text-align: center;
            padding-top: 30px;
        }
        .signature-line {
            border-top: 1px solid #999;
            width: 80%;
            margin: 0 auto 4px auto;
        }
        .signature-label {
            font-size: 8px;
            color: #888;
        }
        .signature-name {
            font-size: 8.5px;
            font-weight: bold;
            color: #333;
            margin-top: 2px;
        }
        .print-info {
            font-size: 7px;
            color: #aaa;
            text-align: center;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    {{-- Watermark --}}
    @if(!empty($watermark))
        <div class="watermark">{{ $watermark }}</div>
    @endif

    <div class="page">

        {{-- ============================================================
             HEADER
             ============================================================ --}}
        <table class="header-table">
            <tr>
                <td style="width: 60%;">
                    @if(!empty($company['logo_path']))
                        <img src="{{ storage_path('app/public/' . $company['logo_path']) }}"
                             alt="Company Logo"
                             style="max-height: 45px; margin-bottom: 5px;">
                        <br>
                    @endif
                    <div class="company-name">{{ $company['name'] }}</div>
                    <div class="company-details">
                        {{ $company['registration_no'] }}<br>
                        {{ $company['address'] }}<br>
                        Tel: {{ $company['phone'] }} | Email: {{ $company['email'] }}
                        @if(!empty($company['website']))
                            <br>{{ $company['website'] }}
                        @endif
                    </div>
                </td>
                <td style="width: 40%;">
                    <div class="document-title">GOODS RECEIPT NOTE</div>
                    <div class="document-subtitle">
                        <span class="status-badge status-{{ $grn->status }}">{{ strtoupper($grn->status) }}</span>
                    </div>
                </td>
            </tr>
        </table>

        <div class="divider"></div>

        {{-- ============================================================
             GRN + VENDOR INFO
             ============================================================ --}}
        <table class="info-grid">
            <tr>
                <td>
                    <div class="info-box">
                        <div class="info-box-title">GRN Details</div>
                        <div class="info-row">
                            <span class="info-label">GRN No:</span>
                            <span class="info-value">{{ $grn->grn_no }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">GRN Date:</span>
                            <span class="info-value">{{ $grn->grn_date ? $grn->grn_date->format('d M Y') : '-' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">PO No:</span>
                            <span class="info-value">{{ $grn->purchaseOrder?->po_no ?? 'N/A' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Delivery Note:</span>
                            <span class="info-value">{{ $grn->delivery_note_no ?? 'N/A' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Receiving Depot:</span>
                            <span class="info-value">{{ $grn->receivingDepot?->depot_name ?? '-' }}</span>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="info-box" style="margin-left: 4px; margin-right: 0;">
                        <div class="info-box-title">Vendor Information</div>
                        <div class="info-row">
                            <span class="info-label">Vendor Name:</span>
                            <span class="info-value">{{ $grn->vendor?->vendor_name ?? $grn->vendor?->company_name ?? '-' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Contact:</span>
                            <span class="info-value">{{ $grn->vendor?->pic_name ?? '-' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Phone:</span>
                            <span class="info-value">{{ $grn->vendor?->pic_phone ?? '-' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Email:</span>
                            <span class="info-value">{{ $grn->vendor?->pic_email ?? '-' }}</span>
                        </div>
                        @if($grn->isPosted())
                        <div class="info-row">
                            <span class="info-label">Posted By:</span>
                            <span class="info-value">{{ $grn->postedBy?->name ?? '-' }} ({{ $grn->posted_at?->format('d M Y H:i') }})</span>
                        </div>
                        @endif
                    </div>
                </td>
            </tr>
        </table>

        {{-- ============================================================
             LINE ITEMS
             ============================================================ --}}
        <div class="section-title">Line Items</div>

        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 30px;" class="text-center">#</th>
                    <th style="width: 80px;">Model</th>
                    <th>Description</th>
                    <th style="width: 55px;" class="text-center">Qty</th>
                    <th style="width: 40px;" class="text-center">Unit</th>
                    <th style="width: 75px;" class="text-right">Unit Cost</th>
                    <th style="width: 85px;" class="text-right">Line Total</th>
                </tr>
            </thead>
            <tbody>
                @php $grandTotal = 0; @endphp
                @foreach($grn->lines as $line)
                    @php $grandTotal += $line->line_total; @endphp
                    <tr>
                        <td class="text-center">{{ $line->line_no }}</td>
                        <td>
                            {{ $line->model?->model_name ?? '-' }}
                            @if($line->model && $line->model->category)
                                <br><span style="font-size: 7.5px; color: #888;">{{ $line->model->category->category_name ?? '' }}</span>
                            @endif
                        </td>
                        <td>{{ $line->description ?? '-' }}</td>
                        <td class="text-center">{{ number_format($line->quantity_received, 0) }}</td>
                        <td class="text-center">{{ $line->unit ?? 'pcs' }}</td>
                        <td class="text-right">{{ number_format($line->unit_cost, 2) }}</td>
                        <td class="text-right">{{ number_format($line->line_total, 2) }}</td>
                    </tr>

                    {{-- Serial Numbers (if any) --}}
                    @if($line->serials->isNotEmpty())
                        <tr class="serials-row">
                            <td colspan="7">
                                <div class="serials-label">Serial Numbers ({{ $line->serials->count() }}):</div>
                                <div class="serials-list">
                                    @foreach($line->serials as $serial)
                                        <span class="serial-chip">{{ $serial->serial_no }}</span>
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>

        {{-- ============================================================
             TOTALS
             ============================================================ --}}
        <table class="totals-table">
            <tr>
                <td class="total-label">Total Items:</td>
                <td class="total-value">{{ $grn->total_items }}</td>
            </tr>
            <tr class="grand-total">
                <td class="total-label">Grand Total (MYR):</td>
                <td class="total-value">{{ number_format($grandTotal, 2) }}</td>
            </tr>
        </table>

        {{-- ============================================================
             REMARKS
             ============================================================ --}}
        @if($grn->remarks)
            <div class="remarks-box">
                <div class="remarks-title">Remarks</div>
                <div class="remarks-text">{{ $grn->remarks }}</div>
            </div>
        @endif

        {{-- ============================================================
             SIGNATURES
             ============================================================ --}}
        <div class="footer">
            <table class="footer-grid">
                <tr>
                    <td>
                        <div class="signature-block">
                            <div class="signature-line"></div>
                            <div class="signature-label">Prepared By</div>
                            <div class="signature-name">{{ $grn->createdBy?->name ?? '-' }}</div>
                        </div>
                    </td>
                    <td>
                        <div class="signature-block">
                            <div class="signature-line"></div>
                            <div class="signature-label">Received By</div>
                            <div class="signature-name">{{ $grn->postedBy?->name ?? '________________' }}</div>
                        </div>
                    </td>
                    <td>
                        <div class="signature-block">
                            <div class="signature-line"></div>
                            <div class="signature-label">Verified By</div>
                            <div class="signature-name">________________</div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        {{-- Print info --}}
        <div class="print-info">
            Printed on {{ now()->format('d M Y H:i') }} | {{ $company['name'] }} &mdash; Terminal Management System
        </div>

    </div>
</body>
</html>
