@extends('layouts.app')

@section('title', 'Create Terminal Model')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.terminal-models.index') }}">Terminal Models</a></li>
                    <li class="breadcrumb-item active">Create</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0">Create Terminal Model</h1>
        </div>
    </div>

    <!-- Form Card -->
    <div class="row">
        <div class="col-md-12">
            <form action="{{ route('admin.terminal-models.store') }}" method="POST" enctype="multipart/form-data" id="createModelForm">
                @csrf
                
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Terminal Model Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- Model Code -->
                            <div class="col-md-4 mb-3">
                                <label for="model_code" class="form-label">Model Code</label>
                                <input type="text" class="form-control @error('model_code') is-invalid @enderror" 
                                       id="model_code" name="model_code" value="{{ old('model_code', $modelCode) }}" 
                                       placeholder="Auto-generated if left blank">
                                <div class="form-text">Leave blank to auto-generate</div>
                                @error('model_code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Model Name -->
                            <div class="col-md-4 mb-3">
                                <label for="model_name" class="form-label">Model Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('model_name') is-invalid @enderror" 
                                       id="model_name" name="model_name" value="{{ old('model_name') }}" required>
                                @error('model_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Category -->
                            <div class="col-md-4 mb-3">
                                <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                                <select class="form-select @error('category_id') is-invalid @enderror" 
                                        id="category_id" name="category_id" required>
                                    <option value="">Select Category</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                            {{ $category->category_name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('category_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Brand -->
                            <div class="col-md-6 mb-3">
                                <label for="brand" class="form-label">Brand</label>
                                <input type="text" class="form-control @error('brand') is-invalid @enderror" 
                                       id="brand" name="brand" value="{{ old('brand') }}">
                                @error('brand')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Warranty Months -->
                            <div class="col-md-3 mb-3">
                                <label for="warranty_months" class="form-label">Warranty (Months)</label>
                                <input type="number" class="form-control @error('warranty_months') is-invalid @enderror" 
                                       id="warranty_months" name="warranty_months" value="{{ old('warranty_months', 12) }}" 
                                       min="0" max="120">
                                @error('warranty_months')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Serial Tracked -->
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Serial Tracking</label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_serial_tracked" 
                                           name="is_serial_tracked" value="1" {{ old('is_serial_tracked', true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_serial_tracked">
                                        Track serial numbers
                                    </label>
                                </div>
                            </div>

                            <!-- Description -->
                            <div class="col-md-12 mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control @error('description') is-invalid @enderror" 
                                          id="description" name="description" rows="3">{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Specifications Section -->
                <div class="card mt-3">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Specifications</h5>
                            <button type="button" class="btn btn-sm btn-primary" id="addSpecBtn">
                                <i class="bi bi-plus-circle"></i> Add Specification
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="specificationsContainer">
                            <!-- Specifications will be added here dynamically -->
                        </div>
                    </div>
                </div>

                <!-- Default Accessories Section -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="mb-0">Default Accessories</h5>
                    </div>
                    <div class="card-body">
                        <label for="default_accessories" class="form-label">Select Accessories</label>
                        <select class="form-select" id="default_accessories" name="default_accessories[]" multiple>
                            @foreach($accessoryModels as $accessory)
                                <option value="{{ $accessory->id }}">{{ $accessory->model_name }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Hold Ctrl (Cmd on Mac) to select multiple accessories</div>
                    </div>
                </div>

                <!-- Image Upload Section -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="mb-0">Model Image</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <label for="image" class="form-label">Upload Image</label>
                                <input type="file" class="form-control @error('image') is-invalid @enderror" 
                                       id="image" name="image" accept="image/jpeg,image/png,image/jpg,image/gif">
                                <div class="form-text">Max size: 2MB. Formats: JPEG, PNG, GIF</div>
                                @error('image')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Preview</label>
                                <div id="imagePreview" class="border rounded p-2 text-center" style="min-height: 150px;">
                                    <span class="text-muted">No image selected</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Additional Settings -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="mb-0">Additional Settings</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="sort_order" class="form-label">Sort Order</label>
                                <input type="number" class="form-control @error('sort_order') is-invalid @enderror" 
                                       id="sort_order" name="sort_order" value="{{ old('sort_order', 0) }}" min="0">
                                @error('sort_order')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                                    <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="card mt-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('admin.terminal-models.index') }}" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Create Terminal Model
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    let specCounter = 0;

    // Add specification row
    $('#addSpecBtn').click(function() {
        addSpecificationRow();
    });

    function addSpecificationRow(key = '', value = '') {
        const html = `
            <div class="row mb-2 spec-row" data-spec-id="${specCounter}">
                <div class="col-md-5">
                    <input type="text" class="form-control" name="specifications[${specCounter}][key]" 
                           placeholder="Specification name (e.g., Processor)" value="${key}">
                </div>
                <div class="col-md-6">
                    <input type="text" class="form-control" name="specifications[${specCounter}][value]" 
                           placeholder="Value (e.g., Quad-core 1.5GHz)" value="${value}">
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-danger btn-sm remove-spec" data-spec-id="${specCounter}">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        `;
        $('#specificationsContainer').append(html);
        specCounter++;
    }

    // Remove specification row
    $(document).on('click', '.remove-spec', function() {
        const specId = $(this).data('spec-id');
        $(`.spec-row[data-spec-id="${specId}"]`).remove();
    });

    // Add initial specification row
    addSpecificationRow();

    // Image preview
    $('#image').change(function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#imagePreview').html(`<img src="${e.target.result}" class="img-fluid" style="max-height: 200px;">`);
            }
            reader.readAsDataURL(file);
        } else {
            $('#imagePreview').html('<span class="text-muted">No image selected</span>');
        }
    });

    // Initialize Select2 for accessories (if available)
    if ($.fn.select2) {
        $('#default_accessories').select2({
            placeholder: 'Select accessories',
            allowClear: true
        });
    }

    // Form validation
    $('#createModelForm').submit(function(e) {
        const modelName = $('#model_name').val().trim();
        const categoryId = $('#category_id').val();

        if (!modelName) {
            e.preventDefault();
            alert('Please enter a model name');
            $('#model_name').focus();
            return false;
        }

        if (!categoryId) {
            e.preventDefault();
            alert('Please select a category');
            $('#category_id').focus();
            return false;
        }
    });
});
</script>
@endpush
