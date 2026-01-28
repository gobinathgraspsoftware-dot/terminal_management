@extends('layouts.supervisor')

@section('title', 'Change Avatar')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Change Avatar</h1>
        <a href="{{ route('supervisor.profile.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Profile
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
                            <img src="{{ Storage::url($user->avatar) }}" 
                                 alt="Current Avatar" 
                                 class="rounded-circle img-thumbnail mb-3"
                                 style="width: 200px; height: 200px; object-fit: cover;">
                        @else
                            <img src="{{ asset('images/default-avatar.png') }}" 
                                 alt="Default Avatar" 
                                 class="rounded-circle img-thumbnail mb-3"
                                 style="width: 200px; height: 200px; object-fit: cover;">
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
                                    <i class="fas fa-trash"></i> Remove Avatar
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
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="fas fa-upload"></i> Upload Avatar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Help Card -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-info-circle"></i> Avatar Guidelines
                    </h6>
                </div>
                <div class="card-body">
                    <h6>Image Requirements:</h6>
                    <ul>
                        <li>Minimum dimensions: 100 x 100 pixels</li>
                        <li>Maximum dimensions: 2000 x 2000 pixels</li>
                        <li>Maximum file size: 2 MB</li>
                        <li>Allowed formats: JPEG, PNG, JPG, GIF</li>
                    </ul>

                    <h6 class="mt-4">Tips for Best Results:</h6>
                    <ul>
                        <li>Use a clear, high-quality photo</li>
                        <li>Square images work best</li>
                        <li>Ensure good lighting</li>
                        <li>Center your face in the photo</li>
                        <li>Use a neutral background</li>
                    </ul>

                    <div class="alert alert-info mt-3">
                        <i class="fas fa-lightbulb"></i>
                        <strong>Pro Tip:</strong> Your avatar will be displayed as a circle, 
                        so make sure important parts of the image are centered.
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-images"></i> Sample Avatars
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-4 mb-3">
                            <div class="avatar-sample">
                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mx-auto" 
                                     style="width: 80px; height: 80px; font-size: 2rem;">
                                    {{ substr($user->name, 0, 1) }}
                                </div>
                                <small class="d-block mt-2">Initials</small>
                            </div>
                        </div>
                        <div class="col-4 mb-3">
                            <div class="avatar-sample">
                                <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center mx-auto" 
                                     style="width: 80px; height: 80px;">
                                    <i class="fas fa-user fa-2x"></i>
                                </div>
                                <small class="d-block mt-2">Icon</small>
                            </div>
                        </div>
                        <div class="col-4 mb-3">
                            <div class="avatar-sample">
                                <div class="rounded-circle bg-info text-white d-flex align-items-center justify-content-center mx-auto" 
                                     style="width: 80px; height: 80px;">
                                    <i class="fas fa-camera fa-2x"></i>
                                </div>
                                <small class="d-block mt-2">Photo</small>
                            </div>
                        </div>
                    </div>
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
            // Validate file size
            if (file.size > 2 * 1024 * 1024) {
                alert('File size must not exceed 2MB');
                $(this).val('');
                $('#imagePreviewContainer').hide();
                return;
            }

            // Validate file type
            const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
            if (!allowedTypes.includes(file.type)) {
                alert('Please select a valid image file (JPEG, PNG, JPG, GIF)');
                $(this).val('');
                $('#imagePreviewContainer').hide();
                return;
            }

            // Show preview
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#imagePreview').attr('src', e.target.result);
                $('#imagePreviewContainer').show();
            };
            reader.readAsDataURL(file);

            // Validate dimensions
            const img = new Image();
            img.onload = function() {
                if (this.width < 100 || this.height < 100) {
                    alert('Image dimensions must be at least 100x100 pixels');
                    $('#avatar').val('');
                    $('#imagePreviewContainer').hide();
                    return;
                }
                if (this.width > 2000 || this.height > 2000) {
                    alert('Image dimensions must not exceed 2000x2000 pixels');
                    $('#avatar').val('');
                    $('#imagePreviewContainer').hide();
                    return;
                }
            };
            img.src = URL.createObjectURL(file);
        } else {
            $('#imagePreviewContainer').hide();
        }
    });

    // Form submission with progress
    $('#avatarForm').on('submit', function(e) {
        if (!$('#avatar').val()) {
            e.preventDefault();
            alert('Please select an image to upload');
            return false;
        }

        // Show upload progress (simulated)
        $('#uploadProgressContainer').show();
        $('#submitBtn').prop('disabled', true);

        // Simulate upload progress
        let progress = 0;
        const interval = setInterval(function() {
            progress += 10;
            $('#uploadProgress')
                .css('width', progress + '%')
                .attr('aria-valuenow', progress)
                .text(progress + '%');

            if (progress >= 90) {
                clearInterval(interval);
            }
        }, 100);
    });
});
</script>
@endpush
@endsection
