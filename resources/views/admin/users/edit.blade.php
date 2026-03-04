@extends('layouts.app')

@section('title', 'Edit User: ' . $user->name . ' - TMS')

@section('content')
<div class="container-fluid">
    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="bi bi-person-gear me-2"></i>Edit User: {{ $user->name }}</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Users</a></li>
                    <li class="breadcrumb-item active">Edit</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to List
        </a>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Validation Errors --}}
    <div id="validationErrors" class="alert alert-danger d-none">
        <ul class="mb-0" id="errorList"></ul>
    </div>

    {{-- Edit User Form --}}
    <form id="editUserForm" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        {{-- User Information Section --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0"><i class="bi bi-person me-2"></i>User Information</h6>
            </div>
            <div class="card-body">
                {{-- Avatar --}}
                <div class="text-center mb-4">
                    <div class="avatar-preview mx-auto mb-2" id="avatarPreview"
                         style="width:100px;height:100px;border-radius:50%;background:#e9ecef;display:flex;align-items:center;justify-content:center;font-size:2.5rem;color:#6c757d;overflow:hidden;">
                        @if($user->avatar)
                            <img src="{{ asset('storage/' . $user->avatar) }}"
                                 style="width:100%;height:100%;object-fit:cover;"
                                 alt="{{ $user->name }}"
                                 onerror="this.onerror=null;this.parentElement.innerHTML='{{ strtoupper(substr($user->name, 0, 1)) }}';">
                        @else
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        @endif
                    </div>
                    <div class="d-flex justify-content-center gap-2">
                        <label class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-camera me-1"></i> Change Avatar
                            <input type="file" name="avatar" id="avatarInput" accept="image/jpg,image/jpeg,image/png,image/gif" hidden>
                        </label>
                        @if($user->avatar)
                            <button type="button" class="btn btn-sm btn-outline-danger" id="removeAvatarBtn">
                                <i class="bi bi-trash me-1"></i> Remove
                            </button>
                            <input type="hidden" name="remove_avatar" id="removeAvatarField" value="0">
                        @endif
                    </div>
                    <div class="form-text">Max file size: 2MB. Allowed: JPG, JPEG, PNG, GIF</div>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ $user->name }}" required minlength="3" maxlength="255">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" value="{{ $user->email }}" required maxlength="255">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Phone Number</label>
                        <input type="text" name="phone" class="form-control" value="{{ $user->phone }}" maxlength="20">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Employee ID</label>
                        <input type="text" name="employee_id" class="form-control" value="{{ $user->employee_id }}" maxlength="50">
                        <div class="form-text">Auto-generated if not provided</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Role & Status Section --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0"><i class="bi bi-shield-lock me-2"></i>Role & Status</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Role <span class="text-danger">*</span></label>
                        <select name="role" id="roleSelect" class="form-select select2" required>
                            <option value="">Select a role</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->name }}" {{ $user->hasRole($role->name) ? 'selected' : '' }}>
                                    {{ ucfirst($role->name) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required>
                            <option value="active" {{ $user->status === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ $user->status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            <option value="suspended" {{ $user->status === 'suspended' ? 'selected' : '' }}>Suspended</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- Technician Information Section --}}
        @php
            $isTechnician = $user->hasRole('technician');
            $hasSupervisor = !is_null($user->supervisor_id);
            $userCoverageStates = $user->coverage_states ? (is_string($user->coverage_states) ? json_decode($user->coverage_states, true) : $user->coverage_states) : [];
            $userSkillTags = $user->skill_tags ? (is_string($user->skill_tags) ? json_decode($user->skill_tags, true) : $user->skill_tags) : [];
        @endphp
        <div class="card border-0 shadow-sm mb-4 {{ $isTechnician ? '' : 'd-none' }}" id="technicianSection">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0"><i class="bi bi-tools me-2"></i>Technician Information</h6>
            </div>
            <div class="card-body">
                {{-- Supervisor Toggle --}}
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="hasSupervisorToggle" name="has_supervisor" value="1" {{ $hasSupervisor ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="hasSupervisorToggle">
                            Assign to Supervisor (uncheck for independent technician)
                        </label>
                    </div>
                </div>

                {{-- Supervisor Dropdown --}}
                <div class="mb-3" id="supervisorField" style="{{ $hasSupervisor ? '' : 'display:none;' }}">
                    <label class="form-label fw-semibold">Supervisor <span class="text-danger" id="supervisorRequired" style="{{ $hasSupervisor ? '' : 'display:none;' }}">*</span></label>
                    <select name="supervisor_id" id="supervisorSelect" class="form-select select2">
                        <option value="">Select an option</option>
                        @foreach($supervisors as $supervisor)
                            <option value="{{ $supervisor->id }}" {{ $user->supervisor_id == $supervisor->id ? 'selected' : '' }}>
                                {{ $supervisor->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Coverage States --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold">Coverage States (Work Areas)</label>
                    <select name="coverage_states[]" id="coverageStates" class="form-select select2" multiple>
                        @foreach($states as $state)
                            <option value="{{ $state }}" {{ in_array($state, $userCoverageStates ?? []) ? 'selected' : '' }}>
                                {{ $state }}
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text">Select states where this technician can work</div>
                </div>

                {{-- Skills --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold">Skills</label>
                    <select name="skill_tags[]" id="skillTags" class="form-select select2" multiple>
                        @foreach($skillTags as $skill)
                            <option value="{{ $skill }}" {{ in_array($skill, $userSkillTags ?? []) ? 'selected' : '' }}>
                                {{ $skill }}
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text">Select technician's skills and expertise</div>
                </div>
            </div>
        </div>

        {{-- Bank Details Section --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0"><i class="bi bi-bank me-2"></i>Bank Details</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Bank Name</label>
                        <input type="text" name="bank_name" class="form-control" value="{{ $user->bank_name }}" maxlength="100">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Account Number</label>
                        <input type="text" name="bank_account_no" class="form-control" value="{{ $user->bank_account_no }}" maxlength="50">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Account Name</label>
                        <input type="text" name="bank_account_name" class="form-control" value="{{ $user->bank_account_name }}" maxlength="255">
                    </div>
                </div>
            </div>
        </div>

        {{-- Address Section --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0"><i class="bi bi-geo-alt me-2"></i>Address</h6>
            </div>
            <div class="card-body">
                <textarea name="address" class="form-control" rows="3" maxlength="500">{{ $user->address }}</textarea>
            </div>
        </div>

        {{-- Submit Buttons --}}
        <div class="d-flex justify-content-end gap-2 mb-4">
            <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">
                <i class="bi bi-x-circle me-1"></i> Cancel
            </a>
            <button type="submit" class="btn btn-primary" id="submitBtn">
                <i class="bi bi-check-circle me-1"></i> Update User
            </button>
        </div>
    </form>

    {{-- Change Password Section (Separate Form) --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-warning text-dark">
            <h6 class="mb-0"><i class="bi bi-key me-2"></i>Change Password</h6>
        </div>
        <div class="card-body">
            <form id="changePasswordForm">
                @csrf
                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label fw-semibold">New Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" name="new_password" id="newPassword" class="form-control" required minlength="8" placeholder="Min 8 characters">
                            <button type="button" class="btn btn-outline-secondary toggle-password" data-target="newPassword">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label fw-semibold">Confirm Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" name="new_password_confirmation" id="newPasswordConfirmation" class="form-control" required minlength="8" placeholder="Re-enter password">
                            <button type="button" class="btn btn-outline-secondary toggle-password" data-target="newPasswordConfirmation">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-warning w-100" id="changePasswordBtn">
                            <i class="bi bi-key me-1"></i> Change
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize Select2
    $('.select2').select2({
        theme: 'bootstrap-5',
        width: '100%'
    });

    // Avatar preview
    $('#avatarInput').on('change', function() {
        var file = this.files[0];
        if (file) {
            if (file.size > 2 * 1024 * 1024) {
                showToast('File size must not exceed 2MB', 'error');
                this.value = '';
                return;
            }
            var reader = new FileReader();
            reader.onload = function(e) {
                $('#avatarPreview').html('<img src="' + e.target.result + '" style="width:100%;height:100%;object-fit:cover;">');
            };
            reader.readAsDataURL(file);
            $('#removeAvatarField').val('0');
        }
    });

    // Remove avatar
    $('#removeAvatarBtn').on('click', function() {
        $('#removeAvatarField').val('1');
        $('#avatarPreview').html('<i class="bi bi-person"></i>');
        $('#avatarInput').val('');
        showToast('Avatar will be removed on save', 'info');
    });

    // Role change handler
    $('#roleSelect').on('change', function() {
        var role = $(this).val();
        if (role === 'technician') {
            $('#technicianSection').removeClass('d-none');
        } else {
            $('#technicianSection').addClass('d-none');
            $('#hasSupervisorToggle').prop('checked', false);
            $('#supervisorSelect').val('').trigger('change');
            $('#coverageStates').val([]).trigger('change');
            $('#skillTags').val([]).trigger('change');
        }
    });

    // Supervisor toggle handler - FIX: properly show/hide supervisor field
    $('#hasSupervisorToggle').on('change', function() {
        var isChecked = $(this).is(':checked');
        if (isChecked) {
            $('#supervisorField').slideDown();
            $('#supervisorRequired').show();
        } else {
            $('#supervisorField').slideUp();
            $('#supervisorRequired').hide();
            $('#supervisorSelect').val('').trigger('change');
        }
    });

    // Password toggle
    $(document).on('click', '.toggle-password', function() {
        var target = $(this).data('target');
        var input = $('#' + target);
        var icon = $(this).find('i');
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('bi-eye').addClass('bi-eye-slash');
        } else {
            input.attr('type', 'password');
            icon.removeClass('bi-eye-slash').addClass('bi-eye');
        }
    });

    // Edit User Form submission
    $('#editUserForm').on('submit', function(e) {
        e.preventDefault();

        var formData = new FormData(this);

        // If has_supervisor is unchecked, send as 0
        if (!$('#hasSupervisorToggle').is(':checked')) {
            formData.set('has_supervisor', '0');
        }

        // If role is not technician, remove technician fields
        if ($('#roleSelect').val() !== 'technician') {
            formData.delete('has_supervisor');
            formData.delete('supervisor_id');
            formData.delete('coverage_states[]');
            formData.delete('skill_tags[]');
        }

        $('#submitBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Updating...');
        $('#validationErrors').addClass('d-none');

        $.ajax({
            url: '{{ route("admin.users.update", $user->id) }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(response) {
                if (response.success) {
                    showToast(response.message);
                    if (response.redirect) {
                        setTimeout(function() { window.location.href = response.redirect; }, 1000);
                    }
                } else {
                    showToast(response.message || 'Failed to update user', 'error');
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    var errors = xhr.responseJSON.errors;
                    var errorHtml = '';
                    $.each(errors, function(key, messages) {
                        $.each(messages, function(i, msg) {
                            errorHtml += '<li>' + msg + '</li>';
                        });
                    });
                    $('#errorList').html(errorHtml);
                    $('#validationErrors').removeClass('d-none');
                    $('html, body').animate({ scrollTop: 0 }, 300);
                } else {
                    showToast(xhr.responseJSON?.message || 'An error occurred', 'error');
                }
            },
            complete: function() {
                $('#submitBtn').prop('disabled', false).html('<i class="bi bi-check-circle me-1"></i> Update User');
            }
        });
    });

    // Change Password Form - FIX: Uses POST method (not PUT) to match route
    $('#changePasswordForm').on('submit', function(e) {
        e.preventDefault();

        var newPassword = $('#newPassword').val();
        var confirmPassword = $('#newPasswordConfirmation').val();

        if (newPassword !== confirmPassword) {
            showToast('Passwords do not match', 'error');
            return;
        }

        if (newPassword.length < 8) {
            showToast('Password must be at least 8 characters', 'error');
            return;
        }

        $('#changePasswordBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Changing...');

        $.ajax({
            url: '{{ route("admin.users.change-password", $user->id) }}',
            type: 'POST',  // FIX: Must be POST to match Route::post() definition
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                new_password: newPassword,
                new_password_confirmation: confirmPassword
            },
            success: function(response) {
                if (response.success) {
                    showToast(response.message);
                    $('#changePasswordForm')[0].reset();
                } else {
                    showToast(response.message || 'Failed to change password', 'error');
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    var errors = xhr.responseJSON.errors;
                    var msg = '';
                    $.each(errors, function(key, messages) {
                        msg += messages.join(', ') + '\n';
                    });
                    showToast(msg || 'Validation error', 'error');
                } else {
                    showToast(xhr.responseJSON?.message || 'Failed to change password', 'error');
                }
            },
            complete: function() {
                $('#changePasswordBtn').prop('disabled', false).html('<i class="bi bi-key me-1"></i> Change');
            }
        });
    });
});
</script>
@endpush
