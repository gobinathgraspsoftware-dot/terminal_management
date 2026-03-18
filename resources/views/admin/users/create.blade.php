@extends('layouts.app')

@section('title', 'Create User - TMS')

@php
    // Detect role prefix from current route name (admin.users.create → admin)
    $roleName = explode('.', Route::currentRouteName())[0] ?? 'admin';
@endphp

@section('content')
<div class="container-fluid">
    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="bi bi-person-plus me-2"></i>Create New User</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route($roleName . '.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route($roleName . '.users.index') }}">Users</a></li>
                    <li class="breadcrumb-item active">Create</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route($roleName . '.users.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to List
        </a>
    </div>

    {{-- Validation Errors --}}
    <div id="validationErrors" class="alert alert-danger d-none">
        <ul class="mb-0" id="errorList"></ul>
    </div>

    {{-- Form --}}
    <form id="createUserForm" enctype="multipart/form-data">
        @csrf

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
                        <i class="bi bi-person"></i>
                    </div>
                    <label class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-camera me-1"></i> Upload Avatar
                        <input type="file" name="avatar" id="avatarInput" accept="image/jpg,image/jpeg,image/png,image/gif" hidden>
                    </label>
                    <div class="form-text">Max file size: 2MB. Allowed: JPG, JPEG, PNG, GIF</div>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required minlength="3" maxlength="255" placeholder="Enter full name">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" required maxlength="255" placeholder="Enter email">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Phone Number</label>
                        <input type="text" name="phone" class="form-control" maxlength="20" placeholder="e.g. +60123456789">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Employee ID</label>
                        <input type="text" name="employee_id" class="form-control" maxlength="50" placeholder="Auto-generated if not provided">
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
                                <option value="{{ $role->name }}">{{ ucfirst($role->name) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required>
                            <option value="active" selected>Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="suspended">Suspended</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- Location & Mileage Section (shown for supervisor and technician) --}}
        <div class="card border-0 shadow-sm mb-4 d-none" id="locationSection">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0"><i class="bi bi-geo-alt me-2"></i>Location & Mileage Rate</h6>
            </div>
            <div class="card-body">
                {{-- Info alert for technician --}}
                <div class="alert alert-info d-none" id="techLocationInfo">
                    <i class="bi bi-info-circle me-1"></i>
                    Technician's location and mileage rate are <strong>auto-filled</strong> from the assigned supervisor. You may override these values if needed.
                </div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">State <span class="text-danger">*</span></label>
                        <select name="state_id" id="stateSelect" class="form-select" required>
                            <option value="">Select State</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">District / City <span class="text-danger">*</span></label>
                        <select name="city_id" id="citySelect" class="form-select" required>
                            <option value="">Select City</option>
                        </select>
                        <div class="form-text">Cities will load based on selected state</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Mileage Rate (RM/KM)</label>
                        <div class="input-group">
                            <span class="input-group-text">RM</span>
                            <input type="number" name="mileage_rate" id="mileageRate" class="form-control"
                                   step="0.01" min="0" max="99999.99" placeholder="e.g. 0.60">
                            <span class="input-group-text">per KM</span>
                        </div>
                        <div class="form-text" id="mileageHelp">Rate used for mileage claim calculations</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Technician Information Section (shown only when role = technician) --}}
        <div class="card border-0 shadow-sm mb-4 d-none" id="technicianSection">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0"><i class="bi bi-tools me-2"></i>Technician Information</h6>
            </div>
            <div class="card-body">
                {{-- Supervisor Dropdown (always visible, mandatory for technician) --}}
                <div class="mb-3" id="supervisorField">
                    <label class="form-label fw-semibold">Supervisor <span class="text-danger">*</span></label>
                    <select name="supervisor_id" id="supervisorSelect" class="form-select" required>
                        <option value="">Select State & City first to filter supervisors</option>
                    </select>
                    <div class="form-text">
                        <i class="bi bi-info-circle me-1"></i>
                        Supervisors are filtered based on the selected <strong>State</strong> and <strong>City</strong> above. Please select location first.
                    </div>
                </div>

                {{-- Inherited Info Display --}}
                <div class="d-none" id="inheritedInfoPanel">
                    <div class="alert alert-success mb-3">
                        <h6 class="alert-heading mb-2"><i class="bi bi-arrow-repeat me-1"></i> Inherited from Supervisor</h6>
                        <div class="row">
                            <div class="col-md-4">
                                <small class="text-muted d-block">State</small>
                                <strong id="inheritedState">-</strong>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted d-block">District / City</small>
                                <strong id="inheritedCity">-</strong>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted d-block">Mileage Rate</small>
                                <strong id="inheritedMileage">-</strong>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Skills --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold">Skills</label>
                    <select name="skill_tags[]" id="skillTags" class="form-select select2" multiple>
                        @foreach($skillTags as $skill)
                            <option value="{{ $skill }}">{{ $skill }}</option>
                        @endforeach
                    </select>
                    <div class="form-text">Select technician's skills and expertise</div>
                </div>
            </div>
        </div>

        {{-- Password Section --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0"><i class="bi bi-key me-2"></i>Password</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" name="password" id="password" class="form-control" required minlength="8" placeholder="Min 8 characters">
                            <button type="button" class="btn btn-outline-secondary toggle-password" data-target="password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Confirm Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required minlength="8" placeholder="Re-enter password">
                            <button type="button" class="btn btn-outline-secondary toggle-password" data-target="password_confirmation">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Bank Details Section (shown for technicians) --}}
        <div class="card border-0 shadow-sm mb-4 d-none" id="bankSection">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0"><i class="bi bi-bank me-2"></i>Bank Details (For Commission Payout)</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Bank Name</label>
                        <input type="text" name="bank_name" class="form-control" maxlength="100" placeholder="e.g. Maybank">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Account Number</label>
                        <input type="text" name="bank_account_no" class="form-control" maxlength="50" placeholder="Enter account number">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Account Name</label>
                        <input type="text" name="bank_account_name" class="form-control" maxlength="255" placeholder="Enter account holder name">
                    </div>
                </div>
            </div>
        </div>

        {{-- Address Section --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0"><i class="bi bi-house me-2"></i>Address</h6>
            </div>
            <div class="card-body">
                <textarea name="address" class="form-control" rows="3" maxlength="500" placeholder="Enter full address"></textarea>
            </div>
        </div>

        {{-- Submit Buttons --}}
        <div class="d-flex justify-content-end gap-2 mb-4">
            <a href="{{ route($roleName . '.users.index') }}" class="btn btn-secondary">
                <i class="bi bi-x-circle me-1"></i> Cancel
            </a>
            <button type="submit" class="btn btn-primary" id="submitBtn">
                <i class="bi bi-check-circle me-1"></i> Create User
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {

    // =============================================
    // Global flag: prevents state/city change handlers
    // from resetting supervisor while we auto-fill
    // location from a supervisor selection
    // =============================================
    var isFetchingSupervisor = false;

    // Initialize basic Select2 (non-AJAX)
    $('.select2').not('#stateSelect, #citySelect, #supervisorSelect').select2({
        theme: 'bootstrap-5',
        width: '100%'
    });

    // =============================================
    // State Select2 (AJAX)
    // =============================================
    $('#stateSelect').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Search and select state...',
        allowClear: true,
        ajax: {
            url: '{{ route($roleName . '.ajax.states') }}',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return { search: params.term, page: params.page || 1 };
            },
            processResults: function(data, params) {
                params.page = params.page || 1;
                return { results: data.results, pagination: { more: data.pagination.more } };
            },
            cache: true
        },
        minimumInputLength: 0
    });

    // =============================================
    // City Select2 (AJAX — dependent on state)
    // =============================================
    $('#citySelect').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Select state first...',
        allowClear: true,
        ajax: {
            url: '{{ route($roleName . '.ajax.cities') }}',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return { state_id: $('#stateSelect').val(), search: params.term, page: params.page || 1 };
            },
            processResults: function(data, params) {
                params.page = params.page || 1;
                return { results: data.results, pagination: { more: data.pagination.more } };
            },
            cache: true
        },
        minimumInputLength: 0
    });

    // =============================================
    // Supervisor Select2 (AJAX — filtered by state_id and city_id)
    // =============================================
    function initSupervisorSelect2() {
        // Destroy existing instance if any
        if ($('#supervisorSelect').hasClass('select2-hidden-accessible')) {
            $('#supervisorSelect').select2('destroy');
        }

        $('#supervisorSelect').select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: ($('#stateSelect').val() || $('#citySelect').val())
                ? 'Search and select supervisor...'
                : 'Select State & City first to filter supervisors',
            allowClear: true,
            ajax: {
                url: '{{ route($roleName . '.ajax.supervisors') }}',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        search: params.term,
                        page: params.page || 1,
                        state_id: $('#stateSelect').val(),
                        city_id: $('#citySelect').val()
                    };
                },
                processResults: function(data, params) {
                    params.page = params.page || 1;
                    return { results: data.results, pagination: { more: data.pagination.more } };
                },
                cache: true
            },
            minimumInputLength: 0
        });
    }

    // Initialize supervisor Select2
    initSupervisorSelect2();

    // =============================================
    // Supervisor selection → auto-populate technician location & mileage
    // =============================================
    $('#supervisorSelect').on('select2:select', function(e) {
        var data = e.params.data;
        fetchSupervisorDetail(data.id);
    });

    $('#supervisorSelect').on('select2:clear', function() {
        clearInheritedInfo();
        enableLocationFields();
    });

    function fetchSupervisorDetail(supervisorId) {
        // Set flag BEFORE AJAX so it's ready when change events fire
        isFetchingSupervisor = true;

        $.ajax({
            url: '{{ route($roleName . '.ajax.supervisor-detail') }}',
            dataType: 'json',
            data: { id: supervisorId },
            success: function(resp) {
                if (resp.success) {
                    // Show inherited info panel
                    $('#inheritedInfoPanel').removeClass('d-none');
                    $('#inheritedState').text(resp.state_name || '-');
                    $('#inheritedCity').text(resp.city_name || '-');
                    $('#inheritedMileage').text(resp.mileage_rate ? 'RM ' + parseFloat(resp.mileage_rate).toFixed(2) + ' /KM' : 'Not set');

                    // Update Select2 with supervisor's values
                    if (resp.state_id && resp.state_name) {
                        var stateOption = new Option(resp.state_name, resp.state_id, true, true);
                        $('#stateSelect').empty().append(stateOption).trigger('change');
                    }
                    if (resp.city_id && resp.city_name) {
                        var cityOption = new Option(resp.city_name, resp.city_id, true, true);
                        $('#citySelect').empty().append(cityOption).trigger('change');
                    }
                    if (resp.mileage_rate !== null && resp.mileage_rate !== undefined) {
                        $('#mileageRate').val(parseFloat(resp.mileage_rate).toFixed(2));
                    } else {
                        $('#mileageRate').val('');
                    }

                    // Disable location fields for technician (inherited)
                    disableLocationFields();
                }
            },
            complete: function() {
                // Always clear flag when AJAX completes (success or error)
                isFetchingSupervisor = false;
            }
        });
    }

    function disableLocationFields() {
        $('#techLocationInfo').removeClass('d-none');
        if ($('#roleSelect').val() === 'technician') {
            $('#mileageRate').prop('readonly', true);
        }
    }

    function enableLocationFields() {
        $('#techLocationInfo').addClass('d-none');
        $('#inheritedInfoPanel').addClass('d-none');
        if ($('#roleSelect').val() === 'supervisor') {
            $('#mileageRate').prop('readonly', false);
        }
    }

    function applyMileageReadonly() {
        var role = $('#roleSelect').val();
        if (role === 'technician') {
            $('#mileageRate').prop('readonly', true);
        } else {
            $('#mileageRate').prop('readonly', false);
        }
    }

    function clearInheritedInfo() {
        $('#inheritedInfoPanel').addClass('d-none');
        $('#inheritedState').text('-');
        $('#inheritedCity').text('-');
        $('#inheritedMileage').text('-');
    }

    // =============================================
    // State change → reset City AND re-init Supervisor filter
    // (skipped when isFetchingSupervisor is true)
    // =============================================
    $('#stateSelect').on('change', function() {
        if (isFetchingSupervisor) return;

        // Reset city
        $('#citySelect').val(null).trigger('change');
        if ($(this).val()) {
            $('#citySelect').data('select2').$container.find('.select2-selection__placeholder').text('Search and select city...');
        } else {
            $('#citySelect').data('select2').$container.find('.select2-selection__placeholder').text('Select state first...');
        }

        // For technician role: reset & re-init supervisor when state changes
        if ($('#roleSelect').val() === 'technician') {
            $('#supervisorSelect').val(null).trigger('change');
            clearInheritedInfo();
            enableLocationFields();
            applyMileageReadonly();
            initSupervisorSelect2();
        }
    });

    // City change → re-init Supervisor filter for technician
    // (skipped when isFetchingSupervisor is true)
    $('#citySelect').on('change', function() {
        if (isFetchingSupervisor) return;

        if ($('#roleSelect').val() === 'technician') {
            $('#supervisorSelect').val(null).trigger('change');
            clearInheritedInfo();
            enableLocationFields();
            applyMileageReadonly();
            initSupervisorSelect2();
        }
    });

    // =============================================
    // Avatar preview
    // =============================================
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
        }
    });

    // =============================================
    // Role change handler
    // =============================================
    $('#roleSelect').on('change', function() {
        var role = $(this).val();

        if (role === 'supervisor') {
            // Supervisor: show location section with editable fields, hide technician section
            $('#locationSection').removeClass('d-none');
            $('#technicianSection').addClass('d-none');
            $('#bankSection').addClass('d-none');
            enableLocationFields();
            clearInheritedInfo();
            $('#mileageHelp').text('Set the mileage rate for this supervisor and their team');
            // Reset technician fields
            $('#supervisorSelect').val(null).trigger('change');
            $('#skillTags').val([]).trigger('change');
            applyMileageReadonly();
        } else if (role === 'technician') {
            // Technician: show both location and technician sections
            $('#locationSection').removeClass('d-none');
            $('#technicianSection').removeClass('d-none');
            $('#bankSection').removeClass('d-none');
            $('#mileageHelp').text('Mileage rate is managed by the supervisor (read-only)');
            enableLocationFields();
            applyMileageReadonly();
            // Re-init supervisor dropdown with current state/city filter
            initSupervisorSelect2();
        } else {
            // Admin or other: hide location, technician, bank sections
            $('#locationSection').addClass('d-none');
            $('#technicianSection').addClass('d-none');
            $('#bankSection').addClass('d-none');
            enableLocationFields();
            clearInheritedInfo();
            $('#supervisorSelect').val(null).trigger('change');
            $('#skillTags').val([]).trigger('change');
            // Clear location fields
            $('#stateSelect').val(null).trigger('change');
            $('#citySelect').val(null).trigger('change');
            $('#mileageRate').val('');
        }
    });

    // =============================================
    // Password toggle
    // =============================================
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

    // =============================================
    // Form submission
    // =============================================
    $('#createUserForm').on('submit', function(e) {
        e.preventDefault();

        var formData = new FormData(this);
        var role = $('#roleSelect').val();

        // Remove technician-specific fields for non-technician roles
        if (role !== 'technician') {
            formData.delete('supervisor_id');
            formData.delete('skill_tags[]');
        }

        // Remove location/mileage for admin role
        if (role !== 'supervisor' && role !== 'technician') {
            formData.delete('state_id');
            formData.delete('city_id');
            formData.delete('mileage_rate');
        }

        $('#submitBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Creating...');
        $('#validationErrors').addClass('d-none');

        $.ajax({
            url: '{{ route($roleName . '.users.store') }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(response) {
                if (response.success) {
                    showToast(response.message);
                    if (response.redirect) {
                        window.location.href = response.redirect;
                    }
                } else {
                    showToast(response.message || 'Failed to create user', 'error');
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
                $('#submitBtn').prop('disabled', false).html('<i class="bi bi-check-circle me-1"></i> Create User');
            }
        });
    });
});
</script>
@endpush
