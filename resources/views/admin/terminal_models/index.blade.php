@extends('layouts.app')

@section('title', 'Terminal Models')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3 mb-0">Terminal Models</h1>
            <p class="text-muted">Manage terminal models and specifications</p>
        </div>
        <div class="col-md-4 text-end">
            @can('create', App\Models\TerminalModel::class)
                <a href="{{ route('admin.terminal-models.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Add Terminal Model
                </a>
            @endcan
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Total Models</h6>
                            <h3 class="mb-0">{{ $statistics['total'] }}</h3>
                        </div>
                        <div class="text-primary">
                            <i class="bi bi-box-seam" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Active Models</h6>
                            <h3 class="mb-0 text-success">{{ $statistics['active'] }}</h3>
                        </div>
                        <div class="text-success">
                            <i class="bi bi-check-circle" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-secondary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Inactive Models</h6>
                            <h3 class="mb-0 text-secondary">{{ $statistics['inactive'] }}</h3>
                        </div>
                        <div class="text-secondary">
                            <i class="bi bi-x-circle" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">With Warranty</h6>
                            <h3 class="mb-0 text-info">{{ $statistics['with_warranty'] }}</h3>
                        </div>
                        <div class="text-info">
                            <i class="bi bi-shield-check" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters and DataTable -->
    <div class="card">
        <div class="card-header">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h5 class="mb-0">All Terminal Models</h5>
                </div>
                <div class="col-md-6 text-end">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="filterToggle">
                        <i class="bi bi-funnel"></i> Filters
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-success" id="exportBtn">
                        <i class="bi bi-download"></i> Export
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#importModal">
                        <i class="bi bi-upload"></i> Import
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body">
            <!-- Filter Section -->
            <div id="filterSection" class="mb-3" style="display: none;">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="filterCategory" class="form-label">Category</label>
                        <select id="filterCategory" class="form-select form-select-sm">
                            <option value="">All Categories</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->category_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="filterStatus" class="form-label">Status</label>
                        <select id="filterStatus" class="form-select form-select-sm">
                            <option value="">All Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="filterSerialTracked" class="form-label">Serial Tracking</label>
                        <select id="filterSerialTracked" class="form-select form-select-sm">
                            <option value="">All</option>
                            <option value="1">Yes</option>
                            <option value="0">No</option>
                        </select>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-12">
                        <button type="button" class="btn btn-sm btn-secondary" id="resetFilters">
                            <i class="bi bi-arrow-counterclockwise"></i> Reset
                        </button>
                    </div>
                </div>
            </div>

            <!-- DataTable -->
            <div class="table-responsive">
                <table id="terminalModelsTable" class="table table-hover table-sm">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Model Code</th>
                            <th>Model Name</th>
                            <th>Category</th>
                            <th>Brand</th>
                            <th>Serial Track</th>
                            <th>Warranty</th>
                            <th>Stock</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
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
                        <label for="importFile" class="form-label">Select Excel File</label>
                        <input type="file" class="form-control" id="importFile" name="file" accept=".xlsx,.xls,.csv" required>
                        <div class="form-text">
                            Supported formats: Excel (.xlsx, .xls), CSV (.csv). Max size: 5MB
                        </div>
                    </div>
                    <div class="alert alert-info">
                        <strong>Template Columns:</strong>
                        <ul class="mb-0 mt-2">
                            <li>model_code (optional)</li>
                            <li>model_name (required)</li>
                            <li>category (required)</li>
                            <li>brand</li>
                            <li>description</li>
                            <li>serial_tracked (yes/no)</li>
                            <li>warranty_months</li>
                            <li>status (active/inactive)</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-upload"></i> Import
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
    const table = $('#terminalModelsTable').DataTable({
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
            { data: 'image_preview', name: 'image_preview', orderable: false, searchable: false },
            { data: 'model_code', name: 'model_code' },
            { data: 'model_name', name: 'model_name' },
            { data: 'category_name', name: 'category.category_name' },
            { data: 'brand', name: 'brand' },
            { data: 'serial_tracking_badge', name: 'is_serial_tracked', orderable: false },
            { data: 'warranty_months', name: 'warranty_months' },
            { data: 'stock_level', name: 'stock_level', orderable: false, searchable: false },
            { data: 'status_badge', name: 'status', orderable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        order: [[1, 'asc']],
        pageLength: 25
    });

    // Filter toggle
    $('#filterToggle').click(function() {
        $('#filterSection').slideToggle();
    });

    // Apply filters
    $('#filterCategory, #filterStatus, #filterSerialTracked').change(function() {
        table.ajax.reload();
    });

    // Reset filters
    $('#resetFilters').click(function() {
        $('#filterCategory, #filterStatus, #filterSerialTracked').val('');
        table.ajax.reload();
    });

    // Export
    $('#exportBtn').click(function() {
        const params = new URLSearchParams({
            category_id: $('#filterCategory').val() || '',
            status: $('#filterStatus').val() || '',
            search: $('.dataTables_filter input').val() || ''
        });
        window.location.href = '{{ route("admin.terminal-models.export") }}?' + params.toString();
    });

    // Import
    $('#importForm').submit(function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        $.ajax({
            url: '{{ route("admin.terminal-models.import") }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                $('#importModal').modal('hide');
                $('#importForm')[0].reset();
                
                if (response.success) {
                    toastr.success(response.message);
                    table.ajax.reload();
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr) {
                toastr.error('Import failed: ' + (xhr.responseJSON?.message || 'Unknown error'));
            }
        });
    });

    // Delete functionality
    $(document).on('click', '.delete-btn', function() {
        const id = $(this).data('id');
        
        if (confirm('Are you sure you want to delete this terminal model?')) {
            $.ajax({
                url: `/admin/terminal-models/${id}`,
                type: 'DELETE',
                data: { _token: '{{ csrf_token() }}' },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        table.ajax.reload();
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function(xhr) {
                    toastr.error('Delete failed: ' + (xhr.responseJSON?.message || 'Unknown error'));
                }
            });
        }
    });
});
</script>
@endpush
