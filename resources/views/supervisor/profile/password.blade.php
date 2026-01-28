@extends('layouts.app')

@section('title', 'Change Password')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Change Password</h1>
        <a href="{{ route('supervisor.profile.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Profile
        </a>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Update Your Password</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('supervisor.profile.password.update') }}" method="POST" id="passwordForm">
                        @csrf
                        @method('PUT')

                        <!-- Current Password -->
                        <div class="mb-3">
                            <label for="current_password" class="form-label">
                                Current Password <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="password"
                                       class="form-control @error('current_password') is-invalid @enderror"
                                       id="current_password"
                                       name="current_password"
                                       required>
                                <button class="btn btn-outline-secondary"
                                        type="button"
                                        id="toggleCurrentPassword">
                                    <i class="bi bi-eye"></i>
                                </button>
                                @error('current_password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- New Password -->
                        <div class="mb-3">
                            <label for="password" class="form-label">
                                New Password <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="password"
                                       class="form-control @error('password') is-invalid @enderror"
                                       id="password"
                                       name="password"
                                       required>
                                <button class="btn btn-outline-secondary"
                                        type="button"
                                        id="togglePassword">
                                    <i class="bi bi-eye"></i>
                                </button>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-text">
                                Password must be at least 8 characters with mixed case, numbers, and symbols
                            </div>

                            <!-- Password Strength Meter -->
                            <div class="mt-2">
                                <div class="progress" style="height: 5px;">
                                    <div class="progress-bar"
                                         id="passwordStrength"
                                         role="progressbar"
                                         style="width: 0%"></div>
                                </div>
                                <small id="passwordStrengthText" class="text-muted"></small>
                            </div>
                        </div>

                        <!-- Confirm Password -->
                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">
                                Confirm New Password <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="password"
                                       class="form-control @error('password_confirmation') is-invalid @enderror"
                                       id="password_confirmation"
                                       name="password_confirmation"
                                       required>
                                <button class="btn btn-outline-secondary"
                                        type="button"
                                        id="togglePasswordConfirmation">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <div id="passwordMatchText" class="form-text"></div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('supervisor.profile.index') }}" class="btn btn-secondary">
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="bi bi-key"></i> Change Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Security Tips Card -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="bi bi-shield-check"></i> Password Security Tips
                    </h6>
                </div>
                <div class="card-body">
                    <ul class="mb-0">
                        <li class="mb-2">
                            <strong>Use a strong password:</strong> At least 8 characters with a mix of uppercase,
                            lowercase, numbers, and special characters
                        </li>
                        <li class="mb-2">
                            <strong>Avoid common passwords:</strong> Don't use easily guessable passwords like
                            "password123" or "12345678"
                        </li>
                        <li class="mb-2">
                            <strong>Don't reuse passwords:</strong> Use unique passwords for different accounts
                        </li>
                        <li class="mb-2">
                            <strong>Change regularly:</strong> Update your password periodically for better security
                        </li>
                        <li class="mb-2">
                            <strong>Keep it private:</strong> Never share your password with anyone
                        </li>
                    </ul>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="bi bi-info-circle"></i> Password Requirements
                    </h6>
                </div>
                <div class="card-body">
                    <ul class="mb-0" id="passwordRequirements">
                        <li class="mb-1">
                            <i class="bi bi-circle text-muted" id="req-length"></i>
                            Minimum 8 characters
                        </li>
                        <li class="mb-1">
                            <i class="bi bi-circle text-muted" id="req-uppercase"></i>
                            At least one uppercase letter
                        </li>
                        <li class="mb-1">
                            <i class="bi bi-circle text-muted" id="req-lowercase"></i>
                            At least one lowercase letter
                        </li>
                        <li class="mb-1">
                            <i class="bi bi-circle text-muted" id="req-number"></i>
                            At least one number
                        </li>
                        <li class="mb-1">
                            <i class="bi bi-circle text-muted" id="req-special"></i>
                            At least one special character
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    // Toggle password visibility
    $('#toggleCurrentPassword').on('click', function() {
        togglePasswordVisibility('#current_password', this);
    });

    $('#togglePassword').on('click', function() {
        togglePasswordVisibility('#password', this);
    });

    $('#togglePasswordConfirmation').on('click', function() {
        togglePasswordVisibility('#password_confirmation', this);
    });

    function togglePasswordVisibility(inputId, button) {
        const input = $(inputId);
        const icon = $(button).find('i');

        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('bi-eye').addClass('bi-eye-slash');
        } else {
            input.attr('type', 'password');
            icon.removeClass('bi-eye-slash').addClass('bi-eye');
        }
    }

    // Password strength checker
    $('#password').on('input', function() {
        const password = $(this).val();
        checkPasswordStrength(password);
        checkPasswordRequirements(password);
    });

    // Password match checker
    $('#password_confirmation').on('input', function() {
        const password = $('#password').val();
        const confirmation = $(this).val();

        if (confirmation.length > 0) {
            if (password === confirmation) {
                $('#passwordMatchText')
                    .removeClass('text-danger')
                    .addClass('text-success')
                    .html('<i class="bi bi-check-circle"></i> Passwords match');
            } else {
                $('#passwordMatchText')
                    .removeClass('text-success')
                    .addClass('text-danger')
                    .html('<i class="bi bi-x-circle"></i> Passwords do not match');
            }
        } else {
            $('#passwordMatchText').html('');
        }
    });

    function checkPasswordStrength(password) {
        let strength = 0;
        const strengthBar = $('#passwordStrength');
        const strengthText = $('#passwordStrengthText');

        if (password.length >= 8) strength += 20;
        if (password.length >= 12) strength += 10;
        if (/[a-z]/.test(password)) strength += 20;
        if (/[A-Z]/.test(password)) strength += 20;
        if (/[0-9]/.test(password)) strength += 15;
        if (/[^a-zA-Z0-9]/.test(password)) strength += 15;

        strengthBar.css('width', strength + '%');
        strengthBar.removeClass('bg-danger bg-warning bg-info bg-success');

        if (strength <= 30) {
            strengthBar.addClass('bg-danger');
            strengthText.text('Weak').removeClass().addClass('text-danger');
        } else if (strength <= 60) {
            strengthBar.addClass('bg-warning');
            strengthText.text('Fair').removeClass().addClass('text-warning');
        } else if (strength <= 80) {
            strengthBar.addClass('bg-info');
            strengthText.text('Good').removeClass().addClass('text-info');
        } else {
            strengthBar.addClass('bg-success');
            strengthText.text('Strong').removeClass().addClass('text-success');
        }
    }

    function checkPasswordRequirements(password) {
        updateRequirement('req-length', password.length >= 8);
        updateRequirement('req-uppercase', /[A-Z]/.test(password));
        updateRequirement('req-lowercase', /[a-z]/.test(password));
        updateRequirement('req-number', /[0-9]/.test(password));
        updateRequirement('req-special', /[^a-zA-Z0-9]/.test(password));
    }

    function updateRequirement(id, met) {
        const icon = $('#' + id);
        if (met) {
            icon.removeClass('bi-circle text-muted')
                .addClass('bi-check-circle text-success');
        } else {
            icon.removeClass('bi-check-circle text-success')
                .addClass('bi-circle text-muted');
        }
    }

    // Form validation
    $('#passwordForm').on('submit', function(e) {
        const password = $('#password').val();
        const confirmation = $('#password_confirmation').val();

        if (password !== confirmation) {
            e.preventDefault();
            alert('Passwords do not match!');
            return false;
        }
    });
});
</script>
@endpush
@endsection
