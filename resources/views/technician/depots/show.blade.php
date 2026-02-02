@extends('layouts.app')

@section('title', 'Depot Details')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">{{ $depot->depot_name }}</h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('technician.dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('technician.depots.index') }}">Depots</a></li>
                            <li class="breadcrumb-item active">{{ $depot->depot_code }}</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="{{ route('technician.depots.index') }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Back
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Depot Info & Stock Summary -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Depot Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <th width="40%">Depot Code:</th>
                                    <td><span class="badge bg-secondary">{{ $depot->depot_code }}</span></td>
                                </tr>
                                <tr>
                                    <th>Depot Type:</th>
                                    <td>
                                        @php
                                            $badges = [
                                                'main' => '<span class="badge bg-danger">Main Warehouse</span>',
                                                'regional' => '<span class="badge bg-primary">Regional Depot</span>',
                                                'technician' => '<span class="badge bg-info">Technician Depot</span>',
                                            ];
                                        @endphp
                                        {!! $badges[$depot->depot_type] ?? '' !!}
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <th width="40%">Location:</th>
                                    <td>
                                        @php
                                            $location = array_filter([$depot->city, $depot->state]);
                                        @endphp
                                        {{ implode(', ', $location) ?: '-' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>Contact:</th>
                                    <td>{{ $depot->pic_phone ?: '-' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stock Summary Card -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Stock Summary</h5>
                </div>
                <div class="card-body text-center">
                    <h2 class="mb-2 text-primary">{{ number_format($stockSummary['total_quantity'], 0) }}</h2>
                    <p class="text-muted mb-0">Total Units</p>
                    <hr>
                    <h5 class="mb-0">{{ $stockSummary['total_models'] }}</h5>
                    <small class="text-muted">Different Models</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Current Stock Levels -->
    <div class="row">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Current Stock</h5>
                </div>
                <div class="card-body">
                    @if($stockSummary['items']->isEmpty())
                        <div class="alert alert-info mb-0">
                            <i class="bi bi-info-circle me-1"></i> No stock items in this depot.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th width="35%">Model</th>
                                        <th width="20%">Category</th>
                                        <th width="20%" class="text-center">Quantity</th>
                                        <th width="25%">Last Movement</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($stockSummary['items'] as $item)
                                    <tr>
                                        <td>
                                            <strong>{{ $item->model->model_name }}</strong><br>
                                            <small class="text-muted">{{ $item->model->model_code }}</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">
                                                {{ $item->model->category->category_name ?? 'N/A' }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <strong class="text-primary">{{ number_format($item->quantity_on_hand, 0) }}</strong>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                {{ $item->last_movement_date ? \Carbon\Carbon::parse($item->last_movement_date)->format('d M Y') : '-' }}
                                            </small>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
