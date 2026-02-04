@extends('layouts.app')

@section('title', 'Stock Balance - ' . $locationName)

@section('content')
<div class="container-fluid">
    <div class="mb-3">
        <a href="{{ route('admin.stock-balance.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to Stock Balance
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">
                <i class="bi bi-geo-alt me-2"></i>{{ $locationName }} - Stock Balance
            </h4>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>Model</th>
                            <th>Model Code</th>
                            <th>Category</th>
                            <th class="text-end">On Hand</th>
                            <th class="text-end">Reserved</th>
                            <th class="text-end">Available</th>
                            <th>Last Movement</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($balances as $balance)
                        <tr>
                            <td><strong>{{ $balance->model->model_name }}</strong></td>
                            <td>{{ $balance->model->model_code }}</td>
                            <td>{{ $balance->model->category->category_name ?? '-' }}</td>
                            <td class="text-end">{{ number_format($balance->quantity_on_hand, 2) }}</td>
                            <td class="text-end">{{ number_format($balance->quantity_reserved, 2) }}</td>
                            <td class="text-end"><strong>{{ number_format($balance->quantity_available, 2) }}</strong></td>
                            <td>{{ $balance->last_movement_date?->format('d M Y') ?? '-' }}</td>
                            <td>{!! $balance->stock_status_badge !!}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted">No stock balances found</td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if($balances->count() > 0)
                    <tfoot class="table-light">
                        <tr>
                            <th colspan="3" class="text-end">Total:</th>
                            <th class="text-end">{{ number_format($balances->sum('quantity_on_hand'), 2) }}</th>
                            <th class="text-end">{{ number_format($balances->sum('quantity_reserved'), 2) }}</th>
                            <th class="text-end">{{ number_format($balances->sum('quantity_available'), 2) }}</th>
                            <th colspan="2"></th>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
