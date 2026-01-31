@extends('layouts.app')

@section('title', 'Terminal Categories')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="mb-4">
        <h1 class="h4 mb-0">Terminal Categories</h1>
        <p class="text-muted small">Quick reference for field work</p>
    </div>

    <!-- Category Type Tabs -->
    <ul class="nav nav-pills mb-3" id="categoryTypeTabs" role="tablist">
        @php
            $types = ['terminal', 'router', 'sim', 'accessory', 'other'];
            $typeIcons = [
                'terminal' => 'bi-phone',
                'router' => 'bi-router',
                'sim' => 'bi-sim',
                'accessory' => 'bi-usb-plug',
                'other' => 'bi-three-dots'
            ];
        @endphp
        
        @foreach($types as $index => $type)
            @if(isset($categories[$type]) && $categories[$type]->count() > 0)
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $index === 0 ? 'active' : '' }}" 
                        id="tab-{{ $type }}" 
                        data-bs-toggle="pill" 
                        data-bs-target="#content-{{ $type }}" 
                        type="button">
                    <i class="{{ $typeIcons[$type] }} me-1"></i>
                    {{ ucfirst($type) }}
                    <span class="badge bg-secondary ms-1">{{ $categories[$type]->count() }}</span>
                </button>
            </li>
            @endif
        @endforeach
    </ul>

    <!-- Tab Content -->
    <div class="tab-content" id="categoryTypeContent">
        @foreach($types as $index => $type)
            @if(isset($categories[$type]) && $categories[$type]->count() > 0)
            <div class="tab-pane fade {{ $index === 0 ? 'show active' : '' }}" 
                 id="content-{{ $type }}" 
                 role="tabpanel">
                
                <div class="row g-3">
                    @foreach($categories[$type] as $category)
                    <div class="col-md-6 col-lg-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="mb-0">{{ $category->category_name }}</h6>
                                    <span class="badge bg-primary">{{ strtoupper($category->category_code) }}</span>
                                </div>
                                
                                @if($category->description)
                                <p class="small text-muted mb-2">{{ Str::limit($category->description, 100) }}</p>
                                @endif
                                
                                <div class="d-flex justify-content-between align-items-center border-top pt-2 mt-2">
                                    <div class="small">
                                        @if($category->is_serial_tracked)
                                            <i class="bi bi-upc-scan text-success me-1"></i>
                                            <span class="text-success">Serial Required</span>
                                        @else
                                            <i class="bi bi-x-circle text-muted me-1"></i>
                                            <span class="text-muted">No Serial</span>
                                        @endif
                                    </div>
                                    <div class="small">
                                        <span class="badge bg-secondary">
                                            {{ $category->terminal_models_count }} models
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        @endforeach
    </div>

    <!-- Searchable List (Alternative View) -->
    <div class="card border-0 shadow-sm mt-4">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0">All Categories (Searchable)</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="categoriesTable" class="table table-hover table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Serial</th>
                            <th>Models</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- DataTables will populate this -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<style>
    /* Mobile-friendly card styles */
    @media (max-width: 768px) {
        .nav-pills .nav-link {
            font-size: 0.85rem;
            padding: 0.5rem 0.75rem;
        }
        
        .card-body {
            padding: 0.75rem;
        }
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function() {
    $('#categoriesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route('technician.terminal-categories.datatable') }}',
        columns: [
            { data: 'category_code', width: '15%' },
            { data: 'category_name', width: '35%' },
            { data: 'type_badge', orderable: false, width: '15%' },
            { data: 'serial_tracking', orderable: false, width: '20%' },
            { data: 'model_count', orderable: false, width: '15%' }
        ],
        order: [[1, 'asc']],
        pageLength: 25,
        language: {
            emptyTable: "No categories available"
        },
        responsive: true
    });
});
</script>
@endpush
