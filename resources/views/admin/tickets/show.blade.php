@extends('layouts.app')

@section('title', 'Ticket #' . $ticket->ticket_no . ' — Admin')

@push('styles')
<style>
    .detail-label { font-size: 0.78rem; text-transform: uppercase; letter-spacing: .05em; color: #6c757d; font-weight: 600; margin-bottom: 2px; }
    .detail-value { font-size: 0.95rem; color: #2d3748; font-weight: 500; }
    .timeline-item { border-left: 3px solid #dee2e6; padding-left: 1rem; margin-bottom: 1.25rem; position: relative; }
    .timeline-item::before { content: ''; width: 12px; height: 12px; border-radius: 50%; background: #0d6efd; position: absolute; left: -7.5px; top: 4px; }
    .timeline-item.rejected::before { background: #dc3545; }
    .timeline-item.done_success::before { background: #198754; }
    .timeline-item.closed::before { background: #212529; }
    .comment-bubble { background: #f8f9fa; border-radius: 10px; padding: .75rem 1rem; }
    .comment-bubble.own { background: #e8f4fd; }
    .financial-card .fin-item { text-align: center; padding: .75rem; }
    .financial-card .fin-label { font-size: .75rem; text-transform: uppercase; letter-spacing: .06em; color: #6c757d; font-weight: 600; }
    .financial-card .fin-val { font-size: 1.35rem; font-weight: 700; margin-top: 4px; }
    .financial-card .fin-divider { border-right: 1px solid #dee2e6; }
    .proof-thumb { width: 60px; height: 60px; object-fit: cover; border-radius: 6px; border: 1px solid #dee2e6; cursor: pointer; }
    .status-action-btn { min-width: 110px; }
</style>
@endpush

@section('content')
@php $rolePrefix = 'admin'; @endphp

{{-- Page Header --}}
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h1 class="mb-1"><i class="bi bi-ticket-detailed me-2"></i>{{ $ticket->ticket_no }}</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.tickets.index') }}">Tickets</a></li>
                <li class="breadcrumb-item active">{{ $ticket->ticket_no }}</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        @can('update', $ticket)
            @if(!in_array($ticket->status, ['done_success','done_fail','closed']))
                <a href="{{ route('admin.tickets.edit', $ticket->id) }}" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-pencil me-1"></i>Edit
                </a>
            @endif
        @endcan
        <a href="{{ route('admin.tickets.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
    </div>
</div>

<div class="row g-3">

    {{-- ═══ LEFT COLUMN ═══ --}}
    <div class="col-lg-8">

        {{-- Status + Priority Bar --}}
        <div class="card">
            <div class="card-body d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div>
                        <div class="detail-label">Status</div>
                        <div>{!! \App\Models\Ticket::getStatusBadge($ticket->status) !!}</div>
                    </div>
                    <div>
                        <div class="detail-label">Priority</div>
                        <div>{!! \App\Models\Ticket::getPriorityBadge($ticket->priority) !!}</div>
                    </div>
                    @if($ticket->sla_deadline)
                    <div>
                        <div class="detail-label">SLA</div>
                        <div class="small {{ $ticket->isSlaBreach() ? 'text-danger fw-bold' : 'text-success' }}">
                            <i class="bi bi-clock me-1"></i>{{ $ticket->sla_remaining ?? '-' }}
                        </div>
                    </div>
                    @endif
                </div>
                {{-- Quick action buttons --}}
                <div class="d-flex gap-2 flex-wrap">
                    @if(count($allowedTransitions) > 0)
                        @foreach($allowedTransitions as $transition)
                            @php
                                $btnMap = [
                                    'accepted'     => ['class' => 'btn-success',   'icon' => 'check-circle',    'label' => 'Accept'],
                                    'rejected'     => ['class' => 'btn-danger',    'icon' => 'x-circle',        'label' => 'Reject'],
                                    'in_progress'  => ['class' => 'btn-primary',   'icon' => 'play-circle',     'label' => 'Start'],
                                    'scheduled'    => ['class' => 'btn-warning',   'icon' => 'calendar-event',  'label' => 'Reschedule'],
                                    'done_success' => ['class' => 'btn-success',   'icon' => 'check2-all',      'label' => 'Done ✓'],
                                    'done_fail'    => ['class' => 'btn-danger',    'icon' => 'x-octagon',       'label' => 'Done ✗'],
                                    'closed'       => ['class' => 'btn-dark',      'icon' => 'lock',            'label' => 'Close'],
                                    'assigned'     => ['class' => 'btn-info',      'icon' => 'person-check',    'label' => 'Re-open'],
                                    'open'         => ['class' => 'btn-secondary', 'icon' => 'arrow-counterclockwise', 'label' => 'Re-open'],
                                ];
                                $btn = $btnMap[$transition] ?? ['class' => 'btn-secondary', 'icon' => 'arrow-right', 'label' => ucfirst($transition)];
                            @endphp
                            <button class="btn {{ $btn['class'] }} btn-sm status-action-btn"
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
            <div class="card-header"><i class="bi bi-info-circle me-2"></i>Ticket Details</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-4"><div class="detail-label">Ticket No</div><div class="detail-value">{{ $ticket->ticket_no }}</div></div>
                    <div class="col-sm-4"><div class="detail-label">Vendor Ref No</div><div class="detail-value">{{ $ticket->vendor_ticket_ref_no ?? '-' }}</div></div>
                    <div class="col-sm-4"><div class="detail-label">TID</div><div class="detail-value">{{ $ticket->tid ?? '-' }}</div></div>
                    <div class="col-sm-4"><div class="detail-label">Vendor</div><div class="detail-value">{{ $ticket->vendor?->vendor_name ?? '-' }}</div></div>
                    <div class="col-sm-4"><div class="detail-label">Branch</div><div class="detail-value">{{ $ticket->vendorBranch?->branch_name ?? '-' }}</div></div>
                    <div class="col-sm-4"><div class="detail-label">State / City</div><div class="detail-value">{{ $ticket->state?->name ?? '-' }} / {{ $ticket->city?->name ?? '-' }}</div></div>
                    <div class="col-sm-4"><div class="detail-label">Merchant</div><div class="detail-value">{{ $ticket->merchant_name ?? '-' }}</div></div>
                    <div class="col-sm-4"><div class="detail-label">Contact</div><div class="detail-value">{{ $ticket->contact_number ?? '-' }}</div></div>
                    <div class="col-sm-4"><div class="detail-label">Terminal ID</div><div class="detail-value">{{ $ticket->terminal_id ?? '-' }}</div></div>
                    <div class="col-12"><div class="detail-label">Address</div><div class="detail-value">{{ $ticket->merchant_address ?? '-' }}</div></div>
                    <div class="col-sm-4"><div class="detail-label">Job Category</div><div class="detail-value">{{ $ticket->jobCategory?->category_name ?? '-' }}</div></div>
                    <div class="col-sm-4"><div class="detail-label">Job Type</div><div class="detail-value">{{ $ticket->jobType?->job_title ?? '-' }}</div></div>
                    <div class="col-sm-4"><div class="detail-label">Router ID(s)</div><div class="detail-value">{{ $ticket->getRouterIdsDisplay() }}</div></div>
                    @if($ticket->isReplacementJob())
                    <div class="col-sm-4"><div class="detail-label">Old Router ID(s)</div><div class="detail-value">{{ $ticket->getOldRouterIdsDisplay() }}</div></div>
                    @endif
                    @if($ticket->accessory_type_selected)
                    <div class="col-sm-4"><div class="detail-label">Accessory</div><div class="detail-value">{{ $ticket->getAccessoryTypeLabel() }} — {{ $ticket->accessoryItem?->item_name ?? '-' }} × {{ $ticket->accessory_qty }}</div></div>
                    @endif
                    <div class="col-sm-4"><div class="detail-label">Expected Start</div><div class="detail-value">{{ $ticket->expected_start_date?->format('d M Y') ?? '-' }}</div></div>
                    <div class="col-sm-4"><div class="detail-label">Expected End</div><div class="detail-value">{{ $ticket->expected_end_date?->format('d M Y') ?? '-' }}</div></div>
                    @if($ticket->scheduled_date)
                    <div class="col-sm-4"><div class="detail-label">Scheduled Date</div><div class="detail-value text-warning fw-semibold">{{ $ticket->scheduled_date->format('d M Y H:i') }}</div></div>
                    @endif
                    <div class="col-12"><div class="detail-label">Description</div><div class="detail-value">{{ $ticket->description }}</div></div>
                </div>
            </div>
        </div>

        {{-- ═══ FIX #4: Financial Summary ═══ --}}
        <div class="card financial-card">
            <div class="card-header"><i class="bi bi-currency-dollar me-2"></i>Financial Summary</div>
            <div class="card-body p-0">
                <div class="row g-0">
                    <div class="col fin-item fin-divider">
                        <div class="fin-label">Job Price</div>
                        <div class="fin-val text-dark">RM {{ number_format($ticket->price ?? 0, 2) }}</div>
                        <small class="text-muted">Supervisor rate</small>
                    </div>
                    <div class="col fin-item fin-divider">
                        <div class="fin-label">Mileage</div>
                        <div class="fin-val text-secondary">RM {{ number_format($ticket->mileage_amount ?? 0, 2) }}</div>
                        <small class="text-muted">{{ $ticket->mileage ?? 0 }} km × RM {{ $ticket->mileage_rate ?? 0 }}</small>
                    </div>
                    <div class="col fin-item fin-divider">
                        <div class="fin-label">Toll</div>
                        <div class="fin-val text-secondary">RM {{ number_format($ticket->toll ?? 0, 2) }}</div>
                    </div>
                    <div class="col fin-item fin-divider">
                        <div class="fin-label">Standby / Meal</div>
                        <div class="fin-val text-secondary">RM {{ number_format($ticket->standby_meal ?? 0, 2) }}</div>
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

        {{-- Assign / Reassign (Admin) --}}
        @can('assign', $ticket)
        <div class="card">
            <div class="card-header"><i class="bi bi-person-check me-2"></i>Technician Assignment</div>
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Assign Technician</label>
                        <select id="assignTechnicianSelect" class="form-select">
                            <option value="">— Select Technician —</option>
                            @foreach($technicians as $tech)
                                <option value="{{ $tech->id }}" {{ $ticket->technician_id == $tech->id ? 'selected' : '' }}>{{ $tech->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Remarks</label>
                        <input type="text" id="assignRemarks" class="form-control" placeholder="Optional remarks">
                    </div>
                    <div class="col-md-2">
                        @if(!$ticket->technician_id)
                            <button class="btn btn-primary w-100" onclick="assignTechnician()"><i class="bi bi-person-plus me-1"></i>Assign</button>
                        @else
                            <button class="btn btn-warning w-100" onclick="reassignTechnician()"><i class="bi bi-arrow-repeat me-1"></i>Reassign</button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endcan

        {{-- Claim Update --}}
        @can('updateClaim', $ticket)
        <div class="card">
            <div class="card-header"><i class="bi bi-receipt me-2"></i>Claim Update</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-3">
                        <label class="form-label">Mileage (km)</label>
                        <input type="number" step="0.01" class="form-control" id="claimMileage" value="{{ $ticket->mileage ?? 0 }}">
                    </div>
                    <div class="col-sm-3">
                        <label class="form-label">Toll (RM)</label>
                        <input type="number" step="0.01" class="form-control" id="claimToll" value="{{ $ticket->toll ?? 0 }}">
                    </div>
                    <div class="col-sm-3">
                        <label class="form-label">Standby / Meal (RM)</label>
                        <input type="number" step="0.01" class="form-control" id="claimMeal" value="{{ $ticket->standby_meal ?? 0 }}">
                    </div>
                    <div class="col-sm-3">
                        <label class="form-label">Mileage Remarks</label>
                        <input type="text" class="form-control" id="claimMileageRemarks" value="{{ $ticket->mileage_remarks ?? '' }}">
                    </div>
                    <div class="col-12 text-end">
                        <button class="btn btn-primary" onclick="saveClaim()"><i class="bi bi-save me-1"></i>Update Claim</button>
                    </div>
                </div>
            </div>
        </div>
        @endcan

        {{-- Status History --}}
        <div class="card">
            <div class="card-header"><i class="bi bi-clock-history me-2"></i>Status History</div>
            <div class="card-body">
                @forelse($ticket->statusHistory as $history)
                <div class="timeline-item {{ $history->to_status }}">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <span class="fw-semibold">
                                {{ $history->from_status ? ucfirst(str_replace('_',' ',$history->from_status)) . ' → ' : '' }}
                                {!! \App\Models\Ticket::getStatusBadge($history->to_status) !!}
                            </span>
                            <span class="text-muted small ms-2">by {{ $history->changedBy?->name ?? 'System' }}</span>
                        </div>
                        <small class="text-muted">{{ $history->created_at->format('d M Y H:i') }}</small>
                    </div>
                    @if($history->remarks)
                        <div class="small text-secondary mt-1"><i class="bi bi-chat-dots me-1"></i>{{ $history->remarks }}</div>
                    @endif
                    @if($history->reschedule_reason)
                        <div class="small text-warning mt-1"><i class="bi bi-calendar-x me-1"></i>Reschedule: {{ $history->reschedule_reason }}</div>
                    @endif
                    @if($history->proofs && $history->proofs->count())
                        <div class="mt-2 d-flex flex-wrap gap-2">
                            @foreach($history->proofs as $proof)
                                <a href="{{ asset('storage/' . $proof->file_path) }}" target="_blank" title="{{ $proof->proof_type }}">
                                    @if(str_contains($proof->mime_type ?? '', 'image'))
                                        <img src="{{ asset('storage/' . $proof->file_path) }}" class="proof-thumb" alt="{{ $proof->proof_type }}">
                                    @else
                                        <div class="proof-thumb d-flex align-items-center justify-content-center bg-light">
                                            <i class="bi bi-file-earmark-pdf fs-4 text-danger"></i>
                                        </div>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
                @empty
                    <p class="text-muted mb-0">No status history.</p>
                @endforelse
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
                            <strong class="small">{{ $comment->user->name }}
                                <span class="badge bg-secondary ms-1 text-capitalize">{{ $comment->user->roles->first()?->name ?? '' }}</span>
                            </strong>
                            <small class="text-muted">{{ $comment->created_at->format('d M Y H:i') }}</small>
                        </div>
                        <div class="comment-bubble {{ $comment->user_id === auth()->id() ? 'own' : '' }}">
                            {{ $comment->comment }}
                        </div>
                    </div>
                    @empty
                    <p class="text-muted small" id="noComments">No comments yet.</p>
                    @endforelse
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

    {{-- ═══ RIGHT COLUMN ═══ --}}
    <div class="col-lg-4">

        {{-- People --}}
        <div class="card">
            <div class="card-header"><i class="bi bi-people me-2"></i>People</div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="detail-label">Supervisor</div>
                    <div class="detail-value">{{ $ticket->supervisor?->name ?? 'Unassigned' }}</div>
                    @if($ticket->supervisor)
                        <small class="text-muted text-capitalize">{{ $ticket->supervisor->supervisor_type ?? '' }}</small>
                    @endif
                </div>
                <div class="mb-3">
                    <div class="detail-label">Technician</div>
                    <div class="detail-value">{{ $ticket->technician?->name ?? 'Unassigned' }}</div>
                </div>
                <hr>
                <div class="mb-2">
                    <div class="detail-label">Created By</div>
                    <div class="detail-value small">{{ $ticket->creator?->name ?? '-' }} · {{ $ticket->created_at?->format('d M Y H:i') }}</div>
                </div>
                <div>
                    <div class="detail-label">Last Updated By</div>
                    <div class="detail-value small">{{ $ticket->updater?->name ?? '-' }} · {{ $ticket->updated_at?->format('d M Y H:i') }}</div>
                </div>
            </div>
        </div>

        {{-- SLA Card --}}
        @if($ticket->sla_deadline)
        <div class="card border-{{ $ticket->isSlaBreach() ? 'danger' : ($ticket->sla_status === 'at_risk' ? 'warning' : 'success') }}">
            <div class="card-header bg-{{ $ticket->isSlaBreach() ? 'danger' : ($ticket->sla_status === 'at_risk' ? 'warning' : 'success') }} text-{{ $ticket->isSlaBreach() ? 'white' : ($ticket->sla_status === 'at_risk' ? 'dark' : 'white') }}">
                <i class="bi bi-stopwatch me-2"></i>SLA
            </div>
            <div class="card-body">
                <div class="mb-2"><div class="detail-label">Deadline</div><div class="detail-value">{{ $ticket->sla_deadline->format('d M Y H:i') }}</div></div>
                <div class="mb-2"><div class="detail-label">Remaining</div><div class="detail-value {{ $ticket->isSlaBreach() ? 'text-danger' : 'text-success' }}">{{ $ticket->sla_remaining ?? '-' }}</div></div>
                <div><div class="detail-label">Status</div><div class="detail-value text-capitalize">{{ str_replace('_',' ',$ticket->sla_status ?? '-') }}</div></div>
            </div>
        </div>
        @endif

        {{-- Timestamps --}}
        <div class="card">
            <div class="card-header"><i class="bi bi-calendar-check me-2"></i>Timeline</div>
            <div class="card-body">
                @foreach(['assigned_at' => 'Assigned','accepted_at' => 'Accepted','rejected_at' => 'Rejected','started_at' => 'Started','completed_at' => 'Completed','closed_at' => 'Closed'] as $field => $label)
                    @if($ticket->$field)
                    <div class="mb-2"><div class="detail-label">{{ $label }}</div><div class="detail-value small">{{ $ticket->$field->format('d M Y H:i') }}</div></div>
                    @endif
                @endforeach
            </div>
        </div>

    </div>{{-- /right --}}

</div>{{-- /row --}}

{{-- ═══════════════════════════════════════════════════════
     STATUS CHANGE MODAL  (FIX #2 accept/reject + FIX #3 scheduled_date)
═══════════════════════════════════════════════════════ --}}
<div class="modal fade" id="statusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-arrow-repeat me-2"></i>Change Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="targetStatus">

                <div class="mb-3">
                    <label class="form-label fw-semibold">New Status</label>
                    <div id="newStatusDisplay" class="form-control-plaintext fw-bold text-primary fs-5"></div>
                </div>

                {{-- Old terminal ID (replacement jobs) --}}
                @if($ticket->isReplacementJob() && !$ticket->old_terminal_id)
                <div class="mb-3" id="oldTerminalWrapper">
                    <label class="form-label fw-semibold">Old Router ID <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="oldTerminalId" placeholder="Enter old router/terminal ID">
                </div>
                @endif

                {{-- Reschedule reason (only for scheduled) --}}
                <div class="mb-3 d-none" id="rescheduleWrapper">
                    <label class="form-label fw-semibold">Reschedule Reason <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="rescheduleReason" rows="2" placeholder="Why is this being rescheduled?"></textarea>
                </div>

                {{-- FIX #3: Reschedule target date/time --}}
                <div class="mb-3 d-none" id="scheduledDateWrapper">
                    <label class="form-label fw-semibold">Reschedule To <span class="text-danger">*</span></label>
                    <input type="datetime-local" class="form-control" id="scheduledDate" min="{{ now()->format('Y-m-d\TH:i') }}">
                </div>

                {{-- Proof files --}}
                <div class="mb-3 d-none" id="proofWrapper">
                    <label class="form-label fw-semibold">Proof Files <span class="text-danger">*</span></label>
                    <div id="proofFieldsContainer"></div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Remarks</label>
                    <textarea class="form-control" id="statusRemarks" rows="2" placeholder="Optional remarks…"></textarea>
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
const CHANGE_STATUS_URL = '{{ route('admin.tickets.change-status', $ticket->id) }}';
const ASSIGN_URL        = '{{ route('admin.tickets.assign', $ticket->id) }}';
const REASSIGN_URL      = '{{ route('admin.tickets.reassign', $ticket->id) }}';
const CLAIM_URL         = '{{ route('admin.tickets.update-claim', $ticket->id) }}';
const COMMENT_URL       = '{{ route('admin.tickets.comment', $ticket->id) }}';

@php $proofTypeLabels = \App\Models\Ticket::getProofTypeLabels(); @endphp
const proofTypeLabels = @json($proofTypeLabels);
const proofRequiredMap = {
    scheduled:     ['whatsapp_screenshot','call_log_screenshot'],
    done_success:  ['test_slip'],
    done_fail:     ['service_form'],
};

function openStatusModal(status) {
    $('#targetStatus').val(status);
    const labelMap = {
        accepted: 'Accept', rejected: 'Reject', in_progress: 'In Progress',
        scheduled: 'Reschedule', done_success: 'Done / Success',
        done_fail: 'Done / Fail', closed: 'Close', open: 'Re-open', assigned: 'Re-assign'
    };
    $('#newStatusDisplay').text(labelMap[status] || status);
    $('#rescheduleWrapper, #scheduledDateWrapper, #proofWrapper').addClass('d-none');
    $('#rescheduleReason, #scheduledDate').val('');
    $('#statusRemarks').val('');
    $('#proofFieldsContainer').html('');

    if (status === 'scheduled') {
        $('#rescheduleWrapper, #scheduledDateWrapper').removeClass('d-none');
    }

    const proofTypes = proofRequiredMap[status] || [];
    if (proofTypes.length) {
        $('#proofWrapper').removeClass('d-none');
        let html = '';
        proofTypes.forEach(function(pt) {
            html += `<div class="mb-2">
                <label class="form-label small">${proofTypeLabels[pt] || pt} <span class="text-danger">*</span></label>
                <input type="file" class="form-control form-control-sm" name="proof_files[${pt}]" id="proof_${pt}" accept=".jpg,.jpeg,.png,.pdf">
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
        if (!reason) { showToast('Please enter a reschedule reason.', 'warning'); return; }
        if (!date)   { showToast('Please select a reschedule date/time.', 'warning'); return; }
        fd.append('reschedule_reason', reason);
        fd.append('scheduled_date', date);
    }

    const proofTypes = proofRequiredMap[status] || [];
    let proofOk = true;
    proofTypes.forEach(function(pt) {
        const el = document.getElementById('proof_' + pt);
        if (el && el.files[0]) {
            fd.append('proof_files[' + pt + ']', el.files[0]);
        } else if (el) {
            showToast('Please upload: ' + (proofTypeLabels[pt] || pt), 'warning');
            proofOk = false;
        }
    });
    if (!proofOk) return;

    const oldTerminal = $('#oldTerminalId');
    if (oldTerminal.length && oldTerminal.val()) fd.append('old_terminal_id', oldTerminal.val());

    $('#confirmStatusBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving…');

    $.ajax({
        url: CHANGE_STATUS_URL, type: 'POST', data: fd,
        processData: false, contentType: false,
        success: function(r) {
            bootstrap.Modal.getInstance(document.getElementById('statusModal')).hide();
            showToast(r.message, 'success');
            setTimeout(() => location.reload(), 1000);
        },
        error: function(xhr) {
            const msg = xhr.responseJSON?.message || 'Failed to update status.';
            showToast(msg, 'error');
        },
        complete: function() {
            $('#confirmStatusBtn').prop('disabled', false).html('<i class="bi bi-check-circle me-1"></i>Confirm');
        }
    });
}

function assignTechnician() {
    const tid     = $('#assignTechnicianSelect').val();
    const remarks = $('#assignRemarks').val();
    if (!tid) { showToast('Please select a technician.', 'warning'); return; }
    $.post(ASSIGN_URL, { _token: $('meta[name="csrf-token"]').attr('content'), technician_id: tid, remarks: remarks })
        .done(r => { showToast(r.message, 'success'); setTimeout(() => location.reload(), 1000); })
        .fail(xhr => showToast(xhr.responseJSON?.message || 'Failed.', 'error'));
}

function reassignTechnician() {
    const tid     = $('#assignTechnicianSelect').val();
    const remarks = $('#assignRemarks').val();
    if (!tid) { showToast('Please select a technician.', 'warning'); return; }
    $.post(REASSIGN_URL, { _token: $('meta[name="csrf-token"]').attr('content'), technician_id: tid, remarks: remarks })
        .done(r => { showToast(r.message, 'success'); setTimeout(() => location.reload(), 1000); })
        .fail(xhr => showToast(xhr.responseJSON?.message || 'Failed.', 'error'));
}

function saveClaim() {
    $.post(CLAIM_URL, {
        _token: $('meta[name="csrf-token"]').attr('content'),
        mileage: $('#claimMileage').val(),
        toll: $('#claimToll').val(),
        standby_meal: $('#claimMeal').val(),
        mileage_remarks: $('#claimMileageRemarks').val(),
    }).done(r => showToast(r.message, 'success'))
      .fail(xhr => showToast(xhr.responseJSON?.message || 'Failed.', 'error'));
}

function submitComment() {
    const text = $('#commentText').val().trim();
    if (!text) { showToast('Comment cannot be empty.', 'warning'); return; }
    $.post(COMMENT_URL, { _token: $('meta[name="csrf-token"]').attr('content'), comment: text })
        .done(function(r) {
            $('#noComments').remove();
            const html = `<div class="mb-3">
                <div class="d-flex justify-content-between mb-1">
                    <strong class="small">${r.comment.user_name} <span class="badge bg-secondary ms-1">${r.comment.user_role}</span></strong>
                    <small class="text-muted">${r.comment.created_at}</small>
                </div>
                <div class="comment-bubble own">${$('<div>').text(r.comment.comment).html()}</div>
            </div>`;
            $('#commentsContainer').prepend(html);
            $('#commentText').val('');
        })
        .fail(xhr => showToast(xhr.responseJSON?.message || 'Failed.', 'error'));
}
</script>
@endpush
