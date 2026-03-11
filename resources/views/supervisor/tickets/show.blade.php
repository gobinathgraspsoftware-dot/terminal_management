@extends('layouts.app')

@section('title', 'Ticket ' . $ticket->ticket_no)

@push('styles')
<style>
    .ticket-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 10px;
        padding: 20px;
    }
    .ticket-header .ticket-no { font-size: 1.6rem; font-weight: 700; }
    .detail-label { font-size: 0.8rem; color: #6c757d; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px; }
    .detail-value { font-size: 0.95rem; font-weight: 500; }
    .sla-timer { font-size: 1.1rem; font-weight: 700; }
    .sla-timer.breached { color: #dc3545; animation: pulse 1.5s infinite; }
    @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:0.6} }
    .comment-card { border-left: 3px solid #0d6efd; background: #f8f9fa; border-radius: 8px; margin-bottom: 12px; }
    .comment-card.own { border-left-color: #198754; }
    .comment-card .comment-meta { font-size: 0.8rem; color: #6c757d; }
    .comment-card .comment-body { margin-top: 4px; white-space: pre-wrap; }
    .timeline-item { position: relative; padding-left: 30px; padding-bottom: 16px; border-left: 2px solid #dee2e6; }
    .timeline-item:last-child { border-left-color: transparent; }
    .timeline-item::before {
        content: '';
        position: absolute;
        left: -6px;
        top: 4px;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #0d6efd;
        border: 2px solid #fff;
    }
    .timeline-item .timeline-time { font-size: 0.75rem; color: #6c757d; }
    .action-section { background: #fff; border: 1px solid #dee2e6; border-radius: 10px; padding: 16px; margin-bottom: 16px; }
    .comments-container { max-height: 500px; overflow-y: auto; }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Breadcrumb -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('supervisor.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('supervisor.tickets.index') }}">Tickets</a></li>
                <li class="breadcrumb-item active">{{ $ticket->ticket_no }}</li>
            </ol>
        </nav>
        <div class="d-flex gap-2">
            @if(!in_array($ticket->status, ['completed', 'closed']))
            @can('edit_tickets')
            <a href="{{ route('supervisor.tickets.edit', $ticket->id) }}" class="btn btn-sm btn-outline-warning">
                <i class="bi bi-pencil me-1"></i>Edit
            </a>
            @endcan
            @endif
            <a href="{{ route('supervisor.tickets.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Back
            </a>
        </div>
    </div>

    <!-- Ticket Header -->
    <div class="ticket-header mb-4">
        <div class="row align-items-center">
            <div class="col-md-6">
                <div class="ticket-no">{{ $ticket->ticket_no }}</div>
                <div class="mt-1">
                    <span class="badge bg-{{ \App\Models\Ticket::getStatusBadge($ticket->status) }} fs-6">
                        {{ $statuses[$ticket->status] ?? $ticket->status }}
                    </span>
                    <span class="badge bg-{{ \App\Models\Ticket::getPriorityBadge($ticket->priority) }} fs-6 ms-1">
                        {{ ucfirst($ticket->priority) }}
                    </span>
                </div>
            </div>
            <div class="col-md-6 text-md-end mt-2 mt-md-0">
                <div class="detail-label text-white-50">SLA Timer</div>
                @if($ticket->sla_deadline)
                    <div class="sla-timer {{ $ticket->isSlaBreach() ? 'breached' : '' }}">
                        <i class="bi bi-clock me-1"></i>{{ $ticket->sla_remaining }}
                    </div>
                    <small class="text-white-50">Deadline: {{ $ticket->sla_deadline->format('d M Y H:i') }}</small>
                @else
                    <div class="sla-timer">No SLA Set</div>
                @endif
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- LEFT: Details + Actions -->
        <div class="col-lg-8">
            <!-- Ticket Details -->
            <div class="card mb-4">
                <div class="card-header"><h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Ticket Details</h6></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="detail-label">Vendor</div>
                            <div class="detail-value">{{ $ticket->vendor?->vendor_name ?? '-' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-label">Branch</div>
                            <div class="detail-value">{{ $ticket->vendorBranch?->branch_name ?? '-' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-label">State</div>
                            <div class="detail-value">{{ $ticket->state?->name ?? '-' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-label">District</div>
                            <div class="detail-value">{{ $ticket->city?->name ?? '-' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-label">Supervisor</div>
                            <div class="detail-value">{{ $ticket->supervisor?->name ?? '-' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-label">Assignee</div>
                            <div class="detail-value">
                                @if($ticket->technician)
                                    <span class="badge bg-info">{{ $ticket->technician->name }}</span>
                                @else
                                    <span class="text-muted fst-italic">Unassigned</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-label">Job Type</div>
                            <div class="detail-value">{{ $ticket->jobType?->name ?? '-' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-label">Created By</div>
                            <div class="detail-value">{{ $ticket->creator?->name ?? '-' }} <small class="text-muted">{{ $ticket->created_at->format('d M Y H:i') }}</small></div>
                        </div>
                        <div class="col-12">
                            <div class="detail-label">Description</div>
                            <div class="detail-value bg-light p-3 rounded" style="white-space: pre-wrap;">{{ $ticket->description }}</div>
                        </div>
                        @if($ticket->reschedule_reason)
                        <div class="col-12">
                            <div class="detail-label text-warning">Reschedule Reason</div>
                            <div class="detail-value bg-warning bg-opacity-10 p-3 rounded">{{ $ticket->reschedule_reason }}</div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Actions: Status Change & Assign -->
            @if($ticket->status !== 'closed')
            <div class="row g-3 mb-4">
                <!-- Status Change -->
                @if(count($allowedTransitions) > 0)
                <div class="col-md-6">
                    <div class="action-section">
                        <h6 class="mb-3"><i class="bi bi-arrow-repeat me-2"></i>Change Status</h6>
                        <form id="statusForm">
                            <div class="mb-2">
                                <select id="newStatus" class="form-select form-select-sm" required>
                                    <option value="">Select Status</option>
                                    @foreach($allowedTransitions as $trans)
                                    <option value="{{ $trans }}">{{ $statuses[$trans] ?? $trans }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div id="rescheduleReasonWrap" class="mb-2 d-none">
                                <textarea id="rescheduleReason" class="form-control form-control-sm" rows="2" placeholder="Reschedule reason..."></textarea>
                            </div>
                            <div class="mb-2">
                                <textarea id="statusRemarks" class="form-control form-control-sm" rows="2" placeholder="Remarks (optional)"></textarea>
                            </div>
                            <button type="submit" class="btn btn-sm btn-primary w-100" id="btnChangeStatus">
                                <i class="bi bi-check-circle me-1"></i>Update Status
                            </button>
                        </form>
                    </div>
                </div>
                @endif

                <!-- Assign Technician -->
                @can('assign_tickets')
                @if(!in_array($ticket->status, ['completed', 'closed']))
                <div class="col-md-6">
                    <div class="action-section">
                        <h6 class="mb-3"><i class="bi bi-person-plus me-2"></i>Assign Technician</h6>
                        <form id="assignForm">
                            <div class="mb-2">
                                <select id="assignTechnician" class="form-select form-select-sm" required>
                                    <option value="">Select Technician</option>
                                    @foreach($technicians as $tech)
                                    <option value="{{ $tech->id }}" {{ $ticket->technician_id == $tech->id ? 'selected' : '' }}>
                                        {{ $tech->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-2">
                                <textarea id="assignRemarks" class="form-control form-control-sm" rows="2" placeholder="Assignment remarks (optional)"></textarea>
                            </div>
                            <button type="submit" class="btn btn-sm btn-info w-100 text-white" id="btnAssign">
                                <i class="bi bi-person-check me-1"></i>Assign
                            </button>
                        </form>
                    </div>
                </div>
                @endif
                @endcan
            </div>
            @endif

            <!-- Comments Section -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="bi bi-chat-dots me-2"></i>Comments ({{ $ticket->comments->count() }})</h6>
                </div>
                <div class="card-body">
                    <!-- Comments List -->
                    <div class="comments-container mb-3" id="commentsContainer">
                        @forelse($ticket->comments as $comment)
                        <div class="comment-card p-3 {{ $comment->user_id === auth()->id() ? 'own' : '' }}">
                            <div class="comment-meta">
                                <strong>{{ $comment->user->name }}</strong>
                                <span class="badge bg-{{ $comment->user->roles->first()?->name === 'admin' ? 'danger' : ($comment->user->roles->first()?->name === 'supervisor' ? 'warning' : 'info') }} ms-1" style="font-size:0.7rem;">
                                    {{ ucfirst($comment->user->roles->first()?->name ?? 'user') }}
                                </span>
                                <span class="ms-2">{{ $comment->created_at->format('d M Y H:i') }}</span>
                            </div>
                            <div class="comment-body">{{ $comment->comment }}</div>
                        </div>
                        @empty
                        <div class="text-center text-muted py-3" id="noComments">
                            <i class="bi bi-chat-square-text" style="font-size:2rem;"></i>
                            <p class="mt-2 mb-0">No comments yet. Start the conversation!</p>
                        </div>
                        @endforelse
                    </div>

                    <!-- Add Comment -->
                    @if($ticket->status !== 'closed')
                    <form id="commentForm" class="border-top pt-3">
                        <div class="input-group">
                            <textarea id="commentText" class="form-control" rows="2" placeholder="Write a comment..." required></textarea>
                            <button type="submit" class="btn btn-primary" id="btnComment">
                                <i class="bi bi-send"></i>
                            </button>
                        </div>
                    </form>
                    @endif
                </div>
            </div>
        </div>

        <!-- RIGHT: Timeline -->
        <div class="col-lg-4">
            <!-- Quick Info -->
            <div class="card mb-4">
                <div class="card-header"><h6 class="mb-0"><i class="bi bi-clock-history me-2"></i>Timeline</h6></div>
                <div class="card-body p-3">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <small class="text-muted">Created</small>
                            <small>{{ $ticket->created_at->format('d M Y H:i') }}</small>
                        </div>
                        @if($ticket->assigned_at)
                        <div class="d-flex justify-content-between mb-1">
                            <small class="text-muted">Assigned</small>
                            <small>{{ $ticket->assigned_at->format('d M Y H:i') }}</small>
                        </div>
                        @endif
                        @if($ticket->started_at)
                        <div class="d-flex justify-content-between mb-1">
                            <small class="text-muted">Started</small>
                            <small>{{ $ticket->started_at->format('d M Y H:i') }}</small>
                        </div>
                        @endif
                        @if($ticket->completed_at)
                        <div class="d-flex justify-content-between mb-1">
                            <small class="text-muted">Completed</small>
                            <small>{{ $ticket->completed_at->format('d M Y H:i') }}</small>
                        </div>
                        @endif
                        @if($ticket->closed_at)
                        <div class="d-flex justify-content-between mb-1">
                            <small class="text-muted">Closed</small>
                            <small>{{ $ticket->closed_at->format('d M Y H:i') }}</small>
                        </div>
                        @endif
                    </div>

                    <hr>

                    <!-- Status History -->
                    <h6 class="small text-muted text-uppercase mb-3">Status History</h6>
                    @foreach($ticket->statusHistory as $history)
                    <div class="timeline-item">
                        <div class="timeline-time">{{ $history->created_at?->format('d M Y H:i') }}</div>
                        <div>
                            @if($history->from_status)
                            <span class="badge bg-{{ \App\Models\Ticket::getStatusBadge($history->from_status) }}" style="font-size:0.7rem;">
                                {{ $statuses[$history->from_status] ?? $history->from_status }}
                            </span>
                            <i class="bi bi-arrow-right mx-1"></i>
                            @endif
                            <span class="badge bg-{{ \App\Models\Ticket::getStatusBadge($history->to_status) }}" style="font-size:0.7rem;">
                                {{ $statuses[$history->to_status] ?? $history->to_status }}
                            </span>
                        </div>
                        <small class="text-muted">by {{ $history->changedBy?->name ?? 'System' }}</small>
                        @if($history->remarks)
                        <div class="small text-muted fst-italic mt-1">{{ $history->remarks }}</div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Show/hide reschedule reason
    $('#newStatus').on('change', function() {
        if ($(this).val() === 'rescheduled') {
            $('#rescheduleReasonWrap').removeClass('d-none');
        } else {
            $('#rescheduleReasonWrap').addClass('d-none');
        }
    });

    // Status change
    $('#statusForm').on('submit', function(e) {
        e.preventDefault();
        var status = $('#newStatus').val();
        if (!status) { showToast('Please select a status.', 'warning'); return; }

        var $btn = $('#btnChangeStatus');
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        $.ajax({
            url: '{{ route("supervisor.tickets.change-status", $ticket->id) }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                status: status,
                remarks: $('#statusRemarks').val(),
                reschedule_reason: $('#rescheduleReason').val()
            },
            success: function(res) {
                if (res.success) {
                    showToast(res.message);
                    location.reload();
                }
            },
            error: function(xhr) {
                showToast(xhr.responseJSON?.message || 'Failed to update status.', 'error');
                $btn.prop('disabled', false).html('<i class="bi bi-check-circle me-1"></i>Update Status');
            }
        });
    });

    // Assign technician
    $('#assignForm').on('submit', function(e) {
        e.preventDefault();
        var techId = $('#assignTechnician').val();
        if (!techId) { showToast('Please select a technician.', 'warning'); return; }

        var $btn = $('#btnAssign');
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        $.ajax({
            url: '{{ route("supervisor.tickets.assign", $ticket->id) }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                technician_id: techId,
                remarks: $('#assignRemarks').val()
            },
            success: function(res) {
                if (res.success) {
                    showToast(res.message);
                    location.reload();
                }
            },
            error: function(xhr) {
                showToast(xhr.responseJSON?.message || 'Failed to assign.', 'error');
                $btn.prop('disabled', false).html('<i class="bi bi-person-check me-1"></i>Assign');
            }
        });
    });

    // Add comment
    $('#commentForm').on('submit', function(e) {
        e.preventDefault();
        var comment = $('#commentText').val().trim();
        if (!comment) return;

        var $btn = $('#btnComment');
        $btn.prop('disabled', true);

        $.ajax({
            url: '{{ route("supervisor.tickets.comment", $ticket->id) }}',
            method: 'POST',
            data: { _token: '{{ csrf_token() }}', comment: comment },
            success: function(res) {
                if (res.success) {
                    $('#noComments').remove();
                    var c = res.comment;
                    var roleBadge = c.user_role === 'admin' ? 'danger' : (c.user_role === 'supervisor' ? 'warning' : 'info');
                    var html = '<div class="comment-card p-3 own">' +
                        '<div class="comment-meta">' +
                        '<strong>' + c.user_name + '</strong> ' +
                        '<span class="badge bg-' + roleBadge + ' ms-1" style="font-size:0.7rem;">' + c.user_role.charAt(0).toUpperCase() + c.user_role.slice(1) + '</span> ' +
                        '<span class="ms-2">' + c.created_at + '</span></div>' +
                        '<div class="comment-body">' + $('<div>').text(c.comment).html() + '</div></div>';
                    $('#commentsContainer').append(html);
                    $('#commentText').val('');

                    // Scroll to bottom
                    var container = document.getElementById('commentsContainer');
                    container.scrollTop = container.scrollHeight;
                }
            },
            error: function() { showToast('Failed to add comment.', 'error'); },
            complete: function() { $btn.prop('disabled', false); }
        });
    });

    // Auto-scroll comments to bottom
    var container = document.getElementById('commentsContainer');
    if (container) container.scrollTop = container.scrollHeight;
});
</script>
@endpush
