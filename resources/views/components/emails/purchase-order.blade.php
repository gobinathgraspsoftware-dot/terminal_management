<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Order - {{ $po->po_no }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background: #f9f9f9;
            padding: 30px;
            border-radius: 0 0 8px 8px;
        }
        .po-details {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .detail-label {
            font-weight: bold;
            color: #666;
        }
        .detail-value {
            color: #333;
        }
        .message-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #eee;
            color: #666;
            font-size: 12px;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1 style="margin: 0;">Purchase Order</h1>
        <p style="margin: 10px 0 0 0; font-size: 24px; font-weight: bold;">{{ $po->po_no }}</p>
    </div>

    <div class="content">
        <p>Dear {{ $po->vendor->pic_name ?? 'Vendor' }},</p>

        <p>Please find attached our Purchase Order <strong>{{ $po->po_no }}</strong> dated <strong>{{ $po->po_date->format('d M Y') }}</strong>.</p>

        @if($message)
        <div class="message-box">
            <strong>Additional Message:</strong><br>
            {{ $message }}
        </div>
        @endif

        <div class="po-details">
            <h3 style="margin-top: 0;">Purchase Order Details</h3>
            
            <div class="detail-row">
                <span class="detail-label">PO Number:</span>
                <span class="detail-value">{{ $po->po_no }}</span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">PO Date:</span>
                <span class="detail-value">{{ $po->po_date->format('d M Y') }}</span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Delivery Date:</span>
                <span class="detail-value">{{ $po->delivery_date?->format('d M Y') ?? 'TBD' }}</span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Total Amount:</span>
                <span class="detail-value"><strong>{{ $po->currency }} {{ number_format($po->total_amount, 2) }}</strong></span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Items:</span>
                <span class="detail-value">{{ $po->lines->count() }} line item(s)</span>
            </div>
        </div>

        <div style="background: white; padding: 20px; border-radius: 8px; margin: 20px 0;">
            <h4 style="margin-top: 0;">Delivery Address:</h4>
            <p style="margin: 0; white-space: pre-line;">{{ $po->delivery_address }}</p>
        </div>

        @if($po->payment_terms)
        <div style="background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <strong>Payment Terms:</strong><br>
            {{ $po->payment_terms }}
        </div>
        @endif

        <p>The Purchase Order document is attached to this email in PDF format.</p>

        <p>If you have any questions regarding this purchase order, please don't hesitate to contact us.</p>

        <p>Thank you for your business!</p>

        <p>
            Best regards,<br>
            <strong>{{ $po->createdBy->name ?? 'TMS Team' }}</strong><br>
            GRASP SOFTWARE SOLUTIONS<br>
            Terminal Management System
        </p>
    </div>

    <div class="footer">
        <p>This is an automated email from the Terminal Management System.</p>
        <p>© {{ date('Y') }} GRASP SOFTWARE SOLUTIONS. All rights reserved.</p>
    </div>
</body>
</html>
