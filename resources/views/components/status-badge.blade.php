{{-- 
    Status Badge Component
    Usage: @include('components.status-badge', ['status' => 'active', 'type' => 'vendor'])
--}}

@php
    $status = $status ?? 'unknown';
    $type = $type ?? 'general';
    
    // Define badge colors based on type and status
    $badgeClass = match($type) {
        'vendor', 'partner', 'client' => match($status) {
            'active' => 'bg-success',
            'inactive' => 'bg-secondary',
            default => 'bg-warning'
        },
        'po', 'purchase_order' => match($status) {
            'draft' => 'bg-secondary',
            'pending_approval' => 'bg-warning',
            'approved' => 'bg-info',
            'sent' => 'bg-primary',
            'open' => 'bg-success',
            'partially_received' => 'bg-info',
            'fully_received' => 'bg-success',
            'closed' => 'bg-dark',
            'cancelled' => 'bg-danger',
            default => 'bg-secondary'
        },
        'invoice' => match($status) {
            'draft' => 'bg-secondary',
            'pending' => 'bg-warning',
            'approved' => 'bg-info',
            'sent' => 'bg-primary',
            'paid' => 'bg-success',
            'partial' => 'bg-warning',
            'overdue' => 'bg-danger',
            'void' => 'bg-dark',
            default => 'bg-secondary'
        },
        default => match($status) {
            'active', 'completed', 'approved', 'paid' => 'bg-success',
            'pending', 'in_progress' => 'bg-warning',
            'inactive', 'cancelled', 'rejected' => 'bg-danger',
            default => 'bg-secondary'
        }
    };
    
    $displayText = ucfirst(str_replace('_', ' ', $status));
@endphp

<span class="badge {{ $badgeClass }}">{{ $displayText }}</span>
