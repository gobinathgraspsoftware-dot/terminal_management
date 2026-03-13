@extends('layouts.app')

@section('title', 'Create Vendor - TMS')

@section('content')
<div class="container-fluid">
    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="bi bi-building-add"></i> Create New Vendor</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.vendors.index') }}">Vendors</a></li>
                    <li class="breadcrumb-item active">Create</li>
                </ol>
            </nav>
        </div>
    </div>

    <form id="vendorForm" method="POST" action="{{ route('admin.vendors.store') }}">
        @csrf

        {{-- Basic Information --}}
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="bi bi-info-circle"></i> Basic Information</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="vendor_name" class="form-label">Vendor Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="vendor_name" name="vendor_name"
                               placeholder="Enter vendor name" required>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="col-md-3">
                        <label for="vendor_code" class="form-label">Vendor Code <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="vendor_code" name="vendor_code"
                                   value="{{ $nextCode }}" required>
                            <button type="button" class="btn btn-outline-secondary" id="btnRefreshCode"
                                    title="Refresh auto-generated code">
                                <i class="bi bi-arrow-clockwise"></i>
                            </button>
                        </div>
                        <div id="codeSuggestions" class="mt-1" style="display:none;">
                            <small class="text-muted">Suggestions:</small>
                            <div id="codeSuggestionBtns" class="d-flex flex-wrap gap-1 mt-1"></div>
                        </div>
                        <div id="codeAvailability" class="mt-1"></div>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="col-md-3">
                        <label for="vendor_type_id" class="form-label">Vendor Type <span class="text-danger">*</span></label>
                        <select class="form-select" id="vendor_type_id" name="vendor_type_id" required>
                            <option value="">-- Select Type --</option>
                            @foreach($vendorTypes as $type)
                                <option value="{{ $type->id }}">{{ $type->title }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="col-md-4">
                        <label for="company_name" class="form-label">Company Name</label>
                        <input type="text" class="form-control" id="company_name" name="company_name"
                               placeholder="Legal company name">
                    </div>
                    <div class="col-md-4">
                        <label for="registration_no" class="form-label">Registration No</label>
                        <input type="text" class="form-control" id="registration_no" name="registration_no"
                               placeholder="SSM / Business registration">
                    </div>
                    <div class="col-md-4">
                        <label for="tax_id" class="form-label">Tax ID</label>
                        <input type="text" class="form-control" id="tax_id" name="tax_id"
                               placeholder="Tax identification number">
                    </div>
                </div>
            </div>
        </div>

        {{-- Contact Information --}}
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="bi bi-person-lines-fill"></i> Person In Charge (PIC)</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="pic_name" class="form-label">PIC Name</label>
                        <input type="text" class="form-control" id="pic_name" name="pic_name">
                    </div>
                    <div class="col-md-4">
                        <label for="pic_email" class="form-label">PIC Email</label>
                        <input type="email" class="form-control" id="pic_email" name="pic_email">
                    </div>
                    <div class="col-md-4">
                        <label for="pic_phone" class="form-label">PIC Phone</label>
                        <input type="text" class="form-control" id="pic_phone" name="pic_phone">
                    </div>
                </div>
            </div>
        </div>

        {{-- Bank Details --}}
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="bi bi-bank"></i> Bank Details</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="bank_name" class="form-label">Bank Name</label>
                        <input type="text" class="form-control" id="bank_name" name="bank_name">
                    </div>
                    <div class="col-md-4">
                        <label for="bank_account_no" class="form-label">Account Number</label>
                        <input type="text" class="form-control" id="bank_account_no" name="bank_account_no">
                    </div>
                    <div class="col-md-4">
                        <label for="bank_account_name" class="form-label">Account Name</label>
                        <input type="text" class="form-control" id="bank_account_name" name="bank_account_name">
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
                <h5 class="card-title mb-0"><i class="bi bi-gear"></i> Payment & Status</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="payment_terms" class="form-label">Payment Terms (days)</label>
                        <input type="number" class="form-control" id="payment_terms" name="payment_terms"
                               value="" placeholder="e.g. 30" min="0" max="365">
                        <small class="text-muted">Leave empty if not applicable</small>
                    </div>
                    <div class="col-md-4">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">-- Not Set --</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-12">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"
                                  placeholder="Optional notes about this vendor"></textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- Branches --}}
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><i class="bi bi-geo-alt"></i> Branches</h5>
                <button type="button" class="btn btn-sm btn-success" id="addBranch">
                    <i class="bi bi-plus-lg"></i> Add Branch
                </button>
            </div>
            <div class="card-body">
                <div id="branchesContainer">
                    {{-- First branch (required) --}}
                    <div class="branch-item border rounded p-3 mb-3" data-index="0">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0">Branch #1</h6>
                            <div class="d-flex gap-2 align-items-center">
                                <div class="form-check form-switch">
                                    <input class="form-check-input branch-primary" type="checkbox"
                                           name="branches[0][is_primary]" value="1" checked>
                                    <label class="form-check-label">Primary</label>
                                </div>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Branch Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="branches[0][branch_name]"
                                       required placeholder="e.g. Head Office">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Address</label>
                                <input type="text" class="form-control" name="branches[0][address]">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">State</label>
                                <select class="form-select branch-state" name="branches[0][state_id]" data-index="0">
                                    <option value="">-- Select State --</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">City</label>
                                <select class="form-select branch-city" name="branches[0][city_id]" data-index="0">
                                    <option value="">-- Select City --</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Postcode</label>
                                <input type="text" class="form-control bg-light branch-postcode" name="branches[0][postcode]" readonly>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Country</label>
                                <input type="text" class="form-control bg-light" name="branches[0][country]" value="Malaysia" readonly>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="branches[0][status]">
                                    <option value="active" selected>Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Contact Person</label>
                                <input type="text" class="form-control" name="branches[0][contact_person]">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Contact Email</label>
                                <input type="email" class="form-control" name="branches[0][contact_email]">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Contact Phone</label>
                                <input type="text" class="form-control" name="branches[0][contact_phone]">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Form Actions --}}
        <div class="d-flex justify-content-end gap-2 mb-4">
            <a href="{{ route('admin.vendors.index') }}" class="btn btn-secondary">
                <i class="bi bi-x-circle"></i> Cancel
            </a>
            <button type="submit" class="btn btn-primary" id="btnSubmit">
                <i class="bi bi-check-circle"></i> Create Vendor
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    let branchIndex = 0;
    let suggestTimer = null;

    // ==========================================
    // VENDOR CODE SUGGESTIONS (Gmail-style)
    // ==========================================

    $('#vendor_name').on('input', function() {
        clearTimeout(suggestTimer);
        let name = $(this).val().trim();

        if (name.length < 2) {
            $('#codeSuggestions').hide();
            return;
        }

        suggestTimer = setTimeout(function() {
            $.ajax({
                url: '{{ route("admin.vendors.suggest-code") }}',
                type: 'GET',
                data: { vendor_name: name },
                success: function(res) {
                    if (res.success && res.suggestions.length > 0) {
                        let html = '';
                        res.suggestions.forEach(function(code) {
                            html += '<button type="button" class="btn btn-sm btn-outline-primary code-suggestion-btn" data-code="' + code + '">' + code + '</button>';
                        });
                        $('#codeSuggestionBtns').html(html);
                        $('#codeSuggestions').show();
                    } else {
                        $('#codeSuggestions').hide();
                    }
                }
            });
        }, 400);
    });

    $(document).on('click', '.code-suggestion-btn', function() {
        let code = $(this).data('code');
        $('#vendor_code').val(code);
        $('#codeSuggestions').hide();
        checkCodeAvailability(code);
    });

    $('#btnRefreshCode').on('click', function() {
        $.ajax({
            url: '{{ route("admin.vendors.suggest-code") }}',
            type: 'GET',
            data: { vendor_name: '' },
            success: function(res) {
                if (res.success) {
                    $('#vendor_code').val(res.default_code);
                    checkCodeAvailability(res.default_code);
                }
            }
        });
    });

    let codeCheckTimer = null;
    $('#vendor_code').on('input', function() {
        clearTimeout(codeCheckTimer);
        let code = $(this).val().trim();
        if (code.length < 2) {
            $('#codeAvailability').html('');
            return;
        }
        codeCheckTimer = setTimeout(function() {
            checkCodeAvailability(code);
        }, 300);
    });

    function checkCodeAvailability(code) {
        $.ajax({
            url: '{{ route("admin.vendors.check-code") }}',
            type: 'GET',
            data: { vendor_code: code },
            success: function(res) {
                if (res.available) {
                    $('#codeAvailability').html('<small class="text-success"><i class="bi bi-check-circle"></i> Available</small>');
                    $('#vendor_code').removeClass('is-invalid');
                } else {
                    $('#codeAvailability').html('<small class="text-danger"><i class="bi bi-x-circle"></i> Already in use</small>');
                    $('#vendor_code').addClass('is-invalid');
                }
            }
        });
    }

    // ==========================================
    // BRANCHES MANAGEMENT
    // ==========================================

    // Initialize first branch state
    initBranchState(0);

    // Add branch
    $('#addBranch').on('click', function() {
        branchIndex++;
        let html = getBranchTemplate(branchIndex);
        $('#branchesContainer').append(html);
        initBranchState(branchIndex);
    });

    // Remove branch
    $(document).on('click', '.remove-branch', function() {
        if ($('.branch-item').length <= 1) {
            showToast('error', 'At least one branch is required.');
            return;
        }
        $(this).closest('.branch-item').remove();
        reindexBranches();
    });

    // Primary toggle — only one at a time
    $(document).on('change', '.branch-primary', function() {
        if ($(this).is(':checked')) {
            $('.branch-primary').not(this).prop('checked', false);
        }
    });

    // State → City cascade
    $(document).on('change', '.branch-state', function() {
        let index = $(this).data('index');
        let stateId = $(this).val();
        let $branchItem = $(this).closest('.branch-item');
        let citySelect = $branchItem.find('.branch-city');

        citySelect.html('<option value="">-- Select City --</option>');
        $branchItem.find('.branch-postcode').val('');

        if (stateId) {
            $.ajax({
                url: '{{ route("admin.ajax.cities") }}',
                type: 'GET',
                data: { state_id: stateId },
                success: function(res) {
                    if (res.results) {
                        res.results.forEach(function(city) {
                            citySelect.append('<option value="' + city.id + '" data-postcode="' + (city.postcode || '') + '">' + city.text + '</option>');
                        });
                    }
                }
            });
        }
    });

    // City change → auto-fill postcode
    $(document).on('change', '.branch-city', function() {
        let postcode = $(this).find('option:selected').data('postcode') || '';
        $(this).closest('.branch-item').find('.branch-postcode').val(postcode);
    });

    function initBranchState(index) {
        let stateSelect = $('[name="branches[' + index + '][state_id]"]');
        $.ajax({
            url: '{{ route("admin.ajax.states") }}',
            type: 'GET',
            data: { search: '' },
            success: function(res) {
                if (res.results) {
                    res.results.forEach(function(state) {
                        stateSelect.append('<option value="' + state.id + '">' + state.text + '</option>');
                    });
                }
            }
        });
    }

    function getBranchTemplate(index) {
        return `
        <div class="branch-item border rounded p-3 mb-3" data-index="${index}">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0">Branch #${index + 1}</h6>
                <div class="d-flex gap-2 align-items-center">
                    <div class="form-check form-switch">
                        <input class="form-check-input branch-primary" type="checkbox"
                               name="branches[${index}][is_primary]" value="1">
                        <label class="form-check-label">Primary</label>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger remove-branch">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Branch Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="branches[${index}][branch_name]"
                           required placeholder="e.g. Branch Office">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Address</label>
                    <input type="text" class="form-control" name="branches[${index}][address]">
                </div>
                <div class="col-md-3">
                    <label class="form-label">State</label>
                    <select class="form-select branch-state" name="branches[${index}][state_id]" data-index="${index}">
                        <option value="">-- Select State --</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">City</label>
                    <select class="form-select branch-city" name="branches[${index}][city_id]" data-index="${index}">
                        <option value="">-- Select City --</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Postcode</label>
                    <input type="text" class="form-control bg-light branch-postcode" name="branches[${index}][postcode]" readonly>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Country</label>
                    <input type="text" class="form-control bg-light" name="branches[${index}][country]" value="Malaysia" readonly>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="branches[${index}][status]">
                        <option value="active" selected>Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Contact Person</label>
                    <input type="text" class="form-control" name="branches[${index}][contact_person]">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Contact Email</label>
                    <input type="email" class="form-control" name="branches[${index}][contact_email]">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Contact Phone</label>
                    <input type="text" class="form-control" name="branches[${index}][contact_phone]">
                </div>
            </div>
        </div>`;
    }

    function reindexBranches() {
        $('.branch-item').each(function(i) {
            $(this).attr('data-index', i);
            $(this).find('h6').text('Branch #' + (i + 1));
            $(this).find('[name]').each(function() {
                let name = $(this).attr('name');
                $(this).attr('name', name.replace(/branches\[\d+\]/, 'branches[' + i + ']'));
            });
            $(this).find('.branch-state').attr('data-index', i);
            $(this).find('.branch-city').attr('data-index', i);
        });
        branchIndex = $('.branch-item').length - 1;
    }

    // ==========================================
    // FORM SUBMISSION
    // ==========================================

    $('#vendorForm').on('submit', function(e) {
        e.preventDefault();

        let btn = $('#btnSubmit');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Creating...');

        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').text('');

        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(res) {
                if (res.success) {
                    showToast('success', res.message);
                    if (res.redirect) {
                        setTimeout(function() { window.location.href = res.redirect; }, 1000);
                    }
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).html('<i class="bi bi-check-circle"></i> Create Vendor');

                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    let errors = xhr.responseJSON.errors;
                    Object.keys(errors).forEach(function(field) {
                        let input = $('[name="' + field + '"]');
                        if (input.length) {
                            input.addClass('is-invalid');
                            input.siblings('.invalid-feedback').text(errors[field][0]);
                        } else {
                            let cleanField = field.replace(/\./g, '[').replace(/\[(\w+)$/g, '[$1]');
                            let branchInput = $('[name="' + cleanField + '"]');
                            if (branchInput.length) {
                                branchInput.addClass('is-invalid');
                            }
                        }
                    });
                    showToast('error', 'Please fix the validation errors.');
                } else {
                    showToast('error', xhr.responseJSON?.message || 'An error occurred.');
                }
            }
        });
    });
});
</script>
@endpush
