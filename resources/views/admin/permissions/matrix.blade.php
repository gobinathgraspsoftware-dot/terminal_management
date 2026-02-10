@extends('layouts.app')

@section('title', 'Permission Matrix')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Permission Matrix</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.permissions.index') }}">Permissions</a></li>
                    <li class="breadcrumb-item active">Matrix</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="{{ route('admin.permissions.export-matrix') }}" class="btn btn-outline-success me-2">
                <i class="bi bi-download me-1"></i> Export CSV
            </a>
            <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-primary me-2">
                <i class="bi bi-shield-lock me-1"></i> Manage Roles
            </a>
            <a href="{{ route('admin.permissions.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-key me-1"></i> All Permissions
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Filter by Module:</label>
                    <select class="form-select" id="filter-group">
                        <option value="">All Modules</option>
                        @foreach($permissions as $group => $data)
                            <option value="{{ $group }}">{{ $data['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Search Permission:</label>
                    <input type="text" class="form-control" id="search-permission" placeholder="Type to search...">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Quick Filters:</label>
                    <div class="btn-group w-100" role="group">
                        <button type="button" class="btn btn-outline-secondary active" data-filter="all">All</button>
                        <button type="button" class="btn btn-outline-secondary" data-filter="granted">Granted</button>
                        <button type="button" class="btn btn-outline-secondary" data-filter="not-granted">Not Granted</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Permission Matrix -->
    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="bi bi-grid-3x3-gap me-2"></i>Role vs Permission Matrix
            </h5>
            <div>
                <span class="badge bg-secondary me-2">{{ $roles->count() }} Roles</span>
                <span class="badge bg-primary">{{ collect($permissions)->sum(function($g) { return count($g['permissions']); }) }} Permissions</span>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th style="min-width: 250px; position: sticky; left: 0; background: #212529; z-index: 10;">Permission</th>
                            @foreach($roles as $role)
                                <th class="text-center" style="min-width: 120px;">
                                    <div class="d-flex flex-column align-items-center">
                                        <span class="badge {{ in_array($role->name, $systemRoles) ? 'bg-secondary' : 'bg-success' }} mb-1">
                                            @if(in_array($role->name, $systemRoles))
                                                <i class="bi bi-lock me-1"></i>
                                            @endif
                                            {{ ucwords(str_replace(['_', '-'], ' ', $role->name)) }}
                                        </span>
                                        <small class="text-muted">{{ $role->permissions->count() }} perms</small>
                                    </div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($permissions as $group => $data)
                            <!-- Group Header -->
                            <tr class="group-header-row table-secondary" data-group="{{ $group }}">
                                <td colspan="{{ $roles->count() + 1 }}" class="fw-bold">
                                    <i class="bi bi-folder me-2"></i>{{ $data['label'] }}
                                    <span class="badge bg-primary ms-2">{{ count($data['permissions']) }}</span>
                                </td>
                            </tr>

                            <!-- Permission Rows -->
                            @foreach($data['permissions'] as $permission)
                                <tr class="permission-row" data-group="{{ $group }}" data-permission="{{ $permission->name }}">
                                    <td style="position: sticky; left: 0; background: white; z-index: 5;">
                                        <code class="text-primary">{{ $permission->name }}</code>
                                    </td>
                                    @foreach($roles as $role)
                                        <td class="text-center">
                                            <input type="checkbox"
                                                   class="form-check-input matrix-checkbox"
                                                   data-role-id="{{ $role->id }}"
                                                   data-permission-id="{{ $permission->id }}"
                                                   data-permission-name="{{ $permission->name }}"
                                                   {{ $role->hasPermissionTo($permission) ? 'checked' : '' }}
                                                   {{ in_array($role->name, $systemRoles) ? 'disabled' : '' }}>
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Legend -->
    <div class="card mt-4">
        <div class="card-body">
            <h6 class="card-title">Legend</h6>
            <div class="row">
                <div class="col-md-3">
                    <span class="badge bg-secondary me-2"><i class="bi bi-lock me-1"></i>System Role</span>
                    <small class="text-muted">Cannot be modified</small>
                </div>
                <div class="col-md-3">
                    <span class="badge bg-success me-2">Custom Role</span>
                    <small class="text-muted">Can be modified</small>
                </div>
                <div class="col-md-3">
                    <input type="checkbox" class="form-check-input me-2" checked disabled>
                    <small class="text-muted">Permission granted</small>
                </div>
                <div class="col-md-3">
                    <input type="checkbox" class="form-check-input me-2" disabled>
                    <small class="text-muted">Permission not granted</small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    console.log('✅ Matrix page initialized');

    // ==========================================
    // MODULE FILTER - FIXED
    // ==========================================
    $('#filter-group').on('change', function() {
        var selected = $(this).val();
        console.log('📂 Filter module:', selected || 'All');

        if (selected === '') {
            // Show all
            $('.permission-row, .group-header-row').show();
        } else {
            // Hide all, then show selected
            $('.permission-row, .group-header-row').hide();
            $('[data-group="' + selected + '"]').show();
        }
    });

    // ==========================================
    // SEARCH FILTER - FIXED
    // ==========================================
    $('#search-permission').on('input', function() {
        var term = $(this).val().toLowerCase();
        console.log('🔍 Search:', term || 'cleared');

        if (term === '') {
            $('.permission-row, .group-header-row').show();
            return;
        }

        $('.permission-row').each(function() {
            var name = $(this).data('permission').toLowerCase();
            $(this).toggle(name.indexOf(term) > -1);
        });

        // Update group headers
        $('.group-header-row').each(function() {
            var group = $(this).data('group');
            var count = $('.permission-row[data-group="' + group + '"]:visible').length;
            $(this).toggle(count > 0);
        });
    });

    // ==========================================
    // QUICK FILTERS - FIXED
    // ==========================================
    $('.btn-group button').on('click', function() {
        var filter = $(this).data('filter');
        console.log('⚡ Quick filter:', filter);

        // Update active state
        $('.btn-group button').removeClass('active');
        $(this).addClass('active');

        // Reset other filters
        $('#search-permission').val('');
        $('#filter-group').val('');

        if (filter === 'all') {
            $('.permission-row, .group-header-row').show();
        } else if (filter === 'granted') {
            $('.permission-row').each(function() {
                $(this).toggle($(this).find('.matrix-checkbox:checked').length > 0);
            });
            updateGroupHeaders();
        } else if (filter === 'not-granted') {
            $('.permission-row').each(function() {
                var total = $(this).find('.matrix-checkbox').length;
                var checked = $(this).find('.matrix-checkbox:checked').length;
                $(this).toggle(checked < total);
            });
            updateGroupHeaders();
        }
    });

    function updateGroupHeaders() {
        $('.group-header-row').each(function() {
            var group = $(this).data('group');
            var count = $('.permission-row[data-group="' + group + '"]:visible').length;
            $(this).toggle(count > 0);
        });
    }

    // ==========================================
    // PERMISSION TOGGLE (AJAX)
    // ==========================================
    $('.matrix-checkbox:not(:disabled)').on('change', function() {
        var $cb = $(this);
        var data = {
            role_id: $cb.data('role-id'),
            permission_id: $cb.data('permission-id'),
            granted: $cb.is(':checked') ? 1 : 0
        };

        console.log('💾 Saving:', data);
        $cb.prop('disabled', true);

        $.ajax({
            url: '{{ route("admin.permissions.update-matrix") }}',
            type: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: data,
            success: function(res) {
                if (res.success) {
                    toast(res.message, 'success');
                } else {
                    $cb.prop('checked', !data.granted);
                    toast(res.message || 'Update failed', 'error');
                }
            },
            error: function(xhr) {
                $cb.prop('checked', !data.granted);
                toast(xhr.responseJSON?.message || 'Update failed', 'error');
            },
            complete: function() {
                $cb.prop('disabled', false);
            }
        });
    });

    // Simple toast notification
    function toast(msg, type) {
        var bg = type === 'success' ? 'success' : 'danger';
        var $toast = $('<div class="position-fixed top-0 end-0 p-3" style="z-index:9999">' +
            '<div class="toast show bg-' + bg + ' text-white">' +
            '<div class="toast-body">' + msg + '</div></div></div>');
        $('body').append($toast);
        setTimeout(function() { $toast.fadeOut(300, function() { $(this).remove(); }); }, 3000);
    }

    console.log('✅ All filters ready');
});
</script>
@endpush
