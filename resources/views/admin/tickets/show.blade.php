@php use App\Models\Ticket; @endphp
@extends('layouts.app')

@section('title', 'Ticket ' . $ticket->ticket_no)

@section('content')
<div class="container-fluid">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">
                <i class="bi bi-ticket-detailed me-2"></i>{{ $ticket->ticket_no }}
                {!! Ticket::getStatusBadge($ticket->status) !!}
                {!! Ticket::getPriorityBadge($ticket->priority) !!}
                @if($ticket->isSlaBreach())
                    <span class="badge bg-danger"><i class="bi bi-exclamation-triangle me-1"></i>SLA BREACHED</span>
                @endif
            </h4>
            <small class="text-muted">Created {{ $ticket->created_at->format('d M Y H:i') }} by {{ $ticket->creator?->name ?? '-' }}</small>
        </div>
        <div>
            @if(!in_array($ticket->status, [Ticket::STATUS_DONE_SUCCESS, Ticket::STATUS_DONE_FAIL, Ticket::STATUS_CLOSED]))
                @can('edit_tickets')
                <a href="{{ route('admin.tickets.edit', $ticket->id) }}" class="btn btn-outline-primary btn-sm me-1">
                    <i class="bi bi-pencil me-1"></i>Edit
                </a>
                @endcan
            @endif
            <a href="{{ route('admin.tickets.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Back
            </a>
        </div>
    </div>

    <div class="row g-4">
        {{-- Left Column --}}
        <div class="col-lg-8">
            {{-- Ticket Details --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white"><h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Ticket Details</h6></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4"><strong>Vendor:</strong><br>{{ $ticket->vendor?->vendor_name ?? '-' }}</div>
                        <div class="col-md-4"><strong>Vendor Ref No:</strong><br>{{ $ticket->vendor_ticket_ref_no ?? '-' }}</div>
                        <div class="col-md-4"><strong>Branch:</strong><br>{{ $ticket->vendorBranch?->branch_name ?? '-' }}</div>
                        <div class="col-md-4"><strong>State:</strong><br>{{ $ticket->state?->name ?? '-' }}</div>
                        <div class="col-md-4"><strong>District:</strong><br>{{ $ticket->city?->name ?? '-' }}</div>
                        <div class="col-md-4"><strong>Job Type:</strong><br>{{ $ticket->jobType?->job_title ?? '-' }}</div>
                        <div class="col-md-4"><strong>Terminal ID:</strong><br>{{ $ticket->tid ?? '-' }}</div>
                        <div class="col-md-4"><strong>Merchant:</strong><br>{{ $ticket->merchant_name ?? '-' }}</div>
                        <div class="col-md-4"><strong>Contact:</strong><br>{{ $ticket->contact_number ?? '-' }}</div>
                        <div class="col-12"><strong>Address:</strong><br>{{ $ticket->merchant_address ?? '-' }}</div>
                        <div class="col-12"><strong>Description:</strong><br>{{ $ticket->description }}</div>
                    </div>
                </div>
            </div>

            {{-- SLA Info --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white"><h6 class="mb-0"><i class="bi bi-clock me-2"></i>SLA & Timeline</h6></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3"><strong>SLA Hours:</strong><br>{{ $ticket->sla_hours }}h</div>
                        <div class="col-md-3"><strong>Deadline:</strong><br>{{ $ticket->sla_deadline?->format('d M Y H:i') ?? '-' }}</div>
                        <div class="col-md-3"><strong>Remaining:</strong><br>{{ $ticket->sla_remaining ?? '-' }}</div>
                        <div class="col-md-3"><strong>Supervisor:</strong><br>{{ $ticket->supervisor?->name ?? '-' }}</div>
                        <div class="col-md-3"><strong>Technician:</strong><br>{{ $ticket->technician?->name ?? 'Unassigned' }}</div>
                        <div class="col-md-3"><strong>Assigned At:</strong><br>{{ $ticket->assigned_at?->format('d M Y H:i') ?? '-' }}</div>
                        <div class="col-md-3"><strong>Started At:</strong><br>{{ $ticket->started_at?->format('d M Y H:i') ?? '-' }}</div>
                        <div class="col-md-3"><strong>Completed At:</strong><br>{{ $ticket->completed_at?->format('d M Y H:i') ?? '-' }}</div>
                    </div>
                </div>
            </div>

            {{-- Assign Technician --}}
            @if(!in_array($ticket->status, [Ticket::STATUS_DONE_SUCCESS, Ticket::STATUS_DONE_FAIL, Ticket::STATUS_CLOSED]))
                @can('assign_tickets')
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white"><h6 class="mb-0"><i class="bi bi-person-check me-2"></i>Assign Technician</h6></div>
                    <div class="card-body">
                        <form id="assignForm">
                            <div class="row g-2 align-items-end">
                                <div class="col-md-5">
                                    <label class="form-label">Technician <span class="text-danger">*</span></label>
                                    <select name="technician_id" id="assign_technician_id" class="form-select" required>
                                        <option value="">Select Technician</option>
                                        @foreach($technicians as $t)
                                            <option value="{{ $t->id }}" {{ $ticket->technician_id == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label">Remarks</label>
                                    <input type="text" name="remarks" class="form-control" placeholder="Optional">
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-info w-100"><i class="bi bi-person-check me-1"></i>Assign</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                @endcan
            @endif

            {{-- Update Status --}}
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

                        {{-- Reschedule Reason (shown when status = scheduled) --}}
                        <div id="reschedule_section" class="mb-3" style="display:none;">
                            <label class="form-label">Reschedule Reason <span class="text-danger">*</span></label>
                            <textarea name="reschedule_reason" id="reschedule_reason" class="form-control" rows="2" placeholder="Reason for rescheduling..."></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Remarks</label>
                            <textarea name="remarks" class="form-control" rows="2" placeholder="Optional remarks"></textarea>
                        </div>

                        {{-- Proof upload sections --}}
                        <div id="proof_section" style="display:none;">
                            <div class="alert alert-info py-2 mb-3">
                                <i class="bi bi-camera me-1"></i>
                                <strong>Proof upload required.</strong> <span id="proof_help_text"></span>
                            </div>

                            {{-- Scheduled proofs --}}
                            <div id="proof_scheduled" style="display:none;">
                                <div class="mb-3">
                                    <label class="form-label">WhatsApp Screenshot <span class="text-danger">*</span></label>
                                    <input type="file" name="proof_files[whatsapp_screenshot]" class="form-control" accept="image/*,.pdf">
                                    <small class="text-muted">Upload screenshot of WhatsApp conversation</small>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Call Log Screenshot <span class="text-danger">*</span></label>
                                    <input type="file" name="proof_files[call_log_screenshot]" class="form-control" accept="image/*,.pdf">
                                    <small class="text-muted">Upload screenshot of call log</small>
                                </div>
                            </div>

                            {{-- Done/Success proofs --}}
                            <div id="proof_done_success" style="display:none;">
                                <div class="mb-3">
                                    <label class="form-label">Test Slip Image <span class="text-danger">*</span></label>
                                    <input type="file" name="proof_files[test_slip]" class="form-control" accept="image/*,.pdf">
                                    <small class="text-muted">Upload test slip image</small>
                                </div>
                            </div>

                            {{-- Done/Fail proofs --}}
                            <div id="proof_done_fail" style="display:none;">
                                <div class="mb-3">
                                    <label class="form-label">Service Form Image <span class="text-danger">*</span></label>
                                    <input type="file" name="proof_files[service_form]" class="form-control" accept="image/*,.pdf">
                                    <small class="text-muted">Upload service form image</small>
                                </div>
                            </div>
                        </div>

                        <button type="submit" id="btnStatusSubmit" class="btn btn-dark">
                            <i class="bi bi-check-circle me-1"></i>Update Status
                        </button>
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
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>From</th>
                                    <th>To</th>
                                    <th>By</th>
                                    <th>Remarks</th>
                                    <th>Proofs</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($ticket->statusHistory as $h)
                                <tr>
                                    <td>{{ $h->created_at->format('d M Y H:i') }}</td>
                                    <td>{!! $h->from_status ? Ticket::getStatusBadge($h->from_status) : '-' !!}</td>
                                    <td>{!! Ticket::getStatusBadge($h->to_status) !!}</td>
                                    <td>{{ $h->changedBy?->name ?? '-' }}</td>
                                    <td>
                                        {{ $h->remarks ?? '-' }}
                                        @if($h->reschedule_reason)
                                            <br><small class="text-warning"><strong>Reschedule:</strong> {{ $h->reschedule_reason }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($h->proofs && $h->proofs->count())
                                            @foreach($h->proofs as $p)
                                                <a href="{{ asset('storage/' . $p->file_path) }}" target="_blank" class="badge bg-primary text-decoration-none me-1">
                                                    <i class="bi bi-file-earmark me-1"></i>{{ Ticket::getProofTypeLabels()[$p->proof_type] ?? $p->proof_type }}
                                                </a>
                                            @endforeach
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="6" class="text-center text-muted py-3">No status history.</td></tr>
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
                            <div class="flex-shrink-0">
                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width:36px;height:36px;font-size:14px;">
                                    {{ strtoupper(substr($c->user?->name ?? '?', 0, 1)) }}
                                </div>
                            </div>
                            <div class="ms-3 flex-grow-1">
                                <strong>{{ $c->user?->name ?? 'Unknown' }}</strong>
                                <span class="badge bg-light text-dark ms-1">{{ $c->user?->roles->first()?->name ?? '' }}</span>
                                <small class="text-muted ms-2">{{ $c->created_at->format('d M Y H:i') }}</small>
                                <p class="mb-0 mt-1">{{ $c->comment }}</p>
                            </div>
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

        {{-- Right Column: Claim --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white"><h6 class="mb-0"><i class="bi bi-cash-coin me-2"></i>Claim Information</h6></div>
                <div class="card-body">
                    <form id="claimForm">
                        <div class="mb-3">
                            <label class="form-label">Mileage (KM)</label>
                            <input type="number" name="mileage" id="claim_mileage" class="form-control" step="0.01" min="0" value="{{ $ticket->mileage ?? 0 }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mileage Rate (RM/KM)</label>
                            <input type="text" class="form-control" readonly value="{{ number_format($ticket->mileage_rate ?? 0, 2) }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mileage Amount (RM)</label>
                            <input type="text" id="claim_mileage_amount" class="form-control" readonly value="{{ number_format($ticket->mileage_amount ?? 0, 2) }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mileage Remarks</label>
                            <input type="text" name="mileage_remarks" class="form-control" value="{{ $ticket->mileage_remarks }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Toll (RM)</label>
                            <input type="number" name="toll" id="claim_toll" class="form-control" step="0.01" min="0" value="{{ $ticket->toll ?? 0 }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Standby / Meal (RM)</label>
                            <input type="number" name="standby_meal" id="claim_meal" class="form-control" step="0.01" min="0" value="{{ $ticket->standby_meal ?? 0 }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold text-success">Total Claim (RM)</label>
                            <input type="text" id="claim_total" class="form-control fw-bold text-success" readonly value="{{ number_format($ticket->total_claim_amount ?? 0, 2) }}">
                        </div>
                        <button type="submit" class="btn btn-warning w-100">
                            <i class="bi bi-pencil-square me-1"></i>Update Claim
                        </button>
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

    // ── Status change logic ──
    $('#new_status').on('change', function() {
        var status = $(this).val();
        // Hide all proof sections first
        $('#proof_section, #proof_scheduled, #proof_done_success, #proof_done_fail, #reschedule_section').hide();
        // Reset file inputs
        $('#proof_section input[type=file]').val('');

        if (status === 'scheduled') {
            $('#reschedule_section').show();
            $('#proof_section, #proof_scheduled').show();
            $('#proof_help_text').text('Please upload WhatsApp screenshot and Call log screenshot.');
        } else if (status === 'done_success') {
            $('#proof_section, #proof_done_success').show();
            $('#proof_help_text').text('Please upload Test Slip image.');
        } else if (status === 'done_fail') {
            $('#proof_section, #proof_done_fail').show();
            $('#proof_help_text').text('Please upload Service Form image.');
        }
    });

    // ── Status form submit ──
    $('#statusForm').on('submit', function(e) {
        e.preventDefault();
        var status = $('#new_status').val();
        if (!status) { showToast('Please select a status.', 'error'); return; }

        // Validate required proofs
        if (status === 'scheduled') {
            if (!$('input[name="proof_files[whatsapp_screenshot]"]').val()) {
                showToast('WhatsApp screenshot is required for Scheduled status.', 'error'); return;
            }
            if (!$('input[name="proof_files[call_log_screenshot]"]').val()) {
                showToast('Call log screenshot is required for Scheduled status.', 'error'); return;
            }
            if (!$('#reschedule_reason').val().trim()) {
                showToast('Reschedule reason is required.', 'error'); return;
            }
        }
        if (status === 'done_success') {
            if (!$('input[name="proof_files[test_slip]"]').val()) {
                showToast('Test slip image is required for Done/Success status.', 'error'); return;
            }
        }
        if (status === 'done_fail') {
            if (!$('input[name="proof_files[service_form]"]').val()) {
                showToast('Service form image is required for Done/Fail status.', 'error'); return;
            }
        }

        var formData = new FormData(this);
        var btn = $('#btnStatusSubmit');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Updating...');

        $.ajax({
            url: '{{ route("admin.tickets.change-status", $ticket->id) }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            success: function(res) {
                if (res.success) {
                    showToast(res.message, 'success');
                    setTimeout(function() { location.reload(); }, 1000);
                } else {
                    showToast(res.message, 'error');
                    btn.prop('disabled', false).html('<i class="bi bi-check-circle me-1"></i>Update Status');
                }
            },
            error: function(xhr) {
                showToast(xhr.responseJSON?.message || 'Error updating status.', 'error');
                btn.prop('disabled', false).html('<i class="bi bi-check-circle me-1"></i>Update Status');
            }
        });
    });

    // ── Assign form ──
    $('#assignForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: '{{ route("admin.tickets.assign", $ticket->id) }}',
            method: 'POST',
            data: $(this).serialize() + '&_token={{ csrf_token() }}',
            success: function(res) {
                if (res.success) { showToast(res.message, 'success'); setTimeout(function() { location.reload(); }, 1000); }
                else showToast(res.message, 'error');
            },
            error: function(xhr) { showToast(xhr.responseJSON?.message || 'Error.', 'error'); }
        });
    });

    // ── Claim form ──
    var claimRate = mileageRate;
    $('#claim_mileage, #claim_toll, #claim_meal').on('input', function() {
        var km = parseFloat($('#claim_mileage').val()) || 0;
        var mAmt = km * claimRate;
        var toll = parseFloat($('#claim_toll').val()) || 0;
        var meal = parseFloat($('#claim_meal').val()) || 0;
        $('#claim_mileage_amount').val(mAmt.toFixed(2));
        $('#claim_total').val((mAmt + toll + meal).toFixed(2));
    });

    $('#claimForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: '{{ route("admin.tickets.update-claim", $ticket->id) }}',
            method: 'POST',
            data: $(this).serialize() + '&_token={{ csrf_token() }}',
            success: function(res) {
                if (res.success) { showToast(res.message, 'success'); setTimeout(function() { location.reload(); }, 1000); }
                else showToast(res.message, 'error');
            },
            error: function(xhr) { showToast(xhr.responseJSON?.message || 'Error.', 'error'); }
        });
    });

    // ── Comment form ──
    $('#commentForm').on('submit', function(e) {
        e.preventDefault();
        var comment = $('#comment_input').val().trim();
        if (!comment) return;
        $.ajax({
            url: '{{ route("admin.tickets.comment", $ticket->id) }}',
            method: 'POST',
            data: { comment: comment, _token: '{{ csrf_token() }}' },
            success: function(res) {
                if (res.success) {
                    $('#noComments').remove();
                    var c = res.comment;
                    var html = '<div class="d-flex mb-3">' +
                        '<div class="flex-shrink-0"><div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width:36px;height:36px;font-size:14px;">' + c.user_name.charAt(0).toUpperCase() + '</div></div>' +
                        '<div class="ms-3 flex-grow-1"><strong>' + c.user_name + '</strong> <span class="badge bg-light text-dark ms-1">' + c.user_role + '</span> <small class="text-muted ms-2">' + c.created_at + '</small>' +
                        '<p class="mb-0 mt-1">' + $('<div>').text(c.comment).html() + '</p></div></div>';
                    $('#commentsList').prepend(html);
                    $('#comment_input').val('');
                    showToast('Comment added.', 'success');
                }
            },
            error: function() { showToast('Failed to add comment.', 'error'); }
        });
    });
});
</script>
@endpush
