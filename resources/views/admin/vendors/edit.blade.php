@extends('layouts.app')

@section('title', 'Edit Vendor - TMS')

@section('content')
<div class="container-fluid">
    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="bi bi-building-gear"></i> Edit Vendor</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.vendors.index') }}">Vendors</a></li>
                    <li class="breadcrumb-item active">Edit: {{ $vendor->vendor_name }}</li>
                </ol>
            </nav>
        </div>
    </div>

    <form id="vendorForm" method="POST" action="{{ route('admin.vendors.update', $vendor) }}">
        @csrf
        @method('PUT')

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
                               value="{{ old('vendor_name', $vendor->vendor_name) }}" required>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="col-md-3">
                        <label for="vendor_code" class="form-label">Vendor Code</label>
                        <input type="text" class="form-control bg-light" id="vendor_code"
                               value="{{ $vendor->vendor_code }}" readonly>
                        <small class="text-muted">Vendor code cannot be changed after creation</small>
                    </div>
                    <div class="col-md-3">
                        <label for="vendor_type_id" class="form-label">Vendor Type <span class="text-danger">*</span></label>
                        <select class="form-select" id="vendor_type_id" name="vendor_type_id" required>
                            <option value="">-- Select Type --</option>
                            @foreach($vendorTypes as $type)
                                <option value="{{ $type->id }}"
                                    {{ old('vendor_type_id', $vendor->vendor_type_id) == $type->id ? 'selected' : '' }}>
                                    {{ $type->title }}
                                </option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="col-md-4">
                        <label for="company_name" class="form-label">Company Name</label>
                        <input type="text" class="form-control" id="company_name" name="company_name"
                               value="{{ old('company_name', $vendor->company_name) }}">
                    </div>
                    <div class="col-md-4">
                        <label for="registration_no" class="form-label">Registration No</label>
                        <input type="text" class="form-control" id="registration_no" name="registration_no"
                               value="{{ old('registration_no', $vendor->registration_no) }}">
                    </div>
                    <div class="col-md-4">
                        <label for="tax_id" class="form-label">Tax ID</label>
                        <input type="text" class="form-control" id="tax_id" name="tax_id"
                               value="{{ old('tax_id', $vendor->tax_id) }}">
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
                        <input type="text" class="form-control" id="pic_name" name="pic_name"
                               value="{{ old('pic_name', $vendor->pic_name) }}">
                    </div>
                    <div class="col-md-4">
                        <label for="pic_email" class="form-label">PIC Email</label>
                        <input type="email" class="form-control" id="pic_email" name="pic_email"
                               value="{{ old('pic_email', $vendor->pic_email) }}">
                    </div>
                    <div class="col-md-4">
                        <label for="pic_phone" class="form-label">PIC Phone</label>
                        <input type="text" class="form-control" id="pic_phone" name="pic_phone"
                               value="{{ old('pic_phone', $vendor->pic_phone) }}">
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
                        <input type="text" class="form-control" id="bank_name" name="bank_name"
                               value="{{ old('bank_name', $vendor->bank_name) }}">
                    </div>
                    <div class="col-md-4">
                        <label for="bank_account_no" class="form-label">Account Number</label>
                        <input type="text" class="form-control" id="bank_account_no" name="bank_account_no"
                               value="{{ old('bank_account_no', $vendor->bank_account_no) }}">
                    </div>
                    <div class="col-md-4">
                        <label for="bank_account_name" class="form-label">Account Name</label>
                        <input type="text" class="form-control" id="bank_account_name" name="bank_account_name"
                               value="{{ old('bank_account_name', $vendor->bank_account_name) }}">
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
                               value="{{ old('payment_terms', $vendor->payment_terms) }}"
                               placeholder="e.g. 30" min="0" max="365">
                        <small class="text-muted">Leave empty if not applicable</small>
                    </div>
                    <div class="col-md-4">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">-- Not Set --</option>
                            <option value="active" {{ old('status', $vendor->status) === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status', $vendor->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-12">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3">{{ old('notes', $vendor->notes) }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- Branches (original @foreach server-side flow) --}}
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><i class="bi bi-geo-alt"></i> Branches</h5>
                <button type="button" class="btn btn-sm btn-success" id="addBranch">
                    <i class="bi bi-plus-lg"></i> Add Branch
                </button>
            </div>
            <div class="card-body">
                <div id="branchesContainer">
                    @foreach($vendor->branches as $i => $branch)
                    <div class="branch-item border rounded p-3 mb-3" data-index="{{ $i }}">
                        <input type="hidden" name="branches[{{ $i }}][id]" value="{{ $branch->id }}">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0">Branch #{{ $i + 1 }}</h6>
                            <div class="d-flex gap-2 align-items-center">
                                <div class="form-check form-switch">
                                    <input class="form-check-input branch-primary" type="checkbox"
                                           name="branches[{{ $i }}][is_primary]" value="1"
                                           {{ $branch->is_primary ? 'checked' : '' }}>
                                    <label class="form-check-label">Primary</label>
                                </div>
                                @if($vendor->branches->count() > 1)
                                <button type="button" class="btn btn-sm btn-outline-danger remove-branch">
                                    <i class="bi bi-trash"></i>
                                </button>
                                @endif
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Branch Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="branches[{{ $i }}][branch_name]"
                                       value="{{ $branch->branch_name }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Address</label>
                                <input type="text" class="form-control" name="branches[{{ $i }}][address]"
                                       value="{{ $branch->address }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">State</label>
                                <select class="form-select branch-state" name="branches[{{ $i }}][state_id]"
                                        data-index="{{ $i }}" data-selected="{{ $branch->state_id }}">
                                    <option value="">-- Select State --</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">City</label>
                                <select class="form-select branch-city" name="branches[{{ $i }}][city_id]"
                                        data-index="{{ $i }}" data-selected="{{ $branch->city_id }}">
                                    <option value="">-- Select City --</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Postcode</label>
                                <input type="text" class="form-control bg-light branch-postcode" name="branches[{{ $i }}][postcode]"
                                       value="{{ $branch->postcode }}" readonly>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Country</label>
                                <input type="text" class="form-control bg-light" name="branches[{{ $i }}][country]"
                                       value="{{ $branch->country ?? 'Malaysia' }}" readonly>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="branches[{{ $i }}][status]">
                                    <option value="active" {{ ($branch->status ?? 'active') === 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="inactive" {{ ($branch->status ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Contact Person</label>
                                <input type="text" class="form-control" name="branches[{{ $i }}][contact_person]"
                                       value="{{ $branch->contact_person }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Contact Email</label>
                                <input type="email" class="form-control" name="branches[{{ $i }}][contact_email]"
                                       value="{{ $branch->contact_email }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Contact Phone</label>
                                <input type="text" class="form-control" name="branches[{{ $i }}][contact_phone]"
                                       value="{{ $branch->contact_phone }}">
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Form Actions --}}
        <div class="d-flex justify-content-end gap-2 mb-4">
            <a href="{{ route('admin.vendors.index') }}" class="btn btn-secondary">
                <i class="bi bi-x-circle"></i> Cancel
            </a>
            <button type="submit" class="btn btn-primary" id="btnSubmit">
                <i class="bi bi-check-circle"></i> Update Vendor
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    let branchIndex = {{ $vendor->branches->count() - 1 }};

    // ==========================================
    // LOAD EXISTING BRANCH STATES & CITIES
    // ==========================================

    $.ajax({
        url: '{{ route("admin.ajax.states") }}',
        type: 'GET',
        data: { search: '' },
        success: function(res) {
            if (res.results) {
                $('.branch-state').each(function() {
                    let select = $(this);
                    let selected = select.data('selected');
                    res.results.forEach(function(state) {
                        let isSelected = (state.id == selected) ? ' selected' : '';
                        select.append('<option value="' + state.id + '"' + isSelected + '>' + state.text + '</option>');
                    });

                    // Load cities for selected state
                    if (selected) {
                        let index = select.data('index');
                        let citySelect = $('[name="branches[' + index + '][city_id]"]');
                        let citySelected = citySelect.data('selected');

                        $.ajax({
                            url: '{{ route("admin.ajax.cities") }}',
                            type: 'GET',
                            data: { state_id: selected },
                            success: function(cityRes) {
                                if (cityRes.results) {
                                    cityRes.results.forEach(function(city) {
                                        let isCitySelected = (city.id == citySelected) ? ' selected' : '';
                                        citySelect.append('<option value="' + city.id + '" data-postcode="' + (city.postcode || '') + '"' + isCitySelected + '>' + city.text + '</option>');
                                    });
                                }
                            }
                        });
                    }
                });
            }
        }
    });

    // ==========================================
    // BRANCHES MANAGEMENT
    // ==========================================

    $('#addBranch').on('click', function() {
        branchIndex++;
        let html = getBranchTemplate(branchIndex);
        $('#branchesContainer').append(html);
        initBranchState(branchIndex);
    });

    $(document).on('click', '.remove-branch', function() {
        if ($('.branch-item').length <= 1) {
            showToast('error', 'At least one branch is required.');
            return;
        }
        $(this).closest('.branch-item').remove();
        reindexBranches();
    });

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
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Updating...');

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
                btn.prop('disabled', false).html('<i class="bi bi-check-circle"></i> Update Vendor');

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
