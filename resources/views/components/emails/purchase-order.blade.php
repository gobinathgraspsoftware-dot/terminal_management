<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #1f2937;
            background-color: #f3f4f6;
            margin: 0;
            padding: 0;
        }
        .email-wrapper {
            width: 100%;
            background-color: #f3f4f6;
            padding: 20px 0;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .email-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            padding: 30px;
            text-align: center;
        }
        .email-header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }
        .email-header p {
            margin: 8px 0 0 0;
            font-size: 14px;
            opacity: 0.9;
        }
        .email-body {
            padding: 40px 30px;
        }
        .greeting {
            font-size: 16px;
            margin-bottom: 20px;
        }
        .custom-message-box {
            background-color: #eff6ff;
            border-left: 4px solid #3b82f6;
            padding: 16px;
            margin: 24px 0;
            border-radius: 4px;
        }
        .custom-message-box strong {
            display: block;
            margin-bottom: 8px;
            color: #1e40af;
        }
        .po-details-card {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 24px;
            margin: 24px 0;
        }
        .po-details-card h2 {
            margin: 0 0 20px 0;
            font-size: 18px;
            color: #111827;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 12px;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: 500;
            color: #6b7280;
        }
        .detail-value {
            font-weight: 600;
            color: #111827;
            text-align: right;
        }
        .total-amount {
            background-color: #d1fae5;
            padding: 16px;
            border-radius: 6px;
            margin-top: 16px;
            text-align: center;
        }
        .total-amount .label {
            font-size: 14px;
            color: #065f46;
            margin-bottom: 4px;
        }
        .total-amount .amount {
            font-size: 32px;
            font-weight: 700;
            color: #059669;
        }
        .info-text {
            margin: 16px 0;
            line-height: 1.8;
        }
        .signature {
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid #e5e7eb;
        }
        .email-footer {
            background-color: #f9fafb;
            padding: 24px 30px;
            text-align: center;
            color: #6b7280;
            font-size: 13px;
        }
        .email-footer p {
            margin: 8px 0;
        }
        @media only screen and (max-width: 600px) {
            .email-body {
                padding: 24px 16px;
            }
            .po-details-card {
                padding: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-container">
            <!-- Header -->
            <div class="email-header">
                <h1>📋 Purchase Order</h1>
                <p>{{ $po->po_no }}</p>
            </div>

            <!-- Body -->
            <div class="email-body">
                <div class="greeting">
                    Dear <strong>{{ $po->vendor->vendor_name ?? 'Vendor' }}</strong>,
                </div>

                <p class="info-text">
                    We are pleased to send you our Purchase Order for your reference and processing.
                    Please review the details below and the attached PDF document.
                </p>

                {{-- Custom Message - NOTE: Using 'customMessage' NOT 'message' --}}
                @if(!empty($customMessage))
                <div class="custom-message-box">
                    <strong>📝 Message from Buyer:</strong>
                    <div>{{ $customMessage }}</div>
                </div>
                @endif

                <!-- PO Details Card -->
                <div class="po-details-card">
                    <h2>Purchase Order Details</h2>

                    <div class="detail-row">
                        <span class="detail-label">PO Number</span>
                        <span class="detail-value">{{ $po->po_no }}</span>
                    </div>

                    <div class="detail-row">
                        <span class="detail-label">PO Date</span>
                        <span class="detail-value">{{ $po->po_date->format('d M Y') }}</span>
                    </div>

                    @if($po->delivery_date)
                    <div class="detail-row">
                        <span class="detail-label">Expected Delivery</span>
                        <span class="detail-value">{{ $po->delivery_date->format('d M Y') }}</span>
                    </div>
                    @endif

                    @if($po->reference)
                    <div class="detail-row">
                        <span class="detail-label">Reference</span>
                        <span class="detail-value">{{ $po->reference }}</span>
                    </div>
                    @endif

                    @if($po->payment_terms)
                    <div class="detail-row">
                        <span class="detail-label">Payment Terms</span>
                        <span class="detail-value">{{ $po->payment_terms }}</span>
                    </div>
                    @endif

                    <div class="detail-row">
                        <span class="detail-label">Number of Items</span>
                        <span class="detail-value">{{ $po->lines->count() }} line(s)</span>
                    </div>

                    <!-- Total Amount Highlight -->
                    <div class="total-amount">
                        <div class="label">TOTAL AMOUNT</div>
                        <div class="amount">{{ $po->currency }} {{ number_format($po->total_amount, 2) }}</div>
                    </div>
                </div>

                <p class="info-text">
                    📎 <strong>The complete Purchase Order document is attached as a PDF.</strong>
                </p>

                <p class="info-text">
                    If you have any questions or require clarification regarding this Purchase Order,
                    please do not hesitate to contact us.
                </p>

                <p class="info-text">
                    We look forward to receiving your confirmation and timely delivery.
                </p>

                <!-- Signature -->
                <div class="signature">
                    <p style="margin: 0 0 4px 0;">Best regards,</p>
                    <p style="margin: 0; font-weight: 600; font-size: 16px;">{{ config('app.name', 'TMS') }}</p>
                </div>
            </div>

            <!-- Footer -->
            <div class="email-footer">
                <p><strong>⚠️ This is an automated email. Please do not reply directly to this message.</strong></p>
                <p>For inquiries, please contact your designated account manager.</p>
                <p style="margin-top: 16px;">
                    &copy; {{ date('Y') }} {{ config('app.name', 'TMS') }}. All rights reserved.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
