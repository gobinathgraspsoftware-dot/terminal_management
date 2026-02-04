@extends('layouts.app')

@section('title', 'My Inventory')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">My Inventory</h1>
            <p class="text-muted mb-0">View and manage your assigned serial numbers</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary" id="btnColumnToggle">
                <i class="bi bi-columns"></i> Columns
            </button>
            <button type="button" class="btn btn-success" id="btnExport">
                <i class="bi bi-file-earmark-excel"></i> Export
            </button>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card stat-card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">Total In Hand</p>
                            <h3 class="mb-0">{{ number_format($stats['total'] ?? 0) }}</h3>
                        </div>
                        <div class="bg-primary bg-opacity-10 rounded-circle p-3">
                            <i class="bi bi-upc-scan text-primary fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card stat-card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">Available Stock</p>
                            <h3 class="mb-0 text-success">{{ number_format($stats['in_stock'] ?? 0) }}</h3>
                        </div>
                        <div class="bg-success bg-opacity-10 rounded-circle p-3">
                            <i class="bi bi-box-seam text-success fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card stat-card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">Installed</p>
                            <h3 class="mb-0 text-primary">{{ number_format($stats['installed'] ?? 0) }}</h3>
                        </div>
                        <div class="bg-primary bg-opacity-10 rounded-circle p-3">
                            <i class="bi bi-check-circle text-primary fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card stat-card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">Under Service</p>
                            <h3 class="mb-0 text-warning">{{ number_format($stats['under_service'] ?? 0) }}</h3>
                        </div>
                        <div class="bg-warning bg-opacity-10 rounded-circle p-3">
                            <i class="bi bi-tools text-warning fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Card -->
    @include('technician.inventory-serials._filters')

    <!-- Data Table Card -->
    <div class="card">
        <div class="card-header bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">My Serial Numbers</h5>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btnRefresh">
                        <i class="bi bi-arrow-clockwise"></i> Refresh
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btnClearFilters">
                        <i class="bi bi-x-circle"></i> Clear Filters
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-striped" id="serialsTable" style="width:100%">
                    <thead>
                        <tr>
                            <th width="5%">#</th>
                            <th width="18%">Serial Number</th>
                            <th width="15%">Category</th>
                            <th width="15%">Model</th>
                            <th width="12%">Status</th>
                            <th width="12%">Warranty</th>
                            <th width="12%">Received Date</th>
                            <th width="11%">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- DataTable content -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Column Visibility Modal -->
<div class="modal fade" id="columnToggleModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="form-check mb-2">
                    <input class="form-check-input column-toggle" type="checkbox" value="1" id="col1" checked>
                    <label class="form-check-label" for="col1">Serial Number</label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input column-toggle" type="checkbox" value="2" id="col2" checked>
                    <label class="form-check-label" for="col2">Category</label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input column-toggle" type="checkbox" value="3" id="col3" checked>
                    <label class="form-check-label" for="col3">Model</label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input column-toggle" type="checkbox" value="4" id="col4" checked>
                    <label class="form-check-label" for="col4">Status</label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input column-toggle" type="checkbox" value="5" id="col5" checked>
                    <label class="form-check-label" for="col5">Warranty</label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input column-toggle" type="checkbox" value="6" id="col6" checked>
                    <label class="form-check-label" for="col6">Received Date</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="btnShowAll">Show All</button>
            </div>
        </div>
    </div>
</div>

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
<style>
    .stat-card {
        transition: all 0.3s;
    }
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .filter-badge {
        position: absolute;
        top: -8px;
        right: -8px;
        background: #dc3545;
        color: white;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        font-weight: bold;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    let table;

    // Initialize DataTable
    function initDataTable() {
        if ($.fn.DataTable.isDataTable('#serialsTable')) {
            $('#serialsTable').DataTable().destroy();
        }

        table = $('#serialsTable').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            ajax: {
                url: '{{ route('technician.inventory-serials.datatable') }}',
                data: function(d) {
                    d.status = $('#filterStatus').val();
                    d.model_id = $('#filterModel').val();
                    d.category_id = $('#filterCategory').val();
                    d.search = $('#searchInput').val();
                }
            },
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'serial_info', name: 'serial_no' },
                { data: 'category_name', name: 'terminalModel.category.category_name' },
                { data: 'model_name', name: 'terminalModel.model_name' },
                { data: 'status_badge', name: 'current_status' },
                { data: 'warranty', name: 'warranty_end', orderable: false },
                { data: 'grn_info', name: 'grn.grn_no' },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ],
            order: [[1, 'asc']],
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
            language: {
                processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
                emptyTable: 'No serial numbers found',
                zeroRecords: 'No matching serial numbers found'
            }
        });
    }

    initDataTable();

    // Filter handlers
    $('.filter-select').on('change', function() {
        table.ajax.reload();
        updateFilterBadge();
    });

    $('#searchInput').on('keyup', function() {
        table.ajax.reload();
    });

    // Refresh button
    $('#btnRefresh').on('click', function() {
        table.ajax.reload();
    });

    // Clear filters
    $('#btnClearFilters').on('click', function() {
        $('.filter-select').val('').trigger('change');
        $('#searchInput').val('');
        table.ajax.reload();
        updateFilterBadge();
    });

    // Export functionality
    $('#btnExport').on('click', function() {
        const params = new URLSearchParams({
            status: $('#filterStatus').val() || '',
            model_id: $('#filterModel').val() || '',
            category_id: $('#filterCategory').val() || '',
            search: $('#searchInput').val() || ''
        });
        
        window.location.href = '{{ route('technician.inventory-serials.export') }}?' + params.toString();
    });

    // Column visibility toggle
    $('#btnColumnToggle').on('click', function() {
        $('#columnToggleModal').modal('show');
    });

    $('.column-toggle').on('change', function() {
        const column = table.column($(this).val());
        column.visible(!column.visible());
    });

    $('#btnShowAll').on('click', function() {
        $('.column-toggle').prop('checked', true);
        table.columns().visible(true);
    });

    // Update filter badge count
    function updateFilterBadge() {
        let count = 0;
        $('.filter-select').each(function() {
            if ($(this).val()) count++;
        });
        if ($('#searchInput').val()) count++;
        
        if (count > 0) {
            if (!$('.filter-badge').length) {
                $('#filterCard').prepend('<span class="filter-badge">' + count + '</span>');
            } else {
                $('.filter-badge').text(count);
            }
        } else {
            $('.filter-badge').remove();
        }
    }
});
</script>
@endpush
@endsection
