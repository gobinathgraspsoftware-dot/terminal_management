@extends('layouts.app')

@section('title', 'Terminal Categories')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Terminal Categories</h1>
            <p class="text-muted">Manage terminal, router, SIM, and accessory categories</p>
        </div>
        @can('create', App\Models\TerminalCategory::class)
        <a href="{{ route('admin.terminal-categories.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i> Add Category
        </a>
        @endcan
    </div>

    <!-- Statistics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-primary bg-opacity-10 p-3 rounded">
                                <i class="bi bi-grid-3x3-gap text-primary fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small">Total Categories</div>
                            <h4 class="mb-0">{{ $stats['total_categories'] }}</h4>
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
                                <i class="bi bi-check-circle text-success fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small">Active</div>
                            <h4 class="mb-0">{{ $stats['active_categories'] }}</h4>
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
                                <i class="bi bi-upc-scan text-info fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small">Serial Tracked</div>
                            <h4 class="mb-0">{{ $stats['serial_tracked'] }}</h4>
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
                            <div class="bg-warning bg-opacity-10 p-3 rounded">
                                <i class="bi bi-phone text-warning fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small">Total Models</div>
                            <h4 class="mb-0">{{ $stats['total_models'] }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Categories Table Card -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom">
            <div class="row align-items-center">
                <div class="col">
                    <h5 class="mb-0">Category List</h5>
                </div>
                <div class="col-auto">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="toggleSortMode">
                        <i class="bi bi-arrows-move me-1"></i> Enable Sorting
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="categoriesTable" class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th width="40px" class="sort-handle" style="display:none;">
                                <i class="bi bi-grip-vertical"></i>
                            </th>
                            <th>Code</th>
                            <th>Category Name</th>
                            <th>Type</th>
                            <th>Serial Tracking</th>
                            <th>Models</th>
                            <th>Sort Order</th>
                            <th>Status</th>
                            <th width="120px">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="sortableCategories">
                        <!-- DataTables will populate this -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.2/themes/base/jquery-ui.min.css">
<style>
    .ui-sortable-helper {
        background-color: #f8f9fa;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    .sort-handle {
        cursor: move;
        color: #6c757d;
    }
    #categoriesTable.sorting-enabled tbody tr {
        cursor: move;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<script>
$(document).ready(function() {
    let table;
    let sortingEnabled = false;

    // Initialize DataTable
    initializeDataTable();

    function initializeDataTable() {
        table = $('#categoriesTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{ route('admin.terminal-categories.datatable') }}',
            columns: [
                { data: null, orderable: false, searchable: false, visible: false, className: 'sort-handle',
                  render: () => '<i class="bi bi-grip-vertical"></i>' },
                { data: 'category_code' },
                { data: 'category_name' },
                { data: 'type_badge', orderable: false },
                { data: 'serial_tracking', orderable: false },
                { data: 'model_count', orderable: false },
                { data: 'sort_order' },
                { data: 'status_badge', orderable: false },
                { data: 'action', orderable: false, searchable: false }
            ],
            order: [[6, 'asc']],
            pageLength: 25,
            language: {
                emptyTable: "No categories found"
            }
        });
    }

    // Toggle sort mode
    $('#toggleSortMode').click(function() {
        sortingEnabled = !sortingEnabled;
        const btn = $(this);
        
        if (sortingEnabled) {
            btn.html('<i class="bi bi-x-circle me-1"></i> Disable Sorting');
            btn.removeClass('btn-outline-secondary').addClass('btn-warning');
            enableSorting();
        } else {
            btn.html('<i class="bi bi-arrows-move me-1"></i> Enable Sorting');
            btn.removeClass('btn-warning').addClass('btn-outline-secondary');
            disableSorting();
        }
    });

    function enableSorting() {
        // Show drag handle column
        table.column(0).visible(true);
        $('#categoriesTable').addClass('sorting-enabled');
        
        // Initialize sortable
        $('#sortableCategories').sortable({
            handle: '.sort-handle',
            helper: function(e, ui) {
                ui.children().each(function() {
                    $(this).width($(this).width());
                });
                return ui;
            },
            update: function(event, ui) {
                updateSortOrder();
            }
        });
    }

    function disableSorting() {
        table.column(0).visible(false);
        $('#categoriesTable').removeClass('sorting-enabled');
        
        if ($('#sortableCategories').hasClass('ui-sortable')) {
            $('#sortableCategories').sortable('destroy');
        }
    }

    function updateSortOrder() {
        const orders = [];
        $('#sortableCategories tr').each(function(index) {
            const id = $(this).find('.delete-category').data('id');
            if (id) {
                orders.push({
                    id: id,
                    sort_order: index
                });
            }
        });

        $.ajax({
            url: '{{ route('admin.terminal-categories.update-sort-order') }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                orders: orders
            },
            success: function(response) {
                if (response.success) {
                    showToast('success', response.message);
                    table.ajax.reload(null, false);
                }
            },
            error: function(xhr) {
                showToast('error', 'Failed to update sort order');
                table.ajax.reload(null, false);
            }
        });
    }

    // Toggle serial tracking
    $(document).on('change', '.serial-toggle', function() {
        const checkbox = $(this);
        const categoryId = checkbox.data('id');
        const isChecked = checkbox.prop('checked');

        $.ajax({
            url: `/admin/terminal-categories/${categoryId}/toggle-serial-tracking`,
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    showToast('success', response.message);
                } else {
                    checkbox.prop('checked', !isChecked);
                    showToast('error', response.message);
                }
            },
            error: function() {
                checkbox.prop('checked', !isChecked);
                showToast('error', 'Failed to update serial tracking');
            }
        });
    });

    // Delete category
    $(document).on('click', '.delete-category', function() {
        const categoryId = $(this).data('id');
        const categoryName = $(this).data('name');

        if (confirm(`Are you sure you want to delete category "${categoryName}"?`)) {
            $.ajax({
                url: `/admin/terminal-categories/${categoryId}`,
                method: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        showToast('success', response.message);
                        table.ajax.reload();
                    } else {
                        showToast('error', response.message);
                    }
                },
                error: function(xhr) {
                    const message = xhr.responseJSON?.message || 'Failed to delete category';
                    showToast('error', message);
                }
            });
        }
    });

    function showToast(type, message) {
        const bgClass = type === 'success' ? 'bg-success' : 'bg-danger';
        const toast = `
            <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
                <div class="toast show align-items-center text-white ${bgClass} border-0" role="alert">
                    <div class="d-flex">
                        <div class="toast-body">${message}</div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>
            </div>
        `;
        $('body').append(toast);
        setTimeout(() => $('.toast').remove(), 3000);
    }
});
</script>
@endpush
