@extends('layouts.app')
@section('title', 'Stock Movements')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="bi bi-arrow-left-right me-2"></i>Stock Movements</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('supervisor.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('supervisor.inventory.index') }}">Inventory</a></li>
                    <li class="breadcrumb-item active">Movements</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Filters -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="form-label">Movement Type</label>
                    <select id="filter-type" class="form-select form-select-sm">
                        <option value="">All Types</option>
                        @foreach($movementTypes as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Item</label>
                    <select id="filter-item" class="form-select form-select-sm select2">
                        <option value="">All Items</option>
                        @foreach($items as $item)
                        <option value="{{ $item->id }}">{{ $item->item_code }} - {{ $item->item_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Date From</label>
                    <input type="date" id="filter-date-from" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Date To</label>
                    <input type="date" id="filter-date-to" class="form-control form-control-sm">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button id="btn-filter" class="btn btn-primary btn-sm me-2">
                        <i class="bi bi-search me-1"></i> Filter
                    </button>
                    <button id="btn-reset" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-x-circle me-1"></i> Reset
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- DataTable -->
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table id="movementsTable" class="table table-hover table-sm align-middle" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Movement No</th>
                            <th>Type</th>
                            <th>Item</th>
                            <th>Router IDs</th>
                            <th>Qty</th>
                            <th>From</th>
                            <th>To</th>
                            <th>Ticket</th>
                            <th>Condition</th>
                            <th>Date</th>
                            <th>By</th>
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
$(function() {
    // Initialize Select2
    if ($.fn.select2) {
        $('#filter-item').select2({ theme: 'bootstrap-5', placeholder: 'All Items', allowClear: true });
    }

    var table = $('#movementsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("supervisor.inventory.movements.datatable") }}',
            data: function(d) {
                d.movement_type = $('#filter-type').val();
                d.inventory_item_id = $('#filter-item').val();
                d.date_from = $('#filter-date-from').val();
                d.date_to = $('#filter-date-to').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false, width: '40px' },
            { data: 'movement_no' },
            { data: 'movement_type', orderable: false, searchable: false },
            {
                data: null,
                orderable: false,
                searchable: false,
                render: function(data) {
                    return '<span class="fw-semibold">' + data.item_code + '</span><br><small class="text-muted">' + data.item_name + '</small>';
                }
            },
            {
                data: 'router_ids',
                orderable: false,
                searchable: false,
                render: function(data) {
                    if (!data || data === '-') return '<span class="text-muted">-</span>';
                    return '<small class="text-primary">' + data + '</small>';
                }
            },
            {
                data: 'quantity',
                className: 'text-center',
                render: function(data) {
                    var cls = data > 0 ? 'text-success fw-bold' : 'text-danger fw-bold';
                    var prefix = data > 0 ? '+' : '';
                    return '<span class="' + cls + '">' + prefix + data + '</span>';
                }
            },
            { data: 'from_location' },
            { data: 'to_location' },
            {
                data: 'ticket_no',
                orderable: false,
                searchable: false,
                render: function(data) {
                    if (!data || data === '-') return '<span class="text-muted">-</span>';
                    return '<span class="badge bg-primary border">' + data + '</span>';
                }
            },
            { data: 'condition', orderable: false, searchable: false },
            { data: 'movement_date' },
            { data: 'performed_by' }
        ],
        order: [[1, 'desc']],
        pageLength: 25,
        language: { search: '', searchPlaceholder: 'Search...' },
        dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
             "<'row'<'col-sm-12'tr>>" +
             "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>"
    });

    // Filter
    $('#btn-filter').on('click', function() { table.ajax.reload(); });
    $('#btn-reset').on('click', function() {
        $('#filter-type').val('');
        $('#filter-item').val('').trigger('change.select2');
        $('#filter-date-from, #filter-date-to').val('');
        table.ajax.reload();
    });
});
</script>
@endpush
