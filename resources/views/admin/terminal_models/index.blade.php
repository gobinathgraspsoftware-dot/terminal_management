@extends('layouts.app')

@section('title', 'Terminal Models - TMS')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="bi bi-box-seam me-2"></i>Terminal Models</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Terminal Models</li>
                </ol>
            </nav>
        </div>
        <div>
            @can('create_models')
            <a href="{{ route('admin.terminal-models.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i> Add Model
            </a>
            @endcan
            <button class="btn btn-outline-success" id="exportBtn">
                <i class="bi bi-download me-1"></i> Export
            </button>
            @can('create_models')
            <button class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#importModal">
                <i class="bi bi-upload me-1"></i> Import
            </button>
            @endcan
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card stats-card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="stats-value text-primary">{{ $statistics['total'] }}</div>
                    <div class="stats-label">Total Models</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="stats-value text-success">{{ $statistics['active'] }}</div>
                    <div class="stats-label">Active</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="stats-value text-secondary">{{ $statistics['inactive'] }}</div>
                    <div class="stats-label">Inactive</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="stats-value text-info">{{ $statistics['with_warranty'] }}</div>
                    <div class="stats-label">With Warranty</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Category</label>
                    <select class="form-select" id="filterCategory">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->category_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" id="filterStatus">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Serial Tracked</label>
                    <select class="form-select" id="filterSerialTracked">
                        <option value="">All</option>
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-outline-secondary w-100" id="resetFilters">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- DataTable -->
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <table class="table table-hover" id="terminalModelsTable" width="100%">
                <thead class="table-light">
                    <tr>
                        <th>Image</th>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Brand</th>
                        <th>Serial Tracked</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<!-- Import Modal -->
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Import Terminal Models</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="importForm" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Select File (xlsx, xls, csv)</label>
                        <input type="file" class="form-control" name="file" accept=".xlsx,.xls,.csv" required>
                    </div>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-1"></i>
                        File should contain columns: model_code, model_name, category, brand, description, warranty_months, is_serial_tracked, status
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-upload me-1"></i> Import
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize DataTable
    var table = $('#terminalModelsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("admin.terminal-models.datatable") }}',
            data: function(d) {
                d.category_id = $('#filterCategory').val();
                d.status = $('#filterStatus').val();
                d.is_serial_tracked = $('#filterSerialTracked').val();
            }
        },
        columns: [
            { data: 'image_preview', name: 'image_preview', orderable: false, searchable: false, width: '60px' },
            { data: 'model_code', name: 'model_code' },
            { data: 'model_name', name: 'model_name' },
            { data: 'category_name', name: 'category_name', orderable: false, searchable: false },
            { data: 'brand', name: 'brand' },
            { data: 'serial_tracking_badge', name: 'serial_tracking_badge', orderable: false, searchable: false },
            { data: 'stock_level', name: 'stock_level', orderable: false, searchable: false },
            { data: 'status_badge', name: 'status_badge', orderable: false, searchable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false, width: '120px' }
        ],
        order: [[1, 'asc']],
        pageLength: 25,
        responsive: true,
        language: {
            emptyTable: "No terminal models found",
            processing: '<div class="spinner-border spinner-border-sm text-primary" role="status"><span class="visually-hidden">Loading...</span></div> Loading...'
        }
    });

    // Filters
    $('#filterCategory, #filterStatus, #filterSerialTracked').on('change', function() {
        table.ajax.reload();
    });

    $('#resetFilters').on('click', function() {
        $('#filterCategory, #filterStatus, #filterSerialTracked').val('');
        table.ajax.reload();
    });

    // Delete
    $(document).on('click', '.delete-btn', function() {
        var id = $(this).data('id');
        confirmAction('Delete Terminal Model?', 'This action can be undone.', function() {
            $.ajax({
                url: '{{ url("admin/terminal-models") }}/' + id,
                type: 'DELETE',
                success: function(response) {
                    if (response.success) {
                        showToast(response.message);
                        table.ajax.reload();
                    } else {
                        showToast(response.message, 'error');
                    }
                },
                error: function(xhr) {
                    showToast(xhr.responseJSON?.message || 'Delete failed', 'error');
                }
            });
        });
    });

    // Export
    $('#exportBtn').on('click', function() {
        var params = $.param({
            category_id: $('#filterCategory').val(),
            status: $('#filterStatus').val(),
            search: $('input[type="search"]').val()
        });
        window.location.href = '{{ route("admin.terminal-models.export") }}?' + params;
    });

    // Import
    $('#importForm').on('submit', function(e) {
        e.preventDefault();
        var formData = new FormData(this);

        $.ajax({
            url: '{{ route("admin.terminal-models.import") }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    showToast(response.message);
                    $('#importModal').modal('hide');
                    table.ajax.reload();
                } else {
                    showToast(response.message, 'error');
                }
            },
            error: function(xhr) {
                showToast(xhr.responseJSON?.message || 'Import failed', 'error');
            }
        });
    });
});
</script>
@endpush
