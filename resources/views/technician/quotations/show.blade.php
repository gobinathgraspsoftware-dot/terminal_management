@extends('layouts.app')

@section('title', 'Quotation Details')

@section('content')
<div class="pagetitle">
    <h1>Quotation Details</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('technician.dashboard') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('technician.quotations.index') }}">Quotations</a></li>
            <li class="breadcrumb-item active">{{ $quotation->quotation_no }}</li>
        </ol>
    </nav>
</div>

<section class="section">
    <div class="row">
        <div class="col-lg-12">
            <!-- Header Card -->
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3 mt-3">
                        <div>
                            <h5 class="card-title mb-0">{{ $quotation->quotation_no }}</h5>
                            <small class="text-muted">Created on {{ $quotation->created_at->format('d M Y') }}</small>
                        </div>
                        <div>
                            <span class="badge {{ $quotation->getStatusBadgeClass() }} me-2">{{ $quotation->getStatusLabel() }}</span>
                            @if($quotation->quotation_type == 'customer')
                                <span class="badge bg-primary">Customer Quotation</span>
                            @else
                                <span class="badge bg-info">Vendor Quotation</span>
                            @endif
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="mb-3">
                        <a href="{{ route('technician.quotations.index') }}" class="btn btn-secondary btn-sm">
                            <i class="bi bi-arrow-left"></i> Back to List
                        </a>
                    </div>

                    <!-- Quotation Details -->
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr>
                                    <th width="40%">Quotation No:</th>
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
                                <tr>
                                    <th>Type:</th>
                                    <td>{{ $quotation->getTypeLabel() }}</td>
                                </tr>
                                <tr>
                                    <th>{{ $quotation->quotation_type == 'customer' ? 'Client' : 'Vendor' }}:</th>
                                    <td>{{ $quotation->party_name }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr>
                                    <th width="40%">Reference:</th>
                                    <td>{{ $quotation->reference ?: 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Status:</th>
                                    <td><span class="badge {{ $quotation->getStatusBadgeClass() }}">{{ $quotation->getStatusLabel() }}</span></td>
                                </tr>
                                <tr>
                                    <th>Created By:</th>
                                    <td>{{ $quotation->createdBy->name }}</td>
                                </tr>
                                @if($quotation->approvedBy)
                                <tr>
                                    <th>Approved By:</th>
                                    <td>{{ $quotation->approvedBy->name }} on {{ $quotation->approved_at->format('d M Y') }}</td>
                                </tr>
                                @endif
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Line Items Card -->
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Line Items</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Item Type</th>
                                    <th>Description</th>
                                    <th class="text-end">Quantity</th>
                                    <th class="text-end">Unit Price</th>
                                    <th class="text-end">Disc %</th>
                                    <th class="text-end">Tax %</th>
                                    <th class="text-end">Line Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($quotation->lines as $index => $line)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        @if($line->item_type == 'model')
                                            <span class="badge bg-primary">Model</span>
                                        @elseif($line->item_type == 'charge')
                                            <span class="badge bg-success">Charge</span>
                                        @else
                                            <span class="badge bg-secondary">Custom</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $line->description }}
                                        @if($line->model)
                                            <br><small class="text-muted">{{ $line->model->model_name }}</small>
                                        @elseif($line->charge)
                                            <br><small class="text-muted">{{ $line->charge->charge_name }}</small>
                                        @endif
                                    </td>
                                    <td class="text-end">{{ number_format($line->quantity, 2) }}</td>
                                    <td class="text-end">{{ number_format($line->unit_price, 2) }}</td>
                                    <td class="text-end">{{ number_format($line->discount_percent, 2) }}%</td>
                                    <td class="text-end">{{ number_format($line->tax_rate, 2) }}%</td>
                                    <td class="text-end"><strong>{{ number_format($line->line_total, 2) }}</strong></td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="7" class="text-end">Subtotal:</th>
                                    <th class="text-end">{{ number_format($quotation->subtotal_amount, 2) }}</th>
                                </tr>
                                <tr>
                                    <th colspan="7" class="text-end">Tax:</th>
                                    <th class="text-end">{{ number_format($quotation->tax_amount, 2) }}</th>
                                </tr>
                                <tr>
                                    <th colspan="7" class="text-end">Discount:</th>
                                    <th class="text-end">({{ number_format($quotation->discount_amount, 2) }})</th>
                                </tr>
                                <tr class="table-primary">
                                    <th colspan="7" class="text-end">Total:</th>
                                    <th class="text-end"><strong>MYR {{ number_format($quotation->total_amount, 2) }}</strong></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Additional Information -->
            @if($quotation->terms_conditions || $quotation->notes)
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Additional Information</h5>
                    @if($quotation->terms_conditions)
                    <div class="mb-3">
                        <strong>Terms & Conditions:</strong>
                        <p class="mb-0">{{ $quotation->terms_conditions }}</p>
                    </div>
                    @endif
                    @if($quotation->notes)
                    <div>
                        <strong>Notes:</strong>
                        <p class="mb-0">{{ $quotation->notes }}</p>
                    </div>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>
</section>
@endsection
