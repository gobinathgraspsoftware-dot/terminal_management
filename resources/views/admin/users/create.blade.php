@extends('layouts.app')

@section('title', 'Create User')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-user-plus"></i> Create New User
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Users</a></li>
                    <li class="breadcrumb-item active">Create</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>
    </div>

    <!-- Alert Container -->
    <div id="alertContainer"></div>

    <!-- Create User Form -->
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">
                <i class="fas fa-user"></i> User Information
            </h5>
        </div>
        <div class="card-body">
            <form id="createUserForm" method="POST" action="{{ route('admin.users.store') }}" enctype="multipart/form-data">
                @csrf
                
                @include('admin.users._form', [
                    'user' => null,
                    'roles' => $roles,
                    'supervisors' => $supervisors,
                    'states' => $states,
                    'skillTags' => $skillTags,
                    'isEdit' => false
                ])

                <div class="row mt-4">
                    <div class="col-12">
                        <hr>
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="fas fa-save"></i> Create User
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

{{-- No @push('styles') needed - CDNs are in app.blade.php --}}

@push('scripts')
{{-- CDN links removed - now in app.blade.php --}}
{{-- Select2 and jQuery Validation are loaded globally --}}

<script>
$(document).ready(function() {
    // Initialize Select2 for multi-selects
    $('.select2-multiple').select2({
        theme: 'bootstrap-5',
        placeholder: 'Select options',
        allowClear: true,
        width: '100%'
    });

    $('.select2-single').select2({
        theme: 'bootstrap-5',
        placeholder: 'Select an option',
        allowClear: true,
        width: '100%'
    });

    // Form validation
    $('#createUserForm').validate({
        rules: {
            name: {
                required: true,
                minlength: 3,
                maxlength: 255
            },
            email: {
                required: true,
                email: true,
                maxlength: 255
            },
            phone: {
                maxlength: 20
            },
            password: {
                required: true,
                minlength: 8
            },
            password_confirmation: {
                required: true,
                equalTo: '#password'
            },
            role: {
                required: true
            },
            supervisor_id: {
                required: function() {
                    return $('#role').val() === 'technician' && $('#hasSupervisor').is(':checked');
                }
            },
            status: {
                required: true
            },
            avatar: {
                extension: "jpg|jpeg|png|gif",
                filesize: 2097152 // 2MB in bytes
            }
        },
        messages: {
            name: {
                required: 'Please enter user name',
                minlength: 'Name must be at least 3 characters long',
                maxlength: 'Name cannot exceed 255 characters'
            },
            email: {
                required: 'Please enter email address',
                email: 'Please enter a valid email address',
                maxlength: 'Email cannot exceed 255 characters'
            },
            password: {
                required: 'Please enter password',
                minlength: 'Password must be at least 8 characters long'
            },
            password_confirmation: {
                required: 'Please confirm password',
                equalTo: 'Passwords do not match'
            },
            role: {
                required: 'Please select a role'
            },
            supervisor_id: {
                required: 'Please select a supervisor'
            },
            status: {
                required: 'Please select status'
            },
            avatar: {
                extension: 'Please upload a valid image file (jpg, jpeg, png, gif)',
                filesize: 'File size must be less than 2MB'
            }
        },
        errorElement: 'div',
        errorClass: 'invalid-feedback',
        highlight: function(element) {
            $(element).addClass('is-invalid').removeClass('is-valid');
        },
        unhighlight: function(element) {
            $(element).removeClass('is-invalid').addClass('is-valid');
        },
        errorPlacement: function(error, element) {
            if (element.hasClass('select2-hidden-accessible')) {
                error.insertAfter(element.next('.select2-container'));
            } else {
                error.insertAfter(element);
            }
        },
        submitHandler: function(form) {
            submitForm();
        }
    });

    // Custom validation method for file size
    $.validator.addMethod('filesize', function(value, element, param) {
        if (element.files.length === 0) {
            return true;
        }
        return element.files[0].size <= param;
    }, 'File size is too large');

    // Form submission via AJAX
    function submitForm() {
        const form = $('#createUserForm')[0];
        const formData = new FormData(form);
        const submitBtn = $('#submitBtn');

        // Disable submit button
        submitBtn.prop('disabled', true)
            .html('<span class="spinner-border spinner-border-sm me-2"></span> Creating...');

        $.ajax({
            url: $(form).attr('action'),
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    // Show success message
                    showAlert('success', response.message);
                    
                    // Redirect after 1.5 seconds
                    setTimeout(function() {
                        window.location.href = response.redirect;
                    }, 1500);
                } else {
                    showAlert('danger', response.message || 'Failed to create user');
                    submitBtn.prop('disabled', false)
                        .html('<i class="fas fa-save"></i> Create User');
                }
            },
            error: function(xhr) {
                let errorMessage = 'An error occurred while creating the user';
                
                if (xhr.status === 422) {
                    // Validation errors
                    const errors = xhr.responseJSON.errors;
                    let errorList = '<ul class="mb-0">';
                    $.each(errors, function(key, value) {
                        errorList += '<li>' + value[0] + '</li>';
                    });
                    errorList += '</ul>';
                    errorMessage = errorList;
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                
                showAlert('danger', errorMessage);
                submitBtn.prop('disabled', false)
                    .html('<i class="fas fa-save"></i> Create User');
            }
        });
    }

    // Show alert function
    function showAlert(type, message) {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i> 
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        
        $('#alertContainer').html(alertHtml);
        
        // Auto-dismiss after 5 seconds
        if (type !== 'success') {
            setTimeout(function() {
                $('.alert').fadeOut();
            }, 5000);
        }
        
        // Scroll to top
        $('html, body').animate({ scrollTop: 0 }, 'fast');
    }

    // Role-based field visibility (from _form.blade.php)
    // This will be triggered by the included partial
});
</script>
@endpush
