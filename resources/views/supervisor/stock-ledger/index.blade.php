@extends('layouts.app')

@section('title', 'Stock Ledger - Team View')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="bi bi-journal-text me-2"></i>Stock Ledger - My Team
            </h1>
            <p class="text-muted mb-0">Inventory movements for your team technicians</p>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="bi bi-funnel me-2"></i>Filters</h5>
        </div>
        <div class="card-body">
            <form id="filterForm">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Model</label>
                        <select class="form-select" name="model_id" id="modelFilter">
                            <option value="">All Models</option>
                            @foreach($models as $model)
                            <option value="{{ $model->id }}">{{ $model->model_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Technician</label>
                        <select class="form-select" name="technician_id" id="technicianFilter">
                            <option value="">All Team Technicians</option>
                            @foreach($technicians as $tech)
                            <option value="{{ $tech->id }}">{{ $tech->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">From Date</label>
                        <input type="date" class="form-control" name="from_date" id="fromDateFilter">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">To Date</label>
                        <input type="date" class="form-control" name="to_date" id="toDateFilter">
                    </div>

                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search me-1"></i>Apply Filters
                        </button>
                        <button type="button" class="btn btn-secondary" id="resetFilters">
                            <i class="bi bi-x-circle me-1"></i>Reset
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Data Table Card -->
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="ledgerTable" style="width:100%">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Transaction No</th>
                            <th>Type</th>
                            <th>Model</th>
                            <th>Qty</th>
                            <th>From</th>
                            <th>To</th>
                            <th>Created By</th>
                            <th>Actions</th>
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
    const table = $('#ledgerTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("supervisor.stock-ledger.index") }}',
            data: function(d) {
                d.model_id = $('#modelFilter').val();
                d.technician_id = $('#technicianFilter').val();
                d.from_date = $('#fromDateFilter').val();
                d.to_date = $('#toDateFilter').val();
            }
        },
        columns: [
            { data: 'transaction_date' },
            { data: 'transaction_no' },
            { data: 'type_badge', orderable: false },
            { data: 'model_name' },
            { data: 'quantity', className: 'text-end' },
            { data: 'from_location' },
            { data: 'to_location' },
            { data: 'created_by' },
            {
                data: null,
                orderable: false,
                render: function(data) {
                    return '<a href="/supervisor/stock-ledger/' + data.id + '" class="btn btn-sm btn-info">' +
                           '<i class="bi bi-eye"></i></a>';
                }
            }
        ],
        order: [[0, 'desc']],
        pageLength: 25
    });

    $('#filterForm').on('submit', function(e) {
        e.preventDefault();
        table.ajax.reload();
    });

    $('#resetFilters').on('click', function() {
        $('#filterForm')[0].reset();
        table.ajax.reload();
    });
});
</script>
@endpush
