@extends('layouts.app')
@section('title', 'Ticket ' . $ticket->ticket_no)

@section('content')
@php
    $roleName = 'supervisor';
    $user = auth()->user();
    $sv = $ticket->supervisor;
    $isInternal = $user->isInternalSupervisor();
    $isExternal = $user->isExternalSupervisor();
    $claimApplicable = $isExternal;
@endphp

<div class="container-fluid">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="mb-1"><i class="bi bi-ticket-detailed me-2"></i>{{ $ticket->ticket_no }}</h4>
            <div class="d-flex gap-2 align-items-center flex-wrap">
                {!! \App\Models\Ticket::getStatusBadge($ticket->status) !!}
                {!! \App\Models\Ticket::getPriorityBadge($ticket->priority) !!}
                @if($ticket->isSlaBreach())
                <span class="badge bg-danger"><i class="bi bi-exclamation-triangle me-1"></i>SLA Breached</span>
                @endif
                @if($isInternal)
                <span class="badge bg-success">Internal Supervisor</span>
                @else
                <span class="badge bg-warning text-dark">External Supervisor</span>
                @endif
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('supervisor.tickets.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Back
            </a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            {{-- Ticket Details --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white"><h6 class="mb-0">Ticket Details</h6></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4"><strong>Vendor:</strong><br>{{ $ticket->vendor?->vendor_name ?? '-' }}</div>
                        <div class="col-md-4"><strong>Branch:</strong><br>{{ $ticket->vendorBranch?->branch_name ?? '-' }}</div>
                        <div class="col-md-4"><strong>Vendor Ref:</strong><br>{{ $ticket->vendor_ticket_ref_no ?? '-' }}</div>
                        <div class="col-md-4"><strong>State:</strong><br>{{ $ticket->state?->name ?? '-' }}</div>
                        <div class="col-md-4"><strong>City:</strong><br>{{ $ticket->city?->name ?? '-' }}</div>
                        <div class="col-md-4"><strong>TID:</strong><br>{{ $ticket->tid ?? '-' }}</div>
                        <div class="col-md-6"><strong>Merchant:</strong><br>{{ $ticket->merchant_name }}</div>
                        <div class="col-md-6"><strong>Contact:</strong><br>{{ $ticket->contact_number }}</div>
                        <div class="col-12"><strong>Address:</strong><br>{{ $ticket->merchant_address }}</div>
                    </div>
                </div>
            </div>

            {{-- Job Info --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white"><h6 class="mb-0">Job Information</h6></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4"><strong>Job Category:</strong><br>{{ $ticket->jobCategory?->category_name ?? '-' }}</div>
                        <div class="col-md-4"><strong>Job Type:</strong><br>{{ $ticket->jobType?->job_title ?? '-' }}</div>
                        <div class="col-md-4"><strong>Price (RM):</strong><br><span class="fw-bold text-primary fs-5">{{ number_format($ticket->price ?? 0, 2) }}</span></div>
                        @if($ticket->terminal_id)
                        <div class="col-md-4"><strong>Terminal ID:</strong><br>{{ $ticket->terminal_id }}</div>
                        @endif
                        @if($ticket->router_id)
                        <div class="col-md-4"><strong>Router ID:</strong><br>{{ $ticket->router_id }}</div>
                        @endif
                        @if($ticket->old_terminal_id)
                        <div class="col-md-4"><strong>Old Terminal ID:</strong><br><span class="text-warning">{{ $ticket->old_terminal_id }}</span></div>
                        @endif
                        @if($ticket->expected_start_date)
                        <div class="col-md-4"><strong>Expected Start:</strong><br>{{ $ticket->expected_start_date->format('d M Y') }}</div>
                        @endif
                        @if($ticket->expected_end_date)
                        <div class="col-md-4"><strong>Expected End:</strong><br>{{ $ticket->expected_end_date->format('d M Y') }}</div>
                        @endif
                        <div class="col-12"><strong>Description:</strong><br>{{ $ticket->description }}</div>
                    </div>
                </div>
            </div>

            {{-- Accept/Reject for External Supervisor (when assigned to them directly) --}}
            @if($isExternal && $ticket->status === 'assigned' && $ticket->supervisor_id === $user->id && !$ticket->technician_id)
            <div class="card shadow-sm mb-4 border-primary">
                <div class="card-header bg-primary text-white"><h6 class="mb-0"><i class="bi bi-question-circle me-2"></i>Accept or Reject This Ticket?</h6></div>
                <div class="card-body">
                    <p>This ticket has been assigned to you. Please accept or reject it.</p>
                    <form id="acceptRejectForm">
                        <div class="mb-3">
                            <label class="form-label">Remarks</label>
                            <input type="text" name="remarks" class="form-control" placeholder="Optional remarks">
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-success" id="btnAccept"><i class="bi bi-check-lg me-1"></i>Accept</button>
                            <button type="button" class="btn btn-danger" id="btnReject"><i class="bi bi-x-lg me-1"></i>Reject</button>
                        </div>
                    </form>
                </div>
            </div>
            @endif

            {{-- Status Change --}}
            @if(count($allowedTransitions) > 0 && !($isExternal && $ticket->status === 'assigned'))
            @can('changeStatus', $ticket)
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white"><h6 class="mb-0"><i class="bi bi-arrow-repeat me-2"></i>Change Status</h6></div>
                <div class="card-body">
                    <form id="statusForm">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">New Status</label>
                                <select name="status" id="newStatus" class="form-select" required>
                                    <option value="">Select</option>
                                    @foreach($allowedTransitions as $st)
                                    @if(!in_array($st, ['accepted','rejected']))
                                    <option value="{{ $st }}">{{ $statuses[$st] ?? ucfirst($st) }}</option>
                                    @endif
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Remarks</label>
                                <input type="text" name="remarks" class="form-control">
                            </div>
                            <div class="col-12" id="rescheduleGroup" style="display:none;">
                                <label class="form-label">Reschedule Reason <span class="text-danger">*</span></label>
                                <textarea name="reschedule_reason" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="col-12" id="proofSection" style="display:none;">
                                <label class="form-label">Proof Files</label>
                                <input type="file" name="proof_files[]" class="form-control" multiple accept=".jpg,.jpeg,.png,.pdf">
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Update Status</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            @endcan
            @endif

            {{-- Assign/Reassign Technician (Internal supervisor only) --}}
            @if($isInternal && $technicians->count() > 0)
            @can('assign', $ticket)
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white"><h6 class="mb-0"><i class="bi bi-person-plus me-2"></i>{{ $ticket->technician_id ? 'Reassign' : 'Assign' }} Technician</h6></div>
                <div class="card-body">
                    <form id="assignForm">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <select name="technician_id" class="form-select" required>
                                    <option value="">Select Technician</option>
                                    @foreach($technicians as $t)
                                    <option value="{{ $t->id }}" {{ $ticket->technician_id == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4"><input type="text" name="remarks" class="form-control" placeholder="Remarks"></div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-success w-100">
                                    <i class="bi bi-person-check me-1"></i>{{ $ticket->technician_id ? 'Reassign' : 'Assign' }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            @endcan
            @endif

            {{-- Claims --}}
            @if($claimApplicable)
            @can('updateClaim', $ticket)
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white"><h6 class="mb-0"><i class="bi bi-receipt me-2"></i>Claim Information</h6></div>
                <div class="card-body">
                    <form id="claimForm">
                        <div class="row g-3">
                            <div class="col-md-3"><label class="form-label">Mileage (KM)</label><input type="number" name="mileage" class="form-control" step="0.01" value="{{ $ticket->mileage ?? 0 }}"></div>
                            <div class="col-md-3"><label class="form-label">Rate</label><input type="text" class="form-control bg-light" readonly value="{{ number_format($ticket->mileage_rate ?? 0, 2) }}"></div>
                            <div class="col-md-3"><label class="form-label">Toll (RM)</label><input type="number" name="toll" class="form-control" step="0.01" value="{{ $ticket->toll ?? 0 }}"></div>
                            <div class="col-md-3"><label class="form-label">Standby/Meal</label><input type="number" name="standby_meal" class="form-control" step="0.01" value="{{ $ticket->standby_meal ?? 0 }}"></div>
                            <div class="col-md-6"><label class="form-label">Mileage Remarks</label><input type="text" name="mileage_remarks" class="form-control" value="{{ $ticket->mileage_remarks }}"></div>
                            <div class="col-md-3"><label class="form-label">Total Claim</label><input type="text" class="form-control bg-light fw-bold" readonly value="RM {{ number_format($ticket->total_claim_amount ?? 0, 2) }}"></div>
                            <div class="col-md-3 d-flex align-items-end"><button type="submit" class="btn btn-primary w-100"><i class="bi bi-save me-1"></i>Update</button></div>
                        </div>
                    </form>
                </div>
            </div>
            @endcan
            @elseif($isInternal)
            <div class="alert alert-info"><i class="bi bi-info-circle me-2"></i>Claims are not applicable for internal supervisor tickets.</div>
            @endif

            {{-- Comments --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white"><h6 class="mb-0"><i class="bi bi-chat-dots me-2"></i>Comments</h6></div>
                <div class="card-body">
                    @can('addComment', $ticket)
                    <form id="commentForm" class="mb-3">
                        <div class="input-group">
                            <input type="text" name="comment" class="form-control" placeholder="Add a comment..." required>
                            <button type="submit" class="btn btn-primary"><i class="bi bi-send"></i></button>
                        </div>
                    </form>
                    @endcan
                    <div id="commentsList">
                        @forelse($ticket->comments as $c)
                        <div class="border-bottom pb-2 mb-2">
                            <div class="d-flex justify-content-between"><strong>{{ $c->user?->name }}</strong><small class="text-muted">{{ $c->created_at->format('d M Y H:i') }}</small></div>
                            <p class="mb-0">{{ $c->comment }}</p>
                        </div>
                        @empty
                        <p class="text-muted mb-0">No comments yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Status History --}}
            <div class="card shadow-sm">
                <div class="card-header bg-white"><h6 class="mb-0"><i class="bi bi-clock-history me-2"></i>Status History</h6></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light"><tr><th>From</th><th>To</th><th>By</th><th>Remarks</th><th>Date</th></tr></thead>
                            <tbody>
                                @foreach($ticket->statusHistory as $h)
                                <tr>
                                    <td>{!! $h->from_status ? \App\Models\Ticket::getStatusBadge($h->from_status) : '-' !!}</td>
                                    <td>{!! \App\Models\Ticket::getStatusBadge($h->to_status) !!}</td>
                                    <td>{{ $h->changedBy?->name ?? '-' }}</td>
                                    <td>{{ $h->remarks ?? '-' }}</td>
                                    <td>{{ $h->created_at->format('d M Y H:i') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- RIGHT --}}
        <div class="col-lg-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white"><h6 class="mb-0">Summary</h6></div>
                <div class="card-body">
                    <div class="mb-2"><strong>Supervisor:</strong> {{ $sv?->name ?? '-' }}
                        @if($isInternal) <span class="badge bg-success">Internal</span> @else <span class="badge bg-warning text-dark">External</span> @endif
                    </div>
                    <div class="mb-2"><strong>Technician:</strong> {{ $ticket->technician?->name ?? 'Unassigned' }}</div>
                    <div class="mb-2"><strong>SLA Deadline:</strong> {{ $ticket->sla_deadline?->format('d M Y H:i') ?? '-' }}</div>
                    <div class="mb-2"><strong>SLA Remaining:</strong>
                        <span class="{{ $ticket->isSlaBreach() ? 'text-danger fw-bold' : 'text-success' }}">{{ $ticket->sla_remaining ?? '-' }}</span>
                    </div>
                    <div class="mb-2"><strong>Created:</strong> {{ $ticket->created_at->format('d M Y H:i') }}</div>
                    <div class="mb-2"><strong>Created By:</strong> {{ $ticket->creator?->name ?? '-' }}</div>
                    @if($ticket->assigned_at)<div class="mb-2"><strong>Assigned:</strong> {{ $ticket->assigned_at->format('d M Y H:i') }}</div>@endif
                    @if($ticket->accepted_at)<div class="mb-2"><strong>Accepted:</strong> {{ $ticket->accepted_at->format('d M Y H:i') }}</div>@endif
                    @if($ticket->started_at)<div class="mb-2"><strong>Started:</strong> {{ $ticket->started_at->format('d M Y H:i') }}</div>@endif
                    @if($ticket->completed_at)<div class="mb-2"><strong>Completed:</strong> {{ $ticket->completed_at->format('d M Y H:i') }}</div>@endif
                </div>
            </div>

            @if($ticket->proofs->count() > 0)
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white"><h6 class="mb-0"><i class="bi bi-paperclip me-2"></i>Proofs</h6></div>
                <div class="card-body">
                    @foreach($ticket->proofs as $proof)
                    <div class="mb-2">
                        <a href="{{ asset('storage/' . $proof->file_path) }}" target="_blank"><i class="bi bi-file-earmark me-1"></i>{{ $proof->file_name }}</a>
                        <br><small class="text-muted">{{ ucfirst(str_replace('_',' ', $proof->proof_type)) }}</small>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    const ticketId = {{ $ticket->id }};
    const baseUrl = '/supervisor/tickets/' + ticketId;

    // Accept/Reject buttons (External supervisor)
    $('#btnAccept').on('click', function() {
        let remarks = $('#acceptRejectForm [name="remarks"]').val();
        $.post(baseUrl + '/change-status', { status: 'accepted', remarks: remarks, _token: '{{ csrf_token() }}' }, function(res) {
            if (res.success) { showToast('Ticket accepted!', 'success'); setTimeout(() => location.reload(), 1000); }
            else showToast(res.message, 'error');
        }).fail(xhr => showToast(xhr.responseJSON?.message || 'Error', 'error'));
    });

    $('#btnReject').on('click', function() {
        let remarks = $('#acceptRejectForm [name="remarks"]').val();
        if (!remarks) { showToast('Please provide remarks for rejection.', 'warning'); return; }
        $.post(baseUrl + '/change-status', { status: 'rejected', remarks: remarks, _token: '{{ csrf_token() }}' }, function(res) {
            if (res.success) { showToast('Ticket rejected.', 'success'); setTimeout(() => location.reload(), 1000); }
            else showToast(res.message, 'error');
        }).fail(xhr => showToast(xhr.responseJSON?.message || 'Error', 'error'));
    });

    // Status change
    $('#newStatus').on('change', function() {
        let st = $(this).val();
        $('#rescheduleGroup').toggle(st === 'scheduled');
        $('#proofSection').toggle(['scheduled','done_success','done_fail'].includes(st));
    });

    $('#statusForm').on('submit', function(e) {
        e.preventDefault();
        let fd = new FormData(this);
        $.ajax({
            url: baseUrl + '/change-status', method: 'POST', data: fd, processData: false, contentType: false,
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            success: (res) => { if (res.success) { showToast(res.message, 'success'); setTimeout(() => location.reload(), 1000); } else showToast(res.message, 'error'); },
            error: (xhr) => showToast(xhr.responseJSON?.message || 'Error', 'error')
        });
    });

    // Assign
    $('#assignForm').on('submit', function(e) {
        e.preventDefault();
        let url = '{{ $ticket->technician_id ? route("supervisor.tickets.reassign", $ticket->id) : route("supervisor.tickets.assign", $ticket->id) }}';
        $.post(url, $(this).serialize() + '&_token={{ csrf_token() }}', function(res) {
            if (res.success) { showToast(res.message, 'success'); setTimeout(() => location.reload(), 1000); }
            else showToast(res.message, 'error');
        }).fail(xhr => showToast(xhr.responseJSON?.message || 'Error', 'error'));
    });

    // Claim
    $('#claimForm').on('submit', function(e) {
        e.preventDefault();
        $.post(baseUrl + '/update-claim', $(this).serialize() + '&_token={{ csrf_token() }}', function(res) {
            if (res.success) { showToast(res.message, 'success'); setTimeout(() => location.reload(), 1000); }
        }).fail(xhr => showToast(xhr.responseJSON?.message || 'Error', 'error'));
    });

    // Comment
    $('#commentForm').on('submit', function(e) {
        e.preventDefault();
        $.post(baseUrl + '/comment', $(this).serialize() + '&_token={{ csrf_token() }}', function(res) {
            if (res.success) {
                let c = res.comment;
                $('#commentsList').prepend(`<div class="border-bottom pb-2 mb-2"><div class="d-flex justify-content-between"><strong>${c.user_name}</strong><small class="text-muted">${c.created_at}</small></div><p class="mb-0">${c.comment}</p></div>`);
                $('[name="comment"]').val('');
            }
        });
    });
});
</script>
@endpush
