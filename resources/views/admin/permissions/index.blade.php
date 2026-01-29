@extends('layouts.app')

@section('title', 'Permission Management')

@section('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<style>
    .stat-card {
        transition: transform 0.2s ease-in-out;
    }
    .stat-card:hover {
        transform: translateY(-2px);
    }
    .permission-group-card {
        transition: all 0.2s ease;
    }
    .permission-group-card:hover {
        box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.1);
    }
    .permission-badge {
        font-size: 0.8rem;
        margin: 2px;
        padding: 0.4em 0.6em;
        display: inline-block;
    }
    .badge-action {
        font-size: 0.65rem;
        padding: 0.15em 0.4em;
    }
    .group-icon {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
    }
    .permission-group-item {
        transition: all 0.3s ease;
    }
    .permission-group-item.hidden {
        display: none !important;
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Permission Management</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Permissions</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="{{ route('admin.permissions.matrix') }}" class="btn btn-primary">
                <i class="bi bi-grid-3x3-gap me-1"></i> Permission Matrix
            </a>
            <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-shield-lock me-1"></i> Manage Roles
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card stat-card h-100 border-primary">
                <div class="card-body text-center">
                    <h3 class="text-primary mb-1">{{ $stats['total_permissions'] }}</h3>
                    <small class="text-muted">Total Permissions</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card stat-card h-100 border-success">
                <div class="card-body text-center">
                    <h3 class="text-success mb-1">{{ $stats['permissions_in_use'] }}</h3>
                    <small class="text-muted">In Use</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card stat-card h-100 border-warning">
                <div class="card-body text-center">
                    <h3 class="text-warning mb-1">{{ $stats['unused_permissions'] }}</h3>
                    <small class="text-muted">Unused</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card stat-card h-100 border-info">
                <div class="card-body text-center">
                    <h3 class="text-info mb-1">{{ $stats['permission_groups'] }}</h3>
                    <small class="text-muted">Permission Groups</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Permissions by Group -->
    <div class="card mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="bi bi-collection me-2"></i>Permissions by Module
            </h5>
            <div class="input-group" style="width: 300px;">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="text" class="form-control" id="search-groups" placeholder="Search modules or permissions...">
            </div>
        </div>
        <div class="card-body">
            <div class="row" id="permission-groups">
                @php
                    $groupIcons = [
                        'users' => 'bi-people',
                        'partners' => 'bi-building',
                        'clients' => 'bi-person-vcard',
                        'vendors' => 'bi-shop',
                        'sites' => 'bi-geo-alt',
                        'depots' => 'bi-house-door',
                        'models' => 'bi-cpu',
                        'categories' => 'bi-tags',
                        'charges' => 'bi-currency-dollar',
                        'rate_cards' => 'bi-card-list',
                        'inventory' => 'bi-box-seam',
                        'stock_issues' => 'bi-box-arrow-right',
                        'stock_transfers' => 'bi-arrow-left-right',
                        'stock_adjustments' => 'bi-sliders',
                        'quotations' => 'bi-file-text',
                        'purchase_orders' => 'bi-cart',
                        'grns' => 'bi-receipt',
                        'jobs' => 'bi-clipboard-check',
                        'delivery_orders' => 'bi-truck',
                        'site_assets' => 'bi-hdd-stack',
                        'invoices_ar' => 'bi-receipt-cutoff',
                        'invoices_ap' => 'bi-file-earmark-text',
                        'payments' => 'bi-cash-coin',
                        'credit_notes' => 'bi-file-earmark-minus',
                        'aging' => 'bi-calendar-check',
                        'payouts' => 'bi-wallet2',
                        'claims' => 'bi-file-earmark-medical',
                        'reports' => 'bi-bar-chart',
                        'dashboards' => 'bi-speedometer2',
                        'settings' => 'bi-gear',
                        'activity_logs' => 'bi-clock-history',
                        'notifications' => 'bi-bell',
                    ];
                    $groupColors = [
                        'users' => 'primary',
                        'partners' => 'info',
                        'clients' => 'success',
                        'vendors' => 'warning',
                        'sites' => 'danger',
                        'depots' => 'secondary',
                        'models' => 'info',
                        'categories' => 'primary',
                        'charges' => 'success',
                        'rate_cards' => 'warning',
                        'inventory' => 'primary',
                        'stock_issues' => 'danger',
                        'stock_transfers' => 'info',
                        'stock_adjustments' => 'warning',
                        'quotations' => 'success',
                        'purchase_orders' => 'danger',
                        'grns' => 'info',
                        'jobs' => 'danger',
                        'delivery_orders' => 'warning',
                        'site_assets' => 'info',
                        'invoices_ar' => 'success',
                        'invoices_ap' => 'warning',
                        'payments' => 'success',
                        'credit_notes' => 'secondary',
                        'aging' => 'warning',
                        'payouts' => 'info',
                        'claims' => 'danger',
                        'reports' => 'secondary',
                        'dashboards' => 'primary',
                        'settings' => 'dark',
                        'activity_logs' => 'secondary',
                        'notifications' => 'info',
                    ];
                    $actionColors = [
                        'view' => 'info',
                        'view_all' => 'info',
                        'view_team' => 'info',
                        'view_own' => 'info',
                        'view_history' => 'info',
                        'view_assets' => 'info',
                        'view_admin' => 'info',
                        'view_supervisor' => 'info',
                        'view_technician' => 'info',
                        'view_executive' => 'info',
                        'create' => 'success',
                        'edit' => 'warning',
                        'delete' => 'danger',
                        'restore' => 'secondary',
                        'approve' => 'primary',
                        'reject' => 'danger',
                        'submit' => 'primary',
                        'cancel' => 'danger',
                        'void' => 'danger',
                        'post' => 'success',
                        'close' => 'secondary',
                        'export' => 'secondary',
                        'import' => 'secondary',
                        'print' => 'secondary',
                        'send' => 'primary',
                        'assign' => 'primary',
                        'reassign' => 'warning',
                        'assign_roles' => 'primary',
                        'transfer' => 'warning',
                        'adjust' => 'warning',
                        'receive' => 'success',
                        'dispatch' => 'info',
                        'complete' => 'success',
                        'start' => 'info',
                        'fail' => 'danger',
                        'convert' => 'primary',
                        'convert_to_po' => 'primary',
                        'apply' => 'success',
                        'mark_paid' => 'success',
                        'manage' => 'dark',
                        'manage_system' => 'dark',
                        'manage_workflows' => 'dark',
                        'manage_number_series' => 'dark',
                        'manage_contacts' => 'primary',
                        'change_password' => 'warning',
                        'send_reminders' => 'info',
                        'schedule' => 'info',
                    ];
                @endphp
                
                @forelse($permissionGroups as $group => $data)
                    @php
                        $icon = $groupIcons[$group] ?? 'bi-key';
                        $color = $groupColors[$group] ?? 'secondary';
                    @endphp
                    <div class="col-lg-4 col-md-6 mb-4 permission-group-item" data-group="{{ $group }}" data-label="{{ strtolower($data['label']) }}">
                        <div class="card permission-group-card h-100">
                            <div class="card-header bg-white d-flex align-items-center">
                                <div class="group-icon bg-{{ $color }} bg-opacity-10 text-{{ $color }} me-3">
                                    <i class="bi {{ $icon }}"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-0">{{ $data['label'] }}</h6>
                                    <small class="text-muted">{{ count($data['permissions']) }} permissions</small>
                                </div>
                            </div>
                            <div class="card-body">
                                @foreach($data['permissions'] as $permission)
                                    @php
                                        $actionColor = $actionColors[$permission['action']] ?? 'secondary';
                                    @endphp
                                    <span class="badge bg-light text-dark permission-badge" 
                                        data-permission="{{ strtolower($permission['name']) }}"
                                        data-bs-toggle="tooltip" 
                                        title="{{ $permission['name'] }}">
                                        {{ $permission['display_name'] }}
                                        <span class="badge bg-{{ $actionColor }} badge-action">{{ $permission['action'] }}</span>
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="text-center py-5">
                            <i class="bi bi-key display-4 text-muted"></i>
                            <p class="text-muted mt-2">No permissions found. Please run the permission seeder.</p>
                            <code>php artisan db:seed --class=PermissionSeeder</code>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- All Permissions Table -->
    <div class="card">
        <div class="card-header bg-white">
            <h5 class="card-title mb-0">
                <i class="bi bi-list-ul me-2"></i>All Permissions List
            </h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="permissions-table" class="table table-striped table-hover" style="width:100%">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Permission Name</th>
                            <th>Display Name</th>
                            <th>Module</th>
                            <th>Action</th>
                            <th>Roles</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($permissionGroups as $group => $data)
                            @foreach($data['permissions'] as $permission)
                                @php
                                    $actionColor = $actionColors[$permission['action']] ?? 'secondary';
                                @endphp
                                <tr>
                                    <td>{{ $permission['id'] }}</td>
                                    <td><code>{{ $permission['name'] }}</code></td>
                                    <td>{{ $permission['display_name'] }}</td>
                                    <td><span class="badge bg-secondary">{{ $data['label'] }}</span></td>
                                    <td><span class="badge bg-{{ $actionColor }}">{{ $permission['action'] }}</span></td>
                                    <td>
                                        @php
                                            $perm = \Spatie\Permission\Models\Permission::find($permission['id']);
                                            $rolesCount = $perm ? $perm->roles()->count() : 0;
                                        @endphp
                                        <span class="badge {{ $rolesCount > 0 ? 'bg-success' : 'bg-warning' }}">{{ $rolesCount }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize DataTable (client-side)
    var table = $('#permissions-table').DataTable({
        order: [[0, 'asc']],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        language: {
            emptyTable: 'No permissions found',
            zeroRecords: 'No matching permissions found'
        }
    });

    // Search groups and permissions - FIXED VERSION
    $('#search-groups').on('input', function() {
        var searchTerm = $(this).val().toLowerCase().trim();
        
        if (searchTerm === '') {
            // Show all groups and permissions
            $('.permission-group-item').removeClass('hidden').show();
            $('.permission-badge').show();
        } else {
            // Filter each group
            $('.permission-group-item').each(function() {
                var $group = $(this);
                var groupLabel = ($group.data('label') || '').toString().toLowerCase();
                var groupName = ($group.data('group') || '').toString().toLowerCase();
                var groupMatch = groupLabel.indexOf(searchTerm) !== -1 || groupName.indexOf(searchTerm) !== -1;
                
                // Check if any permission in this group matches
                var permissionMatches = false;
                $group.find('.permission-badge').each(function() {
                    var $perm = $(this);
                    var permName = ($perm.data('permission') || '').toString().toLowerCase();
                    var permText = ($perm.text() || '').toString().toLowerCase();
                    
                    if (permName.indexOf(searchTerm) !== -1 || permText.indexOf(searchTerm) !== -1) {
                        $perm.show();
                        permissionMatches = true;
                    } else {
                        $perm.hide();
                    }
                });
                
                // Show/hide group based on matches
                if (groupMatch || permissionMatches) {
                    $group.removeClass('hidden').show();
                    // If group name matches, show all permissions
                    if (groupMatch) {
                        $group.find('.permission-badge').show();
                    }
                } else {
                    $group.addClass('hidden').hide();
                }
            });
        }
    });
});
</script>
@endsection
