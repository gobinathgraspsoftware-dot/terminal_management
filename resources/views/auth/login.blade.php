@extends('layouts.auth')

@section('title', 'Login')
@section('page-title', 'Welcome Back')
@section('page-subtitle', 'Sign in to your account to continue')

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
        <strong>Oops!</strong> Please check the form for errors.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<!-- Login Form -->
<form method="POST" action="{{ route('login') }}" id="loginForm">
    @csrf

    <!-- Email Field -->
    <div class="mb-3">
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
                placeholder="Enter your email"
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

    <!-- Password Field -->
    <div class="mb-3">
        <label for="password" class="form-label">
            <i class="fas fa-lock me-1"></i> Password
        </label>
        <div class="input-group position-relative">
            <span class="input-group-text">
                <i class="fas fa-key"></i>
            </span>
            <input 
                type="password" 
                class="form-control @error('password') is-invalid @enderror" 
                id="password" 
                name="password" 
                placeholder="Enter your password"
                required
            >
            <span class="password-toggle" onclick="togglePassword('password')">
                <i class="fas fa-eye"></i>
            </span>
        </div>
        @error('password')
            <div class="invalid-feedback d-block">
                <i class="fas fa-exclamation-triangle me-1"></i>
                {{ $message }}
            </div>
        @enderror
    </div>

    <!-- Remember Me & Forgot Password -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="form-check">
            <input 
                class="form-check-input" 
                type="checkbox" 
                name="remember" 
                id="remember"
                {{ old('remember') ? 'checked' : '' }}
            >
            <label class="form-check-label" for="remember">
                Remember me
            </label>
        </div>
        <a href="{{ route('password.request') }}" class="auth-link">
            Forgot password?
        </a>
    </div>

    <!-- Submit Button -->
    <div class="d-grid mb-3">
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="fas fa-sign-in-alt me-2"></i>
            Sign In
        </button>
    </div>

    <!-- Additional Info -->
    <div class="text-center text-muted" style="font-size: 13px;">
        <p class="mb-0">
            <i class="fas fa-shield-alt me-1"></i>
            Your data is secured with industry-standard encryption
        </p>
    </div>
</form>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Form validation
    $('#loginForm').on('submit', function(e) {
        let isValid = true;
        
        // Validate email
        const email = $('#email').val();
        if (!email || !isValidEmail(email)) {
            isValid = false;
            showFieldError('email', 'Please enter a valid email address');
        } else {
            clearFieldError('email');
        }
        
        // Validate password
        const password = $('#password').val();
        if (!password || password.length < 8) {
            isValid = false;
            showFieldError('password', 'Password must be at least 8 characters');
        } else {
            clearFieldError('password');
        }
        
        if (!isValid) {
            e.preventDefault();
            return false;
        }
        
        // Show loading state
        const btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true);
        btn.html('<i class="fas fa-spinner fa-spin me-2"></i> Signing in...');
    });
    
    // Email validation helper
    function isValidEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
    
    // Show field error
    function showFieldError(fieldId, message) {
        const field = $('#' + fieldId);
        field.addClass('is-invalid');
        
        // Remove existing error message
        field.closest('.mb-3').find('.invalid-feedback').remove();
        
        // Add new error message
        field.closest('.input-group, .mb-3').after(
            '<div class="invalid-feedback d-block">' +
            '<i class="fas fa-exclamation-triangle me-1"></i>' + message +
            '</div>'
        );
    }
    
    // Clear field error
    function clearFieldError(fieldId) {
        const field = $('#' + fieldId);
        field.removeClass('is-invalid');
        field.closest('.mb-3').find('.invalid-feedback').remove();
    }
    
    // Clear errors on input
    $('#email, #password').on('input', function() {
        clearFieldError($(this).attr('id'));
    });
});
</script>
@endpush