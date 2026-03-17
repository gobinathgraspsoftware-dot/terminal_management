@php use App\Models\Ticket; @endphp
@extends('layouts.app')

@section('title', 'Ticket ' . $ticket->ticket_no)

@section('content')
<div class="container-fluid">
    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h1 class="mb-1">
                <i class="bi bi-ticket-detailed me-2"></i>{{ $ticket->ticket_no }}
                {!! Ticket::getStatusBadge($ticket->status) !!}
                {!! Ticket::getPriorityBadge($ticket->priority) !!}
                @if($ticket->isSlaBreach()) <span class="badge bg-danger"><i class="bi bi-exclamation-triangle me-1"></i>SLA BREACHED</span> @endif
            </h1>
            <small class="text-muted">Created {{ $ticket->created_at->format('d M Y H:i') }}</small>
        </div>
        <a href="{{ route('technician.tickets.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to Tickets</a>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <div class="alert fw-bold mb-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 6px;">
                        <i class="bi bi-info-circle me-2"></i>Ticket Information
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4"><label class="form-label text-muted small">Vendor</label><div class="fw-semibold">{{ $ticket->vendor?->vendor_name ?? '-' }}</div></div>
                        <div class="col-md-4"><label class="form-label text-muted small">Branch</label><div class="fw-semibold">{{ $ticket->vendorBranch?->branch_name ?? '-' }}</div></div>
                        <div class="col-md-4"><label class="form-label text-muted small">Job Type</label><div class="fw-semibold">{{ $ticket->jobType?->job_title ?? '-' }}</div></div>
                        <div class="col-md-4"><label class="form-label text-muted small">State</label><div class="fw-semibold">{{ $ticket->state?->name ?? '-' }}</div></div>
                        <div class="col-md-4"><label class="form-label text-muted small">District</label><div class="fw-semibold">{{ $ticket->city?->name ?? '-' }}</div></div>
                        <div class="col-md-4"><label class="form-label text-muted small">Supervisor</label><div class="fw-semibold">{{ $ticket->supervisor?->name ?? '-' }}</div></div>
                    </div>

                    <div class="alert fw-bold mb-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 6px;">
                        <i class="bi bi-shop me-2"></i>Merchant Details
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4"><label class="form-label text-muted small">TID</label><div class="fw-semibold">{{ $ticket->tid ?? '-' }}</div></div>
                        <div class="col-md-4"><label class="form-label text-muted small">Merchant</label><div class="fw-semibold">{{ $ticket->merchant_name ?? '-' }}</div></div>
                        <div class="col-md-4"><label class="form-label text-muted small">Contact</label><div class="fw-semibold">{{ $ticket->contact_number ?? '-' }}</div></div>
                        <div class="col-md-8"><label class="form-label text-muted small">Address</label><div class="fw-semibold">{{ $ticket->merchant_address ?? '-' }}</div></div>
                    </div>

                    <div class="alert fw-bold mb-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 6px;">
                        <i class="bi bi-clock me-2"></i>SLA
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4"><label class="form-label text-muted small">SLA</label><div class="fw-semibold">{{ $ticket->sla_hours }}h</div></div>
                        <div class="col-md-4"><label class="form-label text-muted small">Deadline</label><div class="fw-semibold">{{ $ticket->sla_deadline?->format('d M Y H:i') ?? '-' }}</div></div>
                        <div class="col-md-4"><label class="form-label text-muted small">Remaining</label><div class="fw-semibold">{{ $ticket->sla_remaining ?? '-' }}</div></div>
                    </div>

                    <div class="alert fw-bold mb-3" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 6px;">
                        <i class="bi bi-card-text me-2"></i>Description
                    </div>
                    <p>{{ $ticket->description }}</p>
                </div>
            </div>

            {{-- Status Update (technician limited: in_progress, scheduled, done_success, done_fail) --}}
            @if(count($allowedTransitions) > 0)
            <div class="card">
                <div class="card-header bg-dark text-white"><i class="bi bi-arrow-repeat me-2"></i>Update Status</div>
                <div class="card-body">
                    <form id="statusForm" enctype="multipart/form-data">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">New Status <span class="text-danger">*</span></label>
                                <select name="status" id="new_status" class="form-select" required>
                                    <option value="">Select Status</option>
                                    @foreach($allowedTransitions as $ts)
                                        @if(in_array($ts, ['in_progress', 'scheduled', 'done_success', 'done_fail']))
                                            <option value="{{ $ts }}">{{ $statuses[$ts] ?? ucfirst(str_replace('_', ' ', $ts)) }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Remarks</label>
                                <input type="text" name="remarks" class="form-control" placeholder="Optional remarks">
                            </div>
                            <div class="col-12" id="reschedule_section" style="display:none;">
                                <label class="form-label">Reschedule Reason <span class="text-danger">*</span></label>
                                <textarea name="reschedule_reason" id="reschedule_reason" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="col-12" id="proof_section" style="display:none;">
                                <div class="alert alert-info py-2 mb-3"><i class="bi bi-camera me-1"></i><span id="proof_help_text"></span> <small class="text-muted">(Optional)</small></div>
                                <div id="proof_scheduled" style="display:none;">
                                    <div class="row g-3">
                                        <div class="col-md-6"><label class="form-label">WhatsApp Screenshot</label><input type="file" name="proof_files[whatsapp_screenshot]" class="form-control" accept="image/*,.pdf"></div>
                                        <div class="col-md-6"><label class="form-label">Call Log Screenshot</label><input type="file" name="proof_files[call_log_screenshot]" class="form-control" accept="image/*,.pdf"></div>
                                    </div>
                                </div>
                                <div id="proof_done_success" style="display:none;">
                                    <div class="col-md-6"><label class="form-label">Test Slip Image</label><input type="file" name="proof_files[test_slip]" class="form-control" accept="image/*,.pdf"></div>
                                </div>
                                <div id="proof_done_fail" style="display:none;">
                                    <div class="col-md-6"><label class="form-label">Service Form Image</label><input type="file" name="proof_files[service_form]" class="form-control" accept="image/*,.pdf"></div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3"><button type="submit" id="btnStatusSubmit" class="btn btn-dark"><i class="bi bi-check-circle me-1"></i>Update Status</button></div>
                    </form>
                </div>
            </div>
            @endif

            {{-- Status History --}}
            <div class="card">
                <div class="card-header"><i class="bi bi-clock-history me-2"></i>Status History</div>
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
            <div class="card">
                <div class="card-header"><i class="bi bi-chat-dots me-2"></i>Comments</div>
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
            <div class="card">
                <div class="card-header"><i class="bi bi-cash-coin me-2"></i>Claim</div>
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
        if (status === 'scheduled') { $('#reschedule_section, #proof_section, #proof_scheduled').show(); $('#proof_help_text').text('You may upload WhatsApp screenshot and Call log screenshot.'); }
        else if (status === 'done_success') { $('#proof_section, #proof_done_success').show(); $('#proof_help_text').text('You may upload Test Slip image.'); }
        else if (status === 'done_fail') { $('#proof_section, #proof_done_fail').show(); $('#proof_help_text').text('You may upload Service Form image.'); }
    });

    $('#statusForm').on('submit', function(e) {
        e.preventDefault();
        var status = $('#new_status').val();
        if (!status) { showToast('Select a status.', 'error'); return; }
        if (status === 'scheduled' && !$('#reschedule_reason').val().trim()) { showToast('Reschedule reason is required.', 'error'); return; }
        var fd = new FormData(this);
        var btn = $('#btnStatusSubmit');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>...');
        $.ajax({
            url: '{{ route("technician.tickets.change-status", $ticket->id) }}', method: 'POST', data: fd, processData: false, contentType: false,
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            success: function(res) { if (res.success) { showToast(res.message, 'success'); setTimeout(function() { location.reload(); }, 1000); } else { showToast(res.message, 'error'); btn.prop('disabled', false).html('<i class="bi bi-check-circle me-1"></i>Update Status'); } },
            error: function(xhr) { showToast(xhr.responseJSON?.message || 'Error.', 'error'); btn.prop('disabled', false).html('<i class="bi bi-check-circle me-1"></i>Update Status'); }
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
            url: '{{ route("technician.tickets.update-claim", $ticket->id) }}', method: 'POST', data: $(this).serialize() + '&_token={{ csrf_token() }}',
            success: function(res) { if (res.success) { showToast(res.message, 'success'); setTimeout(function() { location.reload(); }, 1000); } else showToast(res.message, 'error'); },
            error: function(xhr) { showToast(xhr.responseJSON?.message || 'Error.', 'error'); }
        });
    });

    $('#commentForm').on('submit', function(e) {
        e.preventDefault();
        var comment = $('#comment_input').val().trim();
        if (!comment) return;
        $.ajax({
            url: '{{ route("technician.tickets.comment", $ticket->id) }}', method: 'POST', data: { comment: comment, _token: '{{ csrf_token() }}' },
            success: function(res) {
                if (res.success) {
                    $('#noComments').remove();
                    var c = res.comment;
                    $('#commentsList').prepend('<div class="d-flex mb-3"><div class="flex-shrink-0"><div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width:36px;height:36px;font-size:14px;">' + c.user_name.charAt(0).toUpperCase() + '</div></div><div class="ms-3"><strong>' + c.user_name + '</strong> <small class="text-muted ms-2">' + c.created_at + '</small><p class="mb-0 mt-1">' + $('<div>').text(c.comment).html() + '</p></div></div>');
                    $('#comment_input').val('');
                }
            }
        });
    });
});
</script>
@endpush
