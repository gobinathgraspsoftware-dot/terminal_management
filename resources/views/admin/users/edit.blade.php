@extends('layouts.app')

@section('title', 'Edit User: ' . $user->name . ' - TMS')

@section('content')
<div class="container-fluid">
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
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back to List</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div id="validationErrors" class="alert alert-danger d-none"><ul class="mb-0" id="errorList"></ul></div>

    @php
        $isSupervisor = $user->hasRole('supervisor');
        $isTechnician = $user->hasRole('technician');
        $hasSupervisor = !is_null($user->supervisor_id);
        $showLocation = $isSupervisor || $isTechnician;
        $userSkillTags = $user->skill_tags ? (is_string($user->skill_tags) ? json_decode($user->skill_tags, true) : $user->skill_tags) : [];
    @endphp

    <form id="editUserForm" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        {{-- User Information --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-primary text-white"><h6 class="mb-0"><i class="bi bi-person me-2"></i>User Information</h6></div>
            <div class="card-body">
                <div class="text-center mb-4">
                    <div class="avatar-preview mx-auto mb-2" id="avatarPreview"
                         style="width:100px;height:100px;border-radius:50%;background:#e9ecef;display:flex;align-items:center;justify-content:center;font-size:2.5rem;color:#6c757d;overflow:hidden;">
                        @if($user->avatar)
                            <img src="{{ asset('storage/' . $user->avatar) }}" style="width:100%;height:100%;object-fit:cover;">
                        @else
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        @endif
                    </div>
                    <label class="btn btn-sm btn-outline-primary"><i class="bi bi-camera me-1"></i> Change Avatar<input type="file" name="avatar" id="avatarInput" accept="image/jpg,image/jpeg,image/png,image/gif" hidden></label>
                    @if($user->avatar)
                        <button type="button" class="btn btn-sm btn-outline-danger" id="removeAvatarBtn"><i class="bi bi-trash me-1"></i> Remove</button>
                    @endif
                    <div class="form-text">Max file size: 2MB</div>
                </div>
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label><input type="text" name="name" class="form-control" value="{{ $user->name }}" required></div>
                    <div class="col-md-6"><label class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label><input type="email" name="email" class="form-control" value="{{ $user->email }}" required></div>
                    <div class="col-md-6"><label class="form-label fw-semibold">Phone Number</label><input type="text" name="phone" class="form-control" value="{{ $user->phone }}"></div>
                    <div class="col-md-6"><label class="form-label fw-semibold">Employee ID</label><input type="text" name="employee_id" class="form-control" value="{{ $user->employee_id }}"><div class="form-text">Auto-generated if not provided</div></div>
                </div>
            </div>
        </div>

        {{-- Password (optional) --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-primary text-white"><h6 class="mb-0"><i class="bi bi-lock me-2"></i>Change Password (Optional)</h6></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">New Password</label>
                        <div class="input-group"><input type="password" name="password" id="password" class="form-control" minlength="8" placeholder="Leave blank to keep current"><button class="btn btn-outline-secondary toggle-password" type="button" data-target="password"><i class="bi bi-eye"></i></button></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Confirm New Password</label>
                        <div class="input-group"><input type="password" name="password_confirmation" id="password_confirmation" class="form-control" placeholder="Re-enter new password"><button class="btn btn-outline-secondary toggle-password" type="button" data-target="password_confirmation"><i class="bi bi-eye"></i></button></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Role & Status --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-primary text-white"><h6 class="mb-0"><i class="bi bi-shield-lock me-2"></i>Role & Status</h6></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Role <span class="text-danger">*</span></label>
                        <select name="role" id="roleSelect" class="form-select select2" required>
                            <option value="">Select a role</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->name }}" {{ $user->hasRole($role->name) ? 'selected' : '' }}>{{ ucfirst($role->name) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required>
                            <option value="active" {{ $user->status === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ $user->status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            <option value="suspended" {{ $user->status === 'suspended' ? 'selected' : '' }}>Suspended</option>
                        </select>
                    </div>
                    <div class="col-md-4 {{ $isSupervisor ? '' : 'd-none' }}" id="supervisorTypeField">
                        <label class="form-label fw-semibold">Supervisor Type <span class="text-danger">*</span></label>
                        <select name="supervisor_type" id="supervisorTypeSelect" class="form-select">
                            <option value="">Select Type</option>
                            <option value="internal" {{ $user->supervisor_type === 'internal' ? 'selected' : '' }}>Internal (Has Technician Team)</option>
                            <option value="external" {{ $user->supervisor_type === 'external' ? 'selected' : '' }}>External (No Technician Team)</option>
                        </select>
                        <div class="form-text"><strong>Internal:</strong> Has team, pricing reflects to technicians.<br><strong>External:</strong> No team, pricing reflects to self.</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Location & Mileage --}}
        <div class="card border-0 shadow-sm mb-4 {{ $showLocation ? '' : 'd-none' }}" id="locationSection">
            <div class="card-header bg-primary text-white"><h6 class="mb-0"><i class="bi bi-geo-alt me-2"></i>Location & Mileage Rate</h6></div>
            <div class="card-body">
                <div class="alert alert-info {{ ($isTechnician && $hasSupervisor) ? '' : 'd-none' }}" id="techLocationInfo">
                    <i class="bi bi-info-circle me-1"></i> Location and mileage rate have been <strong>auto-filled</strong> from the assigned supervisor.
                </div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">State <span class="text-danger">*</span></label>
                        <select name="state_id" id="stateSelect" class="form-select">
                            @if($user->state)<option value="{{ $user->state_id }}" selected>{{ $user->state->name }}</option>@else<option value="">Select State</option>@endif
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">District / City <span class="text-danger">*</span></label>
                        <select name="city_id" id="citySelect" class="form-select">
                            @if($user->city)<option value="{{ $user->city_id }}" selected>{{ $user->city->name }} ({{ $user->city->postcode }})</option>@else<option value="">Select City</option>@endif
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Mileage Rate (RM/KM)</label>
                        <div class="input-group"><span class="input-group-text">RM</span><input type="number" name="mileage_rate" id="mileageRate" class="form-control" step="0.01" min="0" max="99999.99" value="{{ $user->mileage_rate }}" placeholder="e.g. 0.60"><span class="input-group-text">per KM</span></div>
                        <div class="form-text" id="mileageHelp">@if($isSupervisor) Set the mileage rate for this supervisor and their team @elseif($isTechnician) Inherited from supervisor (read-only) @else Rate used for mileage claim calculations @endif</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Supervisor Job Pricing --}}
        <div class="card border-0 shadow-sm mb-4 {{ $isSupervisor ? '' : 'd-none' }}" id="pricingSection">
            <div class="card-header bg-primary text-white"><h6 class="mb-0"><i class="bi bi-currency-dollar me-2"></i>Job Category Pricing</h6></div>
            <div class="card-body">
                <div class="alert alert-info mb-3">
                    <i class="bi bi-info-circle me-1"></i>
                    <span id="pricingInfoText">
                        @if($user->supervisor_type === 'internal')
                            <strong>Internal Supervisor:</strong> Pricing will be reflected to all technicians under this supervisor.
                        @elseif($user->supervisor_type === 'external')
                            <strong>External Supervisor:</strong> Pricing will be reflected to this supervisor only.
                        @else
                            Set the pricing for each job category and type.
                        @endif
                    </span>
                </div>
                @foreach($jobCategories as $category)
                <div class="card mb-3">
                    <div class="card-header bg-light"><h6 class="mb-0"><i class="bi bi-tag me-1"></i> {{ $category->category_name }}</h6></div>
                    <div class="card-body">
                        <div class="row g-3">
                            @foreach($jobTypes as $type)
                            <div class="col-md-4">
                                <label class="form-label">{{ $type->job_title }}</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">RM</span>
                                    <input type="number" name="job_pricing[{{ $category->id }}][{{ $type->id }}]"
                                           class="form-control" step="0.01" min="0" max="999999.99"
                                           value="{{ $pricingMap[$category->id][$type->id] ?? '' }}"
                                           placeholder="0.00">
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
        <div class="card border-0 shadow-sm mb-4 {{ $isTechnician ? '' : 'd-none' }}" id="technicianSection">
            <div class="card-header bg-primary text-white"><h6 class="mb-0"><i class="bi bi-tools me-2"></i>Technician Information</h6></div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Supervisor <span class="text-danger">*</span></label>
                    <select name="supervisor_id" id="supervisorSelect" class="form-select">
                        @if($user->supervisor)
                            <option value="{{ $user->supervisor_id }}" selected>{{ $user->supervisor->name }} ({{ $user->supervisor->employee_id }})</option>
                        @else
                            <option value="">Select Supervisor</option>
                        @endif
                    </select>
                    <div class="form-text"><i class="bi bi-info-circle me-1"></i> Only <strong>Internal</strong> supervisors are shown.</div>
                </div>
                <div class="{{ $hasSupervisor ? '' : 'd-none' }}" id="inheritedInfoPanel">
                    <div class="alert alert-success mb-3">
                        <h6 class="alert-heading mb-2"><i class="bi bi-arrow-repeat me-1"></i> Inherited from Supervisor</h6>
                        <div class="row">
                            <div class="col-md-4"><small class="text-muted d-block">State</small><strong id="inheritedState">{{ $user->supervisor?->state?->name ?? '-' }}</strong></div>
                            <div class="col-md-4"><small class="text-muted d-block">City</small><strong id="inheritedCity">{{ $user->supervisor?->city?->name ?? '-' }}</strong></div>
                            <div class="col-md-4"><small class="text-muted d-block">Mileage Rate</small><strong id="inheritedMileage">{{ $user->supervisor?->mileage_rate ? 'RM ' . number_format($user->supervisor->mileage_rate, 2) . ' /KM' : '-' }}</strong></div>
                        </div>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Coverage States</label>
                        <select name="coverage_states[]" id="coverageStates" class="form-select select2" multiple>
                            @php $selectedStates = is_array($user->coverage_states) ? $user->coverage_states : []; @endphp
                            @foreach($selectedStates as $st)<option value="{{ $st }}" selected>{{ $st }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Skill Tags</label>
                        <select name="skill_tags[]" id="skillTags" class="form-select select2" multiple>
                            @foreach($skillTags as $skill)
                                <option value="{{ $skill }}" {{ in_array($skill, $userSkillTags) ? 'selected' : '' }}>{{ $skill }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- Bank Details --}}
        <div class="card border-0 shadow-sm mb-4 {{ $isTechnician ? '' : 'd-none' }}" id="bankSection">
            <div class="card-header bg-primary text-white"><h6 class="mb-0"><i class="bi bi-bank me-2"></i>Bank Details</h6></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4"><label class="form-label fw-semibold">Bank Name</label><input type="text" name="bank_name" class="form-control" value="{{ $user->bank_name }}"></div>
                    <div class="col-md-4"><label class="form-label fw-semibold">Account Number</label><input type="text" name="bank_account_no" class="form-control" value="{{ $user->bank_account_no }}"></div>
                    <div class="col-md-4"><label class="form-label fw-semibold">Account Name</label><input type="text" name="bank_account_name" class="form-control" value="{{ $user->bank_account_name }}"></div>
                </div>
            </div>
        </div>

        {{-- Address --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-primary text-white"><h6 class="mb-0"><i class="bi bi-house me-2"></i>Address</h6></div>
            <div class="card-body"><textarea name="address" class="form-control" rows="3">{{ $user->address }}</textarea></div>
        </div>

        <div class="d-flex justify-content-end gap-2 mb-4">
            <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" id="submitBtn" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i> Update User</button>
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
    $('#coverageStates').select2({ theme: 'bootstrap-5', placeholder: 'Select coverage states', width: '100%', tags: true });
    $('#skillTags').select2({ theme: 'bootstrap-5', placeholder: 'Select skills', width: '100%', tags: true });

    $('#stateSelect').select2({
        theme: 'bootstrap-5', placeholder: 'Select State', width: '100%',
        ajax: { url: '{{ route("admin.ajax.states") }}', dataType: 'json', delay: 250, data: function(params) { return { search: params.term, page: params.page || 1 }; }, processResults: function(data) { return { results: data.results, pagination: data.pagination }; }, cache: true }
    });

    $('#citySelect').select2({
        theme: 'bootstrap-5', placeholder: 'Select City', width: '100%',
        ajax: { url: '{{ route("admin.ajax.cities") }}', dataType: 'json', delay: 250, data: function(params) { return { search: params.term, page: params.page || 1, state_id: $('#stateSelect').val() }; }, processResults: function(data) { return { results: data.results, pagination: data.pagination }; }, cache: true }
    });

    function initSupervisorSelect2() {
        if ($('#supervisorSelect').hasClass('select2-hidden-accessible')) { $('#supervisorSelect').select2('destroy'); }
        $('#supervisorSelect').select2({
            theme: 'bootstrap-5', placeholder: 'Select Supervisor (Internal only)', width: '100%', allowClear: true,
            ajax: { url: '{{ route("admin.ajax.supervisors") }}', dataType: 'json', delay: 250, data: function(params) { return { search: params.term, page: params.page || 1, state_id: $('#stateSelect').val(), city_id: $('#citySelect').val(), type: 'internal' }; }, processResults: function(data) { return { results: data.results, pagination: data.pagination }; }, cache: true }
        });
    }

    // If editing a technician, init supervisor select2
    @if($isTechnician)
        initSupervisorSelect2();
        $('#supervisorSelect').prop('required', true);
        $('#stateSelect, #citySelect').prop('required', true);
        @if($hasSupervisor)
            $('#stateSelect').prop('disabled', true);
            $('#citySelect').prop('disabled', true);
            $('#mileageRate').prop('readonly', true);
        @endif
    @elseif($isSupervisor)
        $('#stateSelect, #citySelect').prop('required', true);
        $('#supervisorSelect').prop('required', false);
    @endif

    // Supervisor selected — inherit
    $('#supervisorSelect').on('select2:select', function(e) {
        isFetchingSupervisor = true;
        $.ajax({
            url: '{{ route("admin.ajax.supervisor-detail") }}',
            type: 'GET',
            data: { id: e.params.data.id },
            success: function(response) {
                if (response.success) {
                    var sup = response;
                    if (sup.state_id && sup.state_name) { $('#stateSelect').append(new Option(sup.state_name, sup.state_id, true, true)).trigger('change.select2'); }
                    if (sup.city_id && sup.city_name) { $('#citySelect').append(new Option(sup.city_name, sup.city_id, true, true)).trigger('change.select2'); }
                    if (sup.mileage_rate) { $('#mileageRate').val(sup.mileage_rate); }
                    $('#stateSelect, #citySelect').prop('disabled', true);
                    $('#mileageRate').prop('readonly', true);
                    $('#inheritedState').text(sup.state_name || '-');
                    $('#inheritedCity').text(sup.city_name || '-');
                    $('#inheritedMileage').text(sup.mileage_rate ? 'RM ' + parseFloat(sup.mileage_rate).toFixed(2) + ' /KM' : '-');
                    $('#inheritedInfoPanel, #techLocationInfo').removeClass('d-none');
                    isFetchingSupervisor = false;
                }
            },
            error: function() { isFetchingSupervisor = false; }
        });
    });

    $('#supervisorSelect').on('select2:clear', function() { clearInheritedInfo(); enableLocationFields(); applyMileageReadonly(); });

    function clearInheritedInfo() { $('#inheritedInfoPanel, #techLocationInfo').addClass('d-none'); }
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

    // Avatar
    $('#avatarInput').on('change', function() {
        var file = this.files[0];
        if (file) {
            if (file.size > 2 * 1024 * 1024) { showToast('File size must not exceed 2MB', 'error'); this.value = ''; return; }
            var reader = new FileReader();
            reader.onload = function(e) { $('#avatarPreview').html('<img src="' + e.target.result + '" style="width:100%;height:100%;object-fit:cover;">'); };
            reader.readAsDataURL(file);
        }
    });
    $('#removeAvatarBtn').on('click', function() {
        $('#avatarPreview').html('<i class="bi bi-person" style="font-size:2.5rem;"></i>');
        if ($('input[name="remove_avatar"]').length === 0) { $('<input>').attr({ type: 'hidden', name: 'remove_avatar', value: '1' }).appendTo('#editUserForm'); }
    });

    // Role change
    $('#roleSelect').on('change', function() {
        var role = $(this).val();
        if (role === 'supervisor') {
            $('#supervisorTypeField, #locationSection, #pricingSection').removeClass('d-none');
            $('#technicianSection, #bankSection').addClass('d-none');
            enableLocationFields(); clearInheritedInfo(); applyMileageReadonly(); updatePricingInfo();
            $('#stateSelect, #citySelect').prop('required', true);
            $('#supervisorSelect').prop('required', false);
        } else if (role === 'technician') {
            $('#supervisorTypeField, #pricingSection').addClass('d-none');
            $('#supervisorTypeSelect').val('');
            $('#locationSection, #technicianSection, #bankSection').removeClass('d-none');
            enableLocationFields(); applyMileageReadonly(); initSupervisorSelect2();
            $('#stateSelect, #citySelect').prop('required', true);
            $('#supervisorSelect').prop('required', true);
        } else {
            $('#supervisorTypeField, #pricingSection, #locationSection, #technicianSection, #bankSection').addClass('d-none');
            $('#supervisorTypeSelect').val('');
            enableLocationFields(); clearInheritedInfo();
            $('#stateSelect, #citySelect, #supervisorSelect').prop('required', false);
        }
    });

    $('#supervisorTypeSelect').on('change', function() { updatePricingInfo(); });

    function updatePricingInfo() {
        var type = $('#supervisorTypeSelect').val();
        if (type === 'internal') { $('#pricingInfoText').html('<strong>Internal Supervisor:</strong> Pricing reflects to all technicians under this supervisor.'); }
        else if (type === 'external') { $('#pricingInfoText').html('<strong>External Supervisor:</strong> Pricing reflects to this supervisor only.'); }
        else { $('#pricingInfoText').text('Set the pricing for each job category and type.'); }
    }

    // Password toggle
    $(document).on('click', '.toggle-password', function() {
        var input = $('#' + $(this).data('target')), icon = $(this).find('i');
        input.attr('type', input.attr('type') === 'password' ? 'text' : 'password');
        icon.toggleClass('bi-eye bi-eye-slash');
    });

    // Form submission
    $('#editUserForm').on('submit', function(e) {
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

        $('#submitBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Updating...');
        $('#validationErrors').addClass('d-none');

        $.ajax({
            url: '{{ route("admin.users.update", $user->id) }}', type: 'POST', data: formData, processData: false, contentType: false,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(response) {
                if (response.success) { showToast(response.message); if (response.redirect) window.location.href = response.redirect; }
                else { showToast(response.message || 'Failed to update user', 'error'); }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    var errorHtml = '';
                    $.each(xhr.responseJSON.errors, function(key, messages) { $.each(messages, function(i, msg) { errorHtml += '<li>' + msg + '</li>'; }); });
                    $('#errorList').html(errorHtml); $('#validationErrors').removeClass('d-none'); $('html, body').animate({ scrollTop: 0 }, 300);
                } else { showToast(xhr.responseJSON?.message || 'An error occurred', 'error'); }
            },
            complete: function() {
                $('#submitBtn').prop('disabled', false).html('<i class="bi bi-check-circle me-1"></i> Update User');
                if ($('#roleSelect').val() === 'technician' && $('#supervisorSelect').val()) { $('#stateSelect, #citySelect').prop('disabled', true); }
            }
        });
    });
});
</script>
@endpush
