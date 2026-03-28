@extends('layouts.app')

@section('title', 'Ticket #' . $ticket->ticket_no . ' — Technician')

@push('styles')
<style>
    .detail-label { font-size: .78rem; text-transform: uppercase; letter-spacing: .05em; color: #6c757d; font-weight: 600; margin-bottom: 2px; }
    .detail-value { font-size: .95rem; color: #2d3748; font-weight: 500; }
    .timeline-item { border-left: 3px solid #dee2e6; padding-left: 1rem; margin-bottom: 1.25rem; position: relative; }
    .timeline-item::before { content: ''; width: 12px; height: 12px; border-radius: 50%; background: #0d6efd; position: absolute; left: -7.5px; top: 4px; }
    .timeline-item.rejected::before  { background: #dc3545; }
    .timeline-item.done_success::before { background: #198754; }
    .timeline-item.closed::before    { background: #212529; }
    .comment-bubble { background: #f8f9fa; border-radius: 10px; padding: .75rem 1rem; }
    .comment-bubble.own { background: #e8f4fd; }
    .financial-card .fin-item   { text-align: center; padding: .75rem; }
    .financial-card .fin-label  { font-size: .75rem; text-transform: uppercase; letter-spacing: .06em; color: #6c757d; font-weight: 600; }
    .financial-card .fin-val    { font-size: 1.3rem; font-weight: 700; margin-top: 4px; }
    .financial-card .fin-divider { border-right: 1px solid #dee2e6; }
    .proof-thumb { width: 60px; height: 60px; object-fit: cover; border-radius: 6px; border: 1px solid #dee2e6; cursor: pointer; }
</style>
@endpush

@section('content')

{{-- Page Header --}}
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h1 class="mb-1"><i class="bi bi-ticket-detailed me-2"></i>{{ $ticket->ticket_no }}</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('technician.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('technician.tickets.index') }}">My Tickets</a></li>
                <li class="breadcrumb-item active">{{ $ticket->ticket_no }}</li>
            </ol>
        </nav>
    </div>
    <a href="{{ route('technician.tickets.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Back
    </a>
</div>

<div class="row g-3">

    {{-- ═══ LEFT ═══ --}}
    <div class="col-lg-8">

        {{-- Status + Action Buttons --}}
        <div class="card">
            <div class="card-body d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div><div class="detail-label">Status</div><div>{!! \App\Models\Ticket::getStatusBadge($ticket->status) !!}</div></div>
                    <div><div class="detail-label">Priority</div><div>{!! \App\Models\Ticket::getPriorityBadge($ticket->priority) !!}</div></div>
                    @if($ticket->sla_deadline)
                    <div>
                        <div class="detail-label">SLA</div>
                        <div class="small {{ $ticket->isSlaBreach() ? 'text-danger fw-bold' : 'text-success' }}">
                            <i class="bi bi-clock me-1"></i>{{ $ticket->sla_remaining ?? '-' }}
                        </div>
                    </div>
                    @endif
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    @if(count($allowedTransitions) > 0)
                        @foreach($allowedTransitions as $transition)
                            @php
                                $btnMap = [
                                    'accepted'     => ['class' => 'btn-success',  'icon' => 'check-circle',   'label' => 'Accept'],
                                    'rejected'     => ['class' => 'btn-danger',   'icon' => 'x-circle',       'label' => 'Reject'],
                                    'in_progress'  => ['class' => 'btn-primary',  'icon' => 'play-circle',    'label' => 'Start Job'],
                                    'scheduled'    => ['class' => 'btn-warning',  'icon' => 'calendar-event', 'label' => 'Reschedule'],
                                    'done_success' => ['class' => 'btn-success',  'icon' => 'check2-all',     'label' => 'Done ✓'],
                                    'done_fail'    => ['class' => 'btn-danger',   'icon' => 'x-octagon',      'label' => 'Done ✗'],
                                ];
                                $btn = $btnMap[$transition] ?? ['class' => 'btn-secondary','icon' => 'arrow-right','label' => ucfirst($transition)];
                            @endphp
                            <button class="btn {{ $btn['class'] }} btn-sm"
                                    onclick="openStatusModal('{{ $transition }}')">
                                <i class="bi bi-{{ $btn['icon'] }} me-1"></i>{{ $btn['label'] }}
                            </button>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>

        {{-- Ticket Details --}}
        <div class="card">
            <div class="card-header"><i class="bi bi-info-circle me-2"></i>Job Details</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-4"><div class="detail-label">Ticket No</div><div class="detail-value">{{ $ticket->ticket_no }}</div></div>
                    <div class="col-sm-4"><div class="detail-label">TID</div><div class="detail-value">{{ $ticket->tid ?? '-' }}</div></div>
                    <div class="col-sm-4"><div class="detail-label">Terminal ID</div><div class="detail-value">{{ $ticket->terminal_id ?? '-' }}</div></div>
                    <div class="col-sm-4"><div class="detail-label">Vendor</div><div class="detail-value">{{ $ticket->vendor?->vendor_name ?? '-' }}</div></div>
                    <div class="col-sm-4"><div class="detail-label">Merchant</div><div class="detail-value">{{ $ticket->merchant_name ?? '-' }}</div></div>
                    <div class="col-sm-4"><div class="detail-label">Contact</div><div class="detail-value">{{ $ticket->contact_number ?? '-' }}</div></div>
                    <div class="col-sm-4"><div class="detail-label">State / City</div><div class="detail-value">{{ $ticket->state?->name ?? '-' }} / {{ $ticket->city?->name ?? '-' }}</div></div>
                    <div class="col-sm-4"><div class="detail-label">Job Category</div><div class="detail-value">{{ $ticket->jobCategory?->category_name ?? '-' }}</div></div>
                    <div class="col-sm-4"><div class="detail-label">Job Type</div><div class="detail-value">{{ $ticket->jobType?->job_title ?? '-' }}</div></div>
                    <div class="col-sm-4"><div class="detail-label">Router ID(s)</div><div class="detail-value">{{ $ticket->getRouterIdsDisplay() }}</div></div>
                    @if($isReplacement)
                    <div class="col-sm-4"><div class="detail-label">Old Router ID(s)</div><div class="detail-value">{{ $ticket->getOldRouterIdsDisplay() }}</div></div>
                    @endif
                    @if($ticket->scheduled_date)
                    <div class="col-sm-4"><div class="detail-label">Scheduled Date</div><div class="detail-value text-warning fw-semibold">{{ $ticket->scheduled_date->format('d M Y H:i') }}</div></div>
                    @endif
                    <div class="col-12"><div class="detail-label">Address</div><div class="detail-value">{{ $ticket->merchant_address ?? '-' }}</div></div>
                    <div class="col-12"><div class="detail-label">Description</div><div class="detail-value">{{ $ticket->description }}</div></div>
                </div>
            </div>
        </div>

        {{-- FIX #4: Financial Summary (technician view) --}}
        <div class="card financial-card">
            <div class="card-header"><i class="bi bi-currency-dollar me-2"></i>Financial Summary</div>
            <div class="card-body p-0">
                <div class="row g-0">
                    <div class="col fin-item fin-divider">
                        <div class="fin-label">Job Price</div>
                        <div class="fin-val text-dark">RM {{ number_format($ticket->price ?? 0, 2) }}</div>
                    </div>
                    <div class="col fin-item fin-divider">
                        <div class="fin-label">Mileage</div>
                        <div class="fin-val text-secondary">RM {{ number_format($ticket->mileage_amount ?? 0, 2) }}</div>
                        <small class="text-muted">{{ $ticket->mileage ?? 0 }} km</small>
                    </div>
                    <div class="col fin-item fin-divider">
                        <div class="fin-label">Toll + Meal</div>
                        <div class="fin-val text-secondary">RM {{ number_format(($ticket->toll ?? 0) + ($ticket->standby_meal ?? 0), 2) }}</div>
                    </div>
                    <div class="col fin-item fin-divider">
                        <div class="fin-label">Total Claim</div>
                        <div class="fin-val text-info">RM {{ number_format($ticket->total_claim_amount ?? 0, 2) }}</div>
                    </div>
                    <div class="col fin-item">
                        <div class="fin-label">Grand Total</div>
                        <div class="fin-val text-primary">RM {{ number_format($ticket->grand_total, 2) }}</div>
                        <small class="text-muted">Price + Claim</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- ═══ Claim Update (Technician — external supervisor tickets only) ═══ --}}
        @can('updateClaim', $ticket)
        @php $mileageRate = $ticket->mileage_rate ?? $ticket->supervisor?->mileage_rate ?? 0; @endphp
        <div class="card border-0 shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-receipt me-2 text-primary"></i><strong>My Claim</strong></span>
                <span class="badge bg-info text-dark">
                    Mileage Rate: RM {{ number_format($mileageRate, 2) }} / km
                </span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    {{-- Mileage --}}
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Mileage (km)</label>
                        <input type="number" step="0.01" min="0" class="form-control claim-input"
                               id="claimMileage" value="{{ $ticket->mileage ?? 0 }}"
                               placeholder="0.00">
                        <div class="form-text">
                            Amount: RM <span id="mileageAmtDisplay">{{ number_format(($ticket->mileage ?? 0) * $mileageRate, 2) }}</span>
                        </div>
                    </div>
                    {{-- Toll --}}
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Toll (RM)</label>
                        <div class="input-group">
                            <span class="input-group-text">RM</span>
                            <input type="number" step="0.01" min="0" class="form-control claim-input"
                                   id="claimToll" value="{{ $ticket->toll ?? 0 }}"
                                   placeholder="0.00">
                        </div>
                    </div>
                    {{-- Standby / Meal --}}
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Standby / Meal (RM)</label>
                        <div class="input-group">
                            <span class="input-group-text">RM</span>
                            <input type="number" step="0.01" min="0" class="form-control claim-input"
                                   id="claimMeal" value="{{ $ticket->standby_meal ?? 0 }}"
                                   placeholder="0.00">
                        </div>
                    </div>
                    {{-- Mileage Remarks --}}
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Mileage Remarks</label>
                        <input type="text" class="form-control" id="claimMileageRemarks"
                               value="{{ $ticket->mileage_remarks ?? '' }}"
                               placeholder="e.g. KL to Subang">
                    </div>
                </div>

                {{-- Live Claim Total Preview --}}
                <div class="mt-3 p-3 rounded" style="background:#f8f9fa;border:1px solid #e2e8f0;">
                    <div class="row text-center g-2">
                        <div class="col">
                            <div class="small text-muted">Mileage Amount</div>
                            <div class="fw-bold" id="previewMileage">RM {{ number_format(($ticket->mileage ?? 0) * $mileageRate, 2) }}</div>
                        </div>
                        <div class="col-auto d-flex align-items-center text-muted">+</div>
                        <div class="col">
                            <div class="small text-muted">Toll</div>
                            <div class="fw-bold" id="previewToll">RM {{ number_format($ticket->toll ?? 0, 2) }}</div>
                        </div>
                        <div class="col-auto d-flex align-items-center text-muted">+</div>
                        <div class="col">
                            <div class="small text-muted">Meal</div>
                            <div class="fw-bold" id="previewMeal">RM {{ number_format($ticket->standby_meal ?? 0, 2) }}</div>
                        </div>
                        <div class="col-auto d-flex align-items-center text-muted">=</div>
                        <div class="col">
                            <div class="small text-muted fw-semibold">Total Claim</div>
                            <div class="fw-bold text-primary fs-5" id="previewTotal">RM {{ number_format($ticket->total_claim_amount ?? 0, 2) }}</div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end mt-3 gap-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="resetClaim()">
                        <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
                    </button>
                    <button type="button" class="btn btn-primary" id="saveClaimBtn" onclick="saveClaim()">
                        <i class="bi bi-save me-1"></i>Save Claim
                    </button>
                </div>
            </div>
        </div>
        @endcan

        {{-- Status History --}}
        <div class="card">
            <div class="card-header"><i class="bi bi-clock-history me-2"></i>History</div>
            <div class="card-body">
                @forelse($ticket->statusHistory as $history)
                <div class="timeline-item {{ $history->to_status }}">
                    <div class="d-flex justify-content-between">
                        <span class="fw-semibold">{!! \App\Models\Ticket::getStatusBadge($history->to_status) !!}</span>
                        <small class="text-muted">{{ $history->created_at->format('d M Y H:i') }}</small>
                    </div>
                    @if($history->remarks)<div class="small text-secondary mt-1">{{ $history->remarks }}</div>@endif
                    @if($history->reschedule_reason)<div class="small text-warning mt-1"><i class="bi bi-calendar-x me-1"></i>{{ $history->reschedule_reason }}</div>@endif
                    @if($history->proofs?->count())
                    <div class="mt-2 d-flex flex-wrap gap-2">
                        @foreach($history->proofs as $proof)
                            <a href="{{ asset('storage/'.$proof->file_path) }}" target="_blank">
                                @if(str_contains($proof->mime_type ?? '', 'image'))
                                    <img src="{{ asset('storage/'.$proof->file_path) }}" class="proof-thumb">
                                @else
                                    <div class="proof-thumb d-flex align-items-center justify-content-center bg-light"><i class="bi bi-file-earmark-pdf fs-4 text-danger"></i></div>
                                @endif
                            </a>
                        @endforeach
                    </div>
                    @endif
                </div>
                @empty<p class="text-muted mb-0">No history.</p>@endforelse
            </div>
        </div>

        {{-- Comments --}}
        <div class="card">
            <div class="card-header"><i class="bi bi-chat-square-dots me-2"></i>Comments</div>
            <div class="card-body">
                <div id="commentsContainer" class="mb-3">
                    @forelse($ticket->comments as $comment)
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <strong class="small">{{ $comment->user->name }}</strong>
                            <small class="text-muted">{{ $comment->created_at->format('d M Y H:i') }}</small>
                        </div>
                        <div class="comment-bubble {{ $comment->user_id === auth()->id() ? 'own' : '' }}">{{ $comment->comment }}</div>
                    </div>
                    @empty<p class="text-muted small" id="noComments">No comments yet.</p>@endforelse
                </div>
                @can('addComment', $ticket)
                <div class="input-group">
                    <textarea class="form-control" id="commentText" rows="2" placeholder="Write a comment…"></textarea>
                    <button class="btn btn-primary" onclick="submitComment()"><i class="bi bi-send"></i></button>
                </div>
                @endcan
            </div>
        </div>

    </div>{{-- /left --}}

    {{-- ═══ RIGHT ═══ --}}
    <div class="col-lg-4">

        <div class="card">
            <div class="card-header"><i class="bi bi-people me-2"></i>Job Info</div>
            <div class="card-body">
                <div class="mb-3"><div class="detail-label">Supervisor</div><div class="detail-value">{{ $ticket->supervisor?->name ?? '-' }}</div></div>
                <div class="mb-3"><div class="detail-label">Assigned To</div><div class="detail-value">{{ $ticket->technician?->name ?? 'Me' }}</div></div>
                <hr>
                <div><div class="detail-label">Created</div><div class="detail-value small">{{ $ticket->created_at?->format('d M Y H:i') }}</div></div>
            </div>
        </div>

        @if($ticket->sla_deadline)
        <div class="card border-{{ $ticket->isSlaBreach() ? 'danger' : 'success' }}">
            <div class="card-header bg-{{ $ticket->isSlaBreach() ? 'danger' : 'success' }} text-white"><i class="bi bi-stopwatch me-2"></i>SLA</div>
            <div class="card-body">
                <div class="mb-2"><div class="detail-label">Deadline</div><div class="detail-value">{{ $ticket->sla_deadline->format('d M Y H:i') }}</div></div>
                <div><div class="detail-label">Remaining</div><div class="detail-value {{ $ticket->isSlaBreach() ? 'text-danger' : 'text-success' }}">{{ $ticket->sla_remaining ?? '-' }}</div></div>
            </div>
        </div>
        @endif

        <div class="card">
            <div class="card-header"><i class="bi bi-calendar-check me-2"></i>Timeline</div>
            <div class="card-body">
                @foreach(['assigned_at'=>'Assigned','accepted_at'=>'Accepted','rejected_at'=>'Rejected','started_at'=>'Started','completed_at'=>'Completed'] as $field => $label)
                    @if($ticket->$field)<div class="mb-2"><div class="detail-label">{{ $label }}</div><div class="detail-value small">{{ $ticket->$field->format('d M Y H:i') }}</div></div>@endif
                @endforeach
            </div>
        </div>

    </div>{{-- /right --}}

</div>

{{-- ══════════ STATUS CHANGE MODAL ══════════ --}}
<div class="modal fade" id="statusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-arrow-repeat me-2"></i>Update Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="targetStatus">
                <div class="mb-3">
                    <div class="detail-label">Changing to</div>
                    <div id="newStatusDisplay" class="fw-bold text-primary fs-5 mt-1"></div>
                </div>
                @if($isReplacement && !$ticket->old_terminal_id)
                <div class="mb-3">
                    <label class="form-label fw-semibold">Old Router ID <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="oldTerminalId" placeholder="e.g. RTR-00123">
                </div>
                @endif
                <div class="mb-3 d-none" id="rescheduleWrapper">
                    <label class="form-label fw-semibold">Reschedule Reason <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="rescheduleReason" rows="2" placeholder="Why rescheduling?"></textarea>
                </div>
                {{-- FIX #3: Reschedule target date/time --}}
                <div class="mb-3 d-none" id="scheduledDateWrapper">
                    <label class="form-label fw-semibold">Reschedule To <span class="text-danger">*</span></label>
                    <input type="datetime-local" class="form-control" id="scheduledDate" min="{{ now()->format('Y-m-d\TH:i') }}">
                </div>
                <div class="mb-3 d-none" id="proofWrapper">
                    <label class="form-label fw-semibold">Proof Files <span class="text-danger">*</span></label>
                    <div id="proofFieldsContainer"></div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Remarks</label>
                    <textarea class="form-control" id="statusRemarks" rows="2" placeholder="Optional…"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmStatusBtn" onclick="submitStatus()">
                    <i class="bi bi-check-circle me-1"></i>Confirm
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const CHANGE_STATUS_URL = '{{ route('technician.tickets.change-status', $ticket->id) }}';
const CLAIM_URL         = '{{ route('technician.tickets.update-claim', $ticket->id) }}';
const COMMENT_URL       = '{{ route('technician.tickets.comment', $ticket->id) }}';
const MILEAGE_RATE      = {{ (float)($ticket->mileage_rate ?? $ticket->supervisor?->mileage_rate ?? 0) }};

@php $proofTypeLabels = \App\Models\Ticket::getProofTypeLabels(); @endphp
const proofTypeLabels = @json($proofTypeLabels);
const proofRequiredMap = {
    scheduled: ['whatsapp_screenshot','call_log_screenshot'],
    done_success: ['test_slip'],
    done_fail: ['service_form'],
};

function openStatusModal(status) {
    $('#targetStatus').val(status);
    const labelMap = { accepted:'Accept', rejected:'Reject', in_progress:'Start Job',
        scheduled:'Reschedule', done_success:'Done / Success', done_fail:'Done / Fail' };
    $('#newStatusDisplay').text(labelMap[status] || status);
    $('#rescheduleWrapper,#scheduledDateWrapper,#proofWrapper').addClass('d-none');
    $('#rescheduleReason,#scheduledDate,#statusRemarks').val('');
    $('#proofFieldsContainer').html('');

    if (status === 'scheduled') {
        $('#rescheduleWrapper,#scheduledDateWrapper').removeClass('d-none');
    }
    const proofTypes = proofRequiredMap[status] || [];
    if (proofTypes.length) {
        $('#proofWrapper').removeClass('d-none');
        let html = '';
        proofTypes.forEach(pt => {
            html += `<div class="mb-2">
                <label class="form-label small">${proofTypeLabels[pt]||pt} <span class="text-danger">*</span></label>
                <input type="file" class="form-control form-control-sm" id="proof_${pt}" accept=".jpg,.jpeg,.png,.pdf">
            </div>`;
        });
        $('#proofFieldsContainer').html(html);
    }
    new bootstrap.Modal(document.getElementById('statusModal')).show();
}

function submitStatus() {
    const status  = $('#targetStatus').val();
    const remarks = $('#statusRemarks').val();
    const fd      = new FormData();
    fd.append('_token', $('meta[name="csrf-token"]').attr('content'));
    fd.append('status', status);
    if (remarks) fd.append('remarks', remarks);

    if (status === 'scheduled') {
        const reason = $('#rescheduleReason').val().trim();
        const date   = $('#scheduledDate').val();
        if (!reason) { showToast('Please enter a reschedule reason.','warning'); return; }
        if (!date)   { showToast('Please select a reschedule date/time.','warning'); return; }
        fd.append('reschedule_reason', reason);
        fd.append('scheduled_date', date);
    }

    const proofTypes = proofRequiredMap[status] || [];
    let ok = true;
    proofTypes.forEach(pt => {
        const el = document.getElementById('proof_' + pt);
        if (el?.files[0]) fd.append('proof_files['+pt+']', el.files[0]);
        else if (el) { showToast('Upload: ' + (proofTypeLabels[pt]||pt), 'warning'); ok = false; }
    });
    if (!ok) return;

    const oldTerm = $('#oldTerminalId');
    if (oldTerm.length && oldTerm.val()) fd.append('old_terminal_id', oldTerm.val());

    $('#confirmStatusBtn').prop('disabled',true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving…');
    $.ajax({
        url: CHANGE_STATUS_URL, type: 'POST', data: fd,
        processData: false, contentType: false,
        success: r => { bootstrap.Modal.getInstance(document.getElementById('statusModal')).hide(); showToast(r.message,'success'); setTimeout(()=>location.reload(),1000); },
        error: xhr => showToast(xhr.responseJSON?.message||'Failed.','error'),
        complete: () => $('#confirmStatusBtn').prop('disabled',false).html('<i class="bi bi-check-circle me-1"></i>Confirm'),
    });
}

// ── Claim Live Calculation ──
function recalcClaim() {
    const mileage    = parseFloat($('#claimMileage').val()) || 0;
    const toll       = parseFloat($('#claimToll').val())    || 0;
    const meal       = parseFloat($('#claimMeal').val())    || 0;
    const mileageAmt = mileage * MILEAGE_RATE;
    const total      = mileageAmt + toll + meal;
    $('#mileageAmtDisplay').text(mileageAmt.toFixed(2));
    $('#previewMileage').text('RM ' + mileageAmt.toFixed(2));
    $('#previewToll').text('RM '    + toll.toFixed(2));
    $('#previewMeal').text('RM '    + meal.toFixed(2));
    $('#previewTotal').text('RM '   + total.toFixed(2));
}

$(document).on('input', '.claim-input', recalcClaim);

function resetClaim() {
    $('#claimMileage').val(0);
    $('#claimToll').val(0);
    $('#claimMeal').val(0);
    $('#claimMileageRemarks').val('');
    recalcClaim();
}

function saveClaim() {
    const mileage = parseFloat($('#claimMileage').val()) || 0;
    const toll    = parseFloat($('#claimToll').val())    || 0;
    const meal    = parseFloat($('#claimMeal').val())    || 0;
    const remarks = $('#claimMileageRemarks').val();

    if (mileage < 0 || toll < 0 || meal < 0) {
        showToast('Claim values cannot be negative.', 'warning');
        return;
    }

    $('#saveClaimBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving…');

    $.post(CLAIM_URL, {
        _token:          $('meta[name="csrf-token"]').attr('content'),
        mileage:         mileage,
        toll:            toll,
        standby_meal:    meal,
        mileage_remarks: remarks,
    })
    .done(function(r) {
        showToast(r.message, 'success');
        recalcClaim();
        // FIX 3: Append claim history entry live
        const now        = new Date().toLocaleString('en-GB', { day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit' });
        const mileageAmt = mileage * MILEAGE_RATE;
        const total      = mileageAmt + toll + meal;
        const histHtml   = `<div class="timeline-item">
            <div class="d-flex justify-content-between align-items-start">
                <div><span class="fw-semibold"><span class="badge bg-info">Claim Updated</span></span>
                <span class="text-muted small ms-2">by You</span></div>
                <small class="text-muted">${now}</small>
            </div>
            <div class="small text-secondary mt-1">
                Mileage: ${mileage.toFixed(2)} km × RM ${MILEAGE_RATE.toFixed(2)} = RM ${mileageAmt.toFixed(2)} |
                Toll: RM ${toll.toFixed(2)} |
                Meal: RM ${meal.toFixed(2)} |
                <strong>Total: RM ${total.toFixed(2)}</strong>
                ${remarks ? ' | Note: ' + $('<div>').text(remarks).html() : ''}
            </div>
        </div>`;
        const histContainer = $('.timeline-item').first().parent();
        histContainer.prepend(histHtml);
    })
    .fail(function(xhr) {
        showToast(xhr.responseJSON?.message || 'Failed to save claim.', 'error');
    })
    .always(function() {
        $('#saveClaimBtn').prop('disabled', false).html('<i class="bi bi-save me-1"></i>Save Claim');
    });
}

function submitComment() {
    const text = $('#commentText').val().trim();
    if (!text) { showToast('Comment cannot be empty.','warning'); return; }
    $.post(COMMENT_URL, { _token: $('meta[name="csrf-token"]').attr('content'), comment: text })
        .done(r => {
            $('#noComments').remove();
            $('#commentsContainer').prepend(`<div class="mb-3">
                <div class="d-flex justify-content-between mb-1">
                    <strong class="small">${r.comment.user_name}</strong>
                    <small class="text-muted">${r.comment.created_at}</small>
                </div>
                <div class="comment-bubble own">${$('<div>').text(r.comment.comment).html()}</div>
            </div>`);
            $('#commentText').val('');
        })
        .fail(xhr => showToast(xhr.responseJSON?.message||'Failed.','error'));
}
</script>
@endpush
