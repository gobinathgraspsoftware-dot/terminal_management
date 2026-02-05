<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Stock Card - {{ $stockCardData['serial']->serial_no }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            @page {
                margin: 0.5cm;
            }
        }
        
        body {
            font-size: 13px;
            font-family: Arial, sans-serif;
        }
        
        .header-section {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
            color: white;
            padding: 15px;
            margin: -12px -12px 20px -12px;
            border-radius: 0;
        }
        
        .info-box {
            background-color: #e0f7fa;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 10px;
            border-left: 4px solid #17a2b8;
        }
        
        .info-label {
            font-weight: 600;
            color: #00838f;
            font-size: 11px;
            text-transform: uppercase;
            margin-bottom: 2px;
        }
        
        .info-value {
            font-size: 15px;
            color: #212529;
            font-weight: 500;
        }
        
        table {
            font-size: 11px;
        }
        
        table thead {
            background-color: #17a2b8;
            color: white;
        }
        
        table tbody tr:nth-child(even) {
            background-color: #f0f9fa;
        }
        
        .summary-box {
            background: linear-gradient(135deg, #e0f7fa 0%, #f0f9fa 100%);
            border: 2px solid #17a2b8;
            padding: 15px;
            margin-top: 20px;
            border-radius: 8px;
        }
        
        .summary-stat {
            text-align: center;
            padding: 10px;
        }
        
        .summary-stat .stat-label {
            font-weight: 600;
            color: #00838f;
            font-size: 10px;
            text-transform: uppercase;
        }
        
        .summary-stat .stat-value {
            font-size: 20px;
            font-weight: bold;
            margin-top: 3px;
        }
        
        .badge {
            font-size: 10px;
            padding: 3px 7px;
        }
        
        .footer-section {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #dee2e6;
        }
        
        .tech-badge {
            background-color: #17a2b8;
            color: white;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
        }
        
        /* Mobile optimizations */
        @media (max-width: 768px) {
            body {
                font-size: 12px;
            }
            table {
                font-size: 10px;
            }
            .summary-stat .stat-value {
                font-size: 18px;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid p-3">
        <!-- Print Button -->
        <div class="text-end mb-2 no-print">
            <button onclick="window.print()" class="btn btn-info btn-sm">
                <i class="bi bi-printer"></i> Print
            </button>
            <button onclick="window.close()" class="btn btn-secondary btn-sm">
                <i class="bi bi-x-circle"></i> Close
            </button>
        </div>

        <!-- Header -->
        <div class="header-section">
            <div class="row align-items-center">
                <div class="col-8">
                    <h3 class="mb-1">MY STOCK CARD</h3>
                    <p class="mb-1" style="font-size: 13px; opacity: 0.9;">Terminal Management System</p>
                    <span class="tech-badge">
                        <i class="bi bi-person-circle"></i> {{ auth()->user()->name }}
                    </span>
                </div>
                <div class="col-4 text-end">
                    <p class="mb-0" style="font-size: 12px;">{{ now()->format('M d, Y') }}</p>
                    <p class="mb-0" style="font-size: 11px; opacity: 0.8;">{{ now()->format('H:i') }}</p>
                </div>
            </div>
        </div>

        <!-- Serial Information -->
        <div class="row mb-3">
            <div class="col-md-6 mb-2">
                <div class="info-box">
                    <div class="info-label">Serial Number</div>
                    <div class="info-value h4 mb-0 text-info">{{ $stockCardData['serial']->serial_no }}</div>
                </div>
            </div>
            <div class="col-md-6 mb-2">
                <div class="info-box">
                    <div class="info-label">Terminal Model</div>
                    <div class="info-value">{{ $stockCardData['serial']->model ? $stockCardData['serial']->model->model_name : 'N/A' }}</div>
                </div>
            </div>
            <div class="col-md-6 mb-2">
                <div class="info-box">
                    <div class="info-label">Status</div>
                    <div class="info-value">
                        @php
                            $statusColors = [
                                'in_stock' => 'success',
                                'issued' => 'info',
                                'installed' => 'primary',
                                'faulty' => 'danger',
                                'returned' => 'warning',
                            ];
                            $status = $stockCardData['serial']->current_status;
                            $color = $statusColors[$status] ?? 'secondary';
                        @endphp
                        <span class="badge bg-{{ $color }}">{{ ucfirst(str_replace('_', ' ', $status)) }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-2">
                <div class="info-box">
                    <div class="info-label">Location</div>
                    <div class="info-value">{{ $stockCardData['serial']->current_location_name ?? 'N/A' }}</div>
                </div>
            </div>
        </div>

        <!-- Movement History -->
        <h6 class="mb-2 text-info"><i class="bi bi-clock-history me-2"></i>My Movement History</h6>
        <div class="table-responsive">
            <table class="table table-bordered table-sm">
                <thead>
                    <tr>
                        <th width="12%">Date</th>
                        <th width="15%">Transaction</th>
                        <th width="13%">Type</th>
                        <th width="8%" class="text-center">In</th>
                        <th width="8%" class="text-center">Out</th>
                        <th width="8%" class="text-center">Bal</th>
                        <th width="18%">From</th>
                        <th width="18%">To</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($stockCardData['movements'] as $movement)
                        <tr>
                            <td><small>{{ $movement->transaction_date->format('M d, Y') }}</small></td>
                            <td class="font-monospace" style="font-size: 10px;">{{ $movement->transaction_no }}</td>
                            <td>
                                <span class="badge bg-secondary" style="font-size: 9px;">{{ $movement->type_label }}</span>
                            </td>
                            <td class="text-center text-success fw-bold">
                                {{ $movement->quantity > 0 ? $movement->quantity : '-' }}
                            </td>
                            <td class="text-center text-danger fw-bold">
                                {{ $movement->quantity < 0 ? abs($movement->quantity) : '-' }}
                            </td>
                            <td class="text-center fw-bold text-info">{{ $movement->running_balance }}</td>
                            <td style="font-size: 10px;">{{ Str::limit($movement->from_location_name, 20) }}</td>
                            <td style="font-size: 10px;">{{ Str::limit($movement->to_location_name, 20) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-3">
                                <i class="bi bi-inbox"></i> No movement history
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Summary Statistics -->
        <div class="summary-box">
            <h6 class="mb-3 text-center text-info"><i class="bi bi-bar-chart me-2"></i>Summary</h6>
            <div class="row">
                <div class="col-6 col-md-3">
                    <div class="summary-stat">
                        <div class="stat-label">Movements</div>
                        <div class="stat-value text-info">{{ $stockCardData['movement_count'] }}</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="summary-stat">
                        <div class="stat-label">Total In</div>
                        <div class="stat-value text-success">{{ $stockCardData['total_in'] }}</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="summary-stat">
                        <div class="stat-label">Total Out</div>
                        <div class="stat-value text-danger">{{ $stockCardData['total_out'] }}</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="summary-stat">
                        <div class="stat-label">Balance</div>
                        <div class="stat-value text-info">{{ $stockCardData['current_balance'] }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer-section text-center">
            <p class="mb-0 text-muted" style="font-size: 11px;">
                <strong>TMS</strong> - My Stock Card | {{ now()->format('M d, Y H:i') }}
            </p>
            <p class="mb-0 text-muted" style="font-size: 10px;">
                Technician: {{ auth()->user()->name }} | System Generated
            </p>
        </div>
    </div>

    <script>
        // Auto-print on mobile devices (optional)
        // window.addEventListener('load', function() {
        //     if (window.innerWidth < 768) {
        //         setTimeout(() => window.print(), 500);
        //     }
        // });
    </script>
</body>
</html>
