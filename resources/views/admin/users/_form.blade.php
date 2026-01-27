{{-- Reusable User Form Partial --}}
{{-- Used by both create.blade.php and edit.blade.php --}}

<div class="row">
    <!-- Avatar Upload Section -->
    <div class="col-md-12 mb-4">
        <div class="card bg-light">
            <div class="card-body text-center">
                <div class="mb-3">
                    @if($isEdit && $user->avatar)
                        <img src="{{ asset('storage/' . $user->avatar) }}" id="avatarPreview" class="rounded-circle" style="width: 120px; height: 120px; object-fit: cover;" alt="Avatar">
                    @else
                        <div id="avatarPreview" class="bg-secondary text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 120px; height: 120px; font-size: 48px;">
                            {{ $isEdit ? strtoupper(substr($user->name, 0, 1)) : '?' }}
                        </div>
                    @endif
                </div>
                <div>
                    <label for="avatar" class="btn btn-sm btn-primary">
                        <i class="fas fa-camera"></i> {{ $isEdit ? 'Change' : 'Upload' }} Avatar
                    </label>
                    <input type="file" class="d-none" id="avatar" name="avatar" accept="image/*">
                    @if($isEdit && $user->avatar)
                    <button type="button" class="btn btn-sm btn-danger" id="removeAvatar">
                        <i class="fas fa-trash"></i> Remove
                    </button>
                    @endif
                </div>
                <small class="text-muted d-block mt-2">Max file size: 2MB. Allowed: JPG, JPEG, PNG, GIF</small>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Basic Information -->
    <div class="col-md-6">
        <div class="mb-3">
            <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="name" name="name" 
                   value="{{ old('name', $isEdit ? $user->name : '') }}" 
                   placeholder="Enter full name" required>
        </div>
    </div>

    <div class="col-md-6">
        <div class="mb-3">
            <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
            <input type="email" class="form-control" id="email" name="email" 
                   value="{{ old('email', $isEdit ? $user->email : '') }}" 
                   placeholder="Enter email address" required>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="mb-3">
            <label for="phone" class="form-label">Phone Number</label>
            <input type="text" class="form-control" id="phone" name="phone" 
                   value="{{ old('phone', $isEdit ? $user->phone : '') }}" 
                   placeholder="Enter phone number">
        </div>
    </div>

    <div class="col-md-6">
        <div class="mb-3">
            <label for="employee_id" class="form-label">Employee ID</label>
            <input type="text" class="form-control" id="employee_id" name="employee_id" 
                   value="{{ old('employee_id', $isEdit ? $user->employee_id : '') }}" 
                   placeholder="Auto-generated if left blank" readonly>
            <small class="text-muted">Auto-generated if not provided</small>
        </div>
    </div>
</div>

@if(!$isEdit)
<div class="row">
    <div class="col-md-6">
        <div class="mb-3">
            <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
            <div class="input-group">
                <input type="password" class="form-control" id="password" name="password" 
                       placeholder="Enter password" required>
                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                    <i class="fas fa-eye" id="togglePasswordIcon"></i>
                </button>
            </div>
            <small class="text-muted">Minimum 8 characters</small>
        </div>
    </div>

    <div class="col-md-6">
        <div class="mb-3">
            <label for="password_confirmation" class="form-label">Confirm Password <span class="text-danger">*</span></label>
            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" 
                   placeholder="Confirm password" required>
        </div>
    </div>
</div>
@endif

<hr class="my-4">

<div class="row">
    <!-- Role Selection -->
    <div class="col-md-6">
        <div class="mb-3">
            <label for="role" class="form-label">Role <span class="text-danger">*</span></label>
            <select class="form-select select2-single" id="role" name="role" required>
                <option value="">Select Role</option>
                @foreach($roles as $role)
                    <option value="{{ $role->name }}" 
                        {{ old('role', $isEdit && $user->roles->isNotEmpty() ? $user->roles->first()->name : '') == $role->name ? 'selected' : '' }}>
                        {{ ucfirst($role->name) }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- Status -->
    <div class="col-md-6">
        <div class="mb-3">
            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
            <select class="form-select" id="status" name="status" required>
                <option value="active" {{ old('status', $isEdit ? $user->status : 'active') == 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ old('status', $isEdit ? $user->status : '') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                <option value="suspended" {{ old('status', $isEdit ? $user->status : '') == 'suspended' ? 'selected' : '' }}>Suspended</option>
            </select>
        </div>
    </div>
</div>

<!-- Technician-specific fields (shown only when role is technician) -->
<div id="technicianFields" style="display: none;">
    <hr class="my-4">
    <h5 class="mb-3"><i class="fas fa-tools"></i> Technician Information</h5>
    
    <div class="row">
        <div class="col-md-12">
            <div class="mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="hasSupervisor" name="has_supervisor" 
                           {{ old('has_supervisor', $isEdit && $user->supervisor_id ? 'checked' : '') }}>
                    <label class="form-check-label" for="hasSupervisor">
                        Assign to Supervisor (uncheck for independent technician)
                    </label>
                </div>
            </div>
        </div>
    </div>

    <div class="row" id="supervisorField" style="display: none;">
        <div class="col-md-12">
            <div class="mb-3">
                <label for="supervisor_id" class="form-label">Supervisor <span class="text-danger" id="supervisorRequired">*</span></label>
                <select class="form-select select2-single" id="supervisor_id" name="supervisor_id">
                    <option value="">Select Supervisor</option>
                    @foreach($supervisors as $supervisor)
                        <option value="{{ $supervisor->id }}" 
                            {{ old('supervisor_id', $isEdit ? $user->supervisor_id : '') == $supervisor->id ? 'selected' : '' }}>
                            {{ $supervisor->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="mb-3">
                <label for="coverage_states" class="form-label">Coverage States (Work Areas)</label>
                <select class="form-select select2-multiple" id="coverage_states" name="coverage_states[]" multiple>
                    @php
                        $selectedStates = old('coverage_states', $isEdit && $user->coverage_states ? json_decode($user->coverage_states, true) : []);
                    @endphp
                    @foreach($states as $state)
                        <option value="{{ $state }}" 
                            {{ in_array($state, (array)$selectedStates) ? 'selected' : '' }}>
                            {{ $state }}
                        </option>
                    @endforeach
                </select>
                <small class="text-muted">Select states where this technician can work</small>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="mb-3">
                <label for="skill_tags" class="form-label">Skills</label>
                <select class="form-select select2-multiple" id="skill_tags" name="skill_tags[]" multiple>
                    @php
                        $selectedSkills = old('skill_tags', $isEdit && $user->skill_tags ? json_decode($user->skill_tags, true) : []);
                    @endphp
                    @foreach($skillTags as $skill)
                        <option value="{{ $skill }}" 
                            {{ in_array($skill, (array)$selectedSkills) ? 'selected' : '' }}>
                            {{ $skill }}
                        </option>
                    @endforeach
                </select>
                <small class="text-muted">Select technician's skills and expertise</small>
            </div>
        </div>
    </div>
</div>

<!-- Bank Details Section (shown for technicians - for commission payout) -->
<div id="bankDetailsFields" style="display: none;">
    <hr class="my-4">
    <h5 class="mb-3"><i class="fas fa-university"></i> Bank Details (For Commission Payout)</h5>
    
    <div class="row">
        <div class="col-md-4">
            <div class="mb-3">
                <label for="bank_name" class="form-label">Bank Name</label>
                <input type="text" class="form-control" id="bank_name" name="bank_name" 
                       value="{{ old('bank_name', $isEdit ? $user->bank_name : '') }}" 
                       placeholder="e.g., Maybank">
            </div>
        </div>

        <div class="col-md-4">
            <div class="mb-3">
                <label for="bank_account_no" class="form-label">Account Number</label>
                <input type="text" class="form-control" id="bank_account_no" name="bank_account_no" 
                       value="{{ old('bank_account_no', $isEdit ? $user->bank_account_no : '') }}" 
                       placeholder="Enter account number">
            </div>
        </div>

        <div class="col-md-4">
            <div class="mb-3">
                <label for="bank_account_name" class="form-label">Account Name</label>
                <input type="text" class="form-control" id="bank_account_name" name="bank_account_name" 
                       value="{{ old('bank_account_name', $isEdit ? $user->bank_account_name : '') }}" 
                       placeholder="Name as per bank account">
            </div>
        </div>
    </div>
</div>

<hr class="my-4">

<!-- Address -->
<div class="row">
    <div class="col-md-12">
        <div class="mb-3">
            <label for="address" class="form-label">Address</label>
            <textarea class="form-control" id="address" name="address" rows="3" 
                      placeholder="Enter full address">{{ old('address', $isEdit ? $user->address : '') }}</textarea>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    // Avatar preview
    $('#avatar').on('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            // Validate file size (2MB)
            if (file.size > 2097152) {
                alert('File size must be less than 2MB');
                $(this).val('');
                return;
            }

            // Validate file type
            const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            if (!validTypes.includes(file.type)) {
                alert('Please upload a valid image file (JPG, JPEG, PNG, GIF)');
                $(this).val('');
                return;
            }

            // Preview image
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#avatarPreview').replaceWith(
                    '<img src="' + e.target.result + '" id="avatarPreview" class="rounded-circle" style="width: 120px; height: 120px; object-fit: cover;" alt="Avatar">'
                );
            };
            reader.readAsDataURL(file);
        }
    });

    // Remove avatar
    $('#removeAvatar').on('click', function() {
        if (confirm('Are you sure you want to remove the avatar?')) {
            $('#avatarPreview').replaceWith(
                '<div id="avatarPreview" class="bg-secondary text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 120px; height: 120px; font-size: 48px;">?</div>'
            );
            $('#avatar').val('');
            // Add hidden field to indicate avatar removal
            if ($('input[name="remove_avatar"]').length === 0) {
                $('<input>').attr({
                    type: 'hidden',
                    name: 'remove_avatar',
                    value: '1'
                }).appendTo('form');
            }
        }
    });

    // Toggle password visibility
    $('#togglePassword').on('click', function() {
        const passwordField = $('#password');
        const icon = $('#togglePasswordIcon');
        
        if (passwordField.attr('type') === 'password') {
            passwordField.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            passwordField.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });

    // Role-based field visibility
    function toggleRoleFields() {
        const selectedRole = $('#role').val();
        
        if (selectedRole === 'technician') {
            $('#technicianFields').slideDown();
            $('#bankDetailsFields').slideDown();
            toggleSupervisorField();
        } else {
            $('#technicianFields').slideUp();
            $('#bankDetailsFields').slideUp();
            $('#supervisor_id').val('').trigger('change');
            $('#coverage_states').val([]).trigger('change');
            $('#skill_tags').val([]).trigger('change');
        }
    }

    // Supervisor field toggle
    function toggleSupervisorField() {
        if ($('#hasSupervisor').is(':checked')) {
            $('#supervisorField').slideDown();
            $('#supervisorRequired').show();
        } else {
            $('#supervisorField').slideUp();
            $('#supervisor_id').val('').trigger('change');
            $('#supervisorRequired').hide();
        }
    }

    // Event listeners
    $('#role').on('change', toggleRoleFields);
    $('#hasSupervisor').on('change', toggleSupervisorField);

    // Initialize on page load
    toggleRoleFields();

    // Update avatar preview when name changes (for new users without avatar)
    @if(!$isEdit)
    $('#name').on('input', function() {
        const firstLetter = $(this).val().charAt(0).toUpperCase() || '?';
        if ($('#avatarPreview').is('div')) {
            $('#avatarPreview').text(firstLetter);
        }
    });
    @endif
});
</script>
@endpush