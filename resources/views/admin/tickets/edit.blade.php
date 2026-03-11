@extends('layouts.app')

@section('title', 'Edit Ticket ' . $ticket->ticket_no)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="bi bi-pencil me-2"></i>Edit Ticket {{ $ticket->ticket_no }}</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.tickets.index') }}">Tickets</a></li>
                    <li class="breadcrumb-item active">Edit</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form id="ticketForm">
                @csrf
                @method('PUT')
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="vendor_id" class="form-label">Vendor <span class="text-danger">*</span></label>
                        <select name="vendor_id" id="vendor_id" class="form-select select2" required>
                            <option value="">Select Vendor</option>
                            @foreach($vendors as $vendor)
                            <option value="{{ $vendor->id }}" {{ $ticket->vendor_id == $vendor->id ? 'selected' : '' }}>{{ $vendor->vendor_name }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="col-md-6">
                        <label for="vendor_branch_id" class="form-label">Branch</label>
                        <select name="vendor_branch_id" id="vendor_branch_id" class="form-select">
                            <option value="">Select Branch</option>
                            @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ $ticket->vendor_branch_id == $branch->id ? 'selected' : '' }}>{{ $branch->branch_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="state_id" class="form-label">State</label>
                        <select name="state_id" id="state_id" class="form-select select2">
                            <option value="">Select State</option>
                            @foreach($states as $state)
                            <option value="{{ $state->id }}" {{ $ticket->state_id == $state->id ? 'selected' : '' }}>{{ $state->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="city_id" class="form-label">District</label>
                        <select name="city_id" id="city_id" class="form-select">
                            <option value="">Select District</option>
                            @foreach($cities as $city)
                            <option value="{{ $city->id }}" {{ $ticket->city_id == $city->id ? 'selected' : '' }}>{{ $city->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="supervisor_id" class="form-label">Supervisor <span class="text-danger">*</span></label>
                        <select name="supervisor_id" id="supervisor_id" class="form-select select2" required>
                            <option value="">Select Supervisor</option>
                            @foreach($supervisors as $sup)
                            <option value="{{ $sup->id }}" {{ $ticket->supervisor_id == $sup->id ? 'selected' : '' }}>{{ $sup->name }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="col-md-6">
                        <label for="job_type_id" class="form-label">Job Type <span class="text-danger">*</span></label>
                        <select name="job_type_id" id="job_type_id" class="form-select" required>
                            <option value="">Select Job Type</option>
                            @foreach($jobTypes as $jt)
                            <option value="{{ $jt->id }}" {{ $ticket->job_type_id == $jt->id ? 'selected' : '' }}>{{ $jt->job_title }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="col-md-6">
                        <label for="technician_id" class="form-label">Assignee</label>
                        <select name="technician_id" id="technician_id" class="form-select select2">
                            <option value="">Unassigned</option>
                            @foreach($technicians as $tech)
                            <option value="{{ $tech->id }}" {{ $ticket->technician_id == $tech->id ? 'selected' : '' }}>{{ $tech->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="priority" class="form-label">Priority <span class="text-danger">*</span></label>
                        <select name="priority" id="priority" class="form-select" required>
                            @foreach(\App\Models\Ticket::getPriorities() as $k => $v)
                            <option value="{{ $k }}" {{ $ticket->priority === $k ? 'selected' : '' }}>{{ $v }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="sla_hours" class="form-label">SLA (Hours)</label>
                        <input type="number" name="sla_hours" id="sla_hours" class="form-control" value="{{ $ticket->sla_hours }}" min="1" max="720">
                    </div>
                    <div class="col-12">
                        <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                        <textarea name="description" id="description" class="form-control" rows="4" required>{{ $ticket->description }}</textarea>
                        <div class="invalid-feedback"></div>
                    </div>
                </div>
                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary" id="btnSubmit">
                        <i class="bi bi-check-circle me-1"></i> Update Ticket
                    </button>
                    <a href="{{ route('admin.tickets.show', $ticket->id) }}" class="btn btn-secondary">
                        <i class="bi bi-x-circle me-1"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('.select2').select2({ theme: 'bootstrap-5', width: '100%' });

    $('#vendor_id').on('change', function() {
        var vendorId = $(this).val();
        var $branch = $('#vendor_branch_id');
        $branch.html('<option value="">Loading...</option>');
        if (!vendorId) { $branch.html('<option value="">Select Branch</option>'); return; }
        $.get('{{ route("admin.tickets.ajax.vendor-branches") }}', { vendor_id: vendorId }, function(data) {
            $branch.html('<option value="">Select Branch</option>');
            data.forEach(function(b) { $branch.append('<option value="' + b.id + '">' + b.branch_name + '</option>'); });
        });
    });

    $('#state_id').on('change', function() {
        var stateId = $(this).val();
        var $city = $('#city_id');
        $city.html('<option value="">Loading...</option>');
        if (!stateId) { $city.html('<option value="">Select District</option>'); return; }
        $.get('{{ route("admin.tickets.ajax.cities") }}', { state_id: stateId }, function(data) {
            $city.html('<option value="">Select District</option>');
            data.forEach(function(c) { $city.append('<option value="' + c.id + '">' + c.name + '</option>'); });
        });
    });

    $('#supervisor_id').on('change', function() {
        var supId = $(this).val();
        var $tech = $('#technician_id');
        $.get('{{ route("admin.tickets.ajax.technicians") }}', { supervisor_id: supId }, function(data) {
            var current = '{{ $ticket->technician_id }}';
            $tech.html('<option value="">Unassigned</option>');
            data.forEach(function(t) { $tech.append('<option value="' + t.id + '"' + (t.id == current ? ' selected' : '') + '>' + t.name + '</option>'); });
            $tech.trigger('change.select2');
        });
    });

    $('#ticketForm').on('submit', function(e) {
        e.preventDefault();
        var $btn = $('#btnSubmit');
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Updating...');
        $('.is-invalid').removeClass('is-invalid');

        $.ajax({
            url: '{{ route("admin.tickets.update", $ticket->id) }}',
            method: 'POST',
            data: $(this).serialize(),
            success: function(res) {
                if (res.success) {
                    showToast(res.message);
                    window.location.href = res.redirect;
                }
            },
            error: function(xhr) {
                $btn.prop('disabled', false).html('<i class="bi bi-check-circle me-1"></i> Update Ticket');
                if (xhr.status === 422) {
                    var errors = xhr.responseJSON.errors;
                    Object.keys(errors).forEach(function(field) {
                        var $el = $('[name="' + field + '"]');
                        $el.addClass('is-invalid');
                        $el.siblings('.invalid-feedback').text(errors[field][0]);
                    });
                } else {
                    showToast(xhr.responseJSON?.message || 'An error occurred.', 'error');
                }
            }
        });
    });
});
</script>
@endpush
