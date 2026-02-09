@extends('layouts.app')

@section('title', 'Edit User')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-user-edit"></i> Edit User: {{ $user->name }}
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Users</a></li>
                    <li class="breadcrumb-item active">Edit</li>
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

    <!-- Edit User Form -->
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">
                <i class="fas fa-user"></i> User Information
            </h5>
        </div>
        <div class="card-body">
            <form id="editUserForm" method="POST" action="{{ route('admin.users.update', $user->id) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                
                @include('admin.users._form', [
                    'user' => $user,
                    'roles' => $roles,
                    'supervisors' => $supervisors,
                    'states' => $states,
                    'skillTags' => $skillTags,
                    'isEdit' => true
                ])

                <div class="row mt-4">
                    <div class="col-12">
                        <hr>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                @can('delete_users')
                                    @if($user->id !== auth()->id())
                                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteUserModal">
                                        <i class="fas fa-trash"></i> Delete User
                                    </button>
                                    @endif
                                @endcan
                            </div>
                            <div class="d-flex gap-2">
                                <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                                <button type="submit" class="btn btn-primary" id="submitBtn">
                                    <i class="fas fa-save"></i> Update User
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Change Password Section -->
    @can('changePassword', $user)
    <div class="card shadow-sm mt-4">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0">
                <i class="fas fa-key"></i> Change Password
            </h5>
        </div>
        <div class="card-body">
            <form id="changePasswordForm" method="POST" action="{{ route('admin.users.change-password', $user->id) }}">
                @csrf
                @method('PUT')
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="new_password" name="new_password" placeholder="Enter new password">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="new_password_confirmation" class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="new_password_confirmation" name="new_password_confirmation" placeholder="Confirm new password">
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-warning" id="changePasswordBtn">
                        <i class="fas fa-key"></i> Change Password
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endcan
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteUserModalLabel">
                    <i class="fas fa-exclamation-triangle"></i> Confirm Delete
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete user <strong>{{ $user->name }}</strong>?</p>
                <p class="text-muted small">This action will soft delete the user. You can restore it later if needed.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete User</button>
            </div>
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
    // Initialize Select2
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
    $('#editUserForm').validate({
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
                minlength: 8
            },
            password_confirmation: {
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
                filesize: 2097152
            }
        },
        messages: {
            name: {
                required: 'Please enter user name',
                minlength: 'Name must be at least 3 characters long'
            },
            email: {
                required: 'Please enter email address',
                email: 'Please enter a valid email address'
            },
            password: {
                minlength: 'Password must be at least 8 characters long'
            },
            password_confirmation: {
                equalTo: 'Passwords do not match'
            },
            role: {
                required: 'Please select a role'
            },
            supervisor_id: {
                required: 'Please select a supervisor'
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
        const form = $('#editUserForm')[0];
        const formData = new FormData(form);
        const submitBtn = $('#submitBtn');

        submitBtn.prop('disabled', true)
            .html('<span class="spinner-border spinner-border-sm me-2"></span> Updating...');

        $.ajax({
            url: $(form).attr('action'),
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    showAlert('success', response.message);
                    
                    setTimeout(function() {
                        window.location.href = response.redirect;
                    }, 1500);
                } else {
                    showAlert('danger', response.message || 'Failed to update user');
                    submitBtn.prop('disabled', false)
                        .html('<i class="fas fa-save"></i> Update User');
                }
            },
            error: function(xhr) {
                let errorMessage = 'An error occurred while updating the user';
                
                if (xhr.status === 422) {
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
                    .html('<i class="fas fa-save"></i> Update User');
            }
        });
    }

    // Change Password Form
    $('#changePasswordForm').validate({
        rules: {
            new_password: {
                required: true,
                minlength: 8
            },
            new_password_confirmation: {
                required: true,
                equalTo: '#new_password'
            }
        },
        messages: {
            new_password: {
                required: 'Please enter new password',
                minlength: 'Password must be at least 8 characters long'
            },
            new_password_confirmation: {
                required: 'Please confirm new password',
                equalTo: 'Passwords do not match'
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
        submitHandler: function(form) {
            const submitBtn = $('#changePasswordBtn');
            submitBtn.prop('disabled', true)
                .html('<span class="spinner-border spinner-border-sm me-2"></span> Changing...');

            $.ajax({
                url: $(form).attr('action'),
                method: 'POST',
                data: $(form).serialize(),
                success: function(response) {
                    if (response.success) {
                        showAlert('success', response.message);
                        $('#changePasswordForm')[0].reset();
                        $('#changePasswordForm').validate().resetForm();
                        $('#changePasswordForm input').removeClass('is-valid is-invalid');
                    } else {
                        showAlert('danger', response.message || 'Failed to change password');
                    }
                    submitBtn.prop('disabled', false)
                        .html('<i class="fas fa-key"></i> Change Password');
                },
                error: function(xhr) {
                    let errorMessage = 'An error occurred while changing password';
                    
                    if (xhr.status === 422) {
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
                        .html('<i class="fas fa-key"></i> Change Password');
                }
            });
        }
    });

    // Delete User
    $('#confirmDeleteBtn').on('click', function() {
        const btn = $(this);
        btn.prop('disabled', true)
            .html('<span class="spinner-border spinner-border-sm me-1"></span> Deleting...');

        $.ajax({
            url: '{{ route("admin.users.destroy", $user->id) }}',
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                $('#deleteUserModal').modal('hide');
                showAlert('success', response.message);
                
                setTimeout(function() {
                    window.location.href = '{{ route("admin.users.index") }}';
                }, 1500);
            },
            error: function(xhr) {
                $('#deleteUserModal').modal('hide');
                const message = xhr.responseJSON?.message || 'Failed to delete user';
                showAlert('danger', message);
                btn.prop('disabled', false)
                    .html('Delete User');
            }
        });
    });

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
        
        if (type !== 'success') {
            setTimeout(function() {
                $('.alert').fadeOut();
            }, 5000);
        }
        
        $('html, body').animate({ scrollTop: 0 }, 'fast');
    }
});
</script>
@endpush
