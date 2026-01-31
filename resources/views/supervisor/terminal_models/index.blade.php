@extends('layouts.app')

@section('title', 'Terminal Models')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3 mb-0">Terminal Models</h1>
            <p class="text-muted">View terminal models and stock levels</p>
        </div>
    </div>

    <!-- Statistics -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-muted mb-1">Total Models</h6>
                            <h3>{{ $statistics['total'] }}</h3>
                        </div>
                        <i class="bi bi-box-seam text-primary" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-muted mb-1">Active Models</h6>
                            <h3 class="text-success">{{ $statistics['active'] }}</h3>
                        </div>
                        <i class="bi bi-check-circle text-success" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-muted mb-1">With Warranty</h6>
                            <h3 class="text-info">{{ $statistics['with_warranty'] }}</h3>
                        </div>
                        <i class="bi bi-shield-check text-info" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- DataTable Card -->
    <div class="card">
        <div class="card-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h5 class="mb-0">Active Terminal Models</h5>
                </div>
                <div class="col-md-4 text-end">
                    <select id="filterCategory" class="form-select form-select-sm" style="width: auto; display: inline-block;">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->category_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
        <div class="card-body">
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
                            <th>Stock</th>
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
    const table = $('#terminalModelsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("supervisor.terminal-models.datatable") }}',
            data: function(d) {
                d.category_id = $('#filterCategory').val();
            }
        },
        columns: [
            { data: 'image_preview', orderable: false, searchable: false },
            { data: 'model_code' },
            { data: 'model_name' },
            { data: 'category_name' },
            { data: 'brand' },
            { data: 'serial_tracking_badge', orderable: false },
            { data: 'stock_level', orderable: false, searchable: false },
            { data: 'actions', orderable: false, searchable: false }
        ],
        order: [[1, 'asc']],
        pageLength: 25
    });

    $('#filterCategory').change(function() {
        table.ajax.reload();
    });
});
</script>
@endpush
