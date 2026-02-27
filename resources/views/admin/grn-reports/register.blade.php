@extends('layouts.app')

@section('title', 'GRN Register - TMS')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">GRN Register</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.grn-reports.index') }}">GRN Reports</a></li>
                    <li class="breadcrumb-item active">Register</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="{{ route('admin.grn-reports.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
            <button type="button" class="btn btn-outline-success btn-sm ms-1" id="btnExport">
                <i class="bi bi-download me-1"></i> Export Excel
            </button>
            <button type="button" class="btn btn-outline-secondary btn-sm ms-1" onclick="window.print()">
                <i class="bi bi-printer me-1"></i> Print
            </button>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-2">
            <div class="card bg-primary text-white border-0">
                <div class="card-body py-2 px-3 text-center">
                    <div class="small text-white-50">Total GRNs</div>
                    <div class="h4 mb-0">{{ number_format($summary['total_grns']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-secondary text-white border-0">
                <div class="card-body py-2 px-3 text-center">
                    <div class="small text-white-50">Draft</div>
                    <div class="h4 mb-0">{{ number_format($summary['draft_grns']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-success text-white border-0">
                <div class="card-body py-2 px-3 text-center">
                    <div class="small text-white-50">Posted</div>
                    <div class="h4 mb-0">{{ number_format($summary['posted_grns']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-danger text-white border-0">
                <div class="card-body py-2 px-3 text-center">
                    <div class="small text-white-50">Cancelled</div>
                    <div class="h4 mb-0">{{ number_format($summary['cancelled_grns']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-info text-white border-0">
                <div class="card-body py-2 px-3 text-center">
                    <div class="small text-white-50">Total Items</div>
                    <div class="h4 mb-0">{{ number_format($summary['total_items']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-warning text-dark border-0">
                <div class="card-body py-2 px-3 text-center">
                    <div class="small text-muted">Total Value</div>
                    <div class="h5 mb-0">RM {{ number_format($summary['total_value'], 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-3">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('admin.grn-reports.register') }}" id="filterForm">
                <div class="row g-2">
                    <div class="col-md-2">
                        <label class="form-label form-label-sm mb-1">Date From</label>
                        <input type="date" class="form-control form-control-sm" name="date_from"
                               value="{{ $filters['date_from'] ?? '' }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label form-label-sm mb-1">Date To</label>
                        <input type="date" class="form-control form-control-sm" name="date_to"
                               value="{{ $filters['date_to'] ?? '' }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label form-label-sm mb-1">Vendor</label>
                        <select class="form-select form-select-sm select2-filter" name="vendor_id">
                            <option value="">All Vendors</option>
                            @foreach($filterOptions['vendors'] as $vendor)
                                <option value="{{ $vendor->id }}" {{ ($filters['vendor_id'] ?? '') == $vendor->id ? 'selected' : '' }}>
                                    {{ $vendor->vendor_name ?? $vendor->company_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label form-label-sm mb-1">Depot</label>
                        <select class="form-select form-select-sm" name="receiving_depot_id">
                            <option value="">All Depots</option>
                            @foreach($filterOptions['depots'] as $depot)
                                <option value="{{ $depot->id }}" {{ ($filters['receiving_depot_id'] ?? '') == $depot->id ? 'selected' : '' }}>
                                    {{ $depot->depot_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label form-label-sm mb-1">Status</label>
                        <select class="form-select form-select-sm" name="status">
                            <option value="">All Status</option>
                            <option value="draft" {{ ($filters['status'] ?? '') == 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="posted" {{ ($filters['status'] ?? '') == 'posted' ? 'selected' : '' }}>Posted</option>
                            <option value="cancelled" {{ ($filters['status'] ?? '') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="bi bi-search me-1"></i> Filter
                        </button>
                        <a href="{{ route('admin.grn-reports.register') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-x-circle me-1"></i> Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Register Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover table-sm">
                    <thead class="table-dark">
                        <tr>
                            <th>GRN No</th>
                            <th>Date</th>
                            <th>Vendor</th>
                            <th>PO No</th>
                            <th>Depot</th>
                            <th>Model</th>
                            <th class="text-end">Qty</th>
                            <th class="text-end">Unit Cost</th>
                            <th class="text-end">Line Total</th>
                            <th>Status</th>
                            <th>Posted</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($grns as $grn)
                            @php $lineCount = $grn->lines->count(); @endphp
                            @foreach($grn->lines as $index => $line)
                                <tr>
                                    @if($index === 0)
                                        <td rowspan="{{ $lineCount ?: 1 }}">
                                            <a href="{{ route('admin.grns.show', $grn) }}" class="fw-bold text-decoration-none">
                                                {{ $grn->grn_no }}
                                            </a>
                                        </td>
                                        <td rowspan="{{ $lineCount ?: 1 }}">{{ $grn->grn_date->format('d M Y') }}</td>
                                        <td rowspan="{{ $lineCount ?: 1 }}">{{ $grn->vendor->vendor_name ?? $grn->vendor->company_name ?? '-' }}</td>
                                        <td rowspan="{{ $lineCount ?: 1 }}">{{ $grn->purchaseOrder->po_no ?? '-' }}</td>
                                        <td rowspan="{{ $lineCount ?: 1 }}">{{ $grn->receivingDepot->depot_name ?? '-' }}</td>
                                    @endif
                                    <td>{{ $line->model->model_name ?? '-' }}</td>
                                    <td class="text-end">{{ number_format($line->quantity_received, 0) }}</td>
                                    <td class="text-end">{{ number_format($line->unit_cost, 2) }}</td>
                                    <td class="text-end">{{ number_format($line->line_total, 2) }}</td>
                                    @if($index === 0)
                                        <td rowspan="{{ $lineCount ?: 1 }}">
                                            @php
                                                $badges = ['draft' => 'secondary', 'posted' => 'success', 'cancelled' => 'danger'];
                                            @endphp
                                            <span class="badge bg-{{ $badges[$grn->status] ?? 'secondary' }}">{{ ucfirst($grn->status) }}</span>
                                        </td>
                                        <td rowspan="{{ $lineCount ?: 1 }}">
                                            @if($grn->posted_at)
                                                {{ $grn->posted_at->format('d M Y') }}
                                                <br><small class="text-muted">{{ $grn->postedBy->name ?? '' }}</small>
                                            @else
                                                -
                                            @endif
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                            @if($grn->lines->isEmpty())
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.grns.show', $grn) }}" class="fw-bold text-decoration-none">
                                            {{ $grn->grn_no }}
                                        </a>
                                    </td>
                                    <td>{{ $grn->grn_date->format('d M Y') }}</td>
                                    <td>{{ $grn->vendor->vendor_name ?? '-' }}</td>
                                    <td>{{ $grn->purchaseOrder->po_no ?? '-' }}</td>
                                    <td>{{ $grn->receivingDepot->depot_name ?? '-' }}</td>
                                    <td colspan="3" class="text-center text-muted">No line items</td>
                                    <td>
                                        <span class="badge bg-{{ $badges[$grn->status] ?? 'secondary' }}">{{ ucfirst($grn->status) }}</span>
                                    </td>
                                    <td>-</td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="11" class="text-center text-muted py-4">No GRNs found matching your criteria.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div class="text-muted small">
                    Showing {{ $grns->firstItem() ?? 0 }} to {{ $grns->lastItem() ?? 0 }} of {{ $grns->total() }} entries
                </div>
                {{ $grns->appends($filters)->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('.select2-filter').select2({
        theme: 'bootstrap-5',
        allowClear: true,
        placeholder: 'Select...',
        width: '100%'
    });

    $('#btnExport').on('click', function() {
        var params = $('#filterForm').serialize();
        window.location.href = '{{ route("admin.grn-reports.export-register") }}?' + params;
    });
});
</script>
@endpush
