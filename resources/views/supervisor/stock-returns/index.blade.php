@extends('layouts.app')

@section('title', 'Stock Returns')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col">
            <h1 class="h3 mb-0">Stock Returns</h1>
            <p class="text-muted">Manage stock returns from technicians to depots</p>
        </div>
        <div class="col-auto">
            @can('create_stock_returns')
            <a href="{{ route('supervisor.stock-returns.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i> New Return
            </a>
            @endcan
            @can('view_stock_returns')
            <button type="button" class="btn btn-success" onclick="exportReturns()">
                <i class="bi bi-file-earmark-excel me-1"></i> Export
            </button>
            @endcan
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-3">
        <div class="card-body">
            <form id="filterForm" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" id="status_filter" name="status">
                        <option value="">All Status</option>
                        <option value="draft">Draft</option>
                        <option value="posted">Posted</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Condition</label>
                    <select class="form-select" id="condition_filter" name="condition">
                        <option value="">All Conditions</option>
                        <option value="good">Good</option>
                        <option value="damaged">Damaged</option>
                        <option value="defective">Defective</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">From Date</label>
                    <input type="date" class="form-control" id="date_from" name="date_from">
                </div>
                <div class="col-md-2">
                    <label class="form-label">To Date</label>
                    <input type="date" class="form-control" id="date_to" name="date_to">
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="button" class="btn btn-secondary w-100" onclick="resetFilters()">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Returns Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="returnsTable" class="table table-hover">
                    <thead>
                        <tr>
                            <th>Return No</th>
                            <th>Date</th>
                            <th>From Technician</th>
                            <th>To Depot</th>
                            <th>Total Items</th>
                            <th>Status</th>
                            <th>Condition</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    const table = $('#returnsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('supervisor.stock-returns.index') }}",
            data: function(d) {
                d.status = $('#status_filter').val();
                d.condition = $('#condition_filter').val();
                d.date_from = $('#date_from').val();
                d.date_to = $('#date_to').val();
            }
        },
        columns: [
            {data: 'issue_no', name: 'issue_no'},
            {data: 'issue_date', name: 'issue_date'},
            {data: 'from_technician', name: 'fromTechnician.name', orderable: false},
            {data: 'to_depot', name: 'toDepot.depot_name', orderable: false},
            {data: 'total_items', name: 'total_items'},
            {data: 'status_badge', name: 'status', orderable: false},
            {data: 'has_damaged', name: 'has_damaged', orderable: false},
            {data: 'action', name: 'action', orderable: false, searchable: false}
        ],
        order: [[0, 'desc']]
    });

    // Apply filters
    $('#status_filter, #condition_filter, #date_from, #date_to').on('change', function() {
        table.draw();
    });
});

function resetFilters() {
    $('#filterForm')[0].reset();
    $('#returnsTable').DataTable().draw();
}

function postReturn(id) {
    Swal.fire({
        title: 'Post Stock Return?',
        text: 'This will create ledger entries and update stock balances.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Post It'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: `/admin/stock-returns/${id}/post`,
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    Swal.fire('Posted!', response.message, 'success');
                    $('#returnsTable').DataTable().ajax.reload();
                },
                error: function(xhr) {
                    Swal.fire('Error!', xhr.responseJSON.message, 'error');
                }
            });
        }
    });
}

function cancelReturn(id) {
    Swal.fire({
        title: 'Cancel Stock Return?',
        text: 'This will reverse all ledger entries.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Cancel It'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: `/admin/stock-returns/${id}/cancel`,
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    Swal.fire('Cancelled!', response.message, 'success');
                    $('#returnsTable').DataTable().ajax.reload();
                },
                error: function(xhr) {
                    Swal.fire('Error!', xhr.responseJSON.message, 'error');
                }
            });
        }
    });
}

function exportReturns() {
    const status = $('#status_filter').val();
    const condition = $('#condition_filter').val();
    const dateFrom = $('#date_from').val();
    const dateTo = $('#date_to').val();
    
    let url = "{{ route('supervisor.stock-returns.export') }}?";
    if (status) url += `status=${status}&`;
    if (condition) url += `condition=${condition}&`;
    if (dateFrom) url += `date_from=${dateFrom}&`;
    if (dateTo) url += `date_to=${dateTo}&`;
    
    window.location.href = url;
}
</script>
@endpush
