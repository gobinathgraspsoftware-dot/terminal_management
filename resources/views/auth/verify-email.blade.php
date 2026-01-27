@extends('layouts.auth')

@section('title', 'Verify Email')
@section('page-title', 'Verify Your Email')
@section('page-subtitle', 'Check your inbox for verification link')

@section('content')
<!-- Status Messages -->
@if (session('status') == 'verification-link-sent')
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        A new verification link has been sent to your email address.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<!-- Info Message -->
<div class="alert alert-info mb-4">
    <i class="fas fa-envelope me-2"></i>
    <div>
        <strong>Almost there!</strong>
        <p class="mb-0 mt-2">
            Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you?
        </p>
    </div>
</div>

<div class="text-center mb-4">
    <div class="mb-3">
        <i class="fas fa-envelope-open-text" style="font-size: 64px; color: #667eea;"></i>
    </div>
    <p class="text-muted mb-0">
        If you didn't receive the email, we'll gladly send you another.
    </p>
</div>

<!-- Resend Verification Email Form -->
<form method="POST" action="{{ route('verification.send') }}">
    @csrf

    <div class="d-grid mb-3">
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="fas fa-paper-plane me-2"></i>
            Resend Verification Email
        </button>
    </div>
</form>

<div class="divider">
    <span>OR</span>
</div>

<!-- Logout Form -->
<form method="POST" action="{{ route('logout') }}">
    @csrf

    <div class="d-grid">
        <button type="submit" class="btn btn-outline-secondary">
            <i class="fas fa-sign-out-alt me-2"></i>
            Log Out
        </button>
    </div>
</form>

<!-- Additional Help -->
<div class="text-center mt-4">
    <small class="text-muted">
        <i class="fas fa-question-circle me-1"></i>
        Having trouble? <a href="mailto:support@tms.com" class="auth-link">Contact Support</a>
    </small>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Add loading state to resend button
    $('form').on('submit', function() {
        const btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true);
        
        if (btn.hasClass('btn-primary')) {
            btn.html('<i class="fas fa-spinner fa-spin me-2"></i> Sending...');
        }
    });
});
</script>
@endpush