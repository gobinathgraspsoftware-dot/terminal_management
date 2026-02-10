@extends('layouts.app')

@section('title', 'Edit Permission')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Edit Permission</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.permissions.index') }}">Permissions</a></li>
                    <li class="breadcrumb-item active">Edit</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="{{ route('admin.permissions.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Permissions
            </a>
        </div>
    </div>

    <!-- Alert Messages -->
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <strong>Please correct the following errors:</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Edit Form -->
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-key me-2"></i>Permission Information
                    </h5>
                    <span class="badge bg-secondary">ID: {{ $permission->id }}</span>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.permissions.update', $permission) }}" method="POST" id="edit-permission-form">
                        @csrf
                        @method('PUT')

                        <!-- Permission Name -->
                        <div class="mb-4">
                            <label for="name" class="form-label">
                                Permission Name <span class="text-danger">*</span>
                                <i class="bi bi-info-circle text-muted" data-bs-toggle="tooltip" 
                                   title="Use lowercase letters, numbers, and underscores only"></i>
                            </label>
                            <input type="text" 
                                   class="form-control @error('name') is-invalid @enderror" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name', $permission->name) }}"
                                   placeholder="e.g., view_users, create_invoices"
                                   required
                                   maxlength="255">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                Current: <code>{{ $permission->name }}</code>
                            </div>
                        </div>

                        <div class="border-top border-bottom py-3 mb-4">
                            <h6 class="text-muted mb-0">
                                <i class="bi bi-tools me-2"></i>Permission Components
                            </h6>
                        </div>

                        <!-- Permission Components -->
                        <div class="row mb-4">
                            <!-- Action Selection -->
                            <div class="col-md-6">
                                <label for="action" class="form-label">
                                    Action Type
                                    <i class="bi bi-info-circle text-muted" data-bs-toggle="tooltip" 
                                       title="Select what action this permission controls"></i>
                                </label>
                                <select class="form-select @error('action') is-invalid @enderror" 
                                        id="action" 
                                        name="action">
                                    <option value="">-- Select Action --</option>
                                    @foreach($knownActions as $action)
                                        <option value="{{ $action }}" 
                                            {{ old('action', $currentAction) == $action ? 'selected' : '' }}>
                                            {{ ucwords(str_replace('_', ' ', $action)) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('action')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Current: <strong>{{ $currentAction }}</strong></div>
                            </div>

                            <!-- Group/Module Selection -->
                            <div class="col-md-6">
                                <label for="group" class="form-label">
                                    Module/Group <span class="text-danger">*</span>
                                    <i class="bi bi-info-circle text-muted" data-bs-toggle="tooltip" 
                                       title="Select which module this permission belongs to"></i>
                                </label>
                                <select class="form-select @error('group') is-invalid @enderror" 
                                        id="group" 
                                        name="group" 
                                        required>
                                    <option value="">-- Select Module --</option>
                                    @foreach($permissionGroups as $key => $label)
                                        <option value="{{ $key }}" 
                                            {{ old('group', $currentGroup) == $key ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('group')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Current: <strong>{{ $currentGroup }}</strong></div>
                            </div>
                        </div>

                        <!-- Updated Permission Name Preview -->
                        <div class="mb-4">
                            <label class="form-label">Updated Permission Name Preview:</label>
                            <div class="alert alert-info mb-0">
                                <i class="bi bi-info-circle me-2"></i>
                                <code id="permission-preview" class="text-dark">{{ $permission->name }}</code>
                            </div>
                        </div>

                        <!-- Description (Optional) -->
                        <div class="mb-4">
                            <label for="description" class="form-label">
                                Description (Optional)
                                <i class="bi bi-info-circle text-muted" data-bs-toggle="tooltip" 
                                   title="Add a human-readable description for this permission"></i>
                            </label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" 
                                      name="description" 
                                      rows="3" 
                                      placeholder="Brief description of what this permission controls"
                                      maxlength="500">{{ old('description', $permission->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                <span id="char-count">0</span>/500 characters
                            </div>
                        </div>

                        <!-- Guard Name -->
                        <div class="mb-4">
                            <label for="guard_name" class="form-label">Guard Name</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="guard_name" 
                                   name="guard_name" 
                                   value="{{ old('guard_name', $permission->guard_name) }}"
                                   readonly>
                            <div class="form-text">Usually 'web' for web application permissions</div>
                        </div>

                        <!-- Roles Using This Permission -->
                        @if($permission->roles->count() > 0)
                        <div class="mb-4">
                            <label class="form-label">
                                <i class="bi bi-shield-lock me-1"></i>Roles Using This Permission:
                            </label>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($permission->roles as $role)
                                    <span class="badge bg-primary">{{ ucwords($role->name) }}</span>
                                @endforeach
                            </div>
                            <div class="alert alert-warning mt-2 mb-0">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <strong>Warning:</strong> Changing the permission name will affect {{ $permission->roles->count() }} role(s).
                            </div>
                        </div>
                        @endif

                        <!-- Form Actions -->
                        <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                            <a href="{{ route('admin.permissions.index') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-x-circle me-1"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary" id="submit-btn">
                                <i class="bi bi-save me-1"></i> Update Permission
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Danger Zone -->
            <div class="card mt-4 border-danger">
                <div class="card-header bg-danger text-white">
                    <h6 class="mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Danger Zone</h6>
                </div>
                <div class="card-body">
                    <p class="mb-3">
                        <strong>Delete Permission:</strong> This action cannot be undone. The permission will be removed from all roles.
                    </p>
                    @if($permission->roles->count() > 0)
                        <div class="alert alert-warning mb-3">
                            <i class="bi bi-info-circle me-2"></i>
                            This permission is currently assigned to {{ $permission->roles->count() }} role(s). 
                            It will be automatically removed from all roles if deleted.
                        </div>
                    @endif
                    <form action="{{ route('admin.permissions.destroy', $permission) }}" 
                          method="POST" 
                          id="delete-form" 
                          class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="button" class="btn btn-danger" onclick="confirmDelete()">
                            <i class="bi bi-trash me-1"></i> Delete Permission
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Character counter for description
    $('#description').on('input', function() {
        var length = $(this).val().length;
        $('#char-count').text(length);
    });

    // Initialize counter on load
    $('#char-count').text($('#description').val().length);

    // Update permission name preview when action or group changes
    function updatePermissionPreview() {
        var action = $('#action').val();
        var group = $('#group').val();
        
        if (action && group) {
            var permissionName = action + '_' + group;
            $('#permission-preview').text(permissionName).removeClass('text-muted').addClass('fw-bold');
            // Update the name field
            $('#name').val(permissionName);
        }
    }

    // Listen for changes
    $('#action, #group').on('change', updatePermissionPreview);

    // Form validation
    $('#edit-permission-form').on('submit', function(e) {
        var name = $('#name').val().trim();

        if (!name) {
            e.preventDefault();
            alert('Permission name is required.');
            return false;
        }

        // Validate format
        if (!/^[a-z0-9_]+$/.test(name)) {
            if (!confirm('Permission name should only contain lowercase letters, numbers, and underscores. Continue anyway?')) {
                e.preventDefault();
                return false;
            }
        }

        // Show loading state
        $('#submit-btn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Updating...');
    });
});

// Delete confirmation
function confirmDelete() {
    if (confirm('Are you sure you want to delete this permission?\n\nThis action cannot be undone and the permission will be removed from all roles.')) {
        if (confirm('This is your last chance. Are you absolutely sure?')) {
            $('#delete-form').submit();
        }
    }
}
</script>
@endsection
