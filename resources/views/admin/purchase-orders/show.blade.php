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
            <a href="{{ route('admin.purchase-orders.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to List
            </a>
            <a href="{{ route('admin.purchase-orders.pdf', $purchaseOrder) }}" class="btn btn-info" target="_blank">
                <i class="bi bi-file-pdf"></i> View PDF
            </a>
            @if(in_array($purchaseOrder->status, ['draft', 'pending_approval']))
                <a href="{{ route('admin.purchase-orders.edit', $purchaseOrder) }}" class="btn btn-warning">
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
                                @if($purchaseOrder->quotation)
                                <tr>
                                    <th>Quotation:</th>
                                    <td>
                                        <a href="{{ route('admin.quotations.show', $purchaseOrder->quotation) }}">
                                            {{ $purchaseOrder->quotation->quotation_no }}
                                        </a>
                                    </td>
                                </tr>
                                @endif
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
                                @if($purchaseOrder->approved_by)
                                <tr>
                                    <th>Approved By:</th>
                                    <td>{{ $purchaseOrder->approvedBy->name }} on {{ $purchaseOrder->approved_at->format('d M Y') }}</td>
                                </tr>
                                @endif
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

            <!-- GRN History -->
            @if($purchaseOrder->grns->count() > 0)
            <div class="card mb-3">
                <div class="card-header bg-success text-white">
                    <i class="bi bi-box-seam"></i> GRN History
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>GRN No</th>
                                    <th>GRN Date</th>
                                    <th>Received By</th>
                                    <th class="text-center">Items</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($purchaseOrder->grns as $grn)
                                <tr>
                                    <td>{{ $grn->grn_no }}</td>
                                    <td>{{ $grn->grn_date->format('d M Y') }}</td>
                                    <td>{{ $grn->createdBy->name ?? '-' }}</td>
                                    <td class="text-center">{{ $grn->lines->count() }}</td>
                                    <td><span class="badge bg-info">{{ ucfirst($grn->status) }}</span></td>
                                    <td>
                                        <a href="{{ route('admin.grns.show', $grn) }}" class="btn btn-sm btn-info">
                                            <i class="bi bi-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

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

        <!-- Right Column - Actions -->
        <div class="col-md-4">
            <!-- Actions Card -->
            <div class="card mb-3">
                <div class="card-header bg-dark text-white">
                    <i class="bi bi-lightning"></i> Actions
                </div>
                <div class="card-body">
                    @if($purchaseOrder->status === 'draft')
                        <button onclick="submitForApproval()" class="btn btn-warning w-100 mb-2">
                            <i class="bi bi-send"></i> Submit for Approval
                        </button>
                        <button onclick="deletePO()" class="btn btn-danger w-100">
                            <i class="bi bi-trash"></i> Delete PO
                        </button>
                    @endif

                    @if($purchaseOrder->status === 'pending_approval')
                        <button onclick="approvePO()" class="btn btn-success w-100 mb-2">
                            <i class="bi bi-check-circle"></i> Approve PO
                        </button>
                        <button onclick="rejectPO()" class="btn btn-danger w-100">
                            <i class="bi bi-x-circle"></i> Reject PO
                        </button>
                    @endif

                    @if($purchaseOrder->status === 'approved')
                        <button onclick="sendToVendor()" class="btn btn-primary w-100 mb-2">
                            <i class="bi bi-envelope"></i> Send to Vendor
                        </button>
                        <button onclick="cancelPO()" class="btn btn-warning w-100">
                            <i class="bi bi-x-circle"></i> Cancel PO
                        </button>
                    @endif

                    @if(in_array($purchaseOrder->status, ['sent', 'open', 'partially_received']))
                        <button onclick="closePO()" class="btn btn-dark w-100">
                            <i class="bi bi-lock"></i> Close PO
                        </button>
                    @endif
                </div>
            </div>

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

@push('scripts')
<script>
function submitForApproval() {
    Swal.fire({
        title: 'Submit for Approval?',
        text: 'This will send the PO for approval.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Submit',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '{{ route('admin.purchase-orders.submit', $purchaseOrder) }}',
                method: 'POST',
                data: { _token: '{{ csrf_token() }}' },
                success: function(response) {
                    Swal.fire('Success!', response.message, 'success').then(() => location.reload());
                },
                error: function(xhr) {
                    Swal.fire('Error!', xhr.responseJSON.message, 'error');
                }
            });
        }
    });
}

function approvePO() {
    Swal.fire({
        title: 'Approve Purchase Order?',
        input: 'textarea',
        inputLabel: 'Approval Notes (Optional)',
        inputPlaceholder: 'Enter approval notes...',
        showCancelButton: true,
        confirmButtonText: 'Approve',
        confirmButtonColor: '#28a745'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '{{ route('admin.purchase-orders.approve', $purchaseOrder) }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    approval_notes: result.value
                },
                success: function(response) {
                    Swal.fire('Approved!', response.message, 'success').then(() => location.reload());
                },
                error: function(xhr) {
                    Swal.fire('Error!', xhr.responseJSON.message, 'error');
                }
            });
        }
    });
}

function rejectPO() {
    Swal.fire({
        title: 'Reject Purchase Order?',
        input: 'textarea',
        inputLabel: 'Rejection Reason',
        inputPlaceholder: 'Enter reason for rejection...',
        inputValidator: (value) => {
            if (!value) {
                return 'Please enter a reason for rejection'
            }
        },
        showCancelButton: true,
        confirmButtonText: 'Reject',
        confirmButtonColor: '#dc3545'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '{{ route('admin.purchase-orders.reject', $purchaseOrder) }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    rejection_reason: result.value
                },
                success: function(response) {
                    Swal.fire('Rejected!', response.message, 'info').then(() => location.reload());
                },
                error: function(xhr) {
                    Swal.fire('Error!', xhr.responseJSON.message, 'error');
                }
            });
        }
    });
}

function sendToVendor() {
    Swal.fire({
        title: 'Send to Vendor',
        html: `
            <div class="text-start">
                <div class="mb-3">
                    <label class="form-label">Email To:</label>
                    <input type="email" id="email_to" class="form-control" value="{{ $purchaseOrder->vendor->pic_email ?? '' }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">CC (Optional):</label>
                    <input type="email" id="email_cc" class="form-control" placeholder="cc@example.com">
                </div>
                <div class="mb-3">
                    <label class="form-label">Message (Optional):</label>
                    <textarea id="email_message" class="form-control" rows="3" placeholder="Additional message..."></textarea>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Send Email',
        preConfirm: () => {
            const email_to = document.getElementById('email_to').value;
            if (!email_to) {
                Swal.showValidationMessage('Please enter recipient email');
                return false;
            }
            return {
                email_to: email_to,
                email_cc: document.getElementById('email_cc').value,
                email_message: document.getElementById('email_message').value
            };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '{{ route('admin.purchase-orders.send', $purchaseOrder) }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    ...result.value
                },
                success: function(response) {
                    Swal.fire('Sent!', response.message, 'success').then(() => location.reload());
                },
                error: function(xhr) {
                    Swal.fire('Error!', xhr.responseJSON.message, 'error');
                }
            });
        }
    });
}

function closePO() {
    Swal.fire({
        title: 'Close Purchase Order?',
        input: 'textarea',
        inputLabel: 'Closure Reason',
        inputPlaceholder: 'Enter reason for closing...',
        inputValidator: (value) => {
            if (!value) {
                return 'Please enter a reason for closing'
            }
        },
        showCancelButton: true,
        confirmButtonText: 'Close PO'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '{{ route('admin.purchase-orders.close', $purchaseOrder) }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    closure_reason: result.value
                },
                success: function(response) {
                    Swal.fire('Closed!', response.message, 'success').then(() => location.reload());
                },
                error: function(xhr) {
                    Swal.fire('Error!', xhr.responseJSON.message, 'error');
                }
            });
        }
    });
}

function cancelPO() {
    Swal.fire({
        title: 'Cancel Purchase Order?',
        input: 'textarea',
        inputLabel: 'Cancellation Reason',
        inputPlaceholder: 'Enter reason for cancellation...',
        inputValidator: (value) => {
            if (!value) {
                return 'Please enter a reason for cancellation'
            }
        },
        showCancelButton: true,
        confirmButtonText: 'Cancel PO',
        confirmButtonColor: '#dc3545'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '{{ route('admin.purchase-orders.cancel', $purchaseOrder) }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    cancellation_reason: result.value
                },
                success: function(response) {
                    Swal.fire('Cancelled!', response.message, 'info').then(() => location.reload());
                },
                error: function(xhr) {
                    Swal.fire('Error!', xhr.responseJSON.message, 'error');
                }
            });
        }
    });
}

function deletePO() {
    Swal.fire({
        title: 'Delete Purchase Order?',
        text: 'This action cannot be undone!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Delete',
        confirmButtonColor: '#dc3545'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '{{ route('admin.purchase-orders.destroy', $purchaseOrder) }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    _method: 'DELETE'
                },
                success: function(response) {
                    Swal.fire('Deleted!', response.message, 'success').then(() => {
                        window.location.href = '{{ route('admin.purchase-orders.index') }}';
                    });
                },
                error: function(xhr) {
                    Swal.fire('Error!', xhr.responseJSON.message, 'error');
                }
            });
        }
    });
}
</script>
@endpush
