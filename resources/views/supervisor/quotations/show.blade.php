@extends('layouts.app')

@section('title', 'Quotation Details')

@section('content')
<div class="pagetitle">
    <h1>Quotation Details</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('supervisor.dashboard') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('supervisor.quotations.index') }}">Quotations</a></li>
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
                            <small class="text-muted">Created by {{ $quotation->createdBy->name }} on {{ $quotation->created_at->format('d M Y') }}</small>
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
                        @if($quotation->isDraft())
                            @can('update', $quotation)
                                <a href="{{ route('supervisor.quotations.edit', $quotation) }}" class="btn btn-warning btn-sm">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>
                                <button class="btn btn-success btn-sm" id="submit-approval-btn">
                                    <i class="bi bi-check-circle"></i> Submit for Approval
                                </button>
                            @endcan
                            @can('delete', $quotation)
                                <button class="btn btn-danger btn-sm" id="delete-btn">
                                    <i class="bi bi-trash"></i> Delete
                                </button>
                            @endcan
                        @endif

                        @if($quotation->status == 'pending_approval')
                            @can('approve', $quotation)
                                <button class="btn btn-success btn-sm" id="approve-btn">
                                    <i class="bi bi-check-circle"></i> Approve
                                </button>
                                <button class="btn btn-danger btn-sm" id="reject-btn">
                                    <i class="bi bi-x-circle"></i> Reject
                                </button>
                            @endcan
                        @endif

                        @if($quotation->status == 'approved')
                            @can('send', $quotation)
                                <button class="btn btn-primary btn-sm" id="send-btn">
                                    <i class="bi bi-send"></i> Send Quotation
                                </button>
                            @endcan
                        @endif

                        @if($quotation->status == 'sent')
                            @can('update', $quotation)
                                <button class="btn btn-success btn-sm" id="accept-btn">
                                    <i class="bi bi-check-circle"></i> Mark as Accepted
                                </button>
                            @endcan
                        @endif

                        @if($quotation->canBeConverted())
                            @can('convertToPO', $quotation)
                                <button class="btn btn-info btn-sm" id="convert-po-btn">
                                    <i class="bi bi-file-earmark-arrow-down"></i> Convert to PO
                                </button>
                            @endcan
                        @endif

                        @can('create', App\Models\Quotation::class)
                            <button class="btn btn-secondary btn-sm" id="duplicate-btn">
                                <i class="bi bi-files"></i> Duplicate
                            </button>
                        @endcan

                        <a href="{{ route('supervisor.quotations.print', $quotation) }}" class="btn btn-secondary btn-sm" target="_blank">
                            <i class="bi bi-printer"></i> Print
                        </a>

                        <a href="{{ route('supervisor.quotations.index') }}" class="btn btn-light btn-sm">
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
                                @if($quotation->approvedBy)
                                <tr>
                                    <th>Approved By:</th>
                                    <td>{{ $quotation->approvedBy->name }} on {{ $quotation->approved_at->format('d M Y') }}</td>
                                </tr>
                                @endif
                                @if($quotation->purchaseOrder)
                                <tr>
                                    <th>Purchase Order:</th>
                                    <td><a href="{{ route('supervisor.purchase-orders.show', $quotation->purchaseOrder) }}">{{ $quotation->purchaseOrder->po_no }}</a></td>
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

@push('scripts')
<script>
$(document).ready(function() {
    // Submit for approval
    $('#submit-approval-btn').click(function() {
        Swal.fire({
            title: 'Submit for Approval?',
            text: "This quotation will be sent for approval.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, submit!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("supervisor.quotations.submit-approval", $quotation) }}',
                    type: 'POST',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('Success!', response.message, 'success').then(() => {
                                location.reload();
                            });
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Error!', xhr.responseJSON?.message || 'An error occurred', 'error');
                    }
                });
            }
        });
    });

    // Approve
    $('#approve-btn').click(function() {
        Swal.fire({
            title: 'Approve Quotation?',
            text: "This quotation will be approved.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, approve!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("supervisor.quotations.process-approval", $quotation) }}',
                    type: 'POST',
                    data: { 
                        _token: '{{ csrf_token() }}',
                        action: 'approve'
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('Approved!', response.message, 'success').then(() => {
                                location.reload();
                            });
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Error!', xhr.responseJSON?.message || 'An error occurred', 'error');
                    }
                });
            }
        });
    });

    // Reject
    $('#reject-btn').click(function() {
        Swal.fire({
            title: 'Reject Quotation?',
            input: 'textarea',
            inputLabel: 'Rejection Reason',
            inputPlaceholder: 'Enter reason for rejection...',
            inputAttributes: {
                'required': 'required'
            },
            showCancelButton: true,
            confirmButtonText: 'Yes, reject!',
            confirmButtonColor: '#d33',
            preConfirm: (reason) => {
                if (!reason) {
                    Swal.showValidationMessage('Reason is required');
                }
                return reason;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("supervisor.quotations.process-approval", $quotation) }}',
                    type: 'POST',
                    data: { 
                        _token: '{{ csrf_token() }}',
                        action: 'reject',
                        reason: result.value
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('Rejected!', response.message, 'success').then(() => {
                                location.reload();
                            });
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Error!', xhr.responseJSON?.message || 'An error occurred', 'error');
                    }
                });
            }
        });
    });

    // Send
    $('#send-btn').click(function() {
        Swal.fire({
            title: 'Send Quotation?',
            text: "This quotation will be marked as sent.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, send!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("supervisor.quotations.send", $quotation) }}',
                    type: 'POST',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('Sent!', response.message, 'success').then(() => {
                                location.reload();
                            });
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Error!', xhr.responseJSON?.message || 'An error occurred', 'error');
                    }
                });
            }
        });
    });

    // Accept
    $('#accept-btn').click(function() {
        Swal.fire({
            title: 'Mark as Accepted?',
            text: "This quotation will be marked as accepted.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, accept!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("supervisor.quotations.accept", $quotation) }}',
                    type: 'POST',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('Accepted!', response.message, 'success').then(() => {
                                location.reload();
                            });
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Error!', xhr.responseJSON?.message || 'An error occurred', 'error');
                    }
                });
            }
        });
    });

    // Convert to PO
    $('#convert-po-btn').click(function() {
        Swal.fire({
            title: 'Convert to Purchase Order?',
            text: "A new Purchase Order will be created from this quotation.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, convert!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("supervisor.quotations.convert-to-po", $quotation) }}',
                    type: 'POST',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('Converted!', response.message, 'success').then(() => {
                                if (response.redirect) {
                                    window.location.href = response.redirect;
                                } else {
                                    location.reload();
                                }
                            });
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Error!', xhr.responseJSON?.message || 'An error occurred', 'error');
                    }
                });
            }
        });
    });

    // Duplicate
    $('#duplicate-btn').click(function() {
        Swal.fire({
            title: 'Duplicate Quotation?',
            text: "A new draft quotation will be created with the same details.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, duplicate!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("supervisor.quotations.duplicate", $quotation) }}',
                    type: 'POST',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('Duplicated!', response.message, 'success').then(() => {
                                if (response.redirect) {
                                    window.location.href = response.redirect;
                                }
                            });
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Error!', xhr.responseJSON?.message || 'An error occurred', 'error');
                    }
                });
            }
        });
    });

    // Delete
    $('#delete-btn').click(function() {
        Swal.fire({
            title: 'Delete Quotation?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("supervisor.quotations.destroy", $quotation) }}',
                    type: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('Deleted!', response.message, 'success').then(() => {
                                window.location.href = '{{ route("supervisor.quotations.index") }}';
                            });
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Error!', xhr.responseJSON?.message || 'An error occurred', 'error');
                    }
                });
            }
        });
    });
});
</script>
@endpush
