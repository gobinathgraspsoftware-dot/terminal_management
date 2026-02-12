@extends('layouts.app')

@section('title', 'Purchase Order Details - TMS')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="bi bi-cart-check"></i> Purchase Order: {{ $purchaseOrder->po_no }}</h2>
            <p class="text-muted mb-0">Created on {{ $purchaseOrder->created_at->format('d M Y, h:i A') }}</p>
        </div>
        <div>
            <a href="{{ route('technician.purchase-orders.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to List
            </a>
            <a href="{{ route('technician.purchase-orders.pdf', $purchaseOrder) }}" class="btn btn-info" target="_blank">
                <i class="bi bi-file-pdf"></i> View PDF
            </a>
            @if(in_array($purchaseOrder->status, ['draft', 'pending_approval']) && $purchaseOrder->created_by === auth()->id())
                <a href="{{ route('technician.purchase-orders.edit', $purchaseOrder) }}" class="btn btn-warning">
                    <i class="bi bi-pencil"></i> Edit
                </a>
            @endif
        </div>
    </div>

    <!-- Status Badge -->
    <div class="mb-4">
        @php
            $statusClasses = [
                'draft' => 'bg-secondary',
                'pending_approval' => 'bg-warning',
                'approved' => 'bg-info',
                'sent' => 'bg-primary',
                'open' => 'bg-success',
                'partially_received' => 'bg-info',
                'fully_received' => 'bg-success',
                'closed' => 'bg-dark',
                'cancelled' => 'bg-danger',
            ];
            $statusClass = $statusClasses[$purchaseOrder->status] ?? 'bg-secondary';
        @endphp
        <span class="badge {{ $statusClass }} fs-5 px-3 py-2">
            {{ ucwords(str_replace('_', ' ', $purchaseOrder->status)) }}
        </span>
    </div>

    <div class="row">
        <!-- Left Column -->
        <div class="col-md-8">
            <!-- PO Details -->
            <div class="card mb-3">
                <div class="card-header bg-primary text-white">
                    <i class="bi bi-info-circle"></i> Purchase Order Details
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless table-sm">
                                <tr>
                                    <th width="40%">PO Number:</th>
                                    <td><strong>{{ $purchaseOrder->po_no }}</strong></td>
                                </tr>
                                <tr>
                                    <th>PO Date:</th>
                                    <td>{{ $purchaseOrder->po_date->format('d M Y') }}</td>
                                </tr>
                                <tr>
                                    <th>Reference:</th>
                                    <td>{{ $purchaseOrder->reference ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Currency:</th>
                                    <td>{{ $purchaseOrder->currency }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless table-sm">
                                <tr>
                                    <th width="40%">Vendor:</th>
                                    <td><strong>{{ $purchaseOrder->vendor->vendor_name }}</strong></td>
                                </tr>
                                <tr>
                                    <th>Delivery Date:</th>
                                    <td>{{ $purchaseOrder->delivery_date?->format('d M Y') ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Receiving Depot:</th>
                                    <td>{{ $purchaseOrder->receivingDepot->depot_name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Created By:</th>
                                    <td>{{ $purchaseOrder->createdBy->name ?? '-' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    @if($purchaseOrder->delivery_address)
                    <div class="mt-3">
                        <strong>Delivery Address:</strong>
                        <p class="mb-0">{{ $purchaseOrder->delivery_address }}</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Line Items -->
            <div class="card mb-3">
                <div class="card-header bg-info text-white">
                    <i class="bi bi-list-check"></i> Line Items
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Terminal Model</th>
                                    <th>Description</th>
                                    <th class="text-center">Ordered</th>
                                    <th class="text-center">Received</th>
                                    <th class="text-center">Outstanding</th>
                                    <th class="text-end">Unit Price</th>
                                    <th class="text-end">Line Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($purchaseOrder->lines as $line)
                                <tr>
                                    <td>{{ $line->line_no }}</td>
                                    <td>{{ $line->model->model_name ?? '-' }}</td>
                                    <td>{{ $line->description ?? '-' }}</td>
                                    <td class="text-center">{{ $line->quantity_ordered }} {{ $line->unit }}</td>
                                    <td class="text-center">{{ $line->quantity_received }} {{ $line->unit }}</td>
                                    <td class="text-center">
                                        <span class="badge {{ $line->quantity_outstanding > 0 ? 'bg-warning' : 'bg-success' }}">
                                            {{ $line->quantity_outstanding }} {{ $line->unit }}
                                        </span>
                                    </td>
                                    <td class="text-end">{{ number_format($line->unit_price, 2) }}</td>
                                    <td class="text-end">{{ number_format($line->line_total, 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="7" class="text-end"><strong>Subtotal:</strong></td>
                                    <td class="text-end"><strong>{{ number_format($purchaseOrder->subtotal, 2) }}</strong></td>
                                </tr>
                                <tr>
                                    <td colspan="7" class="text-end"><strong>Tax:</strong></td>
                                    <td class="text-end"><strong>{{ number_format($purchaseOrder->tax_amount, 2) }}</strong></td>
                                </tr>
                                <tr>
                                    <td colspan="7" class="text-end"><strong>Grand Total:</strong></td>
                                    <td class="text-end"><strong class="text-primary fs-5">{{ $purchaseOrder->currency }} {{ number_format($purchaseOrder->total_amount, 2) }}</strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Additional Info -->
            @if($purchaseOrder->payment_terms || $purchaseOrder->terms_conditions || $purchaseOrder->notes)
            <div class="card mb-3">
                <div class="card-header">
                    <i class="bi bi-file-text"></i> Additional Information
                </div>
                <div class="card-body">
                    @if($purchaseOrder->payment_terms)
                    <div class="mb-3">
                        <strong>Payment Terms:</strong>
                        <p class="mb-0">{{ $purchaseOrder->payment_terms }}</p>
                    </div>
                    @endif

                    @if($purchaseOrder->terms_conditions)
                    <div class="mb-3">
                        <strong>Terms & Conditions:</strong>
                        <p class="mb-0">{{ $purchaseOrder->terms_conditions }}</p>
                    </div>
                    @endif

                    @if($purchaseOrder->notes)
                    <div class="mb-0">
                        <strong>Notes:</strong>
                        <p class="mb-0">{{ $purchaseOrder->notes }}</p>
                    </div>
                    @endif
                </div>
            </div>
            @endif
        </div>

        <!-- Right Column - Timeline -->
        <div class="col-md-4">
            <!-- Timeline -->
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-clock-history"></i> Timeline
                </div>
                <div class="card-body">
                    <ul class="timeline">
                        <li>
                            <strong>Created</strong><br>
                            <small>{{ $purchaseOrder->created_at->format('d M Y, h:i A') }}</small><br>
                            <small class="text-muted">by {{ $purchaseOrder->createdBy->name ?? '-' }}</small>
                        </li>
                        @if($purchaseOrder->approved_at)
                        <li>
                            <strong>Approved</strong><br>
                            <small>{{ $purchaseOrder->approved_at->format('d M Y, h:i A') }}</small><br>
                            <small class="text-muted">by {{ $purchaseOrder->approvedBy->name ?? '-' }}</small>
                        </li>
                        @endif
                        @if($purchaseOrder->sent_at)
                        <li>
                            <strong>Sent to Vendor</strong><br>
                            <small>{{ $purchaseOrder->sent_at->format('d M Y, h:i A') }}</small>
                        </li>
                        @endif
                        @if($purchaseOrder->closed_at)
                        <li>
                            <strong>Closed</strong><br>
                            <small>{{ $purchaseOrder->closed_at->format('d M Y, h:i A') }}</small><br>
                            <small class="text-muted">by {{ $purchaseOrder->closedBy->name ?? '-' }}</small>
                        </li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.timeline {
    list-style: none;
    padding-left: 0;
}
.timeline li {
    padding-left: 20px;
    position: relative;
    padding-bottom: 15px;
    border-left: 2px solid #dee2e6;
}
.timeline li:before {
    content: '';
    width: 10px;
    height: 10px;
    background: #007bff;
    border: 2px solid #fff;
    border-radius: 50%;
    position: absolute;
    left: -6px;
    top: 0;
}
.timeline li:last-child {
    border-left: none;
}
</style>
@endpush
@endsection
