<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotation - {{ $quotation->quotation_no }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none; }
        }
        body { font-family: 'Arial', sans-serif; }
        .company-header { border-bottom: 3px solid #0d6efd; padding-bottom: 20px; margin-bottom: 20px; }
        .quotation-title { font-size: 24px; font-weight: bold; color: #0d6efd; }
        .info-table th { background-color: #f8f9fa; width: 30%; }
        .line-items-table { margin-top: 20px; }
        .line-items-table thead { background-color: #0d6efd; color: white; }
        .totals-table { margin-top: 20px; }
        .totals-table th { text-align: right; }
        .signature-section { margin-top: 50px; }
        .signature-box { border-top: 1px solid #000; margin-top: 60px; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="container mt-4">
        <button class="btn btn-primary no-print mb-3" onclick="window.print()">
            <i class="bi bi-printer"></i> Print
        </button>

        <!-- Company Header -->
        <div class="company-header">
            <div class="row">
                <div class="col-md-6">
                    <h3>GRASP SOFTWARE SOLUTIONS</h3>
                    <p class="mb-0">Terminal Management System</p>
                    <p class="mb-0">Email: info@graspsoftware.com</p>
                    <p class="mb-0">Phone: +60 12-345 6789</p>
                </div>
                <div class="col-md-6 text-end">
                    <div class="quotation-title">QUOTATION</div>
                    <h4 class="text-muted">{{ $quotation->quotation_no }}</h4>
                </div>
            </div>
        </div>

        <!-- Quotation Details -->
        <div class="row">
            <div class="col-md-6">
                <h5>{{ $quotation->quotation_type == 'customer' ? 'Bill To' : 'Vendor' }}:</h5>
                <strong>{{ $quotation->party_name }}</strong>
                @if($quotation->client)
                    <p class="mb-0">{{ $quotation->client->address ?? '' }}</p>
                    <p class="mb-0">{{ $quotation->client->city ?? '' }}, {{ $quotation->client->state ?? '' }}</p>
                    <p class="mb-0">{{ $quotation->client->phone ?? '' }}</p>
                    <p class="mb-0">{{ $quotation->client->email ?? '' }}</p>
                @elseif($quotation->vendor)
                    <p class="mb-0">{{ $quotation->vendor->address ?? '' }}</p>
                    <p class="mb-0">{{ $quotation->vendor->city ?? '' }}, {{ $quotation->vendor->state ?? '' }}</p>
                    <p class="mb-0">{{ $quotation->vendor->phone ?? '' }}</p>
                    <p class="mb-0">{{ $quotation->vendor->email ?? '' }}</p>
                @endif
            </div>
            <div class="col-md-6">
                <table class="table table-sm info-table">
                    <tr>
                        <th>Quotation No:</th>
                        <td>{{ $quotation->quotation_no }}</td>
                    </tr>
                    <tr>
                        <th>Date:</th>
                        <td>{{ \Carbon\Carbon::parse($quotation->quotation_date)->format('d M Y') }}</td>
                    </tr>
                    <tr>
                        <th>Valid Until:</th>
                        <td>{{ \Carbon\Carbon::parse($quotation->valid_until)->format('d M Y') }}</td>
                    </tr>
                    @if($quotation->reference)
                    <tr>
                        <th>Reference:</th>
                        <td>{{ $quotation->reference }}</td>
                    </tr>
                    @endif
                    <tr>
                        <th>Status:</th>
                        <td><strong>{{ $quotation->getStatusLabel() }}</strong></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Line Items -->
        <div class="line-items-table">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th width="5%">#</th>
                        <th width="40%">Description</th>
                        <th width="10%" class="text-center">Quantity</th>
                        <th width="15%" class="text-end">Unit Price (MYR)</th>
                        <th width="10%" class="text-center">Disc %</th>
                        <th width="10%" class="text-center">Tax %</th>
                        <th width="15%" class="text-end">Amount (MYR)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($quotation->lines as $index => $line)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>
                            {{ $line->description }}
                            @if($line->model)
                                <br><small class="text-muted">Model: {{ $line->model->model_name }}</small>
                            @elseif($line->charge)
                                <br><small class="text-muted">Charge: {{ $line->charge->charge_name }}</small>
                            @endif
                        </td>
                        <td class="text-center">{{ number_format($line->quantity, 2) }}</td>
                        <td class="text-end">{{ number_format($line->unit_price, 2) }}</td>
                        <td class="text-center">{{ number_format($line->discount_percent, 2) }}%</td>
                        <td class="text-center">{{ number_format($line->tax_rate, 2) }}%</td>
                        <td class="text-end">{{ number_format($line->line_total, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Totals -->
        <div class="row">
            <div class="col-md-7"></div>
            <div class="col-md-5">
                <table class="table table-sm totals-table">
                    <tr>
                        <th>Subtotal:</th>
                        <td class="text-end">MYR {{ number_format($quotation->subtotal_amount, 2) }}</td>
                    </tr>
                    <tr>
                        <th>Tax:</th>
                        <td class="text-end">MYR {{ number_format($quotation->tax_amount, 2) }}</td>
                    </tr>
                    @if($quotation->discount_amount > 0)
                    <tr>
                        <th>Discount:</th>
                        <td class="text-end">(MYR {{ number_format($quotation->discount_amount, 2) }})</td>
                    </tr>
                    @endif
                    <tr class="table-primary">
                        <th><strong>Grand Total:</strong></th>
                        <th class="text-end"><strong>MYR {{ number_format($quotation->total_amount, 2) }}</strong></th>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Terms & Conditions -->
        @if($quotation->terms_conditions)
        <div class="mt-4">
            <h5>Terms & Conditions</h5>
            <p>{{ $quotation->terms_conditions }}</p>
        </div>
        @endif

        <!-- Notes -->
        @if($quotation->notes)
        <div class="mt-3">
            <h5>Notes</h5>
            <p>{{ $quotation->notes }}</p>
        </div>
        @endif

        <!-- Signature Section -->
        <div class="signature-section no-print">
            <div class="row">
                <div class="col-md-6">
                    <div class="signature-box">
                        <strong>Prepared By:</strong><br>
                        {{ $quotation->createdBy->name }}<br>
                        Date: {{ $quotation->created_at->format('d M Y') }}
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="signature-box">
                        <strong>Accepted By:</strong><br>
                        <br>
                        Date: _______________
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center mt-5 text-muted">
            <small>This is a computer-generated quotation. No signature required.</small>
        </div>
    </div>

    <script>
        // Auto print on load (optional)
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>
