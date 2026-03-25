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
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form action="{{ route('admin.inventory.update', $inventory_item) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row g-3">
                    <!-- Item Code (readonly) -->
                    <div class="col-md-4">
                        <label class="form-label">Item Code</label>
                        <input type="text" class="form-control" value="{{ $inventory_item->item_code }}" readonly>
                    </div>

                    <!-- Item Type -->
                    <div class="col-md-4">
                        <label for="item_type" class="form-label">Item Type <span class="text-danger">*</span></label>
                        <select name="item_type" id="item_type" class="form-select @error('item_type') is-invalid @enderror" required>
                            <option value="router" {{ old('item_type', $inventory_item->item_type) === 'router' ? 'selected' : '' }}>Router</option>
                            <option value="accessory" {{ old('item_type', $inventory_item->item_type) === 'accessory' ? 'selected' : '' }}>Accessory</option>
                        </select>
                        @error('item_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- Accessory Type -->
                    <div class="col-md-4" id="accessory-type-group" style="{{ old('item_type', $inventory_item->item_type) === 'accessory' ? '' : 'display:none;' }}">
                        <label for="accessory_type" class="form-label">Accessory Type <span class="text-danger">*</span></label>
                        <select name="accessory_type" id="accessory_type" class="form-select @error('accessory_type') is-invalid @enderror">
                            <option value="">-- Select --</option>
                            <option value="sim_card" {{ old('accessory_type', $inventory_item->accessory_type) === 'sim_card' ? 'selected' : '' }}>SIM Card</option>
                            <option value="antenna" {{ old('accessory_type', $inventory_item->accessory_type) === 'antenna' ? 'selected' : '' }}>Antenna</option>
                        </select>
                        @error('accessory_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- Item Name -->
                    <div class="col-md-4">
                        <label for="item_name" class="form-label">Item Name <span class="text-danger">*</span></label>
                        <input type="text" name="item_name" id="item_name" class="form-control @error('item_name') is-invalid @enderror"
                               value="{{ old('item_name', $inventory_item->item_name) }}" required maxlength="150">
                        @error('item_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- Brand -->
                    <div class="col-md-4">
                        <label for="brand" class="form-label">Brand</label>
                        <input type="text" name="brand" id="brand" class="form-control @error('brand') is-invalid @enderror"
                               value="{{ old('brand', $inventory_item->brand) }}" maxlength="100">
                        @error('brand') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- Model -->
                    <div class="col-md-4">
                        <label for="model" class="form-label">Model</label>
                        <input type="text" name="model" id="model" class="form-control @error('model') is-invalid @enderror"
                               value="{{ old('model', $inventory_item->model) }}" maxlength="100">
                        @error('model') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- Unit -->
                    <div class="col-md-4">
                        <label for="unit" class="form-label">Unit</label>
                        <input type="text" name="unit" id="unit" class="form-control @error('unit') is-invalid @enderror"
                               value="{{ old('unit', $inventory_item->unit) }}" maxlength="30">
                        @error('unit') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- Reorder Level -->
                    <div class="col-md-4">
                        <label for="reorder_level" class="form-label">Reorder Level</label>
                        <input type="number" name="reorder_level" id="reorder_level" class="form-control @error('reorder_level') is-invalid @enderror"
                               value="{{ old('reorder_level', $inventory_item->reorder_level) }}" min="0">
                        @error('reorder_level') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- Status -->
                    <div class="col-md-4">
                        <label for="status" class="form-label">Status</label>
                        <select name="status" id="status" class="form-select @error('status') is-invalid @enderror">
                            <option value="active" {{ old('status', $inventory_item->status) === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status', $inventory_item->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- Description -->
                    <div class="col-md-8">
                        <label for="description" class="form-label">Description</label>
                        <textarea name="description" id="description" class="form-control @error('description') is-invalid @enderror"
                                  rows="3" maxlength="1000">{{ old('description', $inventory_item->description) }}</textarea>
                        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-1"></i> Update Item
                    </button>
                    <a href="{{ route('admin.inventory.show', $inventory_item) }}" class="btn btn-outline-secondary ms-2">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    function toggleAccessoryType() {
        var type = $('#item_type').val();
        if (type === 'accessory') {
            $('#accessory-type-group').show();
            $('#accessory_type').attr('required', true);
        } else {
            $('#accessory-type-group').hide();
            $('#accessory_type').val('').removeAttr('required');
        }
    }
    $('#item_type').on('change', toggleAccessoryType);
});
</script>
@endpush
