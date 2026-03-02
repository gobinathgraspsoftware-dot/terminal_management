@extends('layouts.auth')

@section('title', 'Forgot Password')
@section('page-title', 'Forgot Password?')
@section('page-subtitle', 'Enter your email to reset your password')

@section('content')
<!-- Status Messages -->
@if (session('status'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        {{ session('status') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>
        @foreach ($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<!-- Info Message -->
<div class="alert alert-info mb-4">
    <i class="fas fa-info-circle me-2"></i>
    <strong>No worries!</strong> Enter your email address and we'll send you a link to reset your password.
</div>

<!-- Forgot Password Form (Standard POST — NOT AJAX due to global ajax.auth middleware) -->
<form method="POST" action="{{ route('password.email') }}" id="forgotPasswordForm">
    @csrf

    <!-- Email Field -->
    <div class="mb-4">
        <label for="email" class="form-label">
            <i class="fas fa-envelope me-1"></i> Email Address
        </label>
        <div class="input-group">
            <span class="input-group-text">
                <i class="fas fa-user"></i>
            </span>
            <input
                type="email"
                class="form-control @error('email') is-invalid @enderror"
                id="email"
                name="email"
                value="{{ old('email') }}"
                placeholder="Enter your registered email"
                required
                autofocus
            >
        </div>
        @error('email')
            <div class="invalid-feedback d-block">
                <i class="fas fa-exclamation-triangle me-1"></i>
                {{ $message }}
            </div>
        @enderror
    </div>

    <!-- Submit Button -->
    <div class="d-grid mb-3">
        <button type="submit" class="btn btn-primary btn-lg" id="btnSubmit">
            <i class="fas fa-paper-plane me-2"></i>
            Send Reset Link
        </button>
    </div>

    <!-- Back to Login -->
    <div class="text-center">
        <a href="{{ route('login') }}" class="auth-link">
            <i class="fas fa-arrow-left me-1"></i>
            Back to Login
        </a>
    </div>
</form>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Client-side validation + loading state (standard form submit)
    $('#forgotPasswordForm').on('submit', function(e) {
        var email = $('#email').val();

        // Validate email
        if (!email || !isValidEmail(email)) {
            e.preventDefault();
            showFieldError('email', 'Please enter a valid email address');
            return false;
        }

        clearFieldError('email');

        // Show loading state (form will submit normally via POST)
        var $btn = $('#btnSubmit');
        $btn.prop('disabled', true);
        $btn.html('<i class="fas fa-spinner fa-spin me-2"></i> Sending...');
    });

    // Email validation helper
    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    // Show field error
    function showFieldError(fieldId, message) {
        var field = $('#' + fieldId);
        field.addClass('is-invalid');
        field.closest('.mb-4').find('.invalid-feedback').remove();
        field.closest('.input-group').after(
            '<div class="invalid-feedback d-block">' +
            '<i class="fas fa-exclamation-triangle me-1"></i>' + message +
            '</div>'
        );
    }

    // Clear field error
    function clearFieldError(fieldId) {
        var field = $('#' + fieldId);
        field.removeClass('is-invalid');
        field.closest('.mb-4').find('.invalid-feedback').remove();
    }

    // Clear errors on input
    $('#email').on('input', function() {
        clearFieldError('email');
    });
});
</script>
@endpush
