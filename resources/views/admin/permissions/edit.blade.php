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
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.permissions.index') }}">Permissions</a></li>
                    <li class="breadcrumb-item active">Edit</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="{{ route('admin.permissions.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to List
            </a>
        </div>
    </div>

    <!-- Warning if permission is in use -->
    @if($rolesUsingPermission->count() > 0)
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <strong>Warning:</strong> This permission is currently assigned to {{ $rolesUsingPermission->count() }} role(s):
        @foreach($rolesUsingPermission as $role)
            <span class="badge bg-secondary">{{ $role->name }}</span>
        @endforeach
        <br>
        Changing this permission may affect access control for these roles.
    </div>
    @endif

    <div class="row">
        <div class="col-md-8">
            <!-- Edit Form Card -->
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-pencil me-2"></i>Permission Details
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.permissions.update', $permission) }}" method="POST" id="editPermissionForm">
                        @csrf
                        @method('PUT')

                        <!-- Permission Name (Read-only) -->
                        <div class="mb-3">
                            <label class="form-label">Current Permission Name</label>
                            <input type="text"
                                   class="form-control bg-light"
                                   value="{{ $permission->name }}"
                                   disabled>
                            <div class="form-text">
                                <i class="bi bi-info-circle me-1"></i>
                                Permission names cannot be changed to maintain system integrity
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
                                   value="{{ old('module', $group ?? $permission->module) }}"
                                   list="existingGroups"
                                   placeholder="e.g., users, sites, inventory"
                                   required>
                            <datalist id="existingGroups">
                                @foreach($existingGroups as $existingGroup)
                                    <option value="{{ $existingGroup }}">
                                @endforeach
                            </datalist>
                            @error('module')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                Enter an existing module or create a new one
                            </div>
                        </div>

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
                                    <option value="{{ $knownAction }}"
                                            {{ old('action', $action) == $knownAction ? 'selected' : '' }}>
                                        {{ ucfirst(str_replace('_', ' ', $knownAction)) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('action')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                Select the action this permission controls
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror"
                                      id="description"
                                      name="description"
                                      rows="3"
                                      placeholder="Brief description of what this permission allows">{{ old('description', $permission->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Guard Name -->
                        <div class="mb-3">
                            <label for="guard_name" class="form-label">Guard</label>
                            <input type="text"
                                   class="form-control bg-light"
                                   id="guard_name"
                                   value="{{ $permission->guard_name }}"
                                   disabled>
                            <div class="form-text">Guard name cannot be changed</div>
                        </div>

                        <!-- Form Actions -->
                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle me-1"></i> Update Permission
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
            <!-- Permission Info -->
            <div class="card mb-3">
                <div class="card-header bg-white">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-info-circle me-2"></i>Permission Information
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <small class="text-muted">Created</small>
                        <div>{{ $permission->created_at->format('M d, Y H:i') }}</div>
                    </div>
                    @if($permission->updated_at && $permission->updated_at != $permission->created_at)
                    <div class="mb-2">
                        <small class="text-muted">Last Updated</small>
                        <div>{{ $permission->updated_at->format('M d, Y H:i') }}</div>
                    </div>
                    @endif
                    <div class="mb-2">
                        <small class="text-muted">ID</small>
                        <div><code>{{ $permission->id }}</code></div>
                    </div>
                </div>
            </div>

            <!-- Roles Using This Permission -->
            @if($rolesUsingPermission->count() > 0)
            <div class="card">
                <div class="card-header bg-white">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-shield-lock me-2"></i>Assigned to Roles
                    </h6>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        @foreach($rolesUsingPermission as $role)
                        <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                            <span>{{ ucfirst($role->name) }}</span>
                            <span class="badge bg-secondary">{{ $role->users_count ?? 0 }} users</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @else
            <div class="card">
                <div class="card-body">
                    <div class="text-muted text-center">
                        <i class="bi bi-shield-x fs-1 d-block mb-2"></i>
                        <small>Not assigned to any roles</small>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Form validation
    $('#editPermissionForm').on('submit', function(e) {
        var module = $('#module').val().trim();
        var action = $('#action').val();

        if (!module || !action) {
            e.preventDefault();
            alert('Please fill in all required fields.');
            return false;
        }
    });

    // Highlight changes
    var originalModule = '{{ $group ?? $permission->module }}';
    var originalAction = '{{ $action }}';

    $('#module, #action').on('change', function() {
        var currentModule = $('#module').val();
        var currentAction = $('#action').val();

        if (currentModule !== originalModule || currentAction !== originalAction) {
            $('.btn-primary').addClass('pulse');
        } else {
            $('.btn-primary').removeClass('pulse');
        }
    });
});
</script>

<style>
.pulse {
    animation: pulse 1s infinite;
}

@keyframes pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(13, 110, 253, 0.4);
    }
    70% {
        box-shadow: 0 0 0 10px rgba(13, 110, 253, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(13, 110, 253, 0);
    }
}
</style>
@endpush
