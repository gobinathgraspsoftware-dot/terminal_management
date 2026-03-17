@php use App\Models\Ticket; @endphp
@extends('layouts.app')

@section('title', 'Ticket ' . $ticket->ticket_no)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">
                <i class="bi bi-ticket-detailed me-2"></i>{{ $ticket->ticket_no }}
                {!! Ticket::getStatusBadge($ticket->status) !!}
                {!! Ticket::getPriorityBadge($ticket->priority) !!}
                @if($ticket->isSlaBreach()) <span class="badge bg-danger"><i class="bi bi-exclamation-triangle me-1"></i>SLA BREACHED</span> @endif
            </h4>
            <small class="text-muted">Created {{ $ticket->created_at->format('d M Y H:i') }} by {{ $ticket->creator?->name ?? '-' }}</small>
        </div>
        <div>
            @if(!in_array($ticket->status, [Ticket::STATUS_DONE_SUCCESS, Ticket::STATUS_DONE_FAIL, Ticket::STATUS_CLOSED]))
                @can('edit_tickets')
                <a href="{{ route('supervisor.tickets.edit', $ticket->id) }}" class="btn btn-outline-primary btn-sm me-1"><i class="bi bi-pencil me-1"></i>Edit</a>
                @endcan
            @endif
            <a href="{{ route('supervisor.tickets.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            {{-- Details --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white"><h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Ticket Details</h6></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4"><strong>Vendor:</strong><br>{{ $ticket->vendor?->vendor_name ?? '-' }}</div>
                        <div class="col-md-4"><strong>Vendor Ref:</strong><br>{{ $ticket->vendor_ticket_ref_no ?? '-' }}</div>
                        <div class="col-md-4"><strong>Branch:</strong><br>{{ $ticket->vendorBranch?->branch_name ?? '-' }}</div>
                        <div class="col-md-4"><strong>State:</strong><br>{{ $ticket->state?->name ?? '-' }}</div>
                        <div class="col-md-4"><strong>District:</strong><br>{{ $ticket->city?->name ?? '-' }}</div>
                        <div class="col-md-4"><strong>Job Type:</strong><br>{{ $ticket->jobType?->job_title ?? '-' }}</div>
                        <div class="col-md-4"><strong>TID:</strong><br>{{ $ticket->tid ?? '-' }}</div>
                        <div class="col-md-4"><strong>Merchant:</strong><br>{{ $ticket->merchant_name ?? '-' }}</div>
                        <div class="col-md-4"><strong>Contact:</strong><br>{{ $ticket->contact_number ?? '-' }}</div>
                        <div class="col-12"><strong>Address:</strong><br>{{ $ticket->merchant_address ?? '-' }}</div>
                        <div class="col-12"><strong>Description:</strong><br>{{ $ticket->description }}</div>
                    </div>
                </div>
            </div>

            {{-- SLA --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white"><h6 class="mb-0"><i class="bi bi-clock me-2"></i>SLA & Timeline</h6></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3"><strong>SLA:</strong><br>{{ $ticket->sla_hours }}h</div>
                        <div class="col-md-3"><strong>Deadline:</strong><br>{{ $ticket->sla_deadline?->format('d M Y H:i') ?? '-' }}</div>
                        <div class="col-md-3"><strong>Remaining:</strong><br>{{ $ticket->sla_remaining ?? '-' }}</div>
                        <div class="col-md-3"><strong>Technician:</strong><br>{{ $ticket->technician?->name ?? 'Unassigned' }}</div>
                    </div>
                </div>
            </div>

            {{-- Assign --}}
            @if(!in_array($ticket->status, [Ticket::STATUS_DONE_SUCCESS, Ticket::STATUS_DONE_FAIL, Ticket::STATUS_CLOSED]))
                @can('assign_tickets')
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white"><h6 class="mb-0"><i class="bi bi-person-check me-2"></i>Assign Technician</h6></div>
                    <div class="card-body">
                        <form id="assignForm">
                            <div class="row g-2 align-items-end">
                                <div class="col-md-5">
                                    <select name="technician_id" class="form-select" required>
                                        <option value="">Select</option>
                                        @foreach($technicians as $t)
                                            <option value="{{ $t->id }}" {{ $ticket->technician_id == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-5"><input type="text" name="remarks" class="form-control" placeholder="Remarks"></div>
                                <div class="col-md-2"><button type="submit" class="btn btn-info w-100"><i class="bi bi-person-check"></i></button></div>
                            </div>
                        </form>
                    </div>
                </div>
                @endcan
            @endif

            {{-- Status Update --}}
            @if(count($allowedTransitions) > 0)
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-dark text-white"><h6 class="mb-0"><i class="bi bi-arrow-repeat me-2"></i>Update Status</h6></div>
                <div class="card-body">
                    <form id="statusForm" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label">New Status <span class="text-danger">*</span></label>
                            <select name="status" id="new_status" class="form-select" required>
                                <option value="">Select Status</option>
                                @foreach($allowedTransitions as $ts)
                                    <option value="{{ $ts }}">{{ $statuses[$ts] ?? ucfirst(str_replace('_', ' ', $ts)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div id="reschedule_section" class="mb-3" style="display:none;">
                            <label class="form-label">Reschedule Reason <span class="text-danger">*</span></label>
                            <textarea name="reschedule_reason" id="reschedule_reason" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Remarks</label>
                            <textarea name="remarks" class="form-control" rows="2" placeholder="Optional remarks"></textarea>
                        </div>
                        <div id="proof_section" style="display:none;">
                            <div class="alert alert-info py-2 mb-3"><i class="bi bi-camera me-1"></i><strong>Proof required.</strong> <span id="proof_help_text"></span></div>
                            <div id="proof_scheduled" style="display:none;">
                                <div class="mb-3"><label class="form-label">WhatsApp Screenshot <span class="text-danger">*</span></label><input type="file" name="proof_files[whatsapp_screenshot]" class="form-control" accept="image/*,.pdf"></div>
                                <div class="mb-3"><label class="form-label">Call Log Screenshot <span class="text-danger">*</span></label><input type="file" name="proof_files[call_log_screenshot]" class="form-control" accept="image/*,.pdf"></div>
                            </div>
                            <div id="proof_done_success" style="display:none;">
                                <div class="mb-3"><label class="form-label">Test Slip Image <span class="text-danger">*</span></label><input type="file" name="proof_files[test_slip]" class="form-control" accept="image/*,.pdf"></div>
                            </div>
                            <div id="proof_done_fail" style="display:none;">
                                <div class="mb-3"><label class="form-label">Service Form Image <span class="text-danger">*</span></label><input type="file" name="proof_files[service_form]" class="form-control" accept="image/*,.pdf"></div>
                            </div>
                        </div>
                        <button type="submit" id="btnStatusSubmit" class="btn btn-dark"><i class="bi bi-check-circle me-1"></i>Update Status</button>
                    </form>
                </div>
            </div>
            @endif

            {{-- Status History --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white"><h6 class="mb-0"><i class="bi bi-clock-history me-2"></i>Status History</h6></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light"><tr><th>Date</th><th>From</th><th>To</th><th>By</th><th>Remarks</th><th>Proofs</th></tr></thead>
                            <tbody>
                                @forelse($ticket->statusHistory as $h)
                                <tr>
                                    <td>{{ $h->created_at->format('d M Y H:i') }}</td>
                                    <td>{!! $h->from_status ? Ticket::getStatusBadge($h->from_status) : '-' !!}</td>
                                    <td>{!! Ticket::getStatusBadge($h->to_status) !!}</td>
                                    <td>{{ $h->changedBy?->name ?? '-' }}</td>
                                    <td>{{ $h->remarks ?? '-' }}@if($h->reschedule_reason)<br><small class="text-warning"><strong>Reschedule:</strong> {{ $h->reschedule_reason }}</small>@endif</td>
                                    <td>
                                        @if($h->proofs && $h->proofs->count())
                                            @foreach($h->proofs as $p)
                                                <a href="{{ asset('storage/' . $p->file_path) }}" target="_blank" class="badge bg-primary text-decoration-none me-1"><i class="bi bi-file-earmark me-1"></i>{{ Ticket::getProofTypeLabels()[$p->proof_type] ?? $p->proof_type }}</a>
                                            @endforeach
                                        @else - @endif
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="6" class="text-center text-muted py-3">No history.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Comments --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white"><h6 class="mb-0"><i class="bi bi-chat-dots me-2"></i>Comments</h6></div>
                <div class="card-body">
                    <div id="commentsList">
                        @forelse($ticket->comments as $c)
                        <div class="d-flex mb-3">
                            <div class="flex-shrink-0"><div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width:36px;height:36px;font-size:14px;">{{ strtoupper(substr($c->user?->name ?? '?', 0, 1)) }}</div></div>
                            <div class="ms-3"><strong>{{ $c->user?->name }}</strong> <small class="text-muted ms-2">{{ $c->created_at->format('d M Y H:i') }}</small><p class="mb-0 mt-1">{{ $c->comment }}</p></div>
                        </div>
                        @empty
                        <p class="text-muted text-center mb-0" id="noComments">No comments yet.</p>
                        @endforelse
                    </div>
                    <hr>
                    <form id="commentForm">
                        <div class="input-group">
                            <textarea name="comment" id="comment_input" class="form-control" rows="2" placeholder="Add a comment..." required></textarea>
                            <button type="submit" class="btn btn-primary"><i class="bi bi-send"></i></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Claim --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white"><h6 class="mb-0"><i class="bi bi-cash-coin me-2"></i>Claim</h6></div>
                <div class="card-body">
                    <form id="claimForm">
                        <div class="mb-3"><label class="form-label">Mileage (KM)</label><input type="number" name="mileage" id="claim_mileage" class="form-control" step="0.01" min="0" value="{{ $ticket->mileage ?? 0 }}"></div>
                        <div class="mb-3"><label class="form-label">Rate</label><input type="text" class="form-control" readonly value="{{ number_format($ticket->mileage_rate ?? 0, 2) }}"></div>
                        <div class="mb-3"><label class="form-label">Amount</label><input type="text" id="claim_mileage_amount" class="form-control" readonly value="{{ number_format($ticket->mileage_amount ?? 0, 2) }}"></div>
                        <div class="mb-3"><label class="form-label">Remarks</label><input type="text" name="mileage_remarks" class="form-control" value="{{ $ticket->mileage_remarks }}"></div>
                        <div class="mb-3"><label class="form-label">Toll (RM)</label><input type="number" name="toll" id="claim_toll" class="form-control" step="0.01" min="0" value="{{ $ticket->toll ?? 0 }}"></div>
                        <div class="mb-3"><label class="form-label">Standby/Meal (RM)</label><input type="number" name="standby_meal" id="claim_meal" class="form-control" step="0.01" min="0" value="{{ $ticket->standby_meal ?? 0 }}"></div>
                        <div class="mb-3"><label class="form-label fw-bold text-success">Total (RM)</label><input type="text" id="claim_total" class="form-control fw-bold text-success" readonly value="{{ number_format($ticket->total_claim_amount ?? 0, 2) }}"></div>
                        <button type="submit" class="btn btn-warning w-100"><i class="bi bi-pencil-square me-1"></i>Update Claim</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    var mileageRate = parseFloat('{{ $ticket->mileage_rate ?? 0 }}');

    $('#new_status').on('change', function() {
        var status = $(this).val();
        $('#proof_section, #proof_scheduled, #proof_done_success, #proof_done_fail, #reschedule_section').hide();
        $('#proof_section input[type=file]').val('');
        if (status === 'scheduled') { $('#reschedule_section, #proof_section, #proof_scheduled').show(); $('#proof_help_text').text('Upload WhatsApp screenshot and Call log screenshot.'); }
        else if (status === 'done_success') { $('#proof_section, #proof_done_success').show(); $('#proof_help_text').text('Upload Test Slip image.'); }
        else if (status === 'done_fail') { $('#proof_section, #proof_done_fail').show(); $('#proof_help_text').text('Upload Service Form image.'); }
    });

    $('#statusForm').on('submit', function(e) {
        e.preventDefault();
        var status = $('#new_status').val();
        if (!status) { showToast('Select a status.', 'error'); return; }
        if (status === 'scheduled') {
            if (!$('input[name="proof_files[whatsapp_screenshot]"]').val()) { showToast('WhatsApp screenshot required.', 'error'); return; }
            if (!$('input[name="proof_files[call_log_screenshot]"]').val()) { showToast('Call log screenshot required.', 'error'); return; }
            if (!$('#reschedule_reason').val().trim()) { showToast('Reschedule reason required.', 'error'); return; }
        }
        if (status === 'done_success' && !$('input[name="proof_files[test_slip]"]').val()) { showToast('Test slip required.', 'error'); return; }
        if (status === 'done_fail' && !$('input[name="proof_files[service_form]"]').val()) { showToast('Service form required.', 'error'); return; }

        var fd = new FormData(this);
        var btn = $('#btnStatusSubmit');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Updating...');
        $.ajax({
            url: '{{ route("supervisor.tickets.change-status", $ticket->id) }}',
            method: 'POST', data: fd, processData: false, contentType: false,
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            success: function(res) { if (res.success) { showToast(res.message, 'success'); setTimeout(function() { location.reload(); }, 1000); } else { showToast(res.message, 'error'); btn.prop('disabled', false).html('<i class="bi bi-check-circle me-1"></i>Update Status'); } },
            error: function(xhr) { showToast(xhr.responseJSON?.message || 'Error.', 'error'); btn.prop('disabled', false).html('<i class="bi bi-check-circle me-1"></i>Update Status'); }
        });
    });

    $('#assignForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: '{{ route("supervisor.tickets.assign", $ticket->id) }}', method: 'POST',
            data: $(this).serialize() + '&_token={{ csrf_token() }}',
            success: function(res) { if (res.success) { showToast(res.message, 'success'); setTimeout(function() { location.reload(); }, 1000); } else showToast(res.message, 'error'); },
            error: function(xhr) { showToast(xhr.responseJSON?.message || 'Error.', 'error'); }
        });
    });

    $('#claim_mileage, #claim_toll, #claim_meal').on('input', function() {
        var km = parseFloat($('#claim_mileage').val()) || 0, mAmt = km * mileageRate;
        $('#claim_mileage_amount').val(mAmt.toFixed(2));
        $('#claim_total').val((mAmt + (parseFloat($('#claim_toll').val())||0) + (parseFloat($('#claim_meal').val())||0)).toFixed(2));
    });

    $('#claimForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: '{{ route("supervisor.tickets.update-claim", $ticket->id) }}', method: 'POST',
            data: $(this).serialize() + '&_token={{ csrf_token() }}',
            success: function(res) { if (res.success) { showToast(res.message, 'success'); setTimeout(function() { location.reload(); }, 1000); } else showToast(res.message, 'error'); },
            error: function(xhr) { showToast(xhr.responseJSON?.message || 'Error.', 'error'); }
        });
    });

    $('#commentForm').on('submit', function(e) {
        e.preventDefault();
        var comment = $('#comment_input').val().trim();
        if (!comment) return;
        $.ajax({
            url: '{{ route("supervisor.tickets.comment", $ticket->id) }}', method: 'POST',
            data: { comment: comment, _token: '{{ csrf_token() }}' },
            success: function(res) {
                if (res.success) {
                    $('#noComments').remove();
                    var c = res.comment;
                    $('#commentsList').prepend('<div class="d-flex mb-3"><div class="flex-shrink-0"><div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width:36px;height:36px;font-size:14px;">' + c.user_name.charAt(0).toUpperCase() + '</div></div><div class="ms-3"><strong>' + c.user_name + '</strong> <small class="text-muted ms-2">' + c.created_at + '</small><p class="mb-0 mt-1">' + $('<div>').text(c.comment).html() + '</p></div></div>');
                    $('#comment_input').val('');
                    showToast('Comment added.', 'success');
                }
            }
        });
    });
});
</script>
@endpush
