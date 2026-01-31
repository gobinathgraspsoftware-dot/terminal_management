@extends('layouts.app')

@section('title', 'Terminal Models')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <h4 class="mb-0">Terminal Models</h4>
        </div>
    </div>

    <!-- Stats Cards (Mobile Friendly) -->
    <div class="row mb-3">
        <div class="col-6">
            <div class="card text-center">
                <div class="card-body py-2">
                    <h5 class="mb-0">{{ $statistics['total'] }}</h5>
                    <small class="text-muted">Total</small>
                </div>
            </div>
        </div>
        <div class="col-6">
            <div class="card text-center">
                <div class="card-body py-2">
                    <h5 class="mb-0 text-success">{{ $statistics['active'] }}</h5>
                    <small class="text-muted">Active</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter -->
    <div class="row mb-3">
        <div class="col-12">
            <select id="filterCategory" class="form-select">
                <option value="">All Categories</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->category_name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- Models List -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="terminalModelsTable" class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Model</th>
                            <th>Category</th>
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
    const table = $('#terminalModelsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("technician.terminal-models.datatable") }}',
            data: function(d) {
                d.category_id = $('#filterCategory').val();
            }
        },
        columns: [
            { data: 'image_preview', orderable: false, searchable: false, width: '60px' },
            { data: 'model_name' },
            { data: 'category_name' },
            { data: 'actions', orderable: false, searchable: false, width: '80px' }
        ],
        order: [[1, 'asc']],
        pageLength: 10,
        responsive: true
    });

    $('#filterCategory').change(function() {
        table.ajax.reload();
    });
});
</script>
@endpush
