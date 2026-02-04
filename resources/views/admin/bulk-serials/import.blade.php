@extends('layouts.app')

@section('title', 'Import Serials')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2><i class="bi bi-upload"></i> Import Serials</h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.bulk-serials.index') }}">Bulk Operations</a></li>
                        <li class="breadcrumb-item active">Import</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8 offset-md-2">
            <!-- Step Wizard -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <!-- Steps Progress -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between">
                            <div class="text-center flex-fill step-item active" data-step="1">
                                <div class="step-number">1</div>
                                <div class="step-label">Download Template</div>
                            </div>
                            <div class="text-center flex-fill step-item" data-step="2">
                                <div class="step-number">2</div>
                                <div class="step-label">Prepare Data</div>
                            </div>
                            <div class="text-center flex-fill step-item" data-step="3">
                                <div class="step-number">3</div>
                                <div class="step-label">Upload File</div>
                            </div>
                            <div class="text-center flex-fill step-item" data-step="4">
                                <div class="step-number">4</div>
                                <div class="step-label">Review & Confirm</div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 1: Download Template -->
                    <div class="step-content" id="step-1">
                        <h4 class="mb-3">Step 1: Download Import Template</h4>
                        <p>Download the Excel template to ensure correct data format.</p>
                        
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> The template includes sample rows and instructions. Delete the sample rows before importing.
                        </div>

                        <a href="{{ route('admin.bulk-serials.download-template') }}" class="btn btn-primary btn-lg">
                            <i class="bi bi-download"></i> Download Template
                        </a>

                        <button type="button" class="btn btn-success btn-lg" onclick="nextStep(2)">
                            Next <i class="bi bi-arrow-right"></i>
                        </button>
                    </div>

                    <!-- Step 2: Prepare Data -->
                    <div class="step-content d-none" id="step-2">
                        <h4 class="mb-3">Step 2: Prepare Your Data</h4>
                        <p>Fill in the Excel template with your serial numbers.</p>

                        <div class="alert alert-warning">
                            <h6><i class="bi bi-exclamation-triangle"></i> Important Guidelines:</h6>
                            <ul class="mb-0">
                                <li><strong>Serial No</strong> - Required and must be unique</li>
                                <li><strong>Model Code or Model Name</strong> - At least one is required</li>
                                <li><strong>Status</strong> - Must be one of: in_stock, issued_to_tech, installed, under_service, returned_to_vendor, wasted, reserved</li>
                                <li><strong>Location Type</strong> - Must be: depot, technician, site, or vendor</li>
                                <li><strong>Location Code or Name</strong> - Required for identifying the location</li>
                                <li>Delete all sample rows before importing</li>
                            </ul>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="table-primary">
                                    <tr>
                                        <th>Column</th>
                                        <th>Required</th>
                                        <th>Description</th>
                                        <th>Example</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><strong>Serial No</strong></td>
                                        <td><span class="badge bg-danger">Yes</span></td>
                                        <td>Unique serial number</td>
                                        <td>SERIAL001</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Model Code</strong></td>
                                        <td><span class="badge bg-warning">Optional</span></td>
                                        <td>Terminal model code</td>
                                        <td>TM001</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Model Name</strong></td>
                                        <td><span class="badge bg-warning">Optional</span></td>
                                        <td>Terminal model name</td>
                                        <td>PAX A920</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Status</strong></td>
                                        <td><span class="badge bg-danger">Yes</span></td>
                                        <td>Current status</td>
                                        <td>in_stock</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Location Type</strong></td>
                                        <td><span class="badge bg-danger">Yes</span></td>
                                        <td>Type of location</td>
                                        <td>depot</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Location Code/Name</strong></td>
                                        <td><span class="badge bg-danger">Yes</span></td>
                                        <td>Location identifier</td>
                                        <td>DEP001 or Main Depot</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <button type="button" class="btn btn-secondary" onclick="prevStep(1)">
                            <i class="bi bi-arrow-left"></i> Previous
                        </button>
                        <button type="button" class="btn btn-success" onclick="nextStep(3)">
                            Next <i class="bi bi-arrow-right"></i>
                        </button>
                    </div>

                    <!-- Step 3: Upload File -->
                    <div class="step-content d-none" id="step-3">
                        <h4 class="mb-3">Step 3: Upload Excel File</h4>
                        
                        <form id="importForm" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <label for="importFile" class="form-label">Select Excel File</label>
                                <input type="file" class="form-control" id="importFile" name="file" accept=".xlsx,.xls,.csv" required>
                                <div class="form-text">Supported formats: XLSX, XLS, CSV (Max: 10MB)</div>
                            </div>

                            <div id="uploadProgress" class="d-none mb-3">
                                <div class="progress">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
                                </div>
                                <small class="text-muted">Processing import...</small>
                            </div>

                            <button type="button" class="btn btn-secondary" onclick="prevStep(2)">
                                <i class="bi bi-arrow-left"></i> Previous
                            </button>
                            <button type="button" class="btn btn-primary" onclick="uploadFile()">
                                <i class="bi bi-upload"></i> Upload & Process
                            </button>
                        </form>
                    </div>

                    <!-- Step 4: Results -->
                    <div class="step-content d-none" id="step-4">
                        <h4 class="mb-3">Step 4: Import Results</h4>
                        
                        <div id="importResults"></div>

                        <div class="mt-3">
                            <a href="{{ route('admin.bulk-serials.index') }}" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Back to Bulk Operations
                            </a>
                            <a href="{{ route('admin.inventory-serials.index') }}" class="btn btn-primary">
                                <i class="bi bi-list-ul"></i> View Inventory
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.step-item {
    position: relative;
}

.step-number {
    width: 50px;
    height: 50px;
    background: #e9ecef;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 1.2rem;
    margin-bottom: 10px;
}

.step-item.active .step-number {
    background: #0d6efd;
    color: white;
}

.step-item.completed .step-number {
    background: #198754;
    color: white;
}

.step-label {
    font-size: 0.9rem;
    color: #6c757d;
}

.step-item.active .step-label {
    color: #0d6efd;
    font-weight: 600;
}
</style>

@push('scripts')
<script>
let currentStep = 1;

function nextStep(step) {
    // Hide current step
    $(`#step-${currentStep}`).addClass('d-none');
    $(`.step-item[data-step="${currentStep}"]`).removeClass('active').addClass('completed');
    
    // Show next step
    currentStep = step;
    $(`#step-${currentStep}`).removeClass('d-none');
    $(`.step-item[data-step="${currentStep}"]`).addClass('active');
}

function prevStep(step) {
    // Hide current step
    $(`#step-${currentStep}`).addClass('d-none');
    $(`.step-item[data-step="${currentStep}"]`).removeClass('active completed');
    
    // Show previous step
    currentStep = step;
    $(`#step-${currentStep}`).removeClass('d-none');
    $(`.step-item[data-step="${currentStep}"]`).addClass('active');
}

function uploadFile() {
    const formData = new FormData($('#importForm')[0]);
    
    // Show progress
    $('#uploadProgress').removeClass('d-none');
    $('.progress-bar').css('width', '50%');
    
    $.ajax({
        url: '{{ route("admin.bulk-serials.import") }}',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            $('.progress-bar').css('width', '100%');
            
            setTimeout(() => {
                $('#uploadProgress').addClass('d-none');
                displayResults(response);
                nextStep(4);
            }, 500);
        },
        error: function(xhr) {
            $('#uploadProgress').addClass('d-none');
            
            let errorMsg = 'Import failed. Please try again.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMsg = xhr.responseJSON.message;
            }
            
            toastr.error(errorMsg);
        }
    });
}

function displayResults(response) {
    let html = '<div class="alert alert-' + (response.success ? 'success' : 'danger') + '">';
    html += '<h5><i class="bi bi-check-circle"></i> ' + response.message + '</h5>';
    
    if (response.results) {
        html += '<ul class="mb-0">';
        html += '<li><strong>Created:</strong> ' + response.results.success + '</li>';
        html += '<li><strong>Updated:</strong> ' + response.results.updated + '</li>';
        html += '<li><strong>Failed:</strong> ' + response.results.failed + '</li>';
        html += '</ul>';
        
        if (response.results.errors && response.results.errors.length > 0) {
            html += '<hr>';
            html += '<h6>Errors:</h6>';
            html += '<div style="max-height: 300px; overflow-y: auto;">';
            html += '<table class="table table-sm table-bordered">';
            html += '<thead><tr><th>Row</th><th>Serial</th><th>Error</th></tr></thead><tbody>';
            
            response.results.errors.forEach(error => {
                html += '<tr>';
                html += '<td>' + error.row + '</td>';
                html += '<td>' + (error.serial_no || 'N/A') + '</td>';
                html += '<td>' + error.errors.join(', ') + '</td>';
                html += '</tr>';
            });
            
            html += '</tbody></table>';
            html += '</div>';
        }
    }
    
    html += '</div>';
    
    $('#importResults').html(html);
}
</script>
@endpush
@endsection
