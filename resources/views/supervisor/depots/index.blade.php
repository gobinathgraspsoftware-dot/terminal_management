@extends('layouts.app')

@section('title', 'Depots')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-12">
            <h2 class="mb-1">Depots</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('supervisor.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Depots</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Depots Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table id="depotsTable" class="table table-hover">
                    <thead>
                        <tr>
                            <th width="5%">#</th>
                            <th width="12%">Code</th>
                            <th width="20%">Depot Name</th>
                            <th width="12%">Type</th>
                            <th width="18%">Location</th>
                            <th width="15%">Stock</th>
                            <th width="10%">Status</th>
                            <th width="8%">Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
@endpush

@push('scripts')
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#depotsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('supervisor.depots.index') }}",
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'depot_code', name: 'depot_code' },
            { data: 'depot_name', name: 'depot_name' },
            { data: 'type_badge', name: 'depot_type' },
            { data: 'location', name: 'city', orderable: false },
            { data: 'stock_summary', name: 'total_items', orderable: false, searchable: false },
            { data: 'status', name: 'status' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ],
        order: [[2, 'asc']],
        pageLength: 25
    });
});
</script>
@endpush
