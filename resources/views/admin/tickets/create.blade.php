@extends('layouts.app')
@section('title', 'Create Ticket')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0"><i class="bi bi-ticket-detailed me-2"></i>Create New Ticket</h4>
        <a href="{{ route('admin.tickets.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Back to List
        </a>
    </div>

    <form id="ticketForm" novalidate>
        @csrf
        <div class="row g-4">
            {{-- LEFT: Main Info --}}
            <div class="col-lg-8">
                {{-- Vendor Section --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white"><h6 class="mb-0"><i class="bi bi-building me-2"></i>Vendor Information</h6></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Vendor <span class="text-danger">*</span></label>
                                <select name="vendor_id" id="vendor_id" class="form-select select2" required>
                                    <option value="">Select Vendor</option>
                                    @foreach($vendors as $v)
                                    <option value="{{ $v->id }}">{{ $v->vendor_name }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Branch <span class="text-danger">*</span></label>
                                <select name="vendor_branch_id" id="vendor_branch_id" class="form-select select2" required>
                                    <option value="">Select Branch</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Vendor Ticket Ref No</label>
                                <input type="text" name="vendor_ticket_ref_no" class="form-control" placeholder="Vendor reference number">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Location Section --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white"><h6 class="mb-0"><i class="bi bi-geo-alt me-2"></i>Location & Merchant</h6></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">State <span class="text-danger">*</span></label>
                                <select name="state_id" id="state_id" class="form-select select2" required>
                                    <option value="">Select State</option>
                                    @foreach($states as $s)
                                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">District / City <span class="text-danger">*</span></label>
                                <select name="city_id" id="city_id" class="form-select select2" required>
                                    <option value="">Select District</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">TID <span class="text-danger">*</span></label>
                                <input type="text" name="tid" class="form-control" required placeholder="Terminal Identifier">
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Merchant Name <span class="text-danger">*</span></label>
                                <input type="text" name="merchant_name" class="form-control" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Contact Number <span class="text-danger">*</span></label>
                                <input type="text" name="contact_number" class="form-control" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Merchant Address <span class="text-danger">*</span></label>
                                <textarea name="merchant_address" class="form-control" rows="2" required></textarea>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Job Category & Type — BOTH INDEPENDENT --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white"><h6 class="mb-0"><i class="bi bi-diagram-3 me-2"></i>Job Category & Type</h6></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Job Category <span class="text-danger">*</span></label>
                                <select name="job_category_id" id="job_category_id" class="form-select" required>
                                    <option value="">Select Category</option>
                                    @foreach($jobCategories as $cat)
                                    <option value="{{ $cat->id }}" data-slug="{{ $cat->slug }}">{{ $cat->category_name }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Controls device ID fields</small>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Job Type <span class="text-danger">*</span></label>
                                <select name="job_type_id" id="job_type_id" class="form-select" required>
                                    <option value="">Select Job Type</option>
                                    @foreach($jobTypes as $jt)
                                    <option value="{{ $jt->id }}" data-slug="{{ $jt->slug }}">{{ $jt->job_title }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Shared across all categories</small>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Price (RM)</label>
                                <input type="text" name="price" id="price_display" class="form-control bg-light" readonly placeholder="0.00">
                                <small class="text-muted">From supervisor pricing (Category + Type)</small>
                            </div>

                            {{-- Dynamic Device ID Fields --}}
                            <div class="col-md-6 device-field" id="terminalIdGroup" style="display:none;">
                                <label class="form-label">Terminal ID <span class="text-danger terminal-required-star" style="display:none;">*</span></label>
                                <input type="text" name="terminal_id" id="terminal_id" class="form-control" placeholder="Enter Terminal ID">
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6 device-field" id="routerIdGroup" style="display:none;">
                                <label class="form-label">Router ID <span class="text-danger router-required-star" style="display:none;">*</span></label>
                                <input type="text" name="router_id" id="router_id" class="form-control" placeholder="Enter Router ID">
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6 device-field" id="oldTerminalIdGroup" style="display:none;">
                                <label class="form-label">Old Terminal ID <span class="text-danger">*</span></label>
                                <input type="text" name="old_terminal_id" id="old_terminal_id" class="form-control" placeholder="Required for replacement">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Description & Schedule --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white"><h6 class="mb-0"><i class="bi bi-card-text me-2"></i>Details & Schedule</h6></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Description <span class="text-danger">*</span></label>
                                <textarea name="description" class="form-control" rows="4" required placeholder="Describe the issue or task..."></textarea>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Expected Start Date</label>
                                <input type="date" name="expected_start_date" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Expected End Date</label>
                                <input type="date" name="expected_end_date" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">SLA Hours</label>
                                <input type="number" name="sla_hours" class="form-control" value="24" min="1" max="720">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- RIGHT: Assignment & Claims --}}
            <div class="col-lg-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white"><h6 class="mb-0"><i class="bi bi-people me-2"></i>Assignment</h6></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Priority <span class="text-danger">*</span></label>
                            <select name="priority" class="form-select" required>
                                @foreach(\App\Models\Ticket::getPriorities() as $key => $label)
                                <option value="{{ $key }}" {{ $key === 'normal' ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Supervisor <span class="text-danger">*</span></label>
                            <select name="supervisor_id" id="supervisor_id" class="form-select select2" required>
                                <option value="">Select Supervisor</option>
                                @foreach($supervisors as $sv)
                                <option value="{{ $sv->id }}" data-type="{{ $sv->supervisor_type }}">{{ $sv->name }} ({{ ucfirst($sv->supervisor_type ?? 'N/A') }})</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback"></div>
                            <div id="supervisorTypeInfo" class="mt-1" style="display:none;">
                                <span id="supervisorTypeBadge"></span>
                            </div>
                        </div>
                        <div class="mb-3" id="technicianGroup" style="display:none;">
                            <label class="form-label">Assign Technician</label>
                            <select name="technician_id" id="technician_id" class="form-select select2">
                                <option value="">Assign Later</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Claims — only for external supervisors --}}
                <div class="card shadow-sm mb-4" id="claimSection" style="display:none;">
                    <div class="card-header bg-white"><h6 class="mb-0"><i class="bi bi-receipt me-2"></i>Claims</h6></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Mileage (KM)</label>
                            <input type="number" name="mileage" id="mileage" class="form-control" step="0.01" min="0" value="0">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mileage Rate (RM/KM)</label>
                            <input type="text" id="mileage_rate_display" class="form-control bg-light" readonly value="0.00">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mileage Amount (RM)</label>
                            <input type="text" id="mileage_amount_display" class="form-control bg-light" readonly value="0.00">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mileage Remarks</label>
                            <input type="text" name="mileage_remarks" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Toll (RM)</label>
                            <input type="number" name="toll" class="form-control" step="0.01" min="0" value="0">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Standby / Meal (RM)</label>
                            <input type="number" name="standby_meal" class="form-control" step="0.01" min="0" value="0">
                        </div>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary" id="btnSubmit">
                        <i class="bi bi-check-lg me-1"></i>Create Ticket
                    </button>
                    <a href="{{ route('admin.tickets.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    @php $roleName = explode('.', Route::currentRouteName())[0]; @endphp
    const roleName = '{{ $roleName }}';
    const baseUrl = '/' + roleName + '/tickets';
    let currentSupervisorType = null;
    let currentMileageRate = 0;

    $('.select2').select2({ theme: 'bootstrap-5', width: '100%' });

    // ══════════════════════════════════════════════════
    // Vendor → Branch cascade
    // ══════════════════════════════════════════════════
    $('#vendor_id').on('change', function() {
        let vendorId = $(this).val();
        $('#vendor_branch_id').html('<option value="">Loading...</option>');
        if (!vendorId) { $('#vendor_branch_id').html('<option value="">Select Branch</option>'); return; }
        $.get(baseUrl + '/ajax/vendor-branches', { vendor_id: vendorId }, function(data) {
            let opts = '<option value="">Select Branch</option>';
            data.forEach(b => opts += `<option value="${b.id}" data-state="${b.state_id}" data-city="${b.city_id}">${b.branch_name}</option>`);
            $('#vendor_branch_id').html(opts).trigger('change.select2');
        });
    });

    $('#vendor_branch_id').on('change', function() {
        let opt = $(this).find(':selected');
        let stateId = opt.data('state'), cityId = opt.data('city');
        if (stateId) {
            $('#state_id').val(stateId).trigger('change.select2');
            setTimeout(() => { if (cityId) $('#city_id').val(cityId).trigger('change.select2'); }, 500);
        }
    });

    // ══════════════════════════════════════════════════
    // State → City + Supervisor filter
    // ══════════════════════════════════════════════════
    $('#state_id').on('change', function() {
        let stateId = $(this).val();
        $('#city_id').html('<option value="">Loading...</option>');
        if (!stateId) { $('#city_id').html('<option value="">Select District</option>'); return; }
        $.get(baseUrl + '/ajax/cities', { state_id: stateId }, function(data) {
            let opts = '<option value="">Select District</option>';
            data.forEach(c => opts += `<option value="${c.id}">${c.name}</option>`);
            $('#city_id').html(opts).trigger('change.select2');
        });
        loadSupervisors(stateId, null);
    });

    $('#city_id').on('change', function() {
        let stateId = $('#state_id').val(), cityId = $(this).val();
        if (stateId) loadSupervisors(stateId, cityId);
    });

    function loadSupervisors(stateId, cityId) {
        $.get(baseUrl + '/ajax/supervisors', { state_id: stateId, city_id: cityId }, function(data) {
            let opts = '<option value="">Select Supervisor</option>';
            data.forEach(s => {
                let typeLabel = s.supervisor_type ? ' (' + s.supervisor_type.charAt(0).toUpperCase() + s.supervisor_type.slice(1) + ')' : '';
                opts += `<option value="${s.id}" data-type="${s.supervisor_type}">${s.name}${typeLabel}</option>`;
            });
            $('#supervisor_id').html(opts).trigger('change.select2');
        });
    }

    // ══════════════════════════════════════════════════
    // Supervisor → type detection, technician, mileage, price
    // ══════════════════════════════════════════════════
    $('#supervisor_id').on('change', function() {
        let svId = $(this).val();
        let svType = $(this).find(':selected').data('type');
        currentSupervisorType = svType;

        if (!svId) { $('#technicianGroup, #claimSection, #supervisorTypeInfo').hide(); return; }

        if (svType === 'internal') {
            $('#supervisorTypeBadge').html('<span class="badge bg-success"><i class="bi bi-building me-1"></i>Internal Supervisor</span>');
            $('#technicianGroup').show();
            $('#claimSection').hide();
        } else {
            $('#supervisorTypeBadge').html('<span class="badge bg-warning text-dark"><i class="bi bi-person-badge me-1"></i>External Supervisor</span>');
            $('#technicianGroup').hide();
            $('#technician_id').val('').trigger('change.select2');
            $('#claimSection').show();
        }
        $('#supervisorTypeInfo').show();

        if (svType === 'internal') {
            $.get(baseUrl + '/ajax/technicians', { supervisor_id: svId }, function(data) {
                let opts = '<option value="">Assign Later</option>';
                data.forEach(t => opts += `<option value="${t.id}">${t.name}</option>`);
                $('#technician_id').html(opts).trigger('change.select2');
            });
        }

        $.get(baseUrl + '/ajax/supervisor-mileage-rate', { supervisor_id: svId }, function(data) {
            currentMileageRate = parseFloat(data.mileage_rate) || 0;
            $('#mileage_rate_display').val(currentMileageRate.toFixed(2));
            calcMileageAmount();
        });

        refreshPrice();
    });

    // ══════════════════════════════════════════════════
    // Job Category → ONLY toggles device fields + refreshes price
    // Job Type → ONLY checks replacement + refreshes price
    // Both are INDEPENDENT — no cascade between them
    // ══════════════════════════════════════════════════
    $('#job_category_id').on('change', function() {
        let slug = $(this).find(':selected').data('slug');
        toggleDeviceFields(slug);
        refreshPrice();
    });

    $('#job_type_id').on('change', function() {
        let slug = $(this).find(':selected').data('slug') || '';
        // Show old_terminal_id only for replacement job types
        if (slug.includes('replacement')) {
            $('#oldTerminalIdGroup').show();
        } else {
            $('#oldTerminalIdGroup').hide();
            $('#old_terminal_id').val('');
        }
        refreshPrice();
    });

    function toggleDeviceFields(slug) {
        hideAllDeviceFields();
        switch(slug) {
            case 'terminal':
                $('#terminalIdGroup').show();
                $('.terminal-required-star').show();
                $('#terminal_id').attr('required', true);
                break;
            case 'router':
                $('#routerIdGroup').show();
                $('.router-required-star').show();
                $('#router_id').attr('required', true);
                break;
            case 'project':
                $('#terminalIdGroup, #routerIdGroup').show();
                // Both optional for project
                break;
            case 'accessories':
            default:
                // Both hidden for accessories
                break;
        }
    }

    function hideAllDeviceFields() {
        $('.device-field').hide();
        $('.terminal-required-star, .router-required-star').hide();
        $('#terminal_id, #router_id, #old_terminal_id').val('').removeAttr('required');
    }

    // ══════════════════════════════════════════════════
    // Price = supervisor + category + type combination
    // ══════════════════════════════════════════════════
    function refreshPrice() {
        let svId = $('#supervisor_id').val();
        let catId = $('#job_category_id').val();
        let typeId = $('#job_type_id').val();
        if (!svId || !catId || !typeId) { $('#price_display').val('0.00'); return; }

        $.get(baseUrl + '/ajax/price', {
            supervisor_id: svId, job_category_id: catId, job_type_id: typeId
        }, function(data) {
            $('#price_display').val(parseFloat(data.price || 0).toFixed(2));
        });
    }

    // Mileage calculation
    $('#mileage').on('input', calcMileageAmount);
    function calcMileageAmount() {
        let km = parseFloat($('#mileage').val()) || 0;
        $('#mileage_amount_display').val((km * currentMileageRate).toFixed(2));
    }

    // ══════════════════════════════════════════════════
    // Form submission
    // ══════════════════════════════════════════════════
    $('#ticketForm').on('submit', function(e) {
        e.preventDefault();
        let btn = $('#btnSubmit');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');

        let formData = new FormData(this);
        formData.set('price', $('#price_display').val());

        $.ajax({
            url: '{{ route("admin.tickets.store") }}',
            method: 'POST', data: formData, processData: false, contentType: false,
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            success: function(res) {
                if (res.success) { showToast(res.message, 'success'); if (res.redirect) window.location.href = res.redirect; }
                else showToast(res.message || 'Error', 'error');
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    let errors = xhr.responseJSON?.errors || {};
                    $('.is-invalid').removeClass('is-invalid');
                    Object.keys(errors).forEach(field => {
                        $('[name="' + field + '"]').addClass('is-invalid').siblings('.invalid-feedback').text(errors[field][0]);
                    });
                    showToast('Please fix validation errors.', 'error');
                } else showToast(xhr.responseJSON?.message || 'Server error.', 'error');
            },
            complete: () => btn.prop('disabled', false).html('<i class="bi bi-check-lg me-1"></i>Create Ticket')
        });
    });
});
</script>
@endpush
