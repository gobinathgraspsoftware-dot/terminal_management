@extends('layouts.app')

@section('title', 'My Stock Ledger')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="bi bi-journal-text me-2"></i>My Stock Ledger
            </h1>
            <p class="text-muted mb-0">All my inventory movements</p>
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
                        <label class="form-label">Transaction Type</label>
                        <select class="form-select" name="transaction_type" id="typeFilter">
                            <option value="">All Types</option>
                            <option value="issue_to_tech">Received from Depot</option>
                            <option value="return_from_tech">Returned to Depot</option>
                            <option value="install">Installed at Site</option>
                            <option value="replacement_out">Replacement (Out)</option>
                            <option value="replacement_in">Replacement (In)</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">From Date</label>
                        <input type="date" class="form-control" name="from_date" id="fromDateFilter">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">To Date</label>
                        <input type="date" class="form-control" name="to_date" id="toDateFilter">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Serial Number</label>
                        <input type="text" class="form-control" name="serial_no" id="serialFilter" placeholder="Search serial">
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
                            <th>Serial No</th>
                            <th>Qty</th>
                            <th>From</th>
                            <th>To</th>
                            <th>Created By</th>
                            <th>Status</th>
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
    // Initialize DataTable
    const table = $('#ledgerTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("technician.stock-ledger.index") }}',
            data: function(d) {
                d.model_id = $('#modelFilter').val();
                d.transaction_type = $('#typeFilter').val();
                d.from_date = $('#fromDateFilter').val();
                d.to_date = $('#toDateFilter').val();
                d.serial_no = $('#serialFilter').val();
            }
        },
        columns: [
            { data: 'transaction_date' },
            { data: 'transaction_no' },
            { data: 'type_badge', orderable: false },
            {
                data: null,
                render: function(data) {
                    return '<strong>' + data.model_name + '</strong><br>' +
                           '<small class="text-muted">' + data.model_code + '</small>';
                }
            },
            { data: 'serial_no' },
            { data: 'quantity', className: 'text-end' },
            { data: 'from_location', orderable: false },
            { data: 'to_location', orderable: false },
            { data: 'created_by' },
            { data: 'reversal_badge', orderable: false },
            {
                data: null,
                orderable: false,
                render: function(data) {
                    return '<a href="/technician/stock-ledger/' + data.id + '" class="btn btn-sm btn-info">' +
                           '<i class="bi bi-eye"></i></a>';
                }
            }
        ],
        order: [[0, 'desc']],
        pageLength: 25,
        language: {
            emptyTable: "No stock movements found",
            zeroRecords: "No matching movements found"
        }
    });

    // Filter form submission
    $('#filterForm').on('submit', function(e) {
        e.preventDefault();
        table.ajax.reload();
    });

    // Reset filters
    $('#resetFilters').on('click', function() {
        $('#filterForm')[0].reset();
        table.ajax.reload();
    });
});
</script>
@endpush
