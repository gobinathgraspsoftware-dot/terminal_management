@extends('layouts.app')

@section('title', 'Job Types')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Job Types</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Job Types</li>
                </ol>
            </nav>
        </div>
        {{-- Add button removed — re-enable when CRUD is needed --}}
    </div>

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h4 class="mb-0 text-primary">{{ $stats['total'] }}</h4>
                    <small class="text-muted">Total Job Types</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h4 class="mb-0 text-success">{{ $stats['active'] }}</h4>
                    <small class="text-muted">Active</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h4 class="mb-0 text-danger">{{ $stats['inactive'] }}</h4>
                    <small class="text-muted">Inactive</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-2">
            <div class="row align-items-center">
                <div class="col-md-3">
                    <select id="filter-status" class="form-select form-select-sm">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="button" id="btn-reset-filter" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- DataTable -->
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <table id="job-types-table" class="table table-hover w-100">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Job Title</th>
                        <th>Slug</th>
                        <th>Description</th>
                        <th>Status</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    var table = $('#job-types-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("admin.job-types.datatable") }}',
            data: function(d) {
                d.status = $('#filter-status').val();
            }
        },
        columns: [
            { data: 'id', name: 'id', width: '50px' },
            { data: 'job_title', name: 'job_title' },
            { data: 'slug', name: 'slug' },
            {
                data: 'description', name: 'description',
                render: function(data) {
                    if (!data) return '<span class="text-muted">—</span>';
                    return data.length > 60 ? data.substring(0, 60) + '...' : data;
                }
            },
            { data: 'status_badge', name: 'status', searchable: false, orderable: false },
        ],
        order: [[1, 'asc']],
        responsive: true,
        language: {
            emptyTable: "No job types found"
        }
    });

    $('#filter-status').on('change', function() { table.draw(); });
    $('#btn-reset-filter').on('click', function() { $('#filter-status').val(''); table.draw(); });
});
</script>
@endpush
