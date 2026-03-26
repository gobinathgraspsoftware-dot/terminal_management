{{--
    Shared Report Filter Panel Component
    Usage: @include('components.reports.filter-panel', ['filters' => [...], 'filterOptions' => $filterOptions])
--}}

@php
    $filters       = $filters ?? [];
    $filterOptions = $filterOptions ?? [];
    $roleName      = explode('.', Route::currentRouteName())[0] ?? 'admin';
@endphp

<div class="card shadow-sm mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <h6 class="mb-0"><i class="bi bi-funnel me-2"></i>Filters</h6>
        <div>
            <button type="button" class="btn btn-sm btn-outline-primary" id="btnApplyFilter">
                <i class="bi bi-search me-1"></i> Apply
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary ms-1" id="btnResetFilter">
                <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
            </button>
        </div>
    </div>
    <div class="card-body">
        <form id="reportFilterForm">
            <div class="row g-3">

                {{-- Date Range --}}
                @if(in_array('date_range', $filters))
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">From Date</label>
                    <input type="date" class="form-control form-control-sm filter-input" name="date_from"
                           id="filterDateFrom" value="{{ now()->startOfMonth()->format('Y-m-d') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">To Date</label>
                    <input type="date" class="form-control form-control-sm filter-input" name="date_to"
                           id="filterDateTo" value="{{ now()->format('Y-m-d') }}">
                </div>
                @endif

                {{-- State --}}
                @if(in_array('state', $filters))
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">State / Region</label>
                    <select class="form-select form-select-sm filter-select2" name="state_id[]" id="filterState" multiple>
                        @foreach($filterOptions['states'] ?? [] as $state)
                            <option value="{{ $state->id }}">{{ $state->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                {{-- City --}}
                @if(in_array('city', $filters))
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">District / City</label>
                    <select class="form-select form-select-sm filter-select2-city" name="city_id[]" id="filterCity" multiple>
                    </select>
                </div>
                @endif

                {{-- Vendor --}}
                @if(in_array('vendor', $filters))
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Vendor</label>
                    <select class="form-select form-select-sm filter-select2" name="vendor_id[]" id="filterVendor" multiple>
                        @foreach($filterOptions['vendors'] ?? [] as $vendor)
                            <option value="{{ $vendor->id }}">{{ $vendor->vendor_name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                {{-- Job Category --}}
                @if(in_array('job_category', $filters))
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Job Category</label>
                    <select class="form-select form-select-sm filter-select2" name="job_category_id[]" id="filterJobCategory" multiple>
                        @foreach($filterOptions['job_categories'] ?? [] as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->category_name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                {{-- Job Type --}}
                @if(in_array('job_type', $filters))
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Job Type</label>
                    <select class="form-select form-select-sm filter-select2" name="job_type_id[]" id="filterJobType" multiple>
                        @foreach($filterOptions['job_types'] ?? [] as $jt)
                            <option value="{{ $jt->id }}">{{ $jt->job_title }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                {{-- Supervisor --}}
                @if(in_array('supervisor', $filters))
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Supervisor</label>
                    <select class="form-select form-select-sm filter-select2" name="supervisor_id[]" id="filterSupervisor" multiple>
                        @foreach($filterOptions['supervisors'] ?? [] as $sup)
                            <option value="{{ $sup->id }}">{{ $sup->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                {{-- Technician --}}
                @if(in_array('technician', $filters))
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Technician</label>
                    <select class="form-select form-select-sm filter-select2" name="technician_id[]" id="filterTechnician" multiple>
                        @foreach($filterOptions['technicians'] ?? [] as $tech)
                            <option value="{{ $tech->id }}">{{ $tech->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                {{-- Ticket Status --}}
                @if(in_array('status', $filters))
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Status</label>
                    <select class="form-select form-select-sm filter-select2" name="status[]" id="filterStatus" multiple>
                        <option value="open">Open</option>
                        <option value="assigned">Assigned</option>
                        <option value="accepted">Accepted</option>
                        <option value="rejected">Rejected</option>
                        <option value="in_progress">In Progress</option>
                        <option value="scheduled">Scheduled</option>
                        <option value="done_success">Done Success</option>
                        <option value="done_fail">Done Fail</option>
                        <option value="rescheduled">Rescheduled</option>
                        <option value="completed">Completed</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>
                @endif

                {{-- Merchant --}}
                @if(in_array('merchant', $filters))
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Merchant</label>
                    <input type="text" class="form-control form-control-sm filter-input" name="merchant_name"
                           id="filterMerchant" placeholder="Search merchant...">
                </div>
                @endif

                {{-- Ticket No --}}
                @if(in_array('ticket_no', $filters))
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Ticket ID / Ref</label>
                    <input type="text" class="form-control form-control-sm filter-input" name="ticket_no"
                           id="filterTicketNo" placeholder="e.g. TKT-00001">
                </div>
                @endif

                {{-- Created By --}}
                @if(in_array('created_by', $filters))
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Created By</label>
                    <select class="form-select form-select-sm filter-select2" name="created_by[]" id="filterCreatedBy" multiple>
                        @foreach($filterOptions['admins'] ?? [] as $adm)
                            <option value="{{ $adm->id }}">{{ $adm->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                {{-- SLA Status --}}
                @if(in_array('sla_status', $filters))
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">SLA Status</label>
                    <select class="form-select form-select-sm filter-select2" name="sla_status[]" id="filterSlaStatus" multiple>
                        <option value="on_track">On Track</option>
                        <option value="at_risk">At Risk</option>
                        <option value="breached">Breached</option>
                    </select>
                </div>
                @endif

                {{-- SLA Breach --}}
                @if(in_array('sla_breach', $filters))
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">SLA Breach</label>
                    <select class="form-select form-select-sm filter-input" name="sla_breach" id="filterSlaBreach">
                        <option value="">All</option>
                        <option value="yes">Yes — Breached</option>
                        <option value="no">No — Within SLA</option>
                    </select>
                </div>
                @endif

                {{-- SLA Time Range --}}
                @if(in_array('sla_time_range', $filters))
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">SLA Time Range</label>
                    <select class="form-select form-select-sm filter-input" name="sla_time_range" id="filterSlaTimeRange">
                        <option value="">All</option>
                        <option value="lt24">&lt; 24 Hours</option>
                        <option value="gt24">&gt; 24 Hours</option>
                    </select>
                </div>
                @endif

                {{-- Rescheduled --}}
                @if(in_array('rescheduled', $filters))
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Rescheduled</label>
                    <select class="form-select form-select-sm filter-input" name="rescheduled" id="filterRescheduled">
                        <option value="">All</option>
                        <option value="yes">Yes</option>
                        <option value="no">No</option>
                    </select>
                </div>
                @endif

                {{-- Claim Type --}}
                @if(in_array('claim_type', $filters))
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Claim Type</label>
                    <select class="form-select form-select-sm filter-select2" name="claim_type[]" id="filterClaimType" multiple>
                        <option value="mileage">Mileage</option>
                        <option value="toll">Toll</option>
                        <option value="meal">Meal</option>
                        <option value="parking">Parking</option>
                        <option value="daily_allowance">Daily Allowance</option>
                        <option value="overnight">Overnight</option>
                        <option value="other">Others</option>
                    </select>
                </div>
                @endif

                {{-- Claim Status --}}
                @if(in_array('claim_status', $filters))
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Claim Status</label>
                    <select class="form-select form-select-sm filter-select2" name="claim_status[]" id="filterClaimStatus" multiple>
                        <option value="draft">Draft</option>
                        <option value="submitted">Submitted</option>
                        <option value="verified">Verified</option>
                        <option value="non_claimable">Non-Claimable</option>
                        <option value="pending_payment">Pending Payment</option>
                        <option value="paid">Paid</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                @endif

                {{-- Claim Category --}}
                @if(in_array('claim_category', $filters))
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Claim Category</label>
                    <select class="form-select form-select-sm filter-input" name="claim_category" id="filterClaimCategory">
                        <option value="">All</option>
                        <option value="ticket">Ticket Claims</option>
                        <option value="other">Other Claims</option>
                    </select>
                </div>
                @endif

                {{-- Payment Status --}}
                @if(in_array('payment_status', $filters))
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Payment Status</label>
                    <select class="form-select form-select-sm filter-input" name="payment_status" id="filterPaymentStatus">
                        <option value="">All</option>
                        <option value="pending">Pending</option>
                        <option value="processed">Processed</option>
                    </select>
                </div>
                @endif

                {{-- Amount Range --}}
                @if(in_array('amount_range', $filters))
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Min Amount (RM)</label>
                    <input type="number" step="0.01" class="form-control form-control-sm filter-input" name="amount_min"
                           id="filterAmountMin" placeholder="0.00">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Max Amount (RM)</label>
                    <input type="number" step="0.01" class="form-control form-control-sm filter-input" name="amount_max"
                           id="filterAmountMax" placeholder="0.00">
                </div>
                @endif

                {{-- Item Type --}}
                @if(in_array('item_type', $filters))
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Item Type</label>
                    <select class="form-select form-select-sm filter-select2" name="item_type[]" id="filterItemType" multiple>
                        <option value="router">Router</option>
                        <option value="accessory">Accessory</option>
                    </select>
                </div>
                @endif

                {{-- Low Stock --}}
                @if(in_array('low_stock', $filters))
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Low Stock</label>
                    <select class="form-select form-select-sm filter-input" name="low_stock" id="filterLowStock">
                        <option value="">All</option>
                        <option value="yes">Low Stock Only</option>
                    </select>
                </div>
                @endif

                {{-- Item Search --}}
                @if(in_array('item_search', $filters))
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Item Name / Model</label>
                    <input type="text" class="form-control form-control-sm filter-input" name="item_search"
                           id="filterItemSearch" placeholder="Search item...">
                </div>
                @endif

                {{-- Terminal ID --}}
                @if(in_array('terminal_id', $filters))
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Terminal ID</label>
                    <input type="text" class="form-control form-control-sm filter-input" name="terminal_id"
                           id="filterTerminalId" placeholder="Terminal / Serial">
                </div>
                @endif

                {{-- Movement Type --}}
                @if(in_array('movement_type', $filters))
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Movement Type</label>
                    <select class="form-select form-select-sm filter-select2" name="movement_type[]" id="filterMovementType" multiple>
                        <option value="stock_in">Stock In</option>
                        <option value="stock_out">Stock Out</option>
                        <option value="stock_return">Return</option>
                        <option value="stock_adjustment">Adjustment</option>
                    </select>
                </div>
                @endif

                {{-- Ticket ID --}}
                @if(in_array('ticket_id', $filters))
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Ticket ID</label>
                    <input type="text" class="form-control form-control-sm filter-input" name="ticket_id"
                           id="filterTicketId" placeholder="Ticket ID number">
                </div>
                @endif

                {{-- Accessory Type --}}
                @if(in_array('accessory_type', $filters))
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Accessory Type</label>
                    <select class="form-select form-select-sm filter-select2" name="accessory_type[]" id="filterAccessoryType" multiple>
                        <option value="sim_card">SIM Card</option>
                        <option value="antenna">Antenna</option>
                    </select>
                </div>
                @endif

                {{-- Usage Type --}}
                @if(in_array('usage_type', $filters))
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Usage Type</label>
                    <select class="form-select form-select-sm filter-input" name="usage_type" id="filterUsageType">
                        <option value="">All</option>
                        <option value="used">Used (Stock Out)</option>
                        <option value="returned">Returned</option>
                    </select>
                </div>
                @endif

                {{-- Report Type (Rejected/Rescheduled) --}}
                @if(in_array('report_type', $filters))
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Type</label>
                    <select class="form-select form-select-sm filter-input" name="report_type" id="filterReportType">
                        <option value="">Both</option>
                        <option value="rejected">Rejected</option>
                        <option value="rescheduled">Rescheduled</option>
                    </select>
                </div>
                @endif

                {{-- Reason --}}
                @if(in_array('reason', $filters))
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Reason</label>
                    <input type="text" class="form-control form-control-sm filter-input" name="reason"
                           id="filterReason" placeholder="Search reason...">
                </div>
                @endif

            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize Select2
    $('.filter-select2').select2({ theme: 'bootstrap-5', placeholder: 'Select...', allowClear: true, width: '100%' });

    // City dropdown — dependent on state
    @if(in_array('city', $filters))
    var $citySelect = $('.filter-select2-city');
    $citySelect.select2({ theme: 'bootstrap-5', placeholder: 'Select state first...', allowClear: true, width: '100%' });

    $('#filterState').on('change.select2', function() {
        var stateIds = $(this).val();
        $citySelect.empty().trigger('change.select2');
        if (stateIds && stateIds.length > 0) {
            var promises = stateIds.map(function(stateId) {
                return $.ajax({ url: '{{ route($roleName . ".ajax.cities") }}', data: { state_id: stateId } });
            });
            $.when.apply($, promises).done(function() {
                var results = stateIds.length === 1 ? [arguments] : Array.from(arguments);
                results.forEach(function(resp) {
                    var data = stateIds.length === 1 ? resp[0] : resp[0];
                    if (data && data.results) {
                        data.results.forEach(function(city) {
                            $citySelect.append(new Option(city.text || city.name, city.id, false, false));
                        });
                    }
                });
                $citySelect.trigger('change.select2');
            });
        }
    });
    @endif

    // Apply Filter
    $('#btnApplyFilter').on('click', function() {
        if (typeof reportTable !== 'undefined') { reportTable.ajax.reload(); }
    });

    // Reset Filter
    $('#btnResetFilter').on('click', function() {
        $('#reportFilterForm')[0].reset();
        $('.filter-select2').val(null).trigger('change.select2');
        @if(in_array('city', $filters))
        $citySelect.empty().trigger('change.select2');
        @endif
        @if(in_array('date_range', $filters))
        $('#filterDateFrom').val('{{ now()->startOfMonth()->format("Y-m-d") }}');
        $('#filterDateTo').val('{{ now()->format("Y-m-d") }}');
        @endif
        if (typeof reportTable !== 'undefined') { reportTable.ajax.reload(); }
    });
});

/**
 * Collect all filter values for AJAX requests.
 */
function getReportFilters() {
    var data = {};
    $('#reportFilterForm').find('.filter-input').each(function() {
        var name = $(this).attr('name');
        var val  = $(this).val();
        if (val && val !== '') { data[name] = val; }
    });
    $('#reportFilterForm').find('.filter-select2, .filter-select2-city').each(function() {
        var name = $(this).attr('name');
        var val  = $(this).val();
        if (val && val.length > 0) { data[name] = val; }
    });
    return data;
}

/**
 * Open print-friendly page in new tab with current filters.
 * Usage in view: openPrintView('ticket-summary')
 * @param {string} reportType — e.g. 'ticket-summary', 'claim', 'sla'
 */
function openPrintView(reportType) {
    var params = $.param(getReportFilters());
    var printUrl = '{{ route($roleName . ".reports.print") }}?type=' + reportType + '&' + params;
    window.open(printUrl, '_blank');
}
</script>
@endpush
