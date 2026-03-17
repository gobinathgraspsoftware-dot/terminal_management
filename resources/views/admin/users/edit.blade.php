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

    @php
        $isSupervisor = $user->hasRole('supervisor');
        $isTechnician = $user->hasRole('technician');
        $hasSupervisor = !is_null($user->supervisor_id);
        $showLocation = $isSupervisor || $isTechnician;
        $userSkillTags = $user->skill_tags ? (is_string($user->skill_tags) ? json_decode($user->skill_tags, true) : $user->skill_tags) : [];
    @endphp

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

        {{-- Location & Mileage Section --}}
        <div class="card border-0 shadow-sm mb-4 {{ $showLocation ? '' : 'd-none' }}" id="locationSection">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0"><i class="bi bi-geo-alt me-2"></i>Location & Mileage Rate</h6>
            </div>
            <div class="card-body">
                {{-- Info alert for technician --}}
                <div class="alert alert-info {{ ($isTechnician && $hasSupervisor) ? '' : 'd-none' }}" id="techLocationInfo">
                    <i class="bi bi-info-circle me-1"></i>
                    Location and mileage rate have been <strong>auto-filled</strong> from the assigned supervisor. You may override these values if needed.
                </div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">State <span class="text-danger">*</span></label>
                        <select name="state_id" id="stateSelect" class="form-select" required>
                            @if($user->state)
                                <option value="{{ $user->state_id }}" selected>{{ $user->state->name }}</option>
                            @else
                                <option value="">Select State</option>
                            @endif
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">District / City <span class="text-danger">*</span></label>
                        <select name="city_id" id="citySelect" class="form-select" required>
                            @if($user->city)
                                <option value="{{ $user->city_id }}" selected>{{ $user->city->name }} ({{ $user->city->postcode }})</option>
                            @else
                                <option value="">Select City</option>
                            @endif
                        </select>
                        <div class="form-text">Cities will load based on selected state</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Mileage Rate (RM/KM)</label>
                        <div class="input-group">
                            <span class="input-group-text">RM</span>
                            <input type="number" name="mileage_rate" id="mileageRate" class="form-control"
                                   step="0.01" min="0" max="99999.99"
                                   value="{{ $user->mileage_rate }}"
                                   placeholder="e.g. 0.60">
                            <span class="input-group-text">per KM</span>
                        </div>
                        <div class="form-text" id="mileageHelp">
                            @if($isSupervisor)
                                Set the mileage rate for this supervisor and their team
                            @elseif($isTechnician)
                                Inherited from supervisor (read-only)
                            @else
                                Rate used for mileage claim calculations
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Technician Information Section --}}
        <div class="card border-0 shadow-sm mb-4 {{ $isTechnician ? '' : 'd-none' }}" id="technicianSection">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0"><i class="bi bi-tools me-2"></i>Technician Information</h6>
            </div>
            <div class="card-body">
                {{-- Supervisor Dropdown (always visible, mandatory for technician) --}}
                <div class="mb-3" id="supervisorField">
                    <label class="form-label fw-semibold">Supervisor <span class="text-danger">*</span></label>
                    <select name="supervisor_id" id="supervisorSelect" class="form-select" required>
                        @if($user->supervisor)
                            <option value="{{ $user->supervisor_id }}" selected>{{ $user->supervisor->name }} ({{ $user->supervisor->employee_id }})</option>
                        @else
                            <option value="">Select State & City first to filter supervisors</option>
                        @endif
                    </select>
                    <div class="form-text">
                        <i class="bi bi-info-circle me-1"></i>
                        Supervisors are filtered based on the selected <strong>State</strong> and <strong>City</strong> above. Change location to see different supervisors.
                    </div>
                </div>

                {{-- Inherited Info Display --}}
                @if($isTechnician && $hasSupervisor && $user->supervisor)
                <div id="inheritedInfoPanel">
                    <div class="alert alert-success mb-3">
                        <h6 class="alert-heading mb-2"><i class="bi bi-arrow-repeat me-1"></i> Inherited from Supervisor: {{ $user->supervisor->name }}</h6>
                        <div class="row">
                            <div class="col-md-4">
                                <small class="text-muted d-block">State</small>
                                <strong id="inheritedState">{{ $user->supervisor->state?->name ?? '-' }}</strong>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted d-block">District / City</small>
                                <strong id="inheritedCity">{{ $user->supervisor->city ? $user->supervisor->city->name . ' (' . $user->supervisor->city->postcode . ')' : '-' }}</strong>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted d-block">Mileage Rate</small>
                                <strong id="inheritedMileage">{{ $user->supervisor->mileage_rate ? 'RM ' . number_format($user->supervisor->mileage_rate, 2) . ' /KM' : 'Not set' }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
                @else
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
                @endif

                {{-- Skills --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold">Skills</label>
                    <select name="skill_tags[]" id="skillTags" class="form-select select2" multiple>
                        @foreach($skillTags as $skill)
                            <option value="{{ $skill }}" {{ in_array($skill, $userSkillTags) ? 'selected' : '' }}>
                                {{ $skill }}
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text">Select technician's skills and expertise</div>
                </div>
            </div>
        </div>

        {{-- Bank Details Section --}}
        <div class="card border-0 shadow-sm mb-4 {{ $isTechnician ? '' : 'd-none' }}" id="bankSection">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0"><i class="bi bi-bank me-2"></i>Bank Details (For Commission Payout)</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Bank Name</label>
                        <input type="text" name="bank_name" class="form-control" value="{{ $user->bank_name }}" maxlength="100" placeholder="e.g. Maybank">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Account Number</label>
                        <input type="text" name="bank_account_no" class="form-control" value="{{ $user->bank_account_no }}" maxlength="50" placeholder="Enter account number">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Account Name</label>
                        <input type="text" name="bank_account_name" class="form-control" value="{{ $user->bank_account_name }}" maxlength="255" placeholder="Enter account holder name">
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
                <textarea name="address" class="form-control" rows="3" maxlength="500" placeholder="Enter full address">{{ $user->address }}</textarea>
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

    {{-- Change Password Section --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-warning text-dark">
            <h6 class="mb-0"><i class="bi bi-shield-lock me-2"></i>Change Password</h6>
        </div>
        <div class="card-body">
            <form id="changePasswordForm">
                @csrf
                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label fw-semibold">New Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" id="newPassword" class="form-control" required minlength="8" placeholder="Min 8 characters">
                            <button type="button" class="btn btn-outline-secondary toggle-password" data-target="newPassword">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label fw-semibold">Confirm Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" id="newPasswordConfirmation" class="form-control" required minlength="8" placeholder="Confirm password">
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
            url: '{{ route("admin.ajax.states") }}',
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
        placeholder: '{{ $user->state_id ? "Search and select city..." : "Select state first..." }}',
        allowClear: true,
        ajax: {
            url: '{{ route("admin.ajax.cities") }}',
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
        // Preserve currently selected value
        var currentVal = $('#supervisorSelect').val();
        var currentText = $('#supervisorSelect option:selected').text();

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
                url: '{{ route("admin.ajax.supervisors") }}',
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
            url: '{{ route("admin.ajax.supervisor-detail") }}',
            dataType: 'json',
            data: { id: supervisorId },
            success: function(resp) {
                if (resp.success) {
                    $('#inheritedInfoPanel').removeClass('d-none');
                    $('#inheritedState').text(resp.state_name || '-');
                    $('#inheritedCity').text(resp.city_name || '-');
                    $('#inheritedMileage').text(resp.mileage_rate ? 'RM ' + parseFloat(resp.mileage_rate).toFixed(2) + ' /KM' : 'Not set');

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
    // =============================================
    $('#stateSelect').on('change', function() {
        if (isFetchingSupervisor) return;

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

    $('#removeAvatarBtn').on('click', function() {
        $('#removeAvatarField').val('1');
        $('#avatarPreview').html('<i class="bi bi-person"></i>');
        $('#avatarInput').val('');
        showToast('Avatar will be removed on save', 'info');
    });

    // =============================================
    // Role change handler
    // =============================================
    $('#roleSelect').on('change', function() {
        var role = $(this).val();

        if (role === 'supervisor') {
            $('#locationSection').removeClass('d-none');
            $('#technicianSection').addClass('d-none');
            $('#bankSection').addClass('d-none');
            enableLocationFields();
            clearInheritedInfo();
            $('#mileageHelp').text('Set the mileage rate for this supervisor and their team');
            $('#supervisorSelect').val(null).trigger('change');
            $('#skillTags').val([]).trigger('change');
            applyMileageReadonly();
        } else if (role === 'technician') {
            $('#locationSection').removeClass('d-none');
            $('#technicianSection').removeClass('d-none');
            $('#bankSection').removeClass('d-none');
            $('#mileageHelp').text('Mileage rate is managed by the supervisor (read-only)');
            enableLocationFields();
            applyMileageReadonly();
            // Re-init supervisor dropdown with current state/city filter
            initSupervisorSelect2();
        } else {
            $('#locationSection').addClass('d-none');
            $('#technicianSection').addClass('d-none');
            $('#bankSection').addClass('d-none');
            enableLocationFields();
            clearInheritedInfo();
            $('#supervisorSelect').val(null).trigger('change');
            $('#skillTags').val([]).trigger('change');
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
    // Edit User Form submission
    // =============================================
    $('#editUserForm').on('submit', function(e) {
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

    // =============================================
    // Change Password Form
    // =============================================
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
            type: 'POST',
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

    // =============================================
    // On page load: lock mileage rate if role is technician
    // =============================================
    applyMileageReadonly();
});
</script>
@endpush
