@extends('layouts.app')

@section('title', 'Terminal Models - TMS')

@section('content')
<div class="container-fluid">
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
    </div>

    <!-- Filter -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Category</label>
                    <select class="form-select" id="filterCategory">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->category_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button class="btn btn-outline-secondary" id="resetFilters">
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
                        <th>Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    var table = $('#terminalModelsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("technician.terminal-models.datatable") }}',
            data: function(d) {
                d.category_id = $('#filterCategory').val();
            }
        },
        columns: [
            { data: 'image_preview', name: 'image_preview', orderable: false, searchable: false, width: '60px' },
            { data: 'model_code', name: 'model_code' },
            { data: 'model_name', name: 'model_name' },
            { data: 'category_name', name: 'category_name', orderable: false, searchable: false },
            { data: 'brand', name: 'brand' },
            { data: 'serial_tracking_badge', name: 'serial_tracking_badge', orderable: false, searchable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        order: [[1, 'asc']],
        pageLength: 25,
        responsive: true
    });

    $('#filterCategory').on('change', function() { table.ajax.reload(); });
    $('#resetFilters').on('click', function() { $('#filterCategory').val(''); table.ajax.reload(); });
});
</script>
@endpush
