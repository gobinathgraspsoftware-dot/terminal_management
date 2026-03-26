@extends('layouts.app')
@section('title', 'Payment Report — TMS')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="bi bi-wallet2 me-2"></i>Payment Report</h4>
            <nav aria-label="breadcrumb"><ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('admin.reports.index') }}">Reports</a></li>
                <li class="breadcrumb-item active">Payment Report</li>
            </ol></nav>
        </div>
        <div class="btn-group">
            <button class="btn btn-success btn-sm" id="btnExportExcel"><i class="bi bi-file-earmark-excel me-1"></i> Excel</button>
            <button class="btn btn-danger btn-sm" id="btnExportPdf"><i class="bi bi-file-earmark-pdf me-1"></i> PDF</button>
        </div>
    </div>

    @include('components.reports.filter-panel', [
        'filters' => ['date_range','payment_status','technician','job_type','amount_range'],
        'filterOptions' => $filterOptions,
    ])

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="reportTable" class="table table-sm table-hover mb-0" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th>#</th><th>Claim No</th><th>Category</th><th>Ticket No</th><th>Technician</th><th>Vendor</th>
                            <th>Date</th><th class="text-end">Mileage</th><th class="text-end">Allowance</th><th class="text-end">Total</th>
                            <th>Status</th><th>Paid Date</th><th>Batch No</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
var reportTable;
$(document).ready(function() {
    reportTable = $('#reportTable').DataTable({
        processing: true, serverSide: true, searching: false,
        ajax: { url: '{{ route("admin.reports.payment.data") }}', type: 'GET', data: function(d) { return $.extend(d, getReportFilters()); } },
        columns: [
            { data: null, orderable: false, render: function(d,t,r,m) { return m.row + m.settings._iDisplayStart + 1; } },
            { data: 'claim_no' }, { data: 'claim_category' }, { data: 'ticket_no' }, { data: 'technician' }, { data: 'vendor' },
            { data: 'claim_date' }, { data: 'total_mileage', className: 'text-end' }, { data: 'total_allowance', className: 'text-end' },
            { data: 'total_amount', className: 'text-end' }, { data: 'status' }, { data: 'paid_at' }, { data: 'batch_no' },
        ],
        order: [], pageLength: 25, language: { emptyTable: 'No payment records found.' }
    });

    $('#btnExportExcel').on('click', function() { window.location.href = '{{ route("admin.reports.payment.export") }}?' + $.param(getReportFilters()) + '&format=xlsx'; });
    $('#btnExportPdf').on('click', function() { window.location.href = '{{ route("admin.reports.payment.export") }}?' + $.param(getReportFilters()) + '&format=pdf'; });
});
</script>
@endpush
