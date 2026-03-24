@extends('layouts.app')
@section('title', 'Stock Transfer')

@section('content')
@php $roleName = explode('.', Route::currentRouteName())[0]; @endphp
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="bi bi-arrow-left-right me-2 text-primary"></i>Stock Transfer</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route($roleName . '.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route($roleName . '.inventory.index') }}">Inventory</a></li>
                    <li class="breadcrumb-item active">Transfer</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row g-4">
        <!-- Transfer Form -->
        <div class="col-md-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="bi bi-arrow-left-right me-2"></i>Create Transfer Request</h6>
                </div>
                <div class="card-body">
                    <form id="transfer-form">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label">Inventory Item <span class="text-danger">*</span></label>
                                <select name="inventory_item_id" class="form-select select2-field" required>
                                    <option value="">Select Item</option>
                                    @foreach($items as $item)
                                    <option value="{{ $item->id }}">{{ $item->item_code }} - {{ $item->item_name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">From <span class="text-danger">*</span></label>
                                <select name="from_holder_type" id="from_type" class="form-select" required>
                                    <option value="warehouse">Warehouse</option>
                                    <option value="technician">Technician</option>
                                </select>
                            </div>
                            <div class="col-md-6" id="from-tech-field" style="display:none;">
                                <label class="form-label">From Technician</label>
                                <select name="from_holder_id" class="form-select select2-field">
                                    <option value="">Select Technician</option>
                                    @foreach($technicians as $tech)
                                    <option value="{{ $tech->id }}">{{ $tech->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">To <span class="text-danger">*</span></label>
                                <select name="to_holder_type" id="to_type" class="form-select" required>
                                    <option value="technician">Technician</option>
                                    <option value="warehouse">Warehouse</option>
                                </select>
                            </div>
                            <div class="col-md-6" id="to-tech-field">
                                <label class="form-label">To Technician</label>
                                <select name="to_holder_id" class="form-select select2-field">
                                    <option value="">Select Technician</option>
                                    @foreach($technicians as $tech)
                                    <option value="{{ $tech->id }}">{{ $tech->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Quantity <span class="text-danger">*</span></label>
                                <input type="number" name="quantity" class="form-control" min="1" value="1" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Transfer Date <span class="text-danger">*</span></label>
                                <input type="date" name="transfer_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label">Reason</label>
                                <textarea name="reason" class="form-control" rows="2"></textarea>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary" id="btn-submit">
                                <i class="bi bi-send me-1"></i> Submit Transfer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Pending Transfers -->
        <div class="col-md-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-clock-history me-2"></i>Pending Transfers</h6>
                </div>
                <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                    @if(isset($pendingTransfers) && $pendingTransfers->count())
                        @foreach($pendingTransfers as $transfer)
                        <div class="border rounded p-3 mb-3">
                            <div class="d-flex justify-content-between mb-2">
                                <code>{{ $transfer->transfer_no }}</code>
                                <span class="badge bg-warning">Pending</span>
                            </div>
                            <p class="mb-1 small"><strong>Item:</strong> {{ $transfer->inventoryItem->item_name ?? 'N/A' }}</p>
                            <p class="mb-1 small"><strong>Qty:</strong> {{ $transfer->quantity }}</p>
                            <p class="mb-1 small">
                                <strong>From:</strong> {{ $transfer->from_holder_type === 'warehouse' ? 'Warehouse' : (\App\Models\User::find($transfer->from_holder_id)?->name ?? 'Unknown') }}
                                <i class="bi bi-arrow-right mx-1"></i>
                                <strong>To:</strong> {{ $transfer->to_holder_type === 'warehouse' ? 'Warehouse' : (\App\Models\User::find($transfer->to_holder_id)?->name ?? 'Unknown') }}
                            </p>
                            <p class="mb-2 small text-muted">By {{ $transfer->createdBy->name ?? 'N/A' }} — {{ $transfer->created_at?->format('d M Y') }}</p>

                            @if($roleName === 'admin')
                            <div class="d-flex gap-2">
                                <button class="btn btn-sm btn-success btn-approve-transfer" data-id="{{ $transfer->id }}">
                                    <i class="bi bi-check-circle me-1"></i>Approve
                                </button>
                                <button class="btn btn-sm btn-danger btn-reject-transfer" data-id="{{ $transfer->id }}">
                                    <i class="bi bi-x-circle me-1"></i>Reject
                                </button>
                            </div>
                            @endif
                        </div>
                        @endforeach
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-check-circle fs-1"></i>
                            <p class="mt-2">No pending transfers.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('.select2-field').select2({ theme: 'bootstrap-5', width: '100%' });

    // Toggle technician dropdowns
    $('#from_type').on('change', function() {
        if ($(this).val() === 'technician') { $('#from-tech-field').slideDown(); }
        else { $('#from-tech-field').slideUp(); $('select[name="from_holder_id"]').val('').trigger('change.select2'); }
    });
    $('#to_type').on('change', function() {
        if ($(this).val() === 'technician') { $('#to-tech-field').slideDown(); }
        else { $('#to-tech-field').slideUp(); $('select[name="to_holder_id"]').val('').trigger('change.select2'); }
    });

    // Submit transfer
    $('#transfer-form').on('submit', function(e) {
        e.preventDefault();
        let btn = $('#btn-submit');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Submitting...');

        $.ajax({
            url: '{{ route($roleName . ".inventory.transfer.create") }}',
            type: 'POST',
            data: new FormData(this),
            processData: false,
            contentType: false,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(res) {
                if (res.success) {
                    showToast('success', res.message);
                    setTimeout(function() { location.reload(); }, 1500);
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).html('<i class="bi bi-send me-1"></i> Submit Transfer');
                showToast('error', xhr.responseJSON?.message || 'Transfer failed.');
            }
        });
    });

    // Approve transfer
    $(document).on('click', '.btn-approve-transfer', function() {
        let id = $(this).data('id');
        Swal.fire({
            title: 'Approve Transfer?',
            text: 'This will move the stock immediately.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Approve',
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ url("admin/inventory/transfer") }}/' + id + '/approve',
                    type: 'POST',
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    success: function(res) {
                        if (res.success) { showToast('success', res.message); setTimeout(function() { location.reload(); }, 1000); }
                    },
                    error: function(xhr) { showToast('error', xhr.responseJSON?.message || 'Approve failed.'); }
                });
            }
        });
    });

    // Reject transfer
    $(document).on('click', '.btn-reject-transfer', function() {
        let id = $(this).data('id');
        Swal.fire({
            title: 'Reject Transfer?',
            input: 'textarea',
            inputLabel: 'Reason for rejection',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Reject',
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ url("admin/inventory/transfer") }}/' + id + '/reject',
                    type: 'POST',
                    data: { reason: result.value },
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    success: function(res) {
                        if (res.success) { showToast('success', res.message); setTimeout(function() { location.reload(); }, 1000); }
                    },
                    error: function(xhr) { showToast('error', xhr.responseJSON?.message || 'Reject failed.'); }
                });
            }
        });
    });
});
</script>
@endpush
