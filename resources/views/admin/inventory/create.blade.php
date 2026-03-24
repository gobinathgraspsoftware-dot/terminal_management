@extends('layouts.app')
@section('title', 'Add Inventory Item')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Add Inventory Item</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.inventory.index') }}">Inventory</a></li>
                    <li class="breadcrumb-item active">Add Item</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('admin.inventory.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form id="create-form">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Item Name <span class="text-danger">*</span></label>
                        <input type="text" name="item_name" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Item Type <span class="text-danger">*</span></label>
                        <select name="item_type" id="item_type" class="form-select" required>
                            <option value="">Select Type</option>
                            <option value="router">Router (Serial Tracked)</option>
                            <option value="accessory">Accessory (Qty Based)</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Category <span class="text-danger">*</span></label>
                        <select name="job_category_id" class="form-select" required>
                            <option value="">Select Category</option>
                            @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->category_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Router-specific: Terminal ID -->
                    <div class="col-md-4" id="serial-field" style="display:none;">
                        <label class="form-label">Terminal ID <span class="text-danger">*</span></label>
                        <input type="text" name="serial_number" class="form-control" placeholder="Unique Terminal ID">
                        <div class="form-text">Required for routers. Must be unique.</div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Brand</label>
                        <input type="text" name="brand" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Model</label>
                        <input type="text" name="model" class="form-control">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Unit <span class="text-danger">*</span></label>
                        <select name="unit" class="form-select" required>
                            <option value="unit">Unit</option>
                            <option value="piece">Piece</option>
                            <option value="set">Set</option>
                            <option value="box">Box</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Reorder Level <span class="text-danger">*</span></label>
                        <input type="number" name="reorder_level" class="form-control" value="5" min="0" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary" id="btn-submit">
                        <i class="bi bi-check-circle me-1"></i> Create Item
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
    // Toggle Terminal ID field based on item type
    $('#item_type').on('change', function() {
        if ($(this).val() === 'router') {
            $('#serial-field').slideDown();
            $('input[name="serial_number"]').prop('required', true);
        } else {
            $('#serial-field').slideUp();
            $('input[name="serial_number"]').prop('required', false).val('');
        }
    });

    // Form submit
    $('#create-form').on('submit', function(e) {
        e.preventDefault();
        let btn = $('#btn-submit');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Saving...');

        let formData = new FormData(this);

        $.ajax({
            url: '{{ route("admin.inventory.store") }}',
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
                btn.prop('disabled', false).html('<i class="bi bi-check-circle me-1"></i> Create Item');
                if (xhr.status === 422) {
                    let errors = xhr.responseJSON.errors;
                    let msg = Object.values(errors).flat().join('<br>');
                    showToast('error', msg);
                } else {
                    showToast('error', xhr.responseJSON?.message || 'Failed to create item.');
                }
            }
        });
    });
});
</script>
@endpush
