@extends('layouts.app')

@section('title', 'Depots Management')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">Depots Management</h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Depots</li>
                        </ol>
                    </nav>
                </div>
                @can('create_depots')
                <div>
                    <a href="{{ route('admin.depots.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-1"></i> Add New Depot
                    </a>
                </div>
                @endcan
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-primary bg-opacity-10 p-3 rounded">
                                <i class="bi bi-building text-primary" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Total Depots</h6>
                            <h3 class="mb-0" id="totalDepots">-</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-danger bg-opacity-10 p-3 rounded">
                                <i class="bi bi-star-fill text-danger" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Main Depots</h6>
                            <h3 class="mb-0" id="mainDepots">-</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-info bg-opacity-10 p-3 rounded">
                                <i class="bi bi-geo-alt-fill text-info" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Regional Depots</h6>
                            <h3 class="mb-0" id="regionalDepots">-</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-success bg-opacity-10 p-3 rounded">
                                <i class="bi bi-box-seam text-success" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Total Stock Items</h6>
                            <h3 class="mb-0" id="totalStock">-</h3>
                        </div>
                    </div>
                </div>
            </div>
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
                            <th width="10%">Code</th>
                            <th width="15%">Depot Name</th>
                            <th width="10%">Type</th>
                            <th width="15%">Location</th>
                            <th width="15%">Person In Charge</th>
                            <th width="12%">Stock</th>
                            <th width="8%">Default</th>
                            <th width="8%">Status</th>
                            <th width="12%">Actions</th>
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
    let table = $('#depotsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('admin.depots.index') }}",
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'depot_code', name: 'depot_code' },
            { data: 'depot_name', name: 'depot_name' },
            { data: 'type_badge', name: 'depot_type' },
            { data: 'location', name: 'city', orderable: false },
            { data: 'pic_info', name: 'pic_name', orderable: false },
            { data: 'stock_summary', name: 'total_items', orderable: false, searchable: false },
            { data: 'default_badge', name: 'is_default' },
            { data: 'status', name: 'status' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ],
        order: [[2, 'asc']],
        pageLength: 25,
        drawCallback: function() {
            updateStats();
        }
    });

    // Update stats
    function updateStats() {
        $.ajax({
            url: "{{ route('admin.depots.index') }}",
            data: { stats: true },
            success: function(response) {
                if (response.data) {
                    let total = response.data.length;
                    let main = response.data.filter(d => d.depot_type === 'main').length;
                    let regional = response.data.filter(d => d.depot_type === 'regional').length;
                    let totalStock = response.data.reduce((sum, d) => sum + (d.total_items || 0), 0);

                    $('#totalDepots').text(total);
                    $('#mainDepots').text(main);
                    $('#regionalDepots').text(regional);
                    $('#totalStock').text(totalStock.toLocaleString());
                }
            }
        });
    }

    // Delete depot
    $(document).on('click', '.btn-delete', function() {
        let depotId = $(this).data('id');
        
        if (confirm('Are you sure you want to delete this depot? This action cannot be undone.')) {
            $.ajax({
                url: "{{ route('admin.depots.index') }}/" + depotId,
                type: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        table.ajax.reload(null, false);
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function(xhr) {
                    let message = xhr.responseJSON?.message || 'Failed to delete depot.';
                    toastr.error(message);
                }
            });
        }
    });

    // Initial stats load
    updateStats();
});
</script>
@endpush
