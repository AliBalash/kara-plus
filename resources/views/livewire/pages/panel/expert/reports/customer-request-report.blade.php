@php
    $summary = $report['summary'];
@endphp

<div>
    @include('livewire.pages.panel.expert.reports.partials.styles')

    <section class="card report-hero mb-4">
        <div class="card-body p-4 p-lg-5 position-relative">
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
                <div>
                    <span class="report-eyebrow"><i class="bx bx-folder-open"></i> Reports / Customers</span>
                    <h3 class="mt-3 mb-2 text-white">Customer Request Intelligence</h3>
                    <p class="report-subtitle">
                        Find every contract for a customer by name and date range, review operational ownership,
                        financial posture, charge composition, and export the same filtered view to a polished Excel workbook.
                    </p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ $exportUrl }}" class="btn btn-light">
                        <i class="bx bx-export me-1"></i> Export Excel
                    </a>
                </div>
            </div>
        </div>
    </section>

    @include('livewire.pages.panel.expert.reports.partials.nav')

    <div class="row g-3 mb-4">
        <div class="col-md-6 col-xl-2">
            <div class="report-kpi card card-body">
                <span class="metric-label">Contracts</span>
                <div class="metric-value mt-2">{{ number_format($summary['matching_contracts']) }}</div>
                <p class="metric-note mt-2">Filtered request records.</p>
            </div>
        </div>
        <div class="col-md-6 col-xl-2">
            <div class="report-kpi card card-body">
                <span class="metric-label">Customers</span>
                <div class="metric-value mt-2">{{ number_format($summary['unique_customers']) }}</div>
                <p class="metric-note mt-2">Unique matched customers.</p>
            </div>
        </div>
        <div class="col-md-6 col-xl-2">
            <div class="report-kpi card card-body">
                <span class="metric-label">Contract Value</span>
                <div class="metric-value mt-2">{{ number_format($summary['gross_contract_value'], 0) }}</div>
                <p class="metric-note mt-2">AED across matched contracts.</p>
            </div>
        </div>
        <div class="col-md-6 col-xl-2">
            <div class="report-kpi card card-body">
                <span class="metric-label">Recorded Payments</span>
                <div class="metric-value mt-2">{{ number_format($summary['recorded_payments'], 0) }}</div>
                <p class="metric-note mt-2">Net registered cashflow.</p>
            </div>
        </div>
        <div class="col-md-6 col-xl-2">
            <div class="report-kpi card card-body">
                <span class="metric-label">Outstanding</span>
                <div class="metric-value mt-2">{{ number_format($summary['outstanding_balance'], 0) }}</div>
                <p class="metric-note mt-2">Open balance still on contracts.</p>
            </div>
        </div>
        <div class="col-md-6 col-xl-2">
            <div class="report-kpi card card-body">
                <span class="metric-label">Avg Rental Days</span>
                <div class="metric-value mt-2">{{ number_format($summary['average_rental_days'], 1) }}</div>
                <p class="metric-note mt-2">Average planned rental span.</p>
            </div>
        </div>
    </div>

    <section class="card report-filter-card mb-4">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                <div>
                    <h5 class="mb-1">Filter the request stream</h5>
                    <p class="text-muted mb-0">Search by customer, phone, passport, contract ID, vehicle or plate.</p>
                </div>
                <button type="button" class="btn btn-outline-secondary" wire:click="clearFilters">
                    <i class="bx bx-reset me-1"></i> Reset Filters
                </button>
            </div>

            <div class="row g-3">
                <div class="col-lg-4">
                    <div class="filter-field">
                        <div class="d-flex justify-content-between align-items-center">
                            <label class="filter-label" for="customerRequestSearch">Customer Search</label>
                            <span class="filter-hint">Name, phone, contract, passport</span>
                        </div>
                        <input id="customerRequestSearch" type="search" class="form-control" placeholder="Start typing..."
                            wire:model.live.debounce.350ms="search">
                    </div>
                </div>
                <div class="col-lg-2">
                    <div class="filter-field">
                        <label class="filter-label" for="customerRequestDateField">Date Basis</label>
                        <select id="customerRequestDateField" class="form-select" wire:model.live="dateField">
                            <option value="created_at">Request Date</option>
                            <option value="pickup_date">Pickup Date</option>
                            <option value="return_date">Return Date</option>
                        </select>
                    </div>
                </div>
                <div class="col-lg-2">
                    <div class="filter-field">
                        <label class="filter-label" for="customerRequestDateFrom">Date From</label>
                        <input id="customerRequestDateFrom" type="date" class="form-control" wire:model.live="dateFrom">
                    </div>
                </div>
                <div class="col-lg-2">
                    <div class="filter-field">
                        <label class="filter-label" for="customerRequestDateTo">Date To</label>
                        <input id="customerRequestDateTo" type="date" class="form-control" wire:model.live="dateTo">
                    </div>
                </div>
                <div class="col-lg-2">
                    <div class="filter-field">
                        <label class="filter-label" for="customerRequestStatus">Status</label>
                        <select id="customerRequestStatus" class="form-select" wire:model.live="status">
                            @foreach ($statusOptions as $option)
                                <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-lg-3">
                    <div class="filter-field">
                        <label class="filter-label" for="customerRequestAgent">Sales Agent</label>
                        <select id="customerRequestAgent" class="form-select" wire:model.live="agentId">
                            <option value="">All agents</option>
                            @foreach ($agents as $agent)
                                <option value="{{ $agent['id'] }}">{{ $agent['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-lg-3">
                    <div class="filter-field">
                        <label class="filter-label" for="customerRequestKardo">KARDO</label>
                        <select id="customerRequestKardo" class="form-select" wire:model.live="kardo">
                            @foreach ($kardoOptions as $option)
                                <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="card report-results-card">
        <div class="report-results-meta">
            <div>
                <h5 class="mb-1">Matched contracts</h5>
                <p class="text-muted mb-0">{{ number_format($summary['matching_contracts']) }} records ready for on-screen review and Excel export.</p>
            </div>
            <div class="report-filter-badges">
                @foreach ($report['filter_summary'] as $label => $value)
                    <span class="badge">{{ $label }}: {{ $value }}</span>
                @endforeach
            </div>
        </div>

        @if ($rows->count())
            <div class="table-responsive">
                <table class="table table-hover mb-0 report-table">
                    <thead>
                        <tr>
                            <th>Contract</th>
                            <th>Customer</th>
                            <th>Vehicle & Route</th>
                            <th>Financial Snapshot</th>
                            <th>Ops Ownership</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                        @foreach ($rows as $row)
                            <tr>
                                <td>
                                    <span class="cell-title">#{{ $row['contract_id'] }}</span>
                                    <span class="cell-subtitle">Request {{ $row['request_date'] }}</span>
                                    <span class="cell-subtitle">Pickup {{ $row['pickup_date'] }}</span>
                                    <span class="cell-subtitle">Return {{ $row['return_date'] }}</span>
                                </td>
                                <td>
                                    <span class="cell-title">{{ $row['customer_name'] }}</span>
                                    <span class="cell-subtitle">{{ $row['customer_phone'] }}</span>
                                    <span class="cell-subtitle">{{ $row['customer_nationality'] }}</span>
                                    <span class="cell-subtitle">Passport: {{ $row['passport_number'] }}</span>
                                </td>
                                <td>
                                    <span class="cell-title">{{ $row['car_name'] }}</span>
                                    <span class="cell-subtitle">Plate: {{ $row['plate_number'] }} | {{ $row['ownership'] }}</span>
                                    <span class="cell-subtitle">{{ $row['pickup_location'] }} → {{ $row['return_location'] }}</span>
                                    <span class="cell-subtitle">{{ $row['selected_insurance'] }}</span>
                                </td>
                                <td>
                                    <span class="cell-metric">{{ number_format($row['total_price'], 2) }} AED total</span>
                                    <span class="cell-subtitle">Paid: {{ number_format($row['net_payments'], 2) }} AED</span>
                                    <span class="cell-subtitle">Outstanding: {{ number_format($row['remaining_balance'], 2) }} AED</span>
                                    <span class="cell-subtitle">Hold: {{ $row['deposit_hold'] }}</span>
                                </td>
                                <td>
                                    <span class="cell-title">{{ $row['assigned_expert'] }}</span>
                                    <span class="cell-subtitle">Sales: {{ $row['sales_agent'] }}</span>
                                    <span class="cell-subtitle">Submitted by: {{ $row['submitted_by'] }}</span>
                                    <span class="cell-subtitle">KARDO: {{ $row['kardo_required'] }}</span>
                                </td>
                                <td>
                                    <x-status-badge :status="$row['status']" />
                                    <div class="mt-2">
                                        <a href="{{ route('rental-requests.details', $row['contract_id']) }}"
                                            class="btn btn-sm btn-outline-primary">
                                            <i class="bx bx-detail me-1"></i> Details
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="p-3">
                {{ $rows->links() }}
            </div>
        @else
            <div class="report-empty">
                <div>
                    <i class="bx bx-search-alt"></i>
                    <h5 class="mt-3 mb-2">No matching contracts</h5>
                    <p class="mb-0">Adjust the customer name, date window, status, or KARDO filter and try again.</p>
                </div>
            </div>
        @endif
    </section>
</div>
