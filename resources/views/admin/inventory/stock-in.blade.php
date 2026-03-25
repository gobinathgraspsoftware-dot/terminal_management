@extends('layouts.app')
@section('title', 'Stock In')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Stock In</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.inventory.index') }}">Inventory</a></li>
                    <li class="breadcrumb-item active">Stock In</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form action="{{ route('admin.inventory.stock-in.process') }}" method="POST" id="stockInForm">
                @csrf

                <div class="row g-3">
                    <!-- Stock Type -->
                    <div class="col-md-4">
                        <label for="stock_type" class="form-label">Stock Type <span class="text-danger">*</span></label>
                        <select name="stock_type" id="stock_type" class="form-select @error('stock_type') is-invalid @enderror" required>
                            <option value="">-- Select --</option>
                            <option value="router" {{ old('stock_type') === 'router' ? 'selected' : '' }}>Router</option>
                            <option value="accessory" {{ old('stock_type') === 'accessory' ? 'selected' : '' }}>Accessory</option>
                        </select>
                        @error('stock_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- Existing Item -->
                    <div class="col-md-4" id="existing-item-group">
                        <label for="inventory_item_id" class="form-label">Select Item <span class="text-danger">*</span></label>
                        <select name="inventory_item_id" id="inventory_item_id" class="form-select select2 @error('inventory_item_id') is-invalid @enderror">
                            <option value="">-- Select Item --</option>
                        </select>
                        @error('inventory_item_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div id="current-stock-info" class="mt-1 small text-muted"></div>
                    </div>

                    <!-- New Router Fields (hidden by default) -->
                    <div id="new-router-fields" style="display:none;">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="item_name" class="form-label">Router Name <span class="text-danger">*</span></label>
                                <input type="text" name="item_name" id="item_name" class="form-control @error('item_name') is-invalid @enderror"
                                       value="{{ old('item_name') }}" maxlength="150">
                                @error('item_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="brand" class="form-label">Brand</label>
                                <input type="text" name="brand" id="brand" class="form-control" value="{{ old('brand') }}" maxlength="100">
                            </div>
                            <div class="col-md-4">
                                <label for="model" class="form-label">Model</label>
                                <input type="text" name="model" id="model" class="form-control" value="{{ old('model') }}" maxlength="100">
                            </div>
                        </div>
                    </div>

                    <!-- Stock In Date -->
                    <div class="col-md-4">
                        <label for="stockin_date" class="form-label">Stock In Date <span class="text-danger">*</span></label>
                        <input type="date" name="stockin_date" id="stockin_date" class="form-control @error('stockin_date') is-invalid @enderror"
                               value="{{ old('stockin_date', date('Y-m-d')) }}" max="{{ date('Y-m-d') }}" required>
                        @error('stockin_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- Quantity -->
                    <div class="col-md-4">
                        <label for="quantity" class="form-label">Quantity <span class="text-danger">*</span></label>
                        <input type="number" name="quantity" id="quantity" class="form-control @error('quantity') is-invalid @enderror"
                               value="{{ old('quantity', 1) }}" min="1" required>
                        @error('quantity') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- Router IDs (dynamic based on quantity — ONLY for routers) -->
                    <div class="col-12" id="router-ids-section" style="display:none;">
                        <label class="form-label fw-bold">Router IDs <small class="text-muted">(one per quantity unit)</small></label>
                        <div id="router-ids-container">
                            <!-- Dynamic fields generated by JS -->
                        </div>
                    </div>

                    <!-- Reason -->
                    <div class="col-md-6">
                        <label for="reason" class="form-label">Reason</label>
                        <input type="text" name="reason" id="reason" class="form-control @error('reason') is-invalid @enderror"
                               value="{{ old('reason', 'Stock In') }}" maxlength="500">
                        @error('reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- Remarks -->
                    <div class="col-md-6">
                        <label for="remarks" class="form-label">Remarks</label>
                        <textarea name="remarks" id="remarks" class="form-control @error('remarks') is-invalid @enderror"
                                  rows="2" maxlength="1000">{{ old('remarks') }}</textarea>
                        @error('remarks') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-box-arrow-in-down me-1"></i> Process Stock In
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
$(function() {
    var routerItems = @json($routerItems);
    var accessoryItems = @json($accessoryItems);
    var currentStockType = '';

    // Toggle UI based on stock type
    function toggleStockType() {
        var type = $('#stock_type').val();
        currentStockType = type;
        var $select = $('#inventory_item_id');
        $select.empty().append('<option value="">-- Select Item --</option>');

        if (type === 'router') {
            routerItems.forEach(function(item) {
                $select.append('<option value="' + item.id + '">' + item.item_code + ' - ' + item.item_name + '</option>');
            });
            $select.append('<option value="">+ Create New Router</option>');
            $('#new-router-fields').hide();
        } else if (type === 'accessory') {
            accessoryItems.forEach(function(item) {
                var label = item.accessory_type === 'sim_card' ? '(SIM)' : '(Antenna)';
                $select.append('<option value="' + item.id + '">' + item.item_code + ' - ' + item.item_name + ' ' + label + '</option>');
            });
            $('#new-router-fields').hide();
        }

        if ($select.hasClass('select2-hidden-accessible')) {
            $select.trigger('change.select2');
        }

        // Show/hide router IDs based on stock type
        updateRouterIdFields();
    }

    // Toggle new router fields
    $('#inventory_item_id').on('change', function() {
        var type = $('#stock_type').val();
        if (type === 'router' && !$(this).val()) {
            $('#new-router-fields').show();
        } else {
            $('#new-router-fields').hide();
        }
        fetchItemStock();
    });

    // Fetch current stock
    function fetchItemStock() {
        var itemId = $('#inventory_item_id').val();
        if (!itemId) {
            $('#current-stock-info').html('');
            return;
        }
        $.get('{{ route("admin.inventory.get-item-stock") }}', { item_id: itemId }, function(data) {
            $('#current-stock-info').html(
                '<i class="bi bi-box me-1"></i>Current warehouse stock: <strong>' + data.warehouse_stock + '</strong>'
            );
        });
    }

    // Generate router ID fields based on quantity — ONLY for router type
    function updateRouterIdFields() {
        var qty = parseInt($('#quantity').val()) || 0;
        var $container = $('#router-ids-container');
        $container.empty();

        // Only show router ID fields when stock type is "router"
        if (currentStockType === 'router' && qty > 0) {
            $('#router-ids-section').show();
            for (var i = 0; i < qty; i++) {
                $container.append(
                    '<div class="row mb-2">' +
                    '<div class="col-md-6">' +
                    '<div class="input-group input-group-sm">' +
                    '<span class="input-group-text">Router ID #' + (i + 1) + '</span>' +
                    '<input type="text" name="router_ids[]" class="form-control" placeholder="Enter Router ID" maxlength="100" required>' +
                    '</div>' +
                    '</div>' +
                    '</div>'
                );
            }
        } else {
            $('#router-ids-section').hide();
        }
    }

    // Events
    $('#stock_type').on('change', toggleStockType);
    $('#quantity').on('change input', updateRouterIdFields);

    // Init
    toggleStockType();

    // Initialize Select2
    if ($.fn.select2) {
        $('#inventory_item_id').select2({ theme: 'bootstrap-5', placeholder: '-- Select Item --', allowClear: true });
    }
});
</script>
@endpush
