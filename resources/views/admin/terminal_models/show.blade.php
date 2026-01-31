@extends('layouts.app')

@section('title', 'Terminal Model Details')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.terminal-models.index') }}">Terminal Models</a></li>
                    <li class="breadcrumb-item active">{{ $terminalModel->model_code }}</li>
                </ol>
            </nav>
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3 mb-0">{{ $terminalModel->model_name }}</h1>
                <div>
                    @can('update', $terminalModel)
                        <a href="{{ route('admin.terminal-models.edit', $terminalModel) }}" class="btn btn-warning">
                            <i class="bi bi-pencil"></i> Edit
                        </a>
                    @endcan
                    <a href="{{ route('admin.terminal-models.index') }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to List
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Left Column - Details -->
        <div class="col-md-8">
            <!-- Basic Information Card -->
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="mb-0">Basic Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Model Code</label>
                            <p class="mb-0">{{ $terminalModel->model_code }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Model Name</label>
                            <p class="mb-0">{{ $terminalModel->model_name }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Category</label>
                            <p class="mb-0">
                                @if($terminalModel->category)
                                    <span class="badge bg-info">{{ $terminalModel->category->category_name }}</span>
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Brand</label>
                            <p class="mb-0">{{ $terminalModel->brand ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Warranty Period</label>
                            <p class="mb-0">{{ $terminalModel->warranty_months }} months</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Serial Tracking</label>
                            <p class="mb-0">
                                @if($terminalModel->is_serial_tracked)
                                    <span class="badge bg-primary"><i class="bi bi-check-circle"></i> Yes</span>
                                @else
                                    <span class="badge bg-secondary">No</span>
                                @endif
                            </p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Status</label>
                            <p class="mb-0">
                                @if($terminalModel->status === 'active')
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-secondary">Inactive</span>
                                @endif
                            </p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Sort Order</label>
                            <p class="mb-0">{{ $terminalModel->sort_order }}</p>
                        </div>
                        @if($terminalModel->description)
                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-bold">Description</label>
                                <p class="mb-0">{{ $terminalModel->description }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Specifications Card -->
            @if($terminalModel->specifications && count($terminalModel->specifications) > 0)
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0">Specifications</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead>
                                    <tr>
                                        <th width="40%">Specification</th>
                                        <th>Value</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($terminalModel->specifications as $spec)
                                        @if(!empty($spec['key']) || !empty($spec['value']))
                                            <tr>
                                                <td><strong>{{ $spec['key'] ?? 'N/A' }}</strong></td>
                                                <td>{{ $spec['value'] ?? 'N/A' }}</td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Default Accessories Card -->
            @if(count($accessories) > 0)
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0">Default Accessories</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group">
                            @foreach($accessories as $accessory)
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong>{{ $accessory->model_code }}</strong> - {{ $accessory->model_name }}
                                            @if($accessory->brand)
                                                <br><small class="text-muted">{{ $accessory->brand }}</small>
                                            @endif
                                        </div>
                                        <span class="badge bg-{{ $accessory->status === 'active' ? 'success' : 'secondary' }}">
                                            {{ ucfirst($accessory->status) }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <!-- Stock Summary Card -->
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="mb-0">Stock Summary</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center mb-3">
                        <div class="col-md-3">
                            <h4 class="text-success">{{ $stockSummary['total_in_stock'] }}</h4>
                            <small class="text-muted">In Stock</small>
                        </div>
                        <div class="col-md-3">
                            <h4 class="text-warning">{{ $stockSummary['total_issued'] }}</h4>
                            <small class="text-muted">Issued</small>
                        </div>
                        <div class="col-md-3">
                            <h4 class="text-info">{{ $stockSummary['total_installed'] }}</h4>
                            <small class="text-muted">Installed</small>
                        </div>
                        <div class="col-md-3">
                            <h4 class="text-primary">{{ $stockSummary['total_overall'] }}</h4>
                            <small class="text-muted">Total</small>
                        </div>
                    </div>

                    @if(count($stockSummary['by_depot']) > 0)
                        <hr>
                        <h6>By Depot</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Depot</th>
                                        <th class="text-end">In Stock</th>
                                        <th class="text-end">Issued</th>
                                        <th class="text-end">Installed</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($stockSummary['by_depot'] as $balance)
                                        <tr>
                                            <td>{{ $balance->depot ? $balance->depot->depot_name : 'Unknown' }}</td>
                                            <td class="text-end">{{ $balance->quantity_in_stock }}</td>
                                            <td class="text-end">{{ $balance->quantity_issued }}</td>
                                            <td class="text-end">{{ $balance->quantity_installed }}</td>
                                            <td class="text-end"><strong>{{ $balance->quantity_in_stock + $balance->quantity_issued + $balance->quantity_installed }}</strong></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted text-center mb-0">No stock available</p>
                    @endif
                </div>
            </div>

            <!-- Recent Movements Card -->
            @if(count($recentMovements) > 0)
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0">Recent Stock Movements (Last 10)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Serial No</th>
                                        <th>Status</th>
                                        <th>Location</th>
                                        <th>Updated</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentMovements as $serial)
                                        <tr>
                                            <td><code>{{ $serial->serial_no }}</code></td>
                                            <td><span class="badge bg-secondary">{{ $serial->current_status }}</span></td>
                                            <td>
                                                @if($serial->current_location_type === 'depot' && $serial->currentLocationDepot)
                                                    <i class="bi bi-building"></i> {{ $serial->currentLocationDepot->depot_name }}
                                                @elseif($serial->current_location_type === 'site' && $serial->currentLocationSite)
                                                    <i class="bi bi-geo-alt"></i> {{ $serial->currentLocationSite->site_name }}
                                                @elseif($serial->current_location_type === 'user' && $serial->currentLocationUser)
                                                    <i class="bi bi-person"></i> {{ $serial->currentLocationUser->name }}
                                                @else
                                                    <span class="text-muted">N/A</span>
                                                @endif
                                            </td>
                                            <td>
                                                <small class="text-muted">{{ $serial->updated_at->format('Y-m-d H:i') }}</small>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Right Column - Image & Meta -->
        <div class="col-md-4">
            <!-- Image Card -->
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="mb-0">Model Image</h5>
                </div>
                <div class="card-body text-center">
                    @if($terminalModel->image_path)
                        <img src="{{ asset('storage/' . $terminalModel->image_path) }}" 
                             alt="{{ $terminalModel->model_name }}" 
                             class="img-fluid rounded">
                    @else
                        <div class="text-muted py-5">
                            <i class="bi bi-image" style="font-size: 4rem;"></i>
                            <p class="mt-2">No image available</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Metadata Card -->
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="mb-0">Metadata</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Created At</label>
                        <p class="mb-0 small">{{ $terminalModel->created_at ? $terminalModel->created_at->format('Y-m-d H:i:s') : 'N/A' }}</p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Updated At</label>
                        <p class="mb-0 small">{{ $terminalModel->updated_at ? $terminalModel->updated_at->format('Y-m-d H:i:s') : 'N/A' }}</p>
                    </div>
                    @if($terminalModel->deleted_at)
                        <div class="mb-0">
                            <label class="form-label fw-bold small">Deleted At</label>
                            <p class="mb-0 small text-danger">{{ $terminalModel->deleted_at->format('Y-m-d H:i:s') }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
