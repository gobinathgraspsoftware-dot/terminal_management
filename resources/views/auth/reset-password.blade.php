@extends('layouts.auth')

@section('title', 'Reset Password')
@section('page-title', 'Reset Password')
@section('page-subtitle', 'Enter your new password')

@section('content')
<!-- Error Messages -->
@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>
        <strong>Oops!</strong> Please check the form for errors.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<!-- Reset Password Form -->
<form method="POST" action="{{ route('password.update') }}" id="resetPasswordForm">
    @csrf

    <!-- Hidden Token Field -->
    <input type="hidden" name="token" value="{{ $token }}">

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
                value="{{ $email ?? old('email') }}" 
                placeholder="Enter your email"
                required 
                autofocus
                readonly
            >
        </div>
        @error('email')
            <div class="invalid-feedback d-block">
                <i class="fas fa-exclamation-triangle me-1"></i>
                {{ $message }}
            </div>
        @enderror
    </div>

    <!-- New Password Field -->
    <div class="mb-3">
        <label for="password" class="form-label">
            <i class="fas fa-lock me-1"></i> New Password
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
                placeholder="Enter new password (min. 8 characters)"
                required
            >
            <span class="password-toggle" onclick="togglePassword('password')">
                <i class="fas fa-eye"></i>
            </span>
        </div>
        <small class="text-muted">
            <i class="fas fa-info-circle me-1"></i>
            Password must be at least 8 characters long
        </small>
        @error('password')
            <div class="invalid-feedback d-block">
                <i class="fas fa-exclamation-triangle me-1"></i>
                {{ $message }}
            </div>
        @enderror
    </div>

    <!-- Confirm Password Field -->
    <div class="mb-4">
        <label for="password_confirmation" class="form-label">
            <i class="fas fa-lock me-1"></i> Confirm New Password
        </label>
        <div class="input-group position-relative">
            <span class="input-group-text">
                <i class="fas fa-key"></i>
            </span>
            <input 
                type="password" 
                class="form-control @error('password_confirmation') is-invalid @enderror" 
                id="password_confirmation" 
                name="password_confirmation" 
                placeholder="Re-enter new password"
                required
            >
            <span class="password-toggle" onclick="togglePassword('password_confirmation')">
                <i class="fas fa-eye"></i>
            </span>
        </div>
        @error('password_confirmation')
            <div class="invalid-feedback d-block">
                <i class="fas fa-exclamation-triangle me-1"></i>
                {{ $message }}
            </div>
        @enderror
    </div>

    <!-- Password Strength Indicator -->
    <div class="mb-4">
        <div class="progress" style="height: 8px; border-radius: 4px;">
            <div id="passwordStrength" class="progress-bar" role="progressbar" style="width: 0%"></div>
        </div>
        <small id="passwordStrengthText" class="text-muted"></small>
    </div>

    <!-- Submit Button -->
    <div class="d-grid mb-3">
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="fas fa-check-circle me-2"></i>
            Reset Password
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
    // Password strength checker
    $('#password').on('input', function() {
        const password = $(this).val();
        const strength = calculatePasswordStrength(password);
        updatePasswordStrengthUI(strength);
    });

    // Form validation
    $('#resetPasswordForm').on('submit', function(e) {
        let isValid = true;
        
        // Validate password
        const password = $('#password').val();
        if (!password || password.length < 8) {
            isValid = false;
            showFieldError('password', 'Password must be at least 8 characters');
        } else {
            clearFieldError('password');
        }
        
        // Validate password confirmation
        const passwordConfirmation = $('#password_confirmation').val();
        if (password !== passwordConfirmation) {
            isValid = false;
            showFieldError('password_confirmation', 'Passwords do not match');
        } else {
            clearFieldError('password_confirmation');
        }
        
        if (!isValid) {
            e.preventDefault();
            return false;
        }
        
        // Show loading state
        const btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true);
        btn.html('<i class="fas fa-spinner fa-spin me-2"></i> Resetting Password...');
    });

    // Password strength calculation
    function calculatePasswordStrength(password) {
        let strength = 0;
        
        if (password.length >= 8) strength += 25;
        if (password.length >= 12) strength += 25;
        if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength += 25;
        if (/\d/.test(password)) strength += 15;
        if (/[^a-zA-Z0-9]/.test(password)) strength += 10;
        
        return Math.min(strength, 100);
    }

    // Update password strength UI
    function updatePasswordStrengthUI(strength) {
        const bar = $('#passwordStrength');
        const text = $('#passwordStrengthText');
        
        bar.css('width', strength + '%');
        
        if (strength < 30) {
            bar.removeClass().addClass('progress-bar bg-danger');
            text.text('Weak password').css('color', '#ef4444');
        } else if (strength < 60) {
            bar.removeClass().addClass('progress-bar bg-warning');
            text.text('Medium password').css('color', '#f59e0b');
        } else if (strength < 80) {
            bar.removeClass().addClass('progress-bar bg-info');
            text.text('Good password').css('color', '#3b82f6');
        } else {
            bar.removeClass().addClass('progress-bar bg-success');
            text.text('Strong password').css('color', '#10b981');
        }
    }

    // Show field error
    function showFieldError(fieldId, message) {
        const field = $('#' + fieldId);
        field.addClass('is-invalid');
        
        // Remove existing error message
        field.closest('.mb-3, .mb-4').find('.invalid-feedback').remove();
        
        // Add new error message
        field.closest('.input-group').after(
            '<div class="invalid-feedback d-block">' +
            '<i class="fas fa-exclamation-triangle me-1"></i>' + message +
            '</div>'
        );
    }

    // Clear field error
    function clearFieldError(fieldId) {
        const field = $('#' + fieldId);
        field.removeClass('is-invalid');
        field.closest('.mb-3, .mb-4').find('.invalid-feedback').remove();
    }

    // Clear errors on input
    $('#password, #password_confirmation').on('input', function() {
        clearFieldError($(this).attr('id'));
    });
});
</script>
@endpush