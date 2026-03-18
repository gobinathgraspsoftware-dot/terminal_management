@extends('layouts.app')

@section('title', 'Create User - TMS')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="bi bi-person-plus me-2"></i>Create New User</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Users</a></li>
                    <li class="breadcrumb-item active">Create</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to List
        </a>
    </div>

    <div id="validationErrors" class="alert alert-danger d-none">
        <ul class="mb-0" id="errorList"></ul>
    </div>

    <form id="createUserForm" enctype="multipart/form-data">
        @csrf

        {{-- User Information --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0"><i class="bi bi-person me-2"></i>User Information</h6>
            </div>
            <div class="card-body">
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

        {{-- Password --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0"><i class="bi bi-lock me-2"></i>Password</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" name="password" id="password" class="form-control" required minlength="8" placeholder="Minimum 8 characters">
                            <button class="btn btn-outline-secondary toggle-password" type="button" data-target="password"><i class="bi bi-eye"></i></button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Confirm Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required placeholder="Re-enter password">
                            <button class="btn btn-outline-secondary toggle-password" type="button" data-target="password_confirmation"><i class="bi bi-eye"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Role & Status --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0"><i class="bi bi-shield-lock me-2"></i>Role & Status</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Role <span class="text-danger">*</span></label>
                        <select name="role" id="roleSelect" class="form-select select2" required>
                            <option value="">Select a role</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->name }}">{{ ucfirst($role->name) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required>
                            <option value="active" selected>Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="suspended">Suspended</option>
                        </select>
                    </div>
                    <div class="col-md-4 d-none" id="supervisorTypeField">
                        <label class="form-label fw-semibold">Supervisor Type <span class="text-danger">*</span></label>
                        <select name="supervisor_type" id="supervisorTypeSelect" class="form-select">
                            <option value="">Select Type</option>
                            <option value="internal">Internal (Has Technician Team)</option>
                            <option value="external">External (No Technician Team)</option>
                        </select>
                        <div class="form-text">
                            <strong>Internal:</strong> Has team, pricing reflects to technicians.<br>
                            <strong>External:</strong> No team, pricing reflects to self only.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Location & Mileage --}}
        <div class="card border-0 shadow-sm mb-4 d-none" id="locationSection">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0"><i class="bi bi-geo-alt me-2"></i>Location & Mileage Rate</h6>
            </div>
            <div class="card-body">
                <div class="alert alert-info d-none" id="techLocationInfo">
                    <i class="bi bi-info-circle me-1"></i>
                    Technician's location and mileage rate are <strong>auto-filled</strong> from the assigned supervisor.
                </div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">State <span class="text-danger">*</span></label>
                        <select name="state_id" id="stateSelect" class="form-select">
                            <option value="">Select State</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">District / City <span class="text-danger">*</span></label>
                        <select name="city_id" id="citySelect" class="form-select">
                            <option value="">Select City</option>
                        </select>
                        <div class="form-text">Cities will load based on selected state</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Mileage Rate (RM/KM)</label>
                        <div class="input-group">
                            <span class="input-group-text">RM</span>
                            <input type="number" name="mileage_rate" id="mileageRate" class="form-control" step="0.01" min="0" max="99999.99" placeholder="e.g. 0.60">
                            <span class="input-group-text">per KM</span>
                        </div>
                        <div class="form-text" id="mileageHelp">Rate used for mileage claim calculations</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Supervisor Job Pricing (shown only when role = supervisor) --}}
        <div class="card border-0 shadow-sm mb-4 d-none" id="pricingSection">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0"><i class="bi bi-currency-dollar me-2"></i>Job Category Pricing</h6>
            </div>
            <div class="card-body">
                <div class="alert alert-info mb-3">
                    <i class="bi bi-info-circle me-1"></i>
                    <span id="pricingInfoText">Set the pricing for each job category and type.</span>
                </div>
                @foreach($jobCategories as $category)
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="bi bi-tag me-1"></i> {{ $category->category_name }}</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            @foreach($jobTypes as $type)
                            <div class="col-md-4">
                                <label class="form-label">{{ $type->job_title }}</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">RM</span>
                                    <input type="number" name="job_pricing[{{ $category->id }}][{{ $type->id }}]"
                                           class="form-control" step="0.01" min="0" max="999999.99" placeholder="0.00">
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Technician Information --}}
        <div class="card border-0 shadow-sm mb-4 d-none" id="technicianSection">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0"><i class="bi bi-tools me-2"></i>Technician Information</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Supervisor <span class="text-danger">*</span></label>
                    <select name="supervisor_id" id="supervisorSelect" class="form-select">
                        <option value="">Select State & City first to filter supervisors</option>
                    </select>
                    <div class="form-text">
                        <i class="bi bi-info-circle me-1"></i> Only <strong>Internal</strong> supervisors are shown. Select location first.
                    </div>
                </div>
                <div class="d-none" id="inheritedInfoPanel">
                    <div class="alert alert-success mb-3">
                        <h6 class="alert-heading mb-2"><i class="bi bi-arrow-repeat me-1"></i> Inherited from Supervisor</h6>
                        <div class="row">
                            <div class="col-md-4"><small class="text-muted d-block">State</small><strong id="inheritedState">-</strong></div>
                            <div class="col-md-4"><small class="text-muted d-block">City</small><strong id="inheritedCity">-</strong></div>
                            <div class="col-md-4"><small class="text-muted d-block">Mileage Rate</small><strong id="inheritedMileage">-</strong></div>
                        </div>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Coverage States</label>
                        <select name="coverage_states[]" id="coverageStates" class="form-select select2" multiple></select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Skill Tags</label>
                        <select name="skill_tags[]" id="skillTags" class="form-select select2" multiple>
                            @foreach($skillTags as $skill)
                                <option value="{{ $skill }}">{{ $skill }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- Bank Details --}}
        <div class="card border-0 shadow-sm mb-4 d-none" id="bankSection">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0"><i class="bi bi-bank me-2"></i>Bank Details (For Commission Payout)</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Bank Name</label>
                        <input type="text" name="bank_name" class="form-control" placeholder="e.g. Maybank">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Account Number</label>
                        <input type="text" name="bank_account_no" class="form-control" placeholder="Enter account number">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Account Name</label>
                        <input type="text" name="bank_account_name" class="form-control" placeholder="Name as per bank account">
                    </div>
                </div>
            </div>
        </div>

        {{-- Address --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0"><i class="bi bi-house me-2"></i>Address</h6>
            </div>
            <div class="card-body">
                <textarea name="address" class="form-control" rows="3" placeholder="Enter full address"></textarea>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mb-4">
            <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" id="submitBtn" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i> Create User</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    var isFetchingSupervisor = false;

    // Select2 init
    $('#roleSelect').select2({ theme: 'bootstrap-5', placeholder: 'Select a role', width: '100%' });
    $('#coverageStates').select2({ theme: 'bootstrap-5', placeholder: 'Select coverage states', width: '100%' });
    $('#skillTags').select2({ theme: 'bootstrap-5', placeholder: 'Select skills', width: '100%', tags: true });

    $('#stateSelect').select2({
        theme: 'bootstrap-5', placeholder: 'Select State', width: '100%',
        ajax: {
            url: '{{ route("admin.ajax.states") }}', dataType: 'json', delay: 250,
            data: function(params) { return { search: params.term, page: params.page || 1 }; },
            processResults: function(data) { return { results: data.results, pagination: data.pagination }; }, cache: true
        }
    });

    $('#citySelect').select2({
        theme: 'bootstrap-5', placeholder: 'Select City', width: '100%',
        ajax: {
            url: '{{ route("admin.ajax.cities") }}', dataType: 'json', delay: 250,
            data: function(params) { return { search: params.term, page: params.page || 1, state_id: $('#stateSelect').val() }; },
            processResults: function(data) { return { results: data.results, pagination: data.pagination }; }, cache: true
        }
    });

    function initSupervisorSelect2() {
        if ($('#supervisorSelect').hasClass('select2-hidden-accessible')) { $('#supervisorSelect').select2('destroy'); }
        $('#supervisorSelect').select2({
            theme: 'bootstrap-5', placeholder: 'Select Supervisor (Internal only)', width: '100%', allowClear: true,
            ajax: {
                url: '{{ route("admin.ajax.supervisors") }}', dataType: 'json', delay: 250,
                data: function(params) {
                    return { search: params.term, page: params.page || 1, state_id: $('#stateSelect').val(), city_id: $('#citySelect').val(), type: 'internal' };
                },
                processResults: function(data) { return { results: data.results, pagination: data.pagination }; }, cache: true
            }
        });
    }

    // Supervisor selected — inherit location
    $('#supervisorSelect').on('select2:select', function(e) {
        isFetchingSupervisor = true;
        $.ajax({
            url: '{{ route("admin.ajax.supervisor-detail") }}',
            type: 'GET',
            data: { id: e.params.data.id },
            success: function(response) {
                if (response.success) {
                    var sup = response;
                    if (sup.state_id && sup.state_name) {
                        $('#stateSelect').append(new Option(sup.state_name, sup.state_id, true, true)).trigger('change.select2');
                    }
                    if (sup.city_id && sup.city_name) {
                        $('#citySelect').append(new Option(sup.city_name, sup.city_id, true, true)).trigger('change.select2');
                    }
                    if (sup.mileage_rate) { $('#mileageRate').val(sup.mileage_rate); }
                    $('#stateSelect').prop('disabled', true);
                    $('#citySelect').prop('disabled', true);
                    $('#mileageRate').prop('readonly', true);
                    $('#inheritedState').text(sup.state_name || '-');
                    $('#inheritedCity').text(sup.city_name || '-');
                    $('#inheritedMileage').text(sup.mileage_rate ? 'RM ' + parseFloat(sup.mileage_rate).toFixed(2) + ' /KM' : '-');
                    $('#inheritedInfoPanel').removeClass('d-none');
                    $('#techLocationInfo').removeClass('d-none');
                    isFetchingSupervisor = false;
                }
            },
            error: function() { isFetchingSupervisor = false; }
        });
    });

    $('#supervisorSelect').on('select2:clear', function() { clearInheritedInfo(); enableLocationFields(); applyMileageReadonly(); });

    function clearInheritedInfo() { $('#inheritedInfoPanel, #techLocationInfo').addClass('d-none'); $('#inheritedState, #inheritedCity, #inheritedMileage').text('-'); }
    function enableLocationFields() { $('#stateSelect, #citySelect').prop('disabled', false); }
    function applyMileageReadonly() { $('#mileageRate').prop('readonly', $('#roleSelect').val() === 'technician'); }

    $('#stateSelect').on('change', function() {
        if (isFetchingSupervisor) return;
        $('#citySelect').val(null).trigger('change.select2');
        if ($('#roleSelect').val() === 'technician') { $('#supervisorSelect').val(null).trigger('change'); clearInheritedInfo(); enableLocationFields(); applyMileageReadonly(); initSupervisorSelect2(); }
    });

    $('#citySelect').on('change', function() {
        if (isFetchingSupervisor) return;
        if ($('#roleSelect').val() === 'technician') { $('#supervisorSelect').val(null).trigger('change'); clearInheritedInfo(); enableLocationFields(); applyMileageReadonly(); initSupervisorSelect2(); }
    });

    // Avatar preview
    $('#avatarInput').on('change', function() {
        var file = this.files[0];
        if (file) {
            if (file.size > 2 * 1024 * 1024) { showToast('File size must not exceed 2MB', 'error'); this.value = ''; return; }
            var reader = new FileReader();
            reader.onload = function(e) { $('#avatarPreview').html('<img src="' + e.target.result + '" style="width:100%;height:100%;object-fit:cover;">'); };
            reader.readAsDataURL(file);
        }
    });

    // Role change handler
    $('#roleSelect').on('change', function() {
        var role = $(this).val();
        if (role === 'supervisor') {
            $('#supervisorTypeField').removeClass('d-none');
            $('#locationSection, #pricingSection').removeClass('d-none');
            $('#technicianSection, #bankSection').addClass('d-none');
            enableLocationFields(); clearInheritedInfo(); applyMileageReadonly();
            $('#mileageHelp').text('Set the mileage rate for this supervisor and their team');
            updatePricingInfo();
            // Toggle required: supervisor needs location, NOT supervisor_id
            $('#stateSelect, #citySelect').prop('required', true);
            $('#supervisorSelect').prop('required', false);
        } else if (role === 'technician') {
            $('#supervisorTypeField, #pricingSection').addClass('d-none');
            $('#supervisorTypeSelect').val('');
            $('#locationSection, #technicianSection, #bankSection').removeClass('d-none');
            $('#mileageHelp').text('Mileage rate is managed by the supervisor (read-only)');
            enableLocationFields(); applyMileageReadonly(); initSupervisorSelect2();
            // Toggle required: technician needs supervisor + location
            $('#stateSelect, #citySelect').prop('required', true);
            $('#supervisorSelect').prop('required', true);
        } else {
            $('#supervisorTypeField, #pricingSection, #locationSection, #technicianSection, #bankSection').addClass('d-none');
            $('#supervisorTypeSelect').val('');
            enableLocationFields(); clearInheritedInfo();
            $('#stateSelect, #citySelect').val(null).trigger('change');
            $('#mileageRate').val('');
            // Toggle required: admin needs none of these
            $('#stateSelect, #citySelect, #supervisorSelect').prop('required', false);
        }
    });

    $('#supervisorTypeSelect').on('change', function() { updatePricingInfo(); });

    function updatePricingInfo() {
        var type = $('#supervisorTypeSelect').val();
        if (type === 'internal') {
            $('#pricingInfoText').html('<strong>Internal Supervisor:</strong> Pricing will be reflected to all technicians registered under this supervisor.');
        } else if (type === 'external') {
            $('#pricingInfoText').html('<strong>External Supervisor:</strong> Pricing will be reflected to this supervisor only. No technician team.');
        } else {
            $('#pricingInfoText').text('Set the pricing for each job category and type.');
        }
    }

    // Password toggle
    $(document).on('click', '.toggle-password', function() {
        var input = $('#' + $(this).data('target')), icon = $(this).find('i');
        input.attr('type', input.attr('type') === 'password' ? 'text' : 'password');
        icon.toggleClass('bi-eye bi-eye-slash');
    });

    // Form submission
    $('#createUserForm').on('submit', function(e) {
        e.preventDefault();
        $('#stateSelect, #citySelect').prop('disabled', false);
        var formData = new FormData(this);
        var role = $('#roleSelect').val();

        if (role !== 'technician') { formData.delete('supervisor_id'); formData.delete('skill_tags[]'); }
        if (role !== 'supervisor' && role !== 'technician') { formData.delete('state_id'); formData.delete('city_id'); formData.delete('mileage_rate'); }
        if (role !== 'supervisor') {
            formData.delete('supervisor_type');
            var keysToDelete = [];
            for (var pair of formData.entries()) { if (pair[0].startsWith('job_pricing')) keysToDelete.push(pair[0]); }
            keysToDelete.forEach(function(key) { formData.delete(key); });
        }

        $('#submitBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Creating...');
        $('#validationErrors').addClass('d-none');

        $.ajax({
            url: '{{ route("admin.users.store") }}', type: 'POST', data: formData, processData: false, contentType: false,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(response) {
                if (response.success) { showToast(response.message); if (response.redirect) window.location.href = response.redirect; }
                else { showToast(response.message || 'Failed to create user', 'error'); }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    var errorHtml = '';
                    $.each(xhr.responseJSON.errors, function(key, messages) { $.each(messages, function(i, msg) { errorHtml += '<li>' + msg + '</li>'; }); });
                    $('#errorList').html(errorHtml); $('#validationErrors').removeClass('d-none'); $('html, body').animate({ scrollTop: 0 }, 300);
                } else { showToast(xhr.responseJSON?.message || 'An error occurred', 'error'); }
            },
            complete: function() {
                $('#submitBtn').prop('disabled', false).html('<i class="bi bi-check-circle me-1"></i> Create User');
                if ($('#roleSelect').val() === 'technician' && $('#supervisorSelect').val()) { $('#stateSelect, #citySelect').prop('disabled', true); }
            }
        });
    });
});
</script>
@endpush
