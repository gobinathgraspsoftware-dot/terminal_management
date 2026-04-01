@extends('layouts.app')

@section('title', 'Manual Stock Return')

@section('content')
<div class="container-fluid">

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="bi bi-box-arrow-up me-2"></i>Manual Stock Return</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.inventory.index') }}">Inventory</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.inventory.stock-return') }}">Stock Return</a></li>
                    <li class="breadcrumb-item active">Create</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('admin.inventory.stock-return') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to List
        </a>
    </div>

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Form Card -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3">
            <h6 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Return Details</h6>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.inventory.stock-return.process') }}" method="POST" id="stockReturnForm">
                @csrf

                <div class="row g-3">
                    <!-- Inventory Item -->
                    <div class="col-md-6">
                        <label for="inventory_item_id" class="form-label">Inventory Item <span class="text-danger">*</span></label>
                        <select name="inventory_item_id" id="inventory_item_id" class="form-select @error('inventory_item_id') is-invalid @enderror" required>
                            <option value="">-- Select Item --</option>
                            @foreach($allItems as $item)
                                <option value="{{ $item->id }}"
                                        data-type="{{ $item->item_type }}"
                                        {{ old('inventory_item_id') == $item->id ? 'selected' : '' }}>
                                    {{ $item->item_code }} - {{ $item->item_name }}
                                    ({{ ucfirst($item->item_type) }})
                                </option>
                            @endforeach
                        </select>
                        @error('inventory_item_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Return Date -->
                    <div class="col-md-3">
                        <label for="stockreturn_date" class="form-label">Return Date <span class="text-danger">*</span></label>
                        <input type="date" name="stockreturn_date" id="stockreturn_date"
                               class="form-control @error('stockreturn_date') is-invalid @enderror"
                               value="{{ old('stockreturn_date', date('Y-m-d')) }}" required>
                        @error('stockreturn_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Item Condition -->
                    <div class="col-md-3">
                        <label for="item_condition" class="form-label">Condition <span class="text-danger">*</span></label>
                        <select name="item_condition" id="item_condition" class="form-select @error('item_condition') is-invalid @enderror" required>
                            <option value="good" {{ old('item_condition') === 'good' ? 'selected' : '' }}>Good</option>
                            <option value="faulty" {{ old('item_condition') === 'faulty' ? 'selected' : '' }}>Faulty</option>
                            <option value="damaged" {{ old('item_condition') === 'damaged' ? 'selected' : '' }}>Damaged</option>
                        </select>
                        @error('item_condition')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Quantity (for accessories) -->
                    <div class="col-md-3" id="quantity_group">
                        <label for="quantity" class="form-label">Quantity <span class="text-danger">*</span></label>
                        <input type="number" name="quantity" id="quantity"
                               class="form-control @error('quantity') is-invalid @enderror"
                               value="{{ old('quantity', 1) }}" min="1">
                        @error('quantity')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Router IDs (for routers) -->
                    <div class="col-md-9" id="router_ids_group" style="display: none;">
                        <label class="form-label">Router IDs <span class="text-danger">*</span></label>
                        <div id="router_ids_container">
                            <div class="input-group mb-2">
                                <input type="text" name="router_ids[]" class="form-control" placeholder="Enter Router ID">
                                <button type="button" class="btn btn-outline-success btn-add-router">
                                    <i class="bi bi-plus-circle"></i>
                                </button>
                            </div>
                        </div>
                        <small class="text-muted">Quantity will be auto-calculated from the number of Router IDs entered.</small>
                    </div>

                    <!-- From Holder Type -->
                    <div class="col-md-3">
                        <label for="from_holder_type" class="form-label">Return From</label>
                        <select name="from_holder_type" id="from_holder_type" class="form-select">
                            <option value="technician">Technician</option>
                            <option value="warehouse">Warehouse (internal)</option>
                        </select>
                    </div>

                    <!-- From Holder ID (technician) -->
                    <div class="col-md-3" id="from_holder_id_group">
                        <label for="from_holder_id" class="form-label">Technician</label>
                        <select name="from_holder_id" id="from_holder_id" class="form-select">
                            <option value="">-- Optional --</option>
                        </select>
                    </div>

                    <!-- Reason -->
                    <div class="col-md-6">
                        <label for="reason" class="form-label">Reason</label>
                        <input type="text" name="reason" id="reason"
                               class="form-control @error('reason') is-invalid @enderror"
                               value="{{ old('reason', 'Manual Stock Return') }}" maxlength="255">
                        @error('reason')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Remarks -->
                    <div class="col-md-12">
                        <label for="remarks" class="form-label">Remarks</label>
                        <textarea name="remarks" id="remarks" rows="2"
                                  class="form-control @error('remarks') is-invalid @enderror"
                                  placeholder="Optional remarks">{{ old('remarks') }}</textarea>
                        @error('remarks')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <hr class="my-4">

                <div class="d-flex justify-content-end">
                    <a href="{{ route('admin.inventory.stock-return') }}" class="btn btn-outline-secondary me-2">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-box-arrow-up me-1"></i> Process Stock Return
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
    // Initialize Select2
    $('#inventory_item_id').select2({ theme: 'bootstrap-5', placeholder: '-- Select Item --', allowClear: true });

    // Toggle router_ids vs quantity based on item type
    $('#inventory_item_id').on('change.select2', function() {
        var selected = $(this).find(':selected');
        var itemType = selected.data('type');

        if (itemType === 'router') {
            $('#router_ids_group').show();
            $('#quantity_group').hide();
            $('#quantity').val(1);
        } else {
            $('#router_ids_group').hide();
            $('#quantity_group').show();
        }
    });

    // Add router ID row
    $(document).on('click', '.btn-add-router', function() {
        var newRow = '<div class="input-group mb-2">' +
            '<input type="text" name="router_ids[]" class="form-control" placeholder="Enter Router ID">' +
            '<button type="button" class="btn btn-outline-danger btn-remove-router"><i class="bi bi-dash-circle"></i></button>' +
            '</div>';
        $('#router_ids_container').append(newRow);
    });

    // Remove router ID row
    $(document).on('click', '.btn-remove-router', function() {
        $(this).closest('.input-group').remove();
    });

    // Toggle from_holder_id visibility
    $('#from_holder_type').on('change', function() {
        if ($(this).val() === 'technician') {
            $('#from_holder_id_group').show();
        } else {
            $('#from_holder_id_group').hide();
            $('#from_holder_id').val('');
        }
    });

    // Trigger initial state
    $('#inventory_item_id').trigger('change.select2');
});
</script>
@endpush
