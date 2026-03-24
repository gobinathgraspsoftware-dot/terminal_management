@extends('layouts.app')
@section('title', 'Edit Inventory Item')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Edit: {{ $inventoryItem->item_name }}</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.inventory.index') }}">Inventory</a></li>
                    <li class="breadcrumb-item active">Edit</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('admin.inventory.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form id="edit-form">
                @csrf
                @method('PUT')
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Item Code</label>
                        <input type="text" class="form-control" value="{{ $inventoryItem->item_code }}" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Item Name <span class="text-danger">*</span></label>
                        <input type="text" name="item_name" class="form-control" value="{{ $inventoryItem->item_name }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Item Type <span class="text-danger">*</span></label>
                        <select name="item_type" id="item_type" class="form-select" required>
                            <option value="router" {{ $inventoryItem->item_type === 'router' ? 'selected' : '' }}>Router (Serial Tracked)</option>
                            <option value="accessory" {{ $inventoryItem->item_type === 'accessory' ? 'selected' : '' }}>Accessory (Qty Based)</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Category <span class="text-danger">*</span></label>
                        <select name="job_category_id" class="form-select" required>
                            <option value="">Select Category</option>
                            @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ $inventoryItem->job_category_id == $cat->id ? 'selected' : '' }}>{{ $cat->category_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4" id="serial-field" style="{{ $inventoryItem->item_type === 'router' ? '' : 'display:none;' }}">
                        <label class="form-label">Terminal ID <span class="text-danger">*</span></label>
                        <input type="text" name="serial_number" class="form-control" value="{{ $inventoryItem->serial_number }}">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Brand</label>
                        <input type="text" name="brand" class="form-control" value="{{ $inventoryItem->brand }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Model</label>
                        <input type="text" name="model" class="form-control" value="{{ $inventoryItem->model }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Unit <span class="text-danger">*</span></label>
                        <select name="unit" class="form-select" required>
                            @foreach(['unit','piece','set','box'] as $u)
                            <option value="{{ $u }}" {{ $inventoryItem->unit === $u ? 'selected' : '' }}>{{ ucfirst($u) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Reorder Level <span class="text-danger">*</span></label>
                        <input type="number" name="reorder_level" class="form-control" value="{{ $inventoryItem->reorder_level }}" min="0" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required>
                            <option value="active" {{ $inventoryItem->status === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ $inventoryItem->status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3">{{ $inventoryItem->description }}</textarea>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary" id="btn-submit">
                        <i class="bi bi-check-circle me-1"></i> Update Item
                    </button>
                    <a href="{{ route('admin.inventory.index') }}" class="btn btn-outline-secondary ms-2">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('#item_type').on('change', function() {
        if ($(this).val() === 'router') {
            $('#serial-field').slideDown();
            $('input[name="serial_number"]').prop('required', true);
        } else {
            $('#serial-field').slideUp();
            $('input[name="serial_number"]').prop('required', false);
        }
    });

    $('#edit-form').on('submit', function(e) {
        e.preventDefault();
        let btn = $('#btn-submit');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Updating...');

        let formData = new FormData(this);

        $.ajax({
            url: '{{ route("admin.inventory.update", $inventoryItem->id) }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(res) {
                if (res.success) {
                    showToast('success', res.message);
                    setTimeout(function() {
                        window.location.href = '{{ route("admin.inventory.index") }}';
                    }, 1000);
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).html('<i class="bi bi-check-circle me-1"></i> Update Item');
                if (xhr.status === 422) {
                    let errors = xhr.responseJSON.errors;
                    let msg = Object.values(errors).flat().join('<br>');
                    showToast('error', msg);
                } else {
                    showToast('error', xhr.responseJSON?.message || 'Failed to update.');
                }
            }
        });
    });
});
</script>
@endpush
