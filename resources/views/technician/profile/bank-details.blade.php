@extends('layouts.technician')

@section('title', 'Bank Details')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Bank Details</h1>
        <a href="{{ route('technician.profile.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Profile
        </a>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Update Bank Details for Commission Payout</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('technician.profile.bank-details.update') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <!-- Bank Name -->
                        <div class="mb-3">
                            <label for="bank_name" class="form-label">
                                Bank Name <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control @error('bank_name') is-invalid @enderror"
                                   id="bank_name"
                                   name="bank_name"
                                   value="{{ old('bank_name', $user->bank_name) }}"
                                   placeholder="e.g., State Bank of India"
                                   required>
                            @error('bank_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Account Number -->
                        <div class="mb-3">
                            <label for="bank_account_no" class="form-label">
                                Account Number <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control @error('bank_account_no') is-invalid @enderror"
                                   id="bank_account_no"
                                   name="bank_account_no"
                                   value="{{ old('bank_account_no', $user->bank_account_no) }}"
                                   placeholder="Enter your account number"
                                   pattern="[0-9]+"
                                   required>
                            @error('bank_account_no')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Enter digits only, no spaces or special characters</div>
                        </div>

                        <!-- Account Holder Name -->
                        <div class="mb-3">
                            <label for="bank_account_name" class="form-label">
                                Account Holder Name <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control @error('bank_account_name') is-invalid @enderror"
                                   id="bank_account_name"
                                   name="bank_account_name"
                                   value="{{ old('bank_account_name', $user->bank_account_name) }}"
                                   placeholder="Name as per bank account"
                                   required>
                            @error('bank_account_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Must match the name on your bank account</div>
                        </div>

                        <!-- IFSC Code -->
                        <div class="mb-3">
                            <label for="ifsc_code" class="form-label">
                                IFSC Code <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control text-uppercase @error('ifsc_code') is-invalid @enderror"
                                   id="ifsc_code"
                                   name="ifsc_code"
                                   value="{{ old('ifsc_code', $user->ifsc_code) }}"
                                   placeholder="e.g., SBIN0001234"
                                   pattern="[A-Z]{4}0[A-Z0-9]{6}"
                                   maxlength="11"
                                   required>
                            @error('ifsc_code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">11 character code (e.g., SBIN0001234)</div>
                        </div>

                        <!-- Branch Name -->
                        <div class="mb-3">
                            <label for="branch_name" class="form-label">
                                Branch Name <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control @error('branch_name') is-invalid @enderror"
                                   id="branch_name"
                                   name="branch_name"
                                   value="{{ old('branch_name', $user->branch_name) }}"
                                   placeholder="Enter branch name and location"
                                   required>
                            @error('branch_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Submit Button -->
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('technician.profile.index') }}" class="btn btn-secondary">
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Bank Details
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            @if($user->bank_name)
            <div class="card mt-4">
                <div class="card-header">
                    <h6 class="mb-0">Current Bank Details</h6>
                </div>
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-sm-4"><strong>Bank Name:</strong></div>
                        <div class="col-sm-8">{{ $user->bank_name }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-sm-4"><strong>Account Number:</strong></div>
                        <div class="col-sm-8">{{ maskAccountNumber($user->bank_account_no) }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-sm-4"><strong>Account Holder:</strong></div>
                        <div class="col-sm-8">{{ $user->bank_account_name }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-sm-4"><strong>IFSC Code:</strong></div>
                        <div class="col-sm-8">{{ $user->ifsc_code }}</div>
                    </div>
                    <div class="row">
                        <div class="col-sm-4"><strong>Branch:</strong></div>
                        <div class="col-sm-8">{{ $user->branch_name }}</div>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Help Card -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-info-circle"></i> Important Information
                    </h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Important:</strong> Bank details are required for commission payouts.
                    </div>

                    <h6>Tips:</h6>
                    <ul class="small mb-0">
                        <li class="mb-2">Double-check all details before saving</li>
                        <li class="mb-2">Account holder name must match your bank records</li>
                        <li class="mb-2">IFSC code can be found on your cheque book or bank statement</li>
                        <li class="mb-2">Incorrect details may delay your commission payments</li>
                        <li class="mb-2">Contact admin if you need to change verified details</li>
                    </ul>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-question-circle"></i> How to Find IFSC Code
                    </h6>
                </div>
                <div class="card-body">
                    <p class="small mb-2">You can find your IFSC code on:</p>
                    <ul class="small mb-0">
                        <li>Bank cheque book (top of the cheque)</li>
                        <li>Bank passbook (first page)</li>
                        <li>Bank statement</li>
                        <li>Net banking portal</li>
                        <li>Mobile banking app</li>
                    </ul>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-shield-alt"></i> Security
                    </h6>
                </div>
                <div class="card-body">
                    <p class="small mb-0">
                        <i class="fas fa-lock text-success"></i>
                        Your bank details are encrypted and securely stored. They are only
                        used for commission payouts and are never shared with third parties.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

@php
function maskAccountNumber($accountNumber) {
    if (!$accountNumber) return '';
    $length = strlen($accountNumber);
    if ($length <= 4) return $accountNumber;
    return str_repeat('*', $length - 4) . substr($accountNumber, -4);
}
@endphp

@push('scripts')
<script>
$(document).ready(function() {
    // Convert IFSC code to uppercase
    $('#ifsc_code').on('input', function() {
        $(this).val($(this).val().toUpperCase());
    });

    // Validate account number (digits only)
    $('#bank_account_no').on('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
    });

    // IFSC code validation
    $('#ifsc_code').on('blur', function() {
        const ifsc = $(this).val();
        const pattern = /^[A-Z]{4}0[A-Z0-9]{6}$/;

        if (ifsc && !pattern.test(ifsc)) {
            $(this).addClass('is-invalid');
            if (!$(this).next('.invalid-feedback').length) {
                $(this).after('<div class="invalid-feedback">Invalid IFSC code format</div>');
            }
        } else {
            $(this).removeClass('is-invalid');
            $(this).next('.invalid-feedback').remove();
        }
    });

    // Form validation before submit
    $('form').on('submit', function(e) {
        const ifsc = $('#ifsc_code').val();
        const pattern = /^[A-Z]{4}0[A-Z0-9]{6}$/;

        if (!pattern.test(ifsc)) {
            e.preventDefault();
            alert('Please enter a valid IFSC code (11 characters, e.g., SBIN0001234)');
            $('#ifsc_code').focus();
            return false;
        }

        const accountNumber = $('#bank_account_no').val();
        if (!/^[0-9]+$/.test(accountNumber)) {
            e.preventDefault();
            alert('Account number should contain only digits');
            $('#bank_account_no').focus();
            return false;
        }
    });
});
</script>
@endpush
@endsection
