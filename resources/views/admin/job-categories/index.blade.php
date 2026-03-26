@extends('layouts.app')

@section('title', 'Job Categories')

@section('content')
<div class="container-fluid">
    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Job Categories</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Job Categories</li>
                </ol>
            </nav>
        </div>
        {{-- Add button removed — re-enable when CRUD is needed --}}
    </div>

    {{-- Stats Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-muted small">Total</div>
                    <div class="h4 mb-0 fw-bold">{{ $stats['total'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-muted small">Active</div>
                    <div class="h4 mb-0 fw-bold text-success">{{ $stats['active'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-muted small">Inactive</div>
                    <div class="h4 mb-0 fw-bold text-danger">{{ $stats['inactive'] }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select id="filterStatus" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="button" id="btnResetFilter" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- DataTable --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <table id="jobCategoriesTable" class="table table-hover align-middle w-100">
                <thead class="table-light">
                    <tr>
                        <th width="5%">#</th>
                        <th>Category Name</th>
                        <th>Slug</th>
                        <th>Description</th>
                        <th width="10%">Status</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    var table = $('#jobCategoriesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("admin.job-categories.datatable") }}',
            data: function(d) {
                d.status = $('#filterStatus').val();
            }
        },
        columns: [
            {
                data: null,
                name: 'id',
                orderable: false,
                searchable: false,
                className: 'text-center',
                render: function(data, type, row, meta) {
                    return meta.row + meta.settings._iDisplayStart + 1;
                }
            },
            { data: 'category_name', name: 'category_name' },
            { data: 'slug', name: 'slug', className: 'text-muted' },
            {
                data: 'description',
                name: 'description',
                orderable: false,
                render: function(data) {
                    if (!data) return '<span class="text-muted">—</span>';
                    return data.length > 60 ? data.substring(0, 60) + '…' : data;
                }
            },
            { data: 'status_badge', name: 'status', orderable: true, searchable: false },
        ],
        order: [[0, 'desc']],
        language: {
            emptyTable: 'No job categories found.',
            zeroRecords: 'No matching records found.'
        }
    });

    $('#filterStatus').on('change', function() { table.ajax.reload(); });
    $('#btnResetFilter').on('click', function() { $('#filterStatus').val(''); table.ajax.reload(); });
});
</script>
@endpush
