@extends('layouts.app')

@section('title', 'Create Permission')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Create New Permission</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.permissions.index') }}">Permissions</a></li>
                    <li class="breadcrumb-item active">Create</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="{{ route('admin.permissions.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to List
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <!-- Create Form Card -->
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-plus-circle me-2"></i>Permission Details
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.permissions.store') }}" method="POST" id="createPermissionForm">
                        @csrf

                        <!-- Action -->
                        <div class="mb-3">
                            <label for="action" class="form-label">
                                Action <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('action') is-invalid @enderror"
                                    id="action"
                                    name="action"
                                    required>
                                <option value="">-- Select Action --</option>
                                @foreach($knownActions as $knownAction)
                                    <option value="{{ $knownAction }}" {{ old('action') == $knownAction ? 'selected' : '' }}>
                                        {{ ucfirst(str_replace('_', ' ', $knownAction)) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('action')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                Select the action this permission controls (e.g., view, create, edit, delete)
                            </div>
                        </div>

                        <!-- Module/Group -->
                        <div class="mb-3">
                            <label for="module" class="form-label">
                                Module <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control @error('module') is-invalid @enderror"
                                   id="module"
                                   name="module"
                                   value="{{ old('module') }}"
                                   list="existingGroupsList"
                                   placeholder="e.g., users, sites, inventory"
                                   required>
                            <datalist id="existingGroupsList">
                                @foreach($existingGroups as $existingGroup)
                                    <option value="{{ $existingGroup }}">
                                @endforeach
                            </datalist>
                            @error('module')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                Enter an existing module or create a new one. Start typing to see suggestions.
                            </div>
                        </div>

                        <!-- Permission Name Preview -->
                        <div class="mb-3">
                            <label class="form-label">Permission Name (Auto-generated)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">
                                    <i class="bi bi-key"></i>
                                </span>
                                <input type="text"
                                       class="form-control bg-light"
                                       id="permission-name-preview"
                                       value="[action]_[module]"
                                       disabled>
                            </div>
                            <div class="form-text">
                                <i class="bi bi-info-circle me-1"></i>
                                Format: action_module (e.g., view_users, create_sites)
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror"
                                      id="description"
                                      name="description"
                                      rows="3"
                                      placeholder="Brief description of what this permission allows">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                Optional: Provide a clear description of what this permission controls
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle me-1"></i> Create Permission
                            </button>
                            <a href="{{ route('admin.permissions.index') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-x-circle me-1"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Info Sidebar -->
        <div class="col-md-4">
            <!-- Naming Convention -->
            <div class="card mb-3">
                <div class="card-header bg-white">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-lightbulb me-2"></i>Naming Convention
                    </h6>
                </div>
                <div class="card-body">
                    <p class="mb-2"><strong>Format:</strong> <code>action_module</code></p>
                    <p class="mb-2"><strong>Examples:</strong></p>
                    <ul class="list-unstyled mb-0">
                        <li><code class="text-primary">view_users</code></li>
                        <li><code class="text-primary">create_sites</code></li>
                        <li><code class="text-primary">edit_inventory</code></li>
                        <li><code class="text-primary">delete_partners</code></li>
                        <li><code class="text-primary">approve_stock_transfers</code></li>
                    </ul>
                </div>
            </div>

            <!-- Common Actions -->
            <div class="card mb-3">
                <div class="card-header bg-white">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-list-check me-2"></i>Common Actions
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        @foreach($knownActions as $action)
                            <div class="col-6">
                                <span class="badge bg-secondary w-100">{{ $action }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Existing Modules -->
            <div class="card">
                <div class="card-header bg-white">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-folder me-2"></i>Existing Modules
                    </h6>
                </div>
                <div class="card-body">
                    @if(count($existingGroups) > 0)
                        <div class="d-flex flex-wrap gap-1">
                            @foreach($existingGroups as $group)
                                <span class="badge bg-info module-badge" data-module="{{ $group }}">
                                    {{ $group }}
                                </span>
                            @endforeach
                        </div>
                    @else
                        <small class="text-muted">No modules yet. Create your first permission!</small>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Update permission name preview
    function updatePermissionPreview() {
        var action = $('#action').val();
        var module = $('#module').val().trim();

        if (action && module) {
            var permissionName = action + '_' + module;
            $('#permission-name-preview').val(permissionName);
        } else if (action) {
            $('#permission-name-preview').val(action + '_[module]');
        } else if (module) {
            $('#permission-name-preview').val('[action]_' + module);
        } else {
            $('#permission-name-preview').val('[action]_[module]');
        }
    }

    // Listen for changes
    $('#action, #module').on('change input', function() {
        updatePermissionPreview();
    });

    // Auto-fill description
    $('#action, #module').on('change', function() {
        var action = $('#action').val();
        var module = $('#module').val().trim();
        var description = $('#description').val().trim();

        if (action && module && !description) {
            var actionLabel = $('#action option:selected').text();
            var moduleLabel = module.charAt(0).toUpperCase() + module.slice(1).replace(/_/g, ' ');
            $('#description').val(actionLabel + ' ' + moduleLabel);
        }
    });

    // Click module badge to auto-fill
    $('.module-badge').on('click', function() {
        var module = $(this).data('module');
        $('#module').val(module).trigger('input');
        $('#module').focus();
    });

    // Form validation
    $('#createPermissionForm').on('submit', function(e) {
        var action = $('#action').val();
        var module = $('#module').val().trim();

        if (!action || !module) {
            e.preventDefault();
            alert('Please select an action and enter a module name.');
            return false;
        }

        // Validate module format (lowercase, alphanumeric and underscores only)
        var moduleRegex = /^[a-z0-9_]+$/;
        if (!moduleRegex.test(module)) {
            e.preventDefault();
            alert('Module name must be lowercase and contain only letters, numbers, and underscores.');
            $('#module').focus();
            return false;
        }
    });

    // Module input validation (convert to lowercase, remove invalid chars)
    $('#module').on('input', function() {
        var val = $(this).val();
        // Convert to lowercase and replace spaces with underscores
        val = val.toLowerCase().replace(/\s+/g, '_');
        // Remove any characters that aren't alphanumeric or underscore
        val = val.replace(/[^a-z0-9_]/g, '');
        $(this).val(val);
        updatePermissionPreview();
    });
});
</script>

<style>
.module-badge {
    cursor: pointer;
    transition: all 0.2s;
}

.module-badge:hover {
    transform: scale(1.05);
    opacity: 0.8;
}
</style>
@endpush
