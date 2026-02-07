@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Stock Adjustments</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Stock Adjustments</li>
                </ol>
            </nav>
        </div>
        <div>
            @can('create_stock_adjustments')
            <a href="{{ route('admin.stock-adjustments.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i> New Adjustment
            </a>
            @endcan
            <a href="{{ route('admin.stock-adjustments.variance-report') }}" class="btn btn-info">
                <i class="bi bi-file-earmark-bar-graph me-1"></i> Variance Report
            </a>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="bi bi-funnel me-2"></i>Filters
            </h5>
        </div>
        <div class="card-body">
            <form id="filterForm">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Depot</label>
                        <select name="depot_id" id="depot_id" class="form-select">
                            <option value="">All Depots</option>
                            @foreach($depots as $depot)
                                <option value="{{ $depot->id }}">{{ $depot->depot_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Adjustment Type</label>
                        <select name="adjustment_type" id="adjustment_type" class="form-select">
                            <option value="">All Types</option>
                            <option value="count">Stock Count</option>
                            <option value="correction">Correction</option>
                            <option value="write_off">Write Off</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" id="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="draft">Draft</option>
                            <option value="pending_approval">Pending Approval</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Date From</label>
                        <input type="date" name="date_from" id="date_from" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Date To</label>
                        <input type="date" name="date_to" id="date_to" class="form-control">
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <button type="button" id="applyFilters" class="btn btn-primary me-2">
                            <i class="bi bi-search me-1"></i>Apply Filters
                        </button>
                        <button type="button" id="resetFilters" class="btn btn-secondary me-2">
                            <i class="bi bi-arrow-clockwise me-1"></i>Reset
                        </button>
                        <button type="button" id="exportBtn" class="btn btn-success">
                            <i class="bi bi-file-excel me-1"></i>Export
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Data Table Card -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="adjustmentsTable" class="table table-hover">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Adjustment No</th>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Depot</th>
                            <th>Items</th>
                            <th>Status</th>
                            <th>Created By</th>
                            <th>Approved By</th>
                            <th>Action</th>
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
    // Initialize DataTable
    let table = $('#adjustmentsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('admin.stock-adjustments.index') }}",
            data: function(d) {
                d.depot_id = $('#depot_id').val();
                d.adjustment_type = $('#adjustment_type').val();
                d.status = $('#status').val();
                d.date_from = $('#date_from').val();
                d.date_to = $('#date_to').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'adjustment_no', name: 'adjustment_no' },
            { data: 'adjustment_date', name: 'adjustment_date' },
            { data: 'adjustment_type_badge', name: 'adjustment_type' },
            { data: 'depot_name', name: 'depot.depot_name' },
            { data: 'total_items', name: 'total_items' },
            { data: 'status_badge', name: 'status' },
            { data: 'created_by_name', name: 'creator.name' },
            { data: 'approved_by_name', name: 'approver.name' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ],
        order: [[2, 'desc']],
        pageLength: 25,
        language: {
            emptyTable: "No stock adjustments found"
        }
    });

    // Apply filters
    $('#applyFilters').click(function() {
        table.draw();
    });

    // Reset filters
    $('#resetFilters').click(function() {
        $('#filterForm')[0].reset();
        table.draw();
    });

    // Export
    $('#exportBtn').click(function() {
        let params = new URLSearchParams({
            depot_id: $('#depot_id').val(),
            adjustment_type: $('#adjustment_type').val(),
            status: $('#status').val(),
            date_from: $('#date_from').val(),
            date_to: $('#date_to').val()
        });
        window.location.href = "{{ route('admin.stock-adjustments.export') }}?" + params.toString();
    });

    // Delete adjustment
    $(document).on('click', '.delete-btn', function() {
        let id = $(this).data('id');
        
        Swal.fire({
            title: 'Are you sure?',
            text: "This adjustment will be permanently deleted!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ url('admin/stock-adjustments') }}/" + id,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('Deleted!', response.message, 'success');
                            table.draw();
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Error!', xhr.responseJSON?.message || 'An error occurred', 'error');
                    }
                });
            }
        });
    });
});
</script>
@endpush
