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
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.permissions.index') }}">Permissions</a></li>
                    <li class="breadcrumb-item active">Create</li>
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

    <!-- Create Form -->
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-key me-2"></i>Permission Information
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.permissions.store') }}" method="POST" id="create-permission-form">
                        @csrf

                        <!-- Permission Name (Manual) -->
                        <div class="mb-4">
                            <label for="name" class="form-label">
                                Permission Name <span class="text-danger">*</span>
                                <i class="bi bi-info-circle text-muted" data-bs-toggle="tooltip" 
                                   title="Use lowercase letters, numbers, and underscores only (e.g., view_users, create_invoices)"></i>
                            </label>
                            <input type="text" 
                                   class="form-control @error('name') is-invalid @enderror" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name') }}"
                                   placeholder="e.g., view_users, create_invoices"
                                   maxlength="255">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                Enter the full permission name manually or use the builder below.
                            </div>
                        </div>

                        <div class="border-top border-bottom py-3 mb-4">
                            <h6 class="text-muted mb-0">
                                <i class="bi bi-tools me-2"></i>OR Use Permission Builder
                            </h6>
                        </div>

                        <!-- Permission Builder -->
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
                                        <option value="{{ $action }}" {{ old('action') == $action ? 'selected' : '' }}>
                                            {{ ucwords(str_replace('_', ' ', $action)) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('action')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
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
                                        <option value="{{ $key }}" {{ old('group') == $key ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('group')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Generated Permission Name Preview -->
                        <div class="mb-4">
                            <label class="form-label">Generated Permission Name Preview:</label>
                            <div class="alert alert-info mb-0">
                                <i class="bi bi-info-circle me-2"></i>
                                <code id="permission-preview" class="text-dark">Select action and module to preview</code>
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
                                      maxlength="500">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                <span id="char-count">0</span>/500 characters
                            </div>
                        </div>

                        <!-- Guard Name (Hidden, default to 'web') -->
                        <input type="hidden" name="guard_name" value="web">

                        <!-- Form Actions -->
                        <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                            <a href="{{ route('admin.permissions.index') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-x-circle me-1"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary" id="submit-btn">
                                <i class="bi bi-save me-1"></i> Create Permission
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Help Card -->
            <div class="card mt-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="bi bi-lightbulb me-2"></i>Naming Convention Guide</h6>
                </div>
                <div class="card-body">
                    <p class="mb-2"><strong>Permission Format:</strong> <code>action_module</code></p>
                    <p class="mb-2"><strong>Examples:</strong></p>
                    <ul class="mb-0">
                        <li><code>view_users</code> - View user listings</li>
                        <li><code>create_invoices</code> - Create new invoices</li>
                        <li><code>edit_sites</code> - Edit site information</li>
                        <li><code>delete_vendors</code> - Delete vendor records</li>
                        <li><code>approve_stock_transfers</code> - Approve stock transfers</li>
                        <li><code>manage_contacts_clients</code> - Manage client contacts</li>
                    </ul>
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

    // Trigger on page load if there's old input
    if ($('#description').val()) {
        $('#char-count').text($('#description').val().length);
    }

    // Generate permission name preview
    function updatePermissionPreview() {
        var action = $('#action').val();
        var group = $('#group').val();
        
        if (action && group) {
            var permissionName = action + '_' + group;
            $('#permission-preview').text(permissionName).removeClass('text-muted').addClass('fw-bold');
            // Auto-fill the name field if it's empty
            if ($('#name').val() === '') {
                $('#name').val(permissionName);
            }
        } else if (group) {
            $('#permission-preview').text('Select action to complete').removeClass('fw-bold').addClass('text-muted');
        } else {
            $('#permission-preview').text('Select action and module to preview').removeClass('fw-bold').addClass('text-muted');
        }
    }

    // Listen for changes
    $('#action, #group').on('change', updatePermissionPreview);

    // If there are old values, update preview
    if ($('#action').val() || $('#group').val()) {
        updatePermissionPreview();
    }

    // Clear auto-generated name when manual name is entered
    $('#name').on('input', function() {
        if ($(this).val().length > 0) {
            // User is manually typing, don't auto-fill
        }
    });

    // Form validation
    $('#create-permission-form').on('submit', function(e) {
        var name = $('#name').val().trim();
        var group = $('#group').val();

        // Must have either a manual name or both action and group
        if (!name && !group) {
            e.preventDefault();
            alert('Please either enter a permission name or select a module.');
            return false;
        }

        // If name is provided but not in correct format, warn user
        if (name && !/^[a-z0-9_]+$/.test(name)) {
            if (!confirm('Permission name should only contain lowercase letters, numbers, and underscores. Continue anyway?')) {
                e.preventDefault();
                return false;
            }
        }

        // Show loading state
        $('#submit-btn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Creating...');
    });
});
</script>
@endsection
