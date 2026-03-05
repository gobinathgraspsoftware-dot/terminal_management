@extends('layouts.app')

@section('title', 'Edit ' . $terminalModel->model_name . ' - TMS')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="bi bi-pencil me-2"></i>Edit Terminal Model</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.terminal-models.index') }}">Terminal Models</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.terminal-models.show', $terminalModel) }}">{{ $terminalModel->model_code }}</a></li>
                    <li class="breadcrumb-item active">Edit</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('admin.terminal-models.show', $terminalModel) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>

    <form action="{{ route('admin.terminal-models.update', $terminalModel) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="row">
            <!-- Main Details -->
            <div class="col-md-8">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Basic Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Model Code</label>
                                <input type="text" class="form-control" name="model_code"
                                       value="{{ old('model_code', $terminalModel->model_code) }}" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Model Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('model_name') is-invalid @enderror"
                                       name="model_name" value="{{ old('model_name', $terminalModel->model_name) }}" required>
                                @error('model_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Category <span class="text-danger">*</span></label>
                                <select class="form-select @error('category_id') is-invalid @enderror" name="category_id" required>
                                    <option value="">Select Category</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{ old('category_id', $terminalModel->category_id) == $category->id ? 'selected' : '' }}>
                                            {{ $category->category_name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Brand</label>
                                <input type="text" class="form-control @error('brand') is-invalid @enderror"
                                       name="brand" value="{{ old('brand', $terminalModel->brand) }}">
                                @error('brand') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea class="form-control @error('description') is-invalid @enderror"
                                          name="description" rows="3">{{ old('description', $terminalModel->description) }}</textarea>
                                @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Specifications -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="bi bi-list-check me-2"></i>Specifications</h6>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="addSpecBtn">
                            <i class="bi bi-plus"></i> Add
                        </button>
                    </div>
                    <div class="card-body">
                        <div id="specificationsContainer">
                            @php
                                $specs = old('specifications', $terminalModel->specifications ?? []);
                                if (!is_array($specs)) $specs = [];
                            @endphp
                            @forelse($specs as $index => $spec)
                                <div class="row g-2 mb-2 spec-row">
                                    <div class="col-md-5">
                                        <input type="text" class="form-control" name="specifications[{{ $index }}][key]"
                                               placeholder="Key" value="{{ $spec['key'] ?? '' }}">
                                    </div>
                                    <div class="col-md-5">
                                        <input type="text" class="form-control" name="specifications[{{ $index }}][value]"
                                               placeholder="Value" value="{{ $spec['value'] ?? '' }}">
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-outline-danger btn-sm remove-spec-btn w-100">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            @empty
                                <div class="row g-2 mb-2 spec-row">
                                    <div class="col-md-5">
                                        <input type="text" class="form-control" name="specifications[0][key]" placeholder="Key">
                                    </div>
                                    <div class="col-md-5">
                                        <input type="text" class="form-control" name="specifications[0][value]" placeholder="Value">
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-outline-danger btn-sm remove-spec-btn w-100">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-md-4">
                <!-- Status & Settings -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h6 class="mb-0"><i class="bi bi-gear me-2"></i>Settings</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select" name="status">
                                <option value="active" {{ old('status', $terminalModel->status) == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status', $terminalModel->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Warranty (Months)</label>
                            <input type="number" class="form-control" name="warranty_months"
                                   value="{{ old('warranty_months', $terminalModel->warranty_months) }}" min="0" max="120">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Sort Order</label>
                            <input type="number" class="form-control" name="sort_order"
                                   value="{{ old('sort_order', $terminalModel->sort_order) }}" min="0">
                        </div>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="is_serial_tracked" value="1"
                                   id="isSerialTracked" {{ old('is_serial_tracked', $terminalModel->is_serial_tracked) ? 'checked' : '' }}>
                            <label class="form-check-label" for="isSerialTracked">Serial Number Tracking</label>
                        </div>
                    </div>
                </div>

                <!-- Image Upload -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h6 class="mb-0"><i class="bi bi-image me-2"></i>Model Image</h6>
                    </div>
                    <div class="card-body text-center">
                        <div class="mb-3 position-relative d-inline-block">
                            @if($terminalModel->image_path && Storage::disk('public')->exists($terminalModel->image_path))
                                <img id="imagePreview" src="{{ asset('storage/' . $terminalModel->image_path) }}"
                                     alt="{{ $terminalModel->model_name }}"
                                     class="img-thumbnail" style="max-width: 200px; max-height: 200px; object-fit: contain;">
                                <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0" id="deleteImageBtn" title="Remove image">
                                    <i class="bi bi-x"></i>
                                </button>
                            @else
                                <img id="imagePreview"
                                     src="data:image/svg+xml;charset=UTF-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22200%22%20height%3D%22200%22%3E%3Crect%20fill%3D%22%23f0f0f0%22%20width%3D%22200%22%20height%3D%22200%22%2F%3E%3Ctext%20fill%3D%22%23999%22%20font-size%3D%2214%22%20x%3D%2250%25%22%20y%3D%2250%25%22%20text-anchor%3D%22middle%22%20dy%3D%22.3em%22%3ENo%20Image%3C%2Ftext%3E%3C%2Fsvg%3E"
                                     class="img-thumbnail" style="max-width: 200px; max-height: 200px;">
                            @endif
                        </div>
                        <input type="file" class="form-control @error('image') is-invalid @enderror"
                               name="image" id="imageInput" accept="image/jpeg,image/png,image/jpg,image/gif">
                        @error('image') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <small class="text-muted d-block mt-1">Max 2MB. JPEG, PNG, JPG, GIF</small>
                    </div>
                </div>

                <!-- Default Accessories -->
                @if($accessoryModels->count() > 0)
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h6 class="mb-0"><i class="bi bi-puzzle me-2"></i>Default Accessories</h6>
                    </div>
                    <div class="card-body">
                        @php
                            $selectedAccessories = old('default_accessories', $terminalModel->default_accessories ?? []);
                            if (!is_array($selectedAccessories)) $selectedAccessories = [];
                        @endphp
                        <select class="form-select" name="default_accessories[]" multiple id="accessoriesSelect">
                            @foreach($accessoryModels as $accessory)
                                <option value="{{ $accessory->id }}" {{ in_array($accessory->id, $selectedAccessories) ? 'selected' : '' }}>
                                    {{ $accessory->model_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                @endif

                <!-- Submit -->
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-1"></i> Update Model
                    </button>
                    <a href="{{ route('admin.terminal-models.show', $terminalModel) }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Select2
    $('#accessoriesSelect').select2({
        theme: 'bootstrap-5',
        placeholder: 'Select accessories...',
        allowClear: true
    });

    // Image preview on file select
    $('#imageInput').on('change', function() {
        var file = this.files[0];
        if (file) {
            if (file.size > 2 * 1024 * 1024) {
                showToast('Image size must not exceed 2MB', 'error');
                this.value = '';
                return;
            }
            var reader = new FileReader();
            reader.onload = function(e) {
                $('#imagePreview').attr('src', e.target.result);
            };
            reader.readAsDataURL(file);
        }
    });

    // Delete existing image
    $('#deleteImageBtn').on('click', function() {
        confirmAction('Delete Image?', 'This will remove the model image.', function() {
            $.ajax({
                url: '{{ route("admin.terminal-models.delete-image", $terminalModel) }}',
                type: 'DELETE',
                success: function(response) {
                    if (response.success) {
                        showToast(response.message);
                        $('#imagePreview').attr('src', 'data:image/svg+xml;charset=UTF-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22200%22%20height%3D%22200%22%3E%3Crect%20fill%3D%22%23f0f0f0%22%20width%3D%22200%22%20height%3D%22200%22%2F%3E%3Ctext%20fill%3D%22%23999%22%20font-size%3D%2214%22%20x%3D%2250%25%22%20y%3D%2250%25%22%20text-anchor%3D%22middle%22%20dy%3D%22.3em%22%3ENo%20Image%3C%2Ftext%3E%3C%2Fsvg%3E');
                        $('#deleteImageBtn').remove();
                    }
                },
                error: function(xhr) {
                    showToast(xhr.responseJSON?.message || 'Failed to delete image', 'error');
                }
            });
        });
    });

    // Specifications
    var specIndex = {{ count($specs) > 0 ? count($specs) : 1 }};
    $('#addSpecBtn').on('click', function() {
        var row = `<div class="row g-2 mb-2 spec-row">
            <div class="col-md-5">
                <input type="text" class="form-control" name="specifications[${specIndex}][key]" placeholder="Key">
            </div>
            <div class="col-md-5">
                <input type="text" class="form-control" name="specifications[${specIndex}][value]" placeholder="Value">
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-outline-danger btn-sm remove-spec-btn w-100">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>`;
        $('#specificationsContainer').append(row);
        specIndex++;
    });

    $(document).on('click', '.remove-spec-btn', function() {
        if ($('.spec-row').length > 1) {
            $(this).closest('.spec-row').remove();
        } else {
            $(this).closest('.spec-row').find('input').val('');
        }
    });
});
</script>
@endpush
