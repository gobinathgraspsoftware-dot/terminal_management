@extends('layouts.app')

@section('title', 'Stock Movements')

@section('content')
<div class="container-fluid">

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="bi bi-arrow-left-right me-2"></i>Stock Movements</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.inventory.index') }}">Inventory</a></li>
                    <li class="breadcrumb-item active">Movements</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="{{ route('admin.inventory.movements.export') }}?{{ http_build_query(request()->all()) }}"
               class="btn btn-success me-2" id="btn_export">
                <i class="bi bi-file-earmark-excel me-1"></i> Export Excel
            </a>
            <a href="{{ route('admin.inventory.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Items
            </a>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3">
            <h6 class="mb-0"><i class="bi bi-funnel me-2"></i>Filters</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-2">
                    <label for="filter_movement_type" class="form-label">Movement Type</label>
                    <select id="filter_movement_type" class="form-select">
                        <option value="">All Types</option>
                        @foreach($movementTypes as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="filter_item" class="form-label">Item Name</label>
                    <select id="filter_item" class="form-select">
                        <option value="">All Items</option>
                        @foreach($items as $item)
                            <option value="{{ $item->id }}">{{ $item->item_code }} - {{ $item->item_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="filter_item_type" class="form-label">Item Type</label>
                    <select id="filter_item_type" class="form-select">
                        <option value="">All Types</option>
                        <option value="router">Router</option>
                        <option value="accessory">Accessory</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="filter_brand" class="form-label">Brand</label>
                    <select id="filter_brand" class="form-select">
                        <option value="">All Brands</option>
                        @foreach($brands as $brand)
                            <option value="{{ $brand }}">{{ $brand }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="filter_model" class="form-label">Model</label>
                    <select id="filter_model" class="form-select">
                        <option value="">All Models</option>
                        @foreach($models as $model)
                            <option value="{{ $model }}">{{ $model }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="row g-3 mt-1">
                <div class="col-md-4">
                    <label class="form-label">Date Range</label>
                    <div class="input-group">
                        <input type="date" id="filter_date_from" class="form-control" placeholder="From">
                        <span class="input-group-text">to</span>
                        <input type="date" id="filter_date_to" class="form-control" placeholder="To">
                    </div>
                </div>
                <div class="col-md-8 d-flex align-items-end justify-content-end">
                    <button type="button" id="btn_reset_filters" class="btn btn-outline-secondary me-2">
                        <i class="bi bi-x-circle me-1"></i> Reset
                    </button>
                    <button type="button" id="btn_apply_filters" class="btn btn-primary">
                        <i class="bi bi-search me-1"></i> Apply Filters
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- DataTable Card -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3">
            <h6 class="mb-0"><i class="bi bi-table me-2"></i>All Stock Movements</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="movementsTable" class="table table-hover table-striped align-middle w-100">
                    <thead class="table-light">
                        <tr>
                            <th width="50">#</th>
                            <th>Movement No</th>
                            <th>Movement Type</th>
                            <th>Item Code</th>
                            <th>Item Name</th>
                            <th>Item Type</th>
                            <th>Brand</th>
                            <th>Model</th>
                            <th>Router IDs</th>
                            <th>Qty</th>
                            <th>From</th>
                            <th>To</th>
                            <th>Ticket No</th>
                            <th>Condition</th>
                            <th>Reason</th>
                            <th>Date</th>
                            <th>Performed By</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    var table = $('#movementsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("admin.inventory.movements.datatable") }}',
            data: function(d) {
                d.movement_type     = $('#filter_movement_type').val();
                d.inventory_item_id = $('#filter_item').val();
                d.item_type         = $('#filter_item_type').val();
                d.brand             = $('#filter_brand').val();
                d.model             = $('#filter_model').val();
                d.date_from         = $('#filter_date_from').val();
                d.date_to           = $('#filter_date_to').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'movement_no' },
            { data: 'movement_type', searchable: false, orderable: false },
            { data: 'item_code' },
            { data: 'item_name' },
            { data: 'item_type', searchable: false, orderable: false },
            { data: 'brand' },
            { data: 'model' },
            { data: 'router_ids', searchable: false, orderable: false },
            { data: 'quantity' },
            { data: 'from_location', searchable: false, orderable: false },
            { data: 'to_location', searchable: false, orderable: false },
            { data: 'ticket_no', searchable: false, orderable: false },
            { data: 'condition', searchable: false, orderable: false },
            { data: 'reason', searchable: false, orderable: false },
            { data: 'movement_date' },
            { data: 'performed_by', searchable: false, orderable: false }
        ],
        order: [[1, 'desc']],
        pageLength: 25,
        language: {
            emptyTable: "No stock movements found",
            processing: '<div class="spinner-border spinner-border-sm text-primary" role="status"><span class="visually-hidden">Loading...</span></div> Loading...'
        }
    });

    // Apply filters
    $('#btn_apply_filters').on('click', function() {
        table.ajax.reload();
    });

    // Reset filters
    $('#btn_reset_filters').on('click', function() {
        $('#filter_movement_type, #filter_item, #filter_item_type, #filter_brand, #filter_model').val('');
        $('#filter_date_from, #filter_date_to').val('');
        // Reset Select2 instances
        $('#filter_item').val('').trigger('change.select2');
        $('#filter_brand').val('').trigger('change.select2');
        $('#filter_model').val('').trigger('change.select2');
        table.ajax.reload();
    });

    // Update export link with current filters
    $('#btn_export').on('click', function(e) {
        e.preventDefault();
        var params = $.param({
            movement_type: $('#filter_movement_type').val(),
            inventory_item_id: $('#filter_item').val(),
            item_type: $('#filter_item_type').val(),
            brand: $('#filter_brand').val(),
            model: $('#filter_model').val(),
            date_from: $('#filter_date_from').val(),
            date_to: $('#filter_date_to').val()
        });
        window.location.href = '{{ route("admin.inventory.movements.export") }}?' + params;
    });

    // Initialize Select2 on filter dropdowns
    $('#filter_item').select2({ theme: 'bootstrap-5', placeholder: 'All Items', allowClear: true });
    $('#filter_brand').select2({ theme: 'bootstrap-5', placeholder: 'All Brands', allowClear: true });
    $('#filter_model').select2({ theme: 'bootstrap-5', placeholder: 'All Models', allowClear: true });
});
</script>
@endpush
