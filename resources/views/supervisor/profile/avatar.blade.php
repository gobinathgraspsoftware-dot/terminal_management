@extends('layouts.app')

@section('title', 'Change Avatar')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Change Avatar</h1>
        <a href="{{ route('supervisor.profile.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Profile
        </a>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Update Profile Picture</h6>
                </div>
                <div class="card-body">
                    <!-- Current Avatar Display -->
                    <div class="text-center mb-4">
                        <h6 class="mb-3">Current Avatar</h6>
                        @if($user->avatar)
                            {{-- Add cache busting timestamp to prevent browser caching --}}
                            <img src="{{ asset('storage/' . $user->avatar) }}?v={{ time() }}"
                                 alt="Current Avatar"
                                 class="rounded-circle img-thumbnail mb-3"
                                 id="currentAvatar"
                                 style="width: 200px; height: 200px; object-fit: cover;">
                        @else
                            <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mb-3"
                                 style="width: 200px; height: 200px; font-size: 5rem;">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                        @endif

                        @if($user->avatar)
                        <div>
                            <form action="{{ route('supervisor.profile.avatar.delete') }}"
                                  method="POST"
                                  id="deleteAvatarForm"
                                  class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="btn btn-sm btn-outline-danger"
                                        onclick="return confirm('Are you sure you want to remove your avatar?')">
                                    <i class="bi bi-trash"></i> Remove Avatar
                                </button>
                            </form>
                        </div>
                        @endif
                    </div>

                    <hr>

                    <!-- Upload New Avatar Form -->
                    <form action="{{ route('supervisor.profile.avatar.update') }}"
                          method="POST"
                          enctype="multipart/form-data"
                          id="avatarForm">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="avatar" class="form-label">
                                Upload New Avatar <span class="text-danger">*</span>
                            </label>
                            <input type="file"
                                   class="form-control @error('avatar') is-invalid @enderror"
                                   id="avatar"
                                   name="avatar"
                                   accept="image/jpeg,image/png,image/jpg,image/gif"
                                   required>
                            @error('avatar')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                Allowed formats: JPEG, PNG, JPG, GIF. Maximum size: 2MB
                            </div>
                        </div>

                        <!-- Image Preview -->
                        <div class="mb-3" id="imagePreviewContainer" style="display: none;">
                            <label class="form-label">Preview</label>
                            <div class="text-center">
                                <img id="imagePreview"
                                     src=""
                                     alt="Preview"
                                     class="rounded-circle img-thumbnail"
                                     style="width: 200px; height: 200px; object-fit: cover;">
                            </div>
                        </div>

                        <!-- Upload Progress -->
                        <div class="mb-3" id="uploadProgressContainer" style="display: none;">
                            <div class="progress">
                                <div class="progress-bar progress-bar-striped progress-bar-animated"
                                     id="uploadProgress"
                                     role="progressbar"
                                     style="width: 0%"
                                     aria-valuenow="0"
                                     aria-valuemin="0"
                                     aria-valuemax="100">0%</div>
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('supervisor.profile.index') }}" class="btn btn-secondary">
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-primary" id="uploadBtn">
                                <i class="bi bi-upload"></i> Upload Avatar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Guidelines Card -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="bi bi-info-circle"></i> Avatar Guidelines
                    </h6>
                </div>
                <div class="card-body">
                    <h6>Image Requirements:</h6>
                    <ul class="mb-3">
                        <li><strong>File Format:</strong> JPEG, PNG, JPG, or GIF</li>
                        <li><strong>File Size:</strong> Maximum 2MB</li>
                        <li><strong>Dimensions:</strong> Minimum 100x100px, Maximum 2000x2000px</li>
                        <li><strong>Recommended:</strong> Square images (1:1 ratio) for best results</li>
                    </ul>

                    <h6>Tips for Best Results:</h6>
                    <ul class="mb-0">
                        <li>Use a clear, recent photo of yourself</li>
                        <li>Ensure good lighting and focus</li>
                        <li>Center your face in the frame</li>
                        <li>Avoid busy backgrounds</li>
                        <li>Use a professional-looking image for work profiles</li>
                    </ul>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="bi bi-shield-check"></i> Privacy & Security
                    </h6>
                </div>
                <div class="card-body">
                    <ul class="mb-0">
                        <li>Your avatar is visible to other users in the system</li>
                        <li>Previous avatars are automatically deleted when you upload a new one</li>
                        <li>You can remove your avatar at any time</li>
                        <li>Make sure you have the right to use the image you upload</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    // Image preview
    $('#avatar').on('change', function(e) {
        const file = e.target.files[0];

        if (file) {
            // Validate file size (2MB)
            const maxSize = 2 * 1024 * 1024; // 2MB in bytes
            if (file.size > maxSize) {
                alert('File size must be less than 2MB');
                $(this).val('');
                $('#imagePreviewContainer').hide();
                return;
            }

            // Validate file type
            const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
            if (!allowedTypes.includes(file.type)) {
                alert('Please select a valid image file (JPEG, PNG, JPG, or GIF)');
                $(this).val('');
                $('#imagePreviewContainer').hide();
                return;
            }

            // Show preview
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#imagePreview').attr('src', e.target.result);
                $('#imagePreviewContainer').slideDown();
            };
            reader.readAsDataURL(file);

            // Validate dimensions
            const img = new Image();
            img.onload = function() {
                if (this.width < 100 || this.height < 100) {
                    alert('Image dimensions must be at least 100x100 pixels');
                    $('#avatar').val('');
                    $('#imagePreviewContainer').hide();
                } else if (this.width > 2000 || this.height > 2000) {
                    alert('Image dimensions must not exceed 2000x2000 pixels');
                    $('#avatar').val('');
                    $('#imagePreviewContainer').hide();
                }
            };
            img.src = URL.createObjectURL(file);
        } else {
            $('#imagePreviewContainer').hide();
        }
    });

    // Form submission
    $('#avatarForm').on('submit', function(e) {
        const file = $('#avatar')[0].files[0];

        if (!file) {
            e.preventDefault();
            alert('Please select an image to upload');
            return false;
        }

        // Show progress bar
        $('#uploadBtn').prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Uploading...');
        $('#uploadProgressContainer').slideDown();

        // Simulate progress (actual progress would need AJAX)
        let progress = 0;
        const progressInterval = setInterval(function() {
            progress += 10;
            if (progress <= 90) {
                $('#uploadProgress')
                    .css('width', progress + '%')
                    .attr('aria-valuenow', progress)
                    .text(progress + '%');
            } else {
                clearInterval(progressInterval);
            }
        }, 200);
    });

    // Delete avatar confirmation
    $('#deleteAvatarForm').on('submit', function(e) {
        if (!confirm('Are you sure you want to remove your avatar? This action cannot be undone.')) {
            e.preventDefault();
            return false;
        }
    });
});
</script>
@endpush
@endsection
