@extends('layouts.app')
@section('title', 'Edit Ticket - ' . $ticket->ticket_no)

@section('content')
<div class="container-fluid">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0"><i class="bi bi-pencil-square me-2 text-warning"></i>Edit Ticket: <span class="text-primary">{{ $ticket->ticket_no }}</span></h4>
            <small class="text-muted">Update ticket details below</small>
        </div>
        <a href="{{ route('admin.tickets.show', $ticket->id) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back to Ticket</a>
    </div>

    <form id="ticketForm" novalidate>
        @csrf @method('PUT')
        <div class="row g-4">
            {{-- ══════ LEFT COLUMN (8-col) ══════ --}}
            <div class="col-lg-8">

                {{-- Vendor --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-2">
                        <h6 class="mb-0"><i class="bi bi-building me-2 text-primary"></i>Vendor Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Vendor <span class="text-danger">*</span></label>
                                <select name="vendor_id" id="vendor_id" class="form-select select2" required>
                                    <option value="">Select Vendor</option>
                                    @foreach($vendors as $v)
                                    <option value="{{ $v->id }}" {{ $ticket->vendor_id == $v->id ? 'selected' : '' }}>{{ $v->vendor_name }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Branch <span class="text-danger">*</span></label>
                                <select name="vendor_branch_id" id="vendor_branch_id" class="form-select select2" required>
                                    <option value="">Select Branch</option>
                                    @foreach($branches as $b)
                                    <option value="{{ $b->id }}" {{ $ticket->vendor_branch_id == $b->id ? 'selected' : '' }}>{{ $b->branch_name }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Vendor Ticket Ref No</label>
                                <input type="text" name="vendor_ticket_ref_no" class="form-control" value="{{ $ticket->vendor_ticket_ref_no }}">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Location & Merchant --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-2">
                        <h6 class="mb-0"><i class="bi bi-geo-alt me-2 text-success"></i>Location & Merchant Details</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">State <span class="text-danger">*</span></label>
                                <select name="state_id" id="state_id" class="form-select select2" required>
                                    <option value="">Select State</option>
                                    @foreach($states as $s)
                                    <option value="{{ $s->id }}" {{ $ticket->state_id == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">District / City <span class="text-danger">*</span></label>
                                <select name="city_id" id="city_id" class="form-select select2" required>
                                    <option value="">Select District</option>
                                    @foreach($cities as $c)
                                    <option value="{{ $c->id }}" {{ $ticket->city_id == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Contact Number <span class="text-danger">*</span></label>
                                <input type="text" name="contact_number" class="form-control" required value="{{ $ticket->contact_number }}">
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Merchant Name <span class="text-danger">*</span></label>
                                <input type="text" name="merchant_name" class="form-control" required value="{{ $ticket->merchant_name }}">
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Merchant Address <span class="text-danger">*</span></label>
                                <textarea name="merchant_address" class="form-control" rows="1" required>{{ $ticket->merchant_address }}</textarea>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Job Configuration --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-2">
                        <h6 class="mb-0"><i class="bi bi-diagram-3 me-2 text-info"></i>Job Configuration</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Job Category <span class="text-danger">*</span></label>
                                <select name="job_category_id" id="job_category_id" class="form-select" required>
                                    <option value="">Select Category</option>
                                    @foreach($jobCategories as $cat)
                                    <option value="{{ $cat->id }}" data-slug="{{ $cat->slug }}" {{ $ticket->job_category_id == $cat->id ? 'selected' : '' }}>{{ $cat->category_name }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Job Type <span class="text-danger">*</span></label>
                                <select name="job_type_id" id="job_type_id" class="form-select" required>
                                    <option value="">Select Job Type</option>
                                    @foreach($jobTypes as $jt)
                                    <option value="{{ $jt->id }}" data-slug="{{ $jt->slug }}" {{ $ticket->job_type_id == $jt->id ? 'selected' : '' }}>{{ $jt->job_title }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Price (RM)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">RM</span>
                                    <input type="text" name="price" id="price_display" class="form-control bg-light fw-bold" readonly value="{{ number_format($ticket->price ?? 0, 2) }}">
                                </div>
                                <div id="priceHint"></div>
                            </div>
                        </div>

                        {{-- Dynamic Device Fields --}}
                        <div class="row g-3 mt-1" id="deviceFieldsRow">
                            <div class="col-md-4 device-field" id="terminalIdGroup">
                                <label class="form-label">Terminal ID <span class="text-danger terminal-required-star">*</span></label>
                                <input type="text" name="terminal_id" id="terminal_id" class="form-control" value="{{ $ticket->terminal_id }}">
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-4 device-field" id="routerIdGroup">
                                <label class="form-label">Router ID <span class="text-danger router-required-star">*</span></label>
                                <input type="text" name="router_id" id="router_id" class="form-control" value="{{ $ticket->router_id }}">
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-4 device-field" id="oldTerminalIdGroup">
                                <label class="form-label">Old Terminal ID</label>
                                <input type="text" name="old_terminal_id" class="form-control" value="{{ $ticket->old_terminal_id }}">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Description & Schedule --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-2">
                        <h6 class="mb-0"><i class="bi bi-card-text me-2 text-warning"></i>Description & Schedule</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Description <span class="text-danger">*</span></label>
                                <textarea name="description" class="form-control" rows="3" required>{{ $ticket->description }}</textarea>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Expected Start Date</label>
                                <input type="date" name="expected_start_date" class="form-control" value="{{ $ticket->expected_start_date?->format('Y-m-d') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Expected End Date</label>
                                <input type="date" name="expected_end_date" class="form-control" value="{{ $ticket->expected_end_date?->format('Y-m-d') }}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ══════ RIGHT COLUMN (4-col) ══════ --}}
            <div class="col-lg-4">

                {{-- Assignment --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-2">
                        <h6 class="mb-0"><i class="bi bi-people me-2 text-danger"></i>Assignment</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Priority <span class="text-danger">*</span></label>
                            <select name="priority" class="form-select" required>
                                @foreach(\App\Models\Ticket::getPriorities() as $key => $label)
                                <option value="{{ $key }}" {{ $ticket->priority === $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Supervisor <span class="text-danger">*</span></label>
                            <select name="supervisor_id" id="supervisor_id" class="form-select select2" required>
                                <option value="">Select Supervisor</option>
                                @foreach($supervisors as $sv)
                                <option value="{{ $sv->id }}" data-type="{{ $sv->supervisor_type }}" {{ $ticket->supervisor_id == $sv->id ? 'selected' : '' }}>{{ $sv->name }} ({{ ucfirst($sv->supervisor_type ?? 'N/A') }})</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div id="supervisorTypeInfo" class="mb-3">
                            <div id="supervisorTypeBadge"></div>
                        </div>
                        <div class="mb-0" id="technicianGroup">
                            <label class="form-label">Assign Technician</label>
                            <select name="technician_id" id="technician_id" class="form-select select2">
                                <option value="">Assign Later</option>
                                @foreach($technicians as $t)
                                <option value="{{ $t->id }}" {{ $ticket->technician_id == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Submit --}}
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-warning" id="btnSubmit">
                        <i class="bi bi-pencil-square me-2"></i>Update Ticket
                    </button>
                    <a href="{{ route('admin.tickets.show', $ticket->id) }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    const baseUrl = '/admin/tickets';
    let currentSupervisorType = '{{ $ticket->supervisor?->supervisor_type }}';

    $('.select2').select2({ theme: 'bootstrap-5', width: '100%' });

    // Init supervisor type + device fields on load
    initSupervisorType();
    @if($ticket->jobCategory)
    toggleDeviceFields('{{ $ticket->jobCategory->slug }}');
    @endif
    @if($ticket->jobType && $ticket->jobType->isReplacement())
    $('#oldTerminalIdGroup').show();
    @endif

    function initSupervisorType() {
        if (currentSupervisorType === 'internal') {
            $('#supervisorTypeBadge').html('<span class="badge bg-success"><i class="bi bi-building me-1"></i>Internal — assign technicians, no claims</span>');
            $('#technicianGroup').show();
        } else if (currentSupervisorType === 'external') {
            $('#supervisorTypeBadge').html('<span class="badge bg-warning text-dark"><i class="bi bi-person-badge me-1"></i>External — direct assignment, claims applicable</span>');
            $('#technicianGroup').hide();
        }
    }

    // ── Vendor → Branch ──
    $('#vendor_id').on('change', function() {
        $.get(baseUrl + '/ajax/vendor-branches', { vendor_id: $(this).val() }, function(data) {
            let o = '<option value="">Select Branch</option>';
            data.forEach(b => o += `<option value="${b.id}">${b.branch_name}</option>`);
            $('#vendor_branch_id').html(o).trigger('change.select2');
        });
    });

    // ── State → City ──
    $('#state_id').on('change', function() {
        $.get(baseUrl + '/ajax/cities', { state_id: $(this).val() }, function(data) {
            let o = '<option value="">Select District</option>';
            data.forEach(c => o += `<option value="${c.id}">${c.name}</option>`);
            $('#city_id').html(o).trigger('change.select2');
        });
    });

    // ── Supervisor change ──
    $('#supervisor_id').on('change', function() {
        currentSupervisorType = $(this).find(':selected').data('type');
        initSupervisorType();
        let svId = $(this).val();
        if (!svId) return;
        if (currentSupervisorType === 'internal') {
            $.get(baseUrl + '/ajax/technicians', { supervisor_id: svId }, function(data) {
                let o = '<option value="">Assign Later</option>';
                data.forEach(t => o += `<option value="${t.id}">${t.name}</option>`);
                $('#technician_id').html(o).trigger('change.select2');
            });
        }
        refreshPrice();
    });

    // ── Job Category → device fields + price ──
    $('#job_category_id').on('change', function() {
        toggleDeviceFields($(this).find(':selected').data('slug'));
        refreshPrice();
    });

    // ── Job Type → replacement check + price ──
    $('#job_type_id').on('change', function() {
        let slug = $(this).find(':selected').data('slug') || '';
        if (slug.includes('replacement')) { $('#oldTerminalIdGroup').show(); } else { $('#oldTerminalIdGroup').hide(); }
        refreshPrice();
    });

    function toggleDeviceFields(slug) {
        $('.device-field').hide(); $('.terminal-required-star, .router-required-star').hide();
        $('#terminal_id, #router_id').removeAttr('required');
        switch(slug) {
            case 'terminal':
                $('#terminalIdGroup').show(); $('.terminal-required-star').show(); $('#terminal_id').attr('required', true); break;
            case 'router':
                $('#routerIdGroup').show(); $('.router-required-star').show(); $('#router_id').attr('required', true); break;
            case 'project':
                $('#terminalIdGroup, #routerIdGroup').show(); break;
        }
    }

    // ── Price fetch ──
    function refreshPrice() {
        let svId = $('#supervisor_id').val(), catId = $('#job_category_id').val(), typeId = $('#job_type_id').val();
        if (!svId || !catId || !typeId) {
            $('#priceHint').html('<small class="text-muted">Select all 3 fields</small>');
            return;
        }
        $('#priceHint').html('<small class="text-info"><i class="bi bi-hourglass-split me-1"></i>Fetching...</small>');
        $.ajax({
            url: baseUrl + '/ajax/price',
            data: { supervisor_id: svId, job_category_id: catId, job_type_id: typeId },
            success: function(data) {
                let p = parseFloat(data.price || 0);
                $('#price_display').val(p.toFixed(2));
                if (p > 0) {
                    $('#price_display').addClass('text-success');
                    $('#priceHint').html('<small class="text-success"><i class="bi bi-check-circle me-1"></i>Price loaded</small>');
                } else {
                    $('#price_display').removeClass('text-success');
                    $('#priceHint').html('<small class="text-warning"><i class="bi bi-exclamation-triangle me-1"></i>No pricing configured</small>');
                }
            },
            error: function() { $('#priceHint').html('<small class="text-danger"><i class="bi bi-x-circle me-1"></i>Failed</small>'); }
        });
    }

    // ── Submit ──
    $('#ticketForm').on('submit', function(e) {
        e.preventDefault();
        let btn = $('#btnSubmit');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Updating...');

        let fd = new FormData(this);
        fd.set('price', $('#price_display').val());

        $.ajax({
            url: '{{ route("admin.tickets.update", $ticket->id) }}',
            method: 'POST', data: fd, processData: false, contentType: false,
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            success: function(res) {
                if (res.success) { showToast(res.message, 'success'); window.location.href = res.redirect; }
                else showToast(res.message, 'error');
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    let errors = xhr.responseJSON?.errors || {};
                    $('.is-invalid').removeClass('is-invalid');
                    let firstEl = null;
                    Object.keys(errors).forEach(f => {
                        let el = $('[name="'+f+'"]');
                        el.addClass('is-invalid').siblings('.invalid-feedback').text(errors[f][0]);
                        if (!firstEl) firstEl = el;
                    });
                    if (firstEl) $('html, body').animate({ scrollTop: firstEl.offset().top - 100 }, 300);
                    showToast('Please fix the validation errors.', 'error');
                } else showToast(xhr.responseJSON?.message || 'Error', 'error');
            },
            complete: () => btn.prop('disabled', false).html('<i class="bi bi-pencil-square me-2"></i>Update Ticket')
        });
    });
});
</script>
@endpush
