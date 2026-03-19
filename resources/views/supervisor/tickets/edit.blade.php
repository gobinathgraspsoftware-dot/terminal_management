@extends('layouts.app')
@section('title', 'Edit Ticket - ' . $ticket->ticket_no)

@section('content')
@php
    $user = auth()->user();
    $isInternal = $user->isInternalSupervisor();
@endphp
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0"><i class="bi bi-pencil me-2"></i>Edit: {{ $ticket->ticket_no }}</h4>
        <a href="{{ route('supervisor.tickets.show', $ticket->id) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
    </div>

    <form id="ticketForm" novalidate>
        @csrf @method('PUT')
        <div class="row g-4">
            <div class="col-lg-8">
                {{-- Vendor --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white"><h6 class="mb-0">Vendor Information</h6></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label">Vendor <span class="text-danger">*</span></label>
                                <select name="vendor_id" id="vendor_id" class="form-select select2" required><option value="">Select</option>@foreach($vendors as $v)<option value="{{ $v->id }}" {{ $ticket->vendor_id == $v->id ? 'selected' : '' }}>{{ $v->vendor_name }}</option>@endforeach</select></div>
                            <div class="col-md-6"><label class="form-label">Branch <span class="text-danger">*</span></label>
                                <select name="vendor_branch_id" id="vendor_branch_id" class="form-select select2" required><option value="">Select</option>@foreach($branches as $b)<option value="{{ $b->id }}" {{ $ticket->vendor_branch_id == $b->id ? 'selected' : '' }}>{{ $b->branch_name }}</option>@endforeach</select></div>
                            <div class="col-md-6"><label class="form-label">Vendor Ref</label><input type="text" name="vendor_ticket_ref_no" class="form-control" value="{{ $ticket->vendor_ticket_ref_no }}"></div>
                        </div>
                    </div>
                </div>

                {{-- Location --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white"><h6 class="mb-0">Location & Merchant</h6></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4"><label class="form-label">State <span class="text-danger">*</span></label>
                                <select name="state_id" id="state_id" class="form-select select2" required><option value="">Select</option>@foreach($states as $s)<option value="{{ $s->id }}" {{ $ticket->state_id == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>@endforeach</select></div>
                            <div class="col-md-4"><label class="form-label">District <span class="text-danger">*</span></label>
                                <select name="city_id" id="city_id" class="form-select select2" required><option value="">Select</option>@foreach($cities as $c)<option value="{{ $c->id }}" {{ $ticket->city_id == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>@endforeach</select></div>
                            <div class="col-md-4"><label class="form-label">TID <span class="text-danger">*</span></label><input type="text" name="tid" class="form-control" required value="{{ $ticket->tid }}"></div>
                            <div class="col-md-6"><label class="form-label">Merchant <span class="text-danger">*</span></label><input type="text" name="merchant_name" class="form-control" required value="{{ $ticket->merchant_name }}"></div>
                            <div class="col-md-6"><label class="form-label">Contact <span class="text-danger">*</span></label><input type="text" name="contact_number" class="form-control" required value="{{ $ticket->contact_number }}"></div>
                            <div class="col-12"><label class="form-label">Address <span class="text-danger">*</span></label><textarea name="merchant_address" class="form-control" rows="2" required>{{ $ticket->merchant_address }}</textarea></div>
                        </div>
                    </div>
                </div>

                {{-- Job Category & Type — INDEPENDENT --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white"><h6 class="mb-0">Job Category & Type</h6></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4"><label class="form-label">Job Category <span class="text-danger">*</span></label>
                                <select name="job_category_id" id="job_category_id" class="form-select" required>
                                    <option value="">Select</option>
                                    @foreach($jobCategories as $cat)<option value="{{ $cat->id }}" data-slug="{{ $cat->slug }}" {{ $ticket->job_category_id == $cat->id ? 'selected' : '' }}>{{ $cat->category_name }}</option>@endforeach
                                </select></div>
                            <div class="col-md-4"><label class="form-label">Job Type <span class="text-danger">*</span></label>
                                <select name="job_type_id" id="job_type_id" class="form-select" required>
                                    <option value="">Select</option>
                                    @foreach($jobTypes as $jt)<option value="{{ $jt->id }}" data-slug="{{ $jt->slug }}" {{ $ticket->job_type_id == $jt->id ? 'selected' : '' }}>{{ $jt->job_title }}</option>@endforeach
                                </select></div>
                            <div class="col-md-4"><label class="form-label">Price (RM)</label><input type="text" name="price" id="price_display" class="form-control bg-light" readonly value="{{ number_format($ticket->price, 2) }}"><div id="priceHint"></div></div>
                            <div class="col-md-6 device-field" id="terminalIdGroup"><label class="form-label">Terminal ID</label><input type="text" name="terminal_id" class="form-control" value="{{ $ticket->terminal_id }}"></div>
                            <div class="col-md-6 device-field" id="routerIdGroup"><label class="form-label">Router ID</label><input type="text" name="router_id" class="form-control" value="{{ $ticket->router_id }}"></div>
                            <div class="col-md-6 device-field" id="oldTerminalIdGroup"><label class="form-label">Old Terminal ID</label><input type="text" name="old_terminal_id" class="form-control" value="{{ $ticket->old_terminal_id }}"></div>
                        </div>
                    </div>
                </div>

                {{-- Details --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white"><h6 class="mb-0">Details</h6></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12"><label class="form-label">Description <span class="text-danger">*</span></label><textarea name="description" class="form-control" rows="4" required>{{ $ticket->description }}</textarea></div>
                            <div class="col-md-4"><label class="form-label">Expected Start</label><input type="date" name="expected_start_date" class="form-control" value="{{ $ticket->expected_start_date?->format('Y-m-d') }}"></div>
                            <div class="col-md-4"><label class="form-label">Expected End</label><input type="date" name="expected_end_date" class="form-control" value="{{ $ticket->expected_end_date?->format('Y-m-d') }}"></div>
                            <div class="col-md-4"><label class="form-label">SLA Hours</label><input type="number" name="sla_hours" class="form-control" value="{{ $ticket->sla_hours }}"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white"><h6 class="mb-0">Assignment</h6></div>
                    <div class="card-body">
                        <div class="mb-3"><label class="form-label">Priority <span class="text-danger">*</span></label>
                            <select name="priority" class="form-select" required>@foreach(\App\Models\Ticket::getPriorities() as $key => $label)<option value="{{ $key }}" {{ $ticket->priority === $key ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div>
                        @if($isInternal)
                        <div class="mb-3"><label class="form-label">Technician</label>
                            <select name="technician_id" class="form-select select2"><option value="">Assign Later</option>@foreach($technicians as $t)<option value="{{ $t->id }}" {{ $ticket->technician_id == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>@endforeach</select></div>
                        @endif
                    </div>
                </div>
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary" id="btnSubmit"><i class="bi bi-check-lg me-1"></i>Update Ticket</button>
                    <a href="{{ route('supervisor.tickets.show', $ticket->id) }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    const baseUrl = '/supervisor/tickets';
    $('.select2').select2({ theme: 'bootstrap-5', width: '100%' });

    @if($ticket->jobCategory)
    toggleDeviceFields('{{ $ticket->jobCategory->slug }}');
    @endif
    @if($ticket->jobType && $ticket->jobType->isReplacement())
    $('#oldTerminalIdGroup').show();
    @endif

    $('#vendor_id').on('change', function() {
        $.get(baseUrl + '/ajax/vendor-branches', { vendor_id: $(this).val() }, function(data) {
            let opts = '<option value="">Select</option>';
            data.forEach(b => opts += `<option value="${b.id}">${b.branch_name}</option>`);
            $('#vendor_branch_id').html(opts).trigger('change.select2');
        });
    });

    $('#state_id').on('change', function() {
        $.get(baseUrl + '/ajax/cities', { state_id: $(this).val() }, function(data) {
            let opts = '<option value="">Select</option>';
            data.forEach(c => opts += `<option value="${c.id}">${c.name}</option>`);
            $('#city_id').html(opts).trigger('change.select2');
        });
    });

    // Job Category → ONLY device fields + price (no cascade)
    $('#job_category_id').on('change', function() {
        toggleDeviceFields($(this).find(':selected').data('slug'));
        refreshPrice();
    });

    // Job Type → ONLY replacement check + price (independent)
    $('#job_type_id').on('change', function() {
        let slug = $(this).find(':selected').data('slug') || '';
        $('#oldTerminalIdGroup').toggle(slug.includes('replacement'));
        refreshPrice();
    });

    function toggleDeviceFields(slug) {
        $('.device-field').hide();
        if (slug === 'terminal') $('#terminalIdGroup').show();
        else if (slug === 'router') $('#routerIdGroup').show();
        else if (slug === 'project') $('#terminalIdGroup, #routerIdGroup').show();
    }

    function refreshPrice() {
        let catId = $('#job_category_id').val(), typeId = $('#job_type_id').val();
        if (!catId || !typeId) { $('#priceHint').html('<small class="text-muted">Select category & type</small>'); return; }
        $('#priceHint').html('<small class="text-info"><i class="bi bi-hourglass-split me-1"></i>Fetching...</small>');
        $.ajax({
            url: baseUrl + '/ajax/price',
            data: { job_category_id: catId, job_type_id: typeId },
            success: function(data) {
                let price = parseFloat(data.price || 0);
                $('#price_display').val(price.toFixed(2));
                if (price > 0) {
                    $('#price_display').addClass('text-success fw-bold').removeClass('text-danger');
                    $('#priceHint').html('<small class="text-success"><i class="bi bi-check-circle me-1"></i>Price loaded</small>');
                } else {
                    $('#priceHint').html('<small class="text-warning"><i class="bi bi-exclamation-triangle me-1"></i>No pricing configured</small>');
                }
            },
            error: function(xhr) {
                console.error('Price fetch failed:', xhr.status);
                $('#priceHint').html('<small class="text-danger"><i class="bi bi-x-circle me-1"></i>Price fetch failed</small>');
            }
        });
    }

    $('#ticketForm').on('submit', function(e) {
        e.preventDefault();
        let btn = $('#btnSubmit'); btn.prop('disabled', true);
        let fd = new FormData(this);
        fd.set('price', $('#price_display').val());
        $.ajax({
            url: '{{ route("supervisor.tickets.update", $ticket->id) }}',
            method: 'POST', data: fd, processData: false, contentType: false,
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            success: function(res) { if (res.success) { showToast(res.message, 'success'); window.location.href = res.redirect; } else showToast(res.message, 'error'); },
            error: function(xhr) {
                if (xhr.status === 422) {
                    let errors = xhr.responseJSON?.errors || {};
                    Object.keys(errors).forEach(f => $('[name="'+f+'"]').addClass('is-invalid').siblings('.invalid-feedback').text(errors[f][0]));
                } else showToast(xhr.responseJSON?.message || 'Error', 'error');
            },
            complete: () => btn.prop('disabled', false)
        });
    });
});
</script>
@endpush
