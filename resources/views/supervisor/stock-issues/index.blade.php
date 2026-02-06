@extends('layouts.app')

@section('title', 'Stock Issues')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-box-arrow-right text-primary"></i> Stock Issues (Team)
            </h1>
            <p class="text-muted mb-0">Issue stock to team technicians or receive returns</p>
        </div>
        @can('create_stock_issues')
        <a href="{{ route('supervisor.stock-issues.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> New Stock Issue
        </a>
        @endcan
    </div>

    <!-- Filters Card -->
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="card-title mb-0"><i class="bi bi-funnel"></i> Filters</h5>
        </div>
        <div class="card-body">
            <form id="filterForm">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Issue Type</label>
                        <select name="issue_type" id="issue_type" class="form-select">
                            <option value="">All Types</option>
                            <option value="issue_to_tech">Issue to Technician</option>
                            <option value="return_from_tech">Return from Technician</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" id="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="draft">Draft</option>
                            <option value="posted">Posted</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Date From</label>
                        <input type="date" name="date_from" id="date_from" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Date To</label>
                        <input type="date" name="date_to" id="date_to" class="form-control">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="button" id="filterBtn" class="btn btn-primary me-2">
                            <i class="bi bi-search"></i> Apply
                        </button>
                        <button type="button" id="resetBtn" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Reset
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
                <table id="stockIssuesTable" class="table table-hover table-striped">
                    <thead>
                        <tr>
                            <th>Issue No</th>
                            <th>Date</th>
                            <th>Type</th>
                            <th>From</th>
                            <th>To</th>
                            <th>Items</th>
                            <th>Status</th>
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
    const table = $('#stockIssuesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("supervisor.stock-issues.index") }}',
            data: function(d) {
                d.issue_type = $('#issue_type').val();
                d.status = $('#status').val();
                d.date_from = $('#date_from').val();
                d.date_to = $('#date_to').val();
            }
        },
        columns: [
            { data: 'issue_no', name: 'issue_no' },
            { data: 'issue_date', name: 'issue_date' },
            { data: 'issue_type_badge', name: 'issue_type', orderable: false },
            { data: 'from_location', name: 'from_location', orderable: false },
            { data: 'to_location', name: 'to_location', orderable: false },
            { data: 'total_items', name: 'total_items' },
            { data: 'status_badge', name: 'status', orderable: false },
            { data: 'action', name: 'action', orderable: false }
        ],
        order: [[1, 'desc']],
        pageLength: 25
    });

    $('#filterBtn').on('click', function() { table.ajax.reload(); });
    $('#resetBtn').on('click', function() {
        $('#filterForm')[0].reset();
        table.ajax.reload();
    });
});

function postIssue(id) {
    Swal.fire({
        title: 'Post Stock Issue?',
        text: 'This will create ledger entries and update stock balances.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        confirmButtonText: 'Yes, Post it!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: `/supervisor/stock-issues/${id}/post`,
                type: 'POST',
                data: { _token: '{{ csrf_token() }}' },
                success: function(response) {
                    Swal.fire('Posted!', response.message, 'success');
                    $('#stockIssuesTable').DataTable().ajax.reload();
                },
                error: function(xhr) {
                    Swal.fire('Error!', xhr.responseJSON?.message || 'Failed to post', 'error');
                }
            });
        }
    });
}
</script>
@endpush
