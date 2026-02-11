<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotation - {{ $quotation->quotation_no }}</title>
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
            background-color: #3498db;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f9f9f9;
            padding: 30px;
            border: 1px solid #ddd;
            border-top: none;
        }
        .quotation-info {
            background-color: white;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid #3498db;
            border-radius: 4px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .label {
            font-weight: bold;
            color: #555;
        }
        .value {
            color: #333;
        }
        .total-amount {
            background-color: #34495e;
            color: white;
            padding: 15px;
            text-align: center;
            border-radius: 4px;
            font-size: 18px;
            font-weight: bold;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            padding: 20px;
            background-color: #34495e;
            color: white;
            border-radius: 0 0 5px 5px;
            font-size: 14px;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 0;
        }
        .note {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
        .custom-message {
            background-color: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $quotation->quotation_type === 'customer' ? 'Quotation for Your Review' : 'Vendor Quotation' }}</h1>
        <p>{{ $quotation->quotation_no }}</p>
    </div>
    
    <div class="content">
        @if($quotation->quotation_type === 'customer')
            <p>Dear {{ $quotation->client->client_name ?? 'Valued Customer' }},</p>
            <p>Thank you for your interest in our products and services. Please find attached our quotation for your review.</p>
        @else
            <p>Dear {{ $quotation->vendor->vendor_name ?? 'Vendor' }},</p>
            <p>We would like to request a quotation for the following items. Please review the attached document and provide your best pricing.</p>
        @endif
        
        @if($customMessage)
            <div class="custom-message">
                <p><strong>Message:</strong></p>
                <p>{{ $customMessage }}</p>
            </div>
        @endif
        
        <div class="quotation-info">
            <h3 style="margin-top: 0; color: #2c3e50;">Quotation Details</h3>
            
            <div class="info-row">
                <span class="label">Quotation No:</span>
                <span class="value">{{ $quotation->quotation_no }}</span>
            </div>
            
            <div class="info-row">
                <span class="label">Date:</span>
                <span class="value">{{ $quotation->quotation_date->format('d M Y') }}</span>
            </div>
            
            @if($quotation->valid_until)
                <div class="info-row">
                    <span class="label">Valid Until:</span>
                    <span class="value">{{ $quotation->valid_until->format('d M Y') }}</span>
                </div>
            @endif
            
            @if($quotation->reference)
                <div class="info-row">
                    <span class="label">Reference:</span>
                    <span class="value">{{ $quotation->reference }}</span>
                </div>
            @endif
            
            <div class="info-row">
                <span class="label">Total Items:</span>
                <span class="value">{{ $quotation->lines->count() }} item(s)</span>
            </div>
        </div>
        
        <div class="total-amount">
            <div style="font-size: 14px; margin-bottom: 5px;">Total Amount</div>
            <div style="font-size: 24px;">{{ $quotation->currency }} {{ number_format($quotation->total_amount, 2) }}</div>
        </div>
        
        @if($quotation->valid_until)
            <div class="note">
                <strong>⚠ Important Notice:</strong><br>
                This quotation is valid until <strong>{{ $quotation->valid_until->format('d M Y') }}</strong>. 
                Please respond before this date to secure the quoted prices.
            </div>
        @endif
        
        @if($quotation->quotation_type === 'customer')
            <p>The detailed quotation is attached as a PDF document. If you have any questions or would like to proceed with this quotation, please don't hesitate to contact us.</p>
            
            <div style="text-align: center; margin: 30px 0;">
                <p><strong>Ready to proceed?</strong></p>
                <p>Please review the attached quotation and let us know if you would like to move forward.</p>
            </div>
        @else
            <p>Please review the attached quotation request and provide your best pricing and delivery timeline at your earliest convenience.</p>
        @endif
        
        <p style="margin-top: 30px;">
            <strong>Contact Information:</strong><br>
            Email: {{ config('mail.from.address', 'info@grasp.com.my') }}<br>
            Phone: +60 3-1234 5678
        </p>
        
        <p>Thank you for your business!</p>
        
        <p>
            Best regards,<br>
            <strong>{{ $quotation->createdBy->name ?? 'GRASP Team' }}</strong><br>
            GRASP SOFTWARE SOLUTIONS
        </p>
    </div>
    
    <div class="footer">
        <p style="margin: 0;">GRASP SOFTWARE SOLUTIONS</p>
        <p style="margin: 5px 0; font-size: 12px;">
            This is an automated email. Please do not reply directly to this message.
        </p>
    </div>
</body>
</html>
