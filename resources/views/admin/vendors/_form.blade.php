{{-- Vendor Form Partial - Shared between Create and Edit --}}
@php
    $isEdit = isset($vendor) && $vendor->exists;
    $vendorTypes = $vendorTypes ?? [];
    $roleName = auth()->user()->roles->first()?->name ?? 'admin';
    $ajaxStatesUrl = route($roleName . '.ajax.states');
    $ajaxCitiesUrl = route($roleName . '.ajax.cities');
@endphp

<form id="vendorForm" method="POST"
    action="{{ $isEdit ? route('admin.vendors.update', $vendor->id) : route('admin.vendors.store') }}">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    {{-- Basic Information --}}
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0"><i class="bi bi-info-circle me-2"></i>Basic Information</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label for="vendor_code" class="form-label">Vendor Code</label>
                    <input type="text" class="form-control" id="vendor_code" name="vendor_code"
                        value="{{ $isEdit ? $vendor->vendor_code : ($nextCode ?? '') }}" readonly>
                </div>
                <div class="col-md-5">
                    <label for="vendor_name" class="form-label">Vendor Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="vendor_name" name="vendor_name"
                        value="{{ $isEdit ? $vendor->vendor_name : old('vendor_name') }}" required>
                </div>
                <div class="col-md-4">
                    <label for="vendor_type" class="form-label">Vendor Type <span class="text-danger">*</span></label>
                    <select class="form-select" id="vendor_type" name="vendor_type" required>
                        <option value="">Select Type</option>
                        @foreach($vendorTypes as $value => $label)
                            <option value="{{ $value }}"
                                {{ ($isEdit ? $vendor->vendor_type : old('vendor_type')) == $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="company_name" class="form-label">Company Name</label>
                    <input type="text" class="form-control" id="company_name" name="company_name"
                        value="{{ $isEdit ? $vendor->company_name : old('company_name') }}">
                </div>
                <div class="col-md-4">
                    <label for="registration_no" class="form-label">Registration No</label>
                    <input type="text" class="form-control" id="registration_no" name="registration_no"
                        value="{{ $isEdit ? $vendor->registration_no : old('registration_no') }}">
                </div>
                <div class="col-md-4">
                    <label for="tax_id" class="form-label">Tax ID</label>
                    <input type="text" class="form-control" id="tax_id" name="tax_id"
                        value="{{ $isEdit ? $vendor->tax_id : old('tax_id') }}">
                </div>
            </div>
        </div>
    </div>

    {{-- Contact Information (PIC) --}}
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0"><i class="bi bi-person me-2"></i>Person In Charge (PIC)</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="pic_name" class="form-label">PIC Name</label>
                    <input type="text" class="form-control" id="pic_name" name="pic_name"
                        value="{{ $isEdit ? $vendor->pic_name : old('pic_name') }}">
                </div>
                <div class="col-md-4">
                    <label for="pic_email" class="form-label">PIC Email</label>
                    <input type="email" class="form-control" id="pic_email" name="pic_email"
                        value="{{ $isEdit ? $vendor->pic_email : old('pic_email') }}">
                </div>
                <div class="col-md-4">
                    <label for="pic_phone" class="form-label">PIC Phone</label>
                    <input type="text" class="form-control" id="pic_phone" name="pic_phone"
                        value="{{ $isEdit ? $vendor->pic_phone : old('pic_phone') }}">
                </div>
            </div>
        </div>
    </div>

    {{-- Branches Section --}}
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0"><i class="bi bi-building me-2"></i>Branches</h5>
            <button type="button" class="btn btn-sm btn-success" id="addBranchBtn">
                <i class="bi bi-plus-circle me-1"></i> Add Branch
            </button>
        </div>
        <div class="card-body">
            <div id="branchesContainer">
                {{-- Branch rows will be rendered here by JS --}}
            </div>
            <div id="noBranchesAlert" class="alert alert-warning d-none">
                <i class="bi bi-exclamation-triangle me-2"></i>
                At least one branch is required. Click "Add Branch" to add one.
            </div>
        </div>
    </div>

    {{-- Bank Details --}}
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0"><i class="bi bi-bank me-2"></i>Bank Details</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="bank_name" class="form-label">Bank Name</label>
                    <input type="text" class="form-control" id="bank_name" name="bank_name"
                        value="{{ $isEdit ? $vendor->bank_name : old('bank_name') }}">
                </div>
                <div class="col-md-4">
                    <label for="bank_account_no" class="form-label">Account Number</label>
                    <input type="text" class="form-control" id="bank_account_no" name="bank_account_no"
                        value="{{ $isEdit ? $vendor->bank_account_no : old('bank_account_no') }}">
                </div>
                <div class="col-md-4">
                    <label for="bank_account_name" class="form-label">Account Name</label>
                    <input type="text" class="form-control" id="bank_account_name" name="bank_account_name"
                        value="{{ $isEdit ? $vendor->bank_account_name : old('bank_account_name') }}">
                </div>
            </div>
            <small class="text-muted mt-2 d-block">
                <i class="bi bi-info-circle"></i> If providing bank details, all three fields are required.
            </small>
        </div>
    </div>

    {{-- Payment & Status --}}
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0"><i class="bi bi-gear me-2"></i>Payment & Status</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="payment_terms" class="form-label">Payment Terms (days) <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="payment_terms" name="payment_terms"
                        value="{{ $isEdit ? $vendor->payment_terms : (old('payment_terms') ?? 30) }}"
                        min="0" max="365" required>
                </div>
                <div class="col-md-4">
                    <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                    <select class="form-select" id="status" name="status" required>
                        <option value="active"
                            {{ ($isEdit ? $vendor->status : (old('status') ?? 'active')) == 'active' ? 'selected' : '' }}>
                            Active
                        </option>
                        <option value="inactive"
                            {{ ($isEdit ? $vendor->status : old('status')) == 'inactive' ? 'selected' : '' }}>
                            Inactive
                        </option>
                    </select>
                </div>
            </div>
            <div class="row g-3 mt-1">
                <div class="col-12">
                    <label for="notes" class="form-label">Notes</label>
                    <textarea class="form-control" id="notes" name="notes" rows="3">{{ $isEdit ? $vendor->notes : old('notes') }}</textarea>
                </div>
            </div>
        </div>
    </div>

    {{-- Form Actions --}}
    <div class="d-flex justify-content-end gap-2 mb-4">
        <a href="{{ route('admin.vendors.index') }}" class="btn btn-secondary">
            <i class="bi bi-x-circle me-1"></i> Cancel
        </a>
        <button type="submit" class="btn btn-primary" id="submitBtn">
            <i class="bi bi-check-circle me-1"></i> {{ $isEdit ? 'Update Vendor' : 'Create Vendor' }}
        </button>
    </div>
</form>

{{-- Branch Row Template (hidden, cloned by JS) --}}
<template id="branchRowTemplate">
    <div class="branch-row card card-body mb-3 border" data-branch-index="__INDEX__">
        <input type="hidden" name="branches[__INDEX__][id]" value="">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0">
                <i class="bi bi-geo-alt me-1"></i> Branch #<span class="branch-number">__NUMBER__</span>
            </h6>
            <div class="d-flex align-items-center gap-2">
                <div class="form-check form-switch">
                    <input class="form-check-input primary-switch" type="checkbox"
                        name="branches[__INDEX__][is_primary]" value="1" id="primary___INDEX__">
                    <label class="form-check-label" for="primary___INDEX__">Primary</label>
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger remove-branch-btn" title="Remove Branch">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Branch Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="branches[__INDEX__][branch_name]" required>
            </div>
            <div class="col-md-8">
                <label class="form-label">Address</label>
                <input type="text" class="form-control" name="branches[__INDEX__][address]">
            </div>
            <div class="col-md-3">
                <label class="form-label">State</label>
                <select class="form-select branch-state-select" name="branches[__INDEX__][state_id]"
                    data-index="__INDEX__">
                    <option value="">Select State</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">City</label>
                <select class="form-select branch-city-select" name="branches[__INDEX__][city_id]"
                    data-index="__INDEX__">
                    <option value="">Select City</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Postcode</label>
                <input type="text" class="form-control branch-postcode"
                    name="branches[__INDEX__][postcode]" readonly>
            </div>
            <div class="col-md-2">
                <label class="form-label">Country</label>
                <input type="text" class="form-control" value="Malaysia" readonly>
                <input type="hidden" name="branches[__INDEX__][country]" value="Malaysia">
            </div>
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select class="form-select" name="branches[__INDEX__][status]">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Contact Person</label>
                <input type="text" class="form-control" name="branches[__INDEX__][contact_person]">
            </div>
            <div class="col-md-4">
                <label class="form-label">Contact Email</label>
                <input type="email" class="form-control" name="branches[__INDEX__][contact_email]">
            </div>
            <div class="col-md-4">
                <label class="form-label">Contact Phone</label>
                <input type="text" class="form-control" name="branches[__INDEX__][contact_phone]">
            </div>
        </div>
    </div>
</template>

@push('scripts')
<script>
$(document).ready(function() {
    let branchIndex = 0;
    let isInitializing = true; // Prevents state-change from clearing city during edit load
    const ajaxStatesUrl = @json($ajaxStatesUrl);
    const ajaxCitiesUrl = @json($ajaxCitiesUrl);

    // Initialize existing branches (edit mode)
    @if($isEdit && $vendor->branches->count() > 0)
        const existingBranches = @json($vendor->branches->load(['state', 'city']));
        existingBranches.forEach(function(branch) {
            addBranchRow(branch);
        });
    @else
        // Add one default branch for create mode
        addBranchRow({ branch_name: 'Main Branch', is_primary: true, country: 'Malaysia', status: 'active' });
    @endif

    // After initial load complete, enable state-change clearing
    setTimeout(function() {
        isInitializing = false;
    }, 500);

    updateNoBranchesAlert();

    // Add Branch button
    $('#addBranchBtn').on('click', function() {
        isInitializing = false;
        addBranchRow({});
        updateNoBranchesAlert();
    });

    // Remove Branch button (delegated)
    $('#branchesContainer').on('click', '.remove-branch-btn', function() {
        const container = $('#branchesContainer');
        if (container.find('.branch-row').length <= 1) {
            showToast('error', 'At least one branch is required.');
            return;
        }

        var $row = $(this).closest('.branch-row');
        // Destroy Select2 instances before removing DOM
        $row.find('.branch-state-select').each(function() {
            if ($(this).hasClass('select2-hidden-accessible')) {
                $(this).select2('destroy');
            }
        });
        $row.find('.branch-city-select').each(function() {
            if ($(this).hasClass('select2-hidden-accessible')) {
                $(this).select2('destroy');
            }
        });
        $row.remove();
        renumberBranches();
        updateNoBranchesAlert();
    });

    // Primary switch - ensure only one primary
    $('#branchesContainer').on('change', '.primary-switch', function() {
        if ($(this).is(':checked')) {
            $('.primary-switch').not(this).prop('checked', false);
        }
    });

    // Form submission
    $('#vendorForm').on('submit', function(e) {
        e.preventDefault();

        var form = $(this);
        var submitBtn = $('#submitBtn');

        // Validate at least one branch
        if ($('#branchesContainer .branch-row').length === 0) {
            showToast('error', 'At least one branch is required.');
            return;
        }

        submitBtn.prop('disabled', true).html(
            '<span class="spinner-border spinner-border-sm me-1"></span> Saving...'
        );

        // Clear previous validation errors
        form.find('.is-invalid').removeClass('is-invalid');
        form.find('.invalid-feedback').remove();

        $.ajax({
            url: form.attr('action'),
            method: form.find('input[name="_method"]').val() || 'POST',
            data: form.serialize(),
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    showToast('success', response.message);
                    if (response.redirect) {
                        setTimeout(function() { window.location.href = response.redirect; }, 1000);
                    }
                } else {
                    showToast('error', response.message || 'Something went wrong.');
                    submitBtn.prop('disabled', false).html(
                        '<i class="bi bi-check-circle me-1"></i> {{ $isEdit ? "Update Vendor" : "Create Vendor" }}'
                    );
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    var errors = xhr.responseJSON?.errors;
                    if (errors) {
                        var firstErrorField = null;
                        $.each(errors, function(field, messages) {
                            var selector;
                            if (field.startsWith('branches.')) {
                                var parts = field.split('.');
                                selector = '[name="branches[' + parts[1] + '][' + parts[2] + ']"]';
                            } else {
                                selector = '[name="' + field + '"]';
                            }

                            var input = form.find(selector);
                            if (input.length) {
                                // For Select2 fields, highlight the container
                                if (input.hasClass('select2-hidden-accessible')) {
                                    input.next('.select2-container').addClass('is-invalid');
                                    input.closest('.col-md-3').append(
                                        '<div class="invalid-feedback d-block">' + messages[0] + '</div>'
                                    );
                                } else {
                                    input.addClass('is-invalid');
                                    input.after('<div class="invalid-feedback">' + messages[0] + '</div>');
                                }
                                if (!firstErrorField) firstErrorField = input;
                            }
                        });

                        if (firstErrorField) {
                            $('html, body').animate({
                                scrollTop: firstErrorField.closest('.branch-row, .card').offset().top - 100
                            }, 300);
                        }

                        showToast('error', 'Please fix the validation errors.');
                    }
                } else {
                    showToast('error', xhr.responseJSON?.message || 'Server error occurred.');
                }
                submitBtn.prop('disabled', false).html(
                    '<i class="bi bi-check-circle me-1"></i> {{ $isEdit ? "Update Vendor" : "Create Vendor" }}'
                );
            }
        });
    });

    /**
     * Add a branch row to the container
     */
    function addBranchRow(data) {
        data = data || {};
        var template = $('#branchRowTemplate').html();
        var html = template
            .replace(/__INDEX__/g, branchIndex)
            .replace(/__NUMBER__/g, branchIndex + 1);

        var $row = $(html);
        $('#branchesContainer').append($row);

        var currentIndex = branchIndex;

        // Populate simple fields
        if (data.id) {
            $row.find('input[name="branches[' + currentIndex + '][id]"]').val(data.id);
        }
        if (data.branch_name) {
            $row.find('input[name="branches[' + currentIndex + '][branch_name]"]').val(data.branch_name);
        }
        if (data.address) {
            $row.find('input[name="branches[' + currentIndex + '][address]"]').val(data.address);
        }
        if (data.postcode) {
            $row.find('.branch-postcode').val(data.postcode);
        }
        if (data.status) {
            $row.find('select[name="branches[' + currentIndex + '][status]"]').val(data.status);
        }
        if (data.contact_person) {
            $row.find('input[name="branches[' + currentIndex + '][contact_person]"]').val(data.contact_person);
        }
        if (data.contact_email) {
            $row.find('input[name="branches[' + currentIndex + '][contact_email]"]').val(data.contact_email);
        }
        if (data.contact_phone) {
            $row.find('input[name="branches[' + currentIndex + '][contact_phone]"]').val(data.contact_phone);
        }
        if (data.is_primary) {
            $('.primary-switch').prop('checked', false);
            $row.find('.primary-switch').prop('checked', true);
        }

        // ============================================================
        // Initialize Select2 for State — with Bootstrap 5 theme
        // ============================================================
        var $stateSelect = $row.find('.branch-state-select');
        $stateSelect.select2({
            theme: 'bootstrap-5',
            placeholder: 'Select State',
            allowClear: true,
            width: '100%',
            ajax: {
                url: ajaxStatesUrl,
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return { search: params.term, page: params.page || 1 };
                },
                processResults: function(response) {
                    return { results: response.results, pagination: response.pagination };
                },
                cache: true
            }
        });

        // ============================================================
        // Initialize Select2 for City — with Bootstrap 5 theme
        // ============================================================
        var $citySelect = $row.find('.branch-city-select');
        $citySelect.select2({
            theme: 'bootstrap-5',
            placeholder: 'Select City',
            allowClear: true,
            width: '100%',
            ajax: {
                url: ajaxCitiesUrl,
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    var stateId = $row.find('.branch-state-select').val();
                    return { state_id: stateId, search: params.term, page: params.page || 1 };
                },
                processResults: function(response) {
                    return { results: response.results, pagination: response.pagination };
                },
                cache: true
            }
        });

        // State change → clear city & postcode (skip during initial edit load)
        $stateSelect.on('change', function() {
            if (!isInitializing) {
                $citySelect.val(null).trigger('change');
                $row.find('.branch-postcode').val('');
            }
        });

        // City select → auto-fill postcode
        $citySelect.on('select2:select', function(e) {
            var selectedData = e.params.data;
            if (selectedData && selectedData.postcode) {
                $row.find('.branch-postcode').val(selectedData.postcode);
            }
        });

        // City clear → clear postcode
        $citySelect.on('select2:clear', function() {
            $row.find('.branch-postcode').val('');
        });

        // ============================================================
        // Pre-select state and city for EDIT mode
        // Uses trigger('change.select2') to avoid firing the change handler
        // ============================================================
        if (data.state_id && data.state) {
            var stateName = data.state.name || '';
            var stateOption = new Option(stateName, data.state_id, true, true);
            $stateSelect.append(stateOption).trigger('change.select2');
        }

        if (data.city_id && data.city) {
            var cityName = data.city.name || '';
            var cityPostcode = data.city.postcode || '';
            var cityText = cityName + (cityPostcode ? ' (' + cityPostcode + ')' : '');
            var cityOption = new Option(cityText, data.city_id, true, true);
            $citySelect.append(cityOption).trigger('change.select2');
        }

        branchIndex++;
    }

    /**
     * Renumber branch visual labels after removal
     */
    function renumberBranches() {
        $('#branchesContainer .branch-row').each(function(i) {
            $(this).find('.branch-number').text(i + 1);
        });
    }

    /**
     * Show/hide "no branches" alert
     */
    function updateNoBranchesAlert() {
        if ($('#branchesContainer .branch-row').length === 0) {
            $('#noBranchesAlert').removeClass('d-none');
        } else {
            $('#noBranchesAlert').addClass('d-none');
        }
    }
});
</script>
@endpush
