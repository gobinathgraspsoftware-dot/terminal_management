@extends('layouts.app')

@section('title', 'Create User - TMS')

@section('content')
<div class="container-fluid">
    {{-- Page Header --}}
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
                    {{-- State & City — ALL roles --}}
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">State <span class="text-danger">*</span></label>
                        <select name="state_id" id="stateSelect" class="form-select" required>
                            <option value="">Select State</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">City <span class="text-danger">*</span></label>
                        <select name="city_id" id="citySelect" class="form-select" required>
                            <option value="">Select City</option>
                        </select>
                        <div class="form-text">Cities will load based on selected state</div>
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

        {{-- Technician Information Section (shown only when role = technician) --}}
        <div class="card border-0 shadow-sm mb-4 d-none" id="technicianSection">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0"><i class="bi bi-tools me-2"></i>Technician Information</h6>
            </div>
            <div class="card-body">
                {{-- Supervisor Toggle --}}
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="hasSupervisorToggle" name="has_supervisor" value="1" checked>
                        <label class="form-check-label fw-semibold" for="hasSupervisorToggle">
                            Assign to Supervisor (uncheck for independent technician)
                        </label>
                    </div>
                </div>

                {{-- Supervisor Dropdown (AJAX filtered by state) --}}
                <div class="mb-3" id="supervisorField">
                    <label class="form-label fw-semibold">Supervisor <span class="text-danger" id="supervisorRequired">*</span></label>
                    <select name="supervisor_id" id="supervisorSelect" class="form-select">
                        <option value="">Select Supervisor</option>
                    </select>
                    <div class="form-text">Supervisors are filtered by selected state</div>
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
                <h6 class="mb-0"><i class="bi bi-geo-alt me-2"></i>Address</h6>
            </div>
            <div class="card-body">
                <textarea name="address" class="form-control" rows="3" maxlength="500" placeholder="Enter full address"></textarea>
            </div>
        </div>

        {{-- Submit Buttons --}}
        <div class="d-flex justify-content-end gap-2 mb-4">
            <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">
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

    // Initialize basic Select2 (non-AJAX)
    $('.select2').not('#stateSelect, #citySelect, #supervisorSelect').select2({
        theme: 'bootstrap-5',
        width: '100%'
    });

    // =============================================
    // State Select2 (AJAX) — ALL roles
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
    // City Select2 (AJAX — dependent on state) — ALL roles
    // =============================================
    $('#citySelect').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Select state first...',
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
    // Supervisor Select2 (AJAX — technician only, filtered by state)
    // =============================================
    $('#supervisorSelect').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Search and select supervisor...',
        allowClear: true,
        ajax: {
            url: '{{ route("admin.ajax.supervisors") }}',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return { state_id: $('#stateSelect').val(), city_id: $('#citySelect').val(), search: params.term, page: params.page || 1 };
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
    // Helper: Load supervisors filtered by current state/city
    // =============================================
    function loadFilteredSupervisors() {
        var stateId = $('#stateSelect').val();
        if (!stateId) return;
        $('#supervisorSelect').val(null).trigger('change');
        $.ajax({
            url: '{{ route("admin.ajax.supervisors") }}',
            dataType: 'json',
            data: { state_id: stateId, city_id: $('#citySelect').val(), search: '', page: 1 },
            success: function(data) {
                $('#supervisorSelect').empty().append('<option value="">Select Supervisor</option>');
                if (data.results && data.results.length > 0) {
                    $.each(data.results, function(i, item) {
                        $('#supervisorSelect').append($('<option>', { value: item.id, text: item.text }));
                    });
                }
                $('#supervisorSelect').trigger('change');
            }
        });
    }

    // =============================================
    // State change → reset City & reload Supervisor if technician
    // =============================================
    $('#stateSelect').on('change', function() {
        $('#citySelect').val(null).trigger('change');
        if ($(this).val()) {
            $('#citySelect').data('select2').$container.find('.select2-selection__placeholder').text('Search and select city...');
        } else {
            $('#citySelect').data('select2').$container.find('.select2-selection__placeholder').text('Select state first...');
        }
        if ($('#roleSelect').val() === 'technician' && $('#hasSupervisorToggle').is(':checked')) {
            loadFilteredSupervisors();
        }
    });

    $('#citySelect').on('change', function() {
        if ($('#roleSelect').val() === 'technician' && $('#hasSupervisorToggle').is(':checked')) {
            $('#supervisorSelect').val(null).trigger('change');
            loadFilteredSupervisors();
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
    // Role change handler — state/city stays, only technician section toggles
    // =============================================
    $('#roleSelect').on('change', function() {
        var role = $(this).val();
        if (role === 'technician') {
            $('#technicianSection').removeClass('d-none');
            $('#bankSection').removeClass('d-none');
            if ($('#hasSupervisorToggle').is(':checked')) {
                loadFilteredSupervisors();
            }
        } else {
            $('#technicianSection').addClass('d-none');
            $('#bankSection').addClass('d-none');
            $('#hasSupervisorToggle').prop('checked', true);
            $('#supervisorSelect').val(null).trigger('change');
            $('#skillTags').val([]).trigger('change');
        }
    });

    // =============================================
    // Supervisor toggle handler
    // =============================================
    $('#hasSupervisorToggle').on('change', function() {
        if ($(this).is(':checked')) {
            $('#supervisorField').slideDown();
            $('#supervisorRequired').show();
            loadFilteredSupervisors();
        } else {
            $('#supervisorField').slideUp();
            $('#supervisorRequired').hide();
            $('#supervisorSelect').val(null).trigger('change');
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
    // Form submission — state_id & city_id ALWAYS sent for all roles
    // =============================================
    $('#createUserForm').on('submit', function(e) {
        e.preventDefault();

        var formData = new FormData(this);

        if (!$('#hasSupervisorToggle').is(':checked')) {
            formData.set('has_supervisor', '0');
        }

        // Remove ONLY technician-specific fields for non-technician roles
        // state_id and city_id are NEVER removed — they apply to ALL roles
        if ($('#roleSelect').val() !== 'technician') {
            formData.delete('has_supervisor');
            formData.delete('supervisor_id');
            formData.delete('skill_tags[]');
        }

        $('#submitBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Creating...');
        $('#validationErrors').addClass('d-none');

        $.ajax({
            url: '{{ route("admin.users.store") }}',
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
