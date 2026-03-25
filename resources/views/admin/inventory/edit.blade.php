@extends('layouts.app')
@section('title', 'Edit Inventory Item')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Edit: {{ $inventory_item->item_name }}</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.inventory.index') }}">Inventory</a></li>
                    <li class="breadcrumb-item active">Edit</li>
                </ol>
            </nav>
        </div>
        <span class="badge bg-secondary fs-6">{{ $inventory_item->item_code }}</span>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form action="{{ route('admin.inventory.update', $inventory_item) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Item Type <span class="text-danger">*</span></label>
                        <select name="item_type" id="item_type" class="form-select @error('item_type') is-invalid @enderror" required>
                            <option value="router" {{ old('item_type', $inventory_item->item_type) === 'router' ? 'selected' : '' }}>Router</option>
                            <option value="accessory" {{ old('item_type', $inventory_item->item_type) === 'accessory' ? 'selected' : '' }}>Accessory</option>
                        </select>
                        @error('item_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4" id="accessory_type_wrapper" style="{{ old('item_type', $inventory_item->item_type) === 'accessory' ? '' : 'display:none;' }}">
                        <label class="form-label fw-semibold">Accessory Type <span class="text-danger">*</span></label>
                        <select name="accessory_type" id="accessory_type" class="form-select @error('accessory_type') is-invalid @enderror">
                            <option value="">-- Select --</option>
                            <option value="sim_card" {{ old('accessory_type', $inventory_item->accessory_type) === 'sim_card' ? 'selected' : '' }}>SIM Card</option>
                            <option value="antenna" {{ old('accessory_type', $inventory_item->accessory_type) === 'antenna' ? 'selected' : '' }}>Antenna</option>
                        </select>
                        @error('accessory_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
                        <select name="job_category_id" class="form-select @error('job_category_id') is-invalid @enderror" required>
                            <option value="">-- Select --</option>
                            @foreach($jobCategories as $cat)
                                <option value="{{ $cat->id }}" {{ old('job_category_id', $inventory_item->job_category_id) == $cat->id ? 'selected' : '' }}>
                                    {{ $cat->category_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('job_category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Item Name <span class="text-danger">*</span></label>
                        <input type="text" name="item_name" class="form-control @error('item_name') is-invalid @enderror"
                               value="{{ old('item_name', $inventory_item->item_name) }}" required>
                        @error('item_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6" id="serial_number_wrapper" style="{{ old('item_type', $inventory_item->item_type) === 'router' ? '' : 'display:none;' }}">
                        <label class="form-label fw-semibold">Terminal ID <span class="text-danger">*</span></label>
                        <input type="text" name="serial_number" id="serial_number"
                               class="form-control @error('serial_number') is-invalid @enderror"
                               value="{{ old('serial_number', $inventory_item->serial_number) }}">
                        @error('serial_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Brand</label>
                        <input type="text" name="brand" class="form-control @error('brand') is-invalid @enderror"
                               value="{{ old('brand', $inventory_item->brand) }}">
                        @error('brand') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Model</label>
                        <input type="text" name="model" class="form-control @error('model') is-invalid @enderror"
                               value="{{ old('model', $inventory_item->model) }}">
                        @error('model') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Unit</label>
                        <input type="text" name="unit" class="form-control @error('unit') is-invalid @enderror"
                               value="{{ old('unit', $inventory_item->unit) }}">
                        @error('unit') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Reorder Level</label>
                        <input type="number" name="reorder_level" class="form-control @error('reorder_level') is-invalid @enderror"
                               value="{{ old('reorder_level', $inventory_item->reorder_level) }}" min="0">
                        @error('reorder_level') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Status</label>
                        <select name="status" class="form-select @error('status') is-invalid @enderror">
                            <option value="active" {{ old('status', $inventory_item->status) === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status', $inventory_item->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea name="description" class="form-control @error('description') is-invalid @enderror"
                                  rows="3">{{ old('description', $inventory_item->description) }}</textarea>
                        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <hr class="my-4">
                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.inventory.show', $inventory_item) }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> Update Item
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    function toggleFields() {
        var type = $('#item_type').val();
        if (type === 'router') {
            $('#serial_number_wrapper').show();
            $('#serial_number').attr('required', true);
            $('#accessory_type_wrapper').hide();
        } else if (type === 'accessory') {
            $('#serial_number_wrapper').hide();
            $('#serial_number').removeAttr('required');
            $('#accessory_type_wrapper').show();
        } else {
            $('#serial_number_wrapper, #accessory_type_wrapper').hide();
        }
    }
    $('#item_type').on('change', toggleFields);
    toggleFields();
});
</script>
@endpush
