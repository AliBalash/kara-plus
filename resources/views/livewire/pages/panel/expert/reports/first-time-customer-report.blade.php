@php
    $summary = $report['summary'];
@endphp

<div>
    @include('livewire.pages.panel.expert.reports.partials.styles')

    <section class="card report-hero mb-4">
        <div class="card-body p-4 p-lg-5 position-relative">
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
                <div>
                    <span class="report-eyebrow"><i class="bx bx-user-plus"></i> Reports / Customer Acquisition</span>
                    <h3 class="mt-3 mb-2 text-white">First-Time Customer Acquisition</h3>
                    <p class="report-subtitle">
                        Track only customers whose contract in the selected window is their first eligible contract in the system,
                        with the same filters, operational detail, and Excel export depth as the other reports.
                    </p>
                </div>
                <a href="{{ $exportUrl }}" class="btn btn-light">
                    <i class="bx bx-export me-1"></i> Export Excel
                </a>
            </div>
        </div>
    </section>

    @include('livewire.pages.panel.expert.reports.partials.nav')

    <div class="row g-3 mb-4">
        <div class="col-md-6 col-xl-2">
            <div class="report-kpi card card-body">
                <span class="metric-label">New Customers</span>
                <div class="metric-value mt-2">{{ number_format($summary['new_customers']) }}</div>
                <p class="metric-note mt-2">Customers with no older eligible contract.</p>
            </div>
        </div>
        <div class="col-md-6 col-xl-2">
            <div class="report-kpi card card-body">
                <span class="metric-label">First Contracts</span>
                <div class="metric-value mt-2">{{ number_format($summary['first_contracts']) }}</div>
                <p class="metric-note mt-2">Rows ready for review and export.</p>
            </div>
        </div>
        <div class="col-md-6 col-xl-2">
            <div class="report-kpi card card-body">
                <span class="metric-label">Contract Value</span>
                <div class="metric-value mt-2">{{ number_format($summary['gross_contract_value'], 0) }}</div>
                <p class="metric-note mt-2">AED on first-time contracts.</p>
            </div>
        </div>
        <div class="col-md-6 col-xl-2">
            <div class="report-kpi card card-body">
                <span class="metric-label">Recorded Payments</span>
                <div class="metric-value mt-2">{{ number_format($summary['recorded_payments'], 0) }}</div>
                <p class="metric-note mt-2">Net collected cashflow.</p>
            </div>
        </div>
        <div class="col-md-6 col-xl-2">
            <div class="report-kpi card card-body">
                <span class="metric-label">Outstanding</span>
                <div class="metric-value mt-2">{{ number_format($summary['outstanding_balance'], 0) }}</div>
                <p class="metric-note mt-2">Open AED still remaining.</p>
            </div>
        </div>
        <div class="col-md-6 col-xl-2">
            <div class="report-kpi card card-body">
                <span class="metric-label">Avg Rental Days</span>
                <div class="metric-value mt-2">{{ number_format($summary['average_rental_days'], 1) }}</div>
                <p class="metric-note mt-2">Average first-rental duration.</p>
            </div>
        </div>
    </div>

    <section class="card report-filter-card mb-4">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                <div>
                    <h5 class="mb-1">Filter first-time customers</h5>
                    <p class="text-muted mb-0">Only customers without any older eligible contract will stay in the result.</p>
                </div>
                <button type="button" class="btn btn-outline-secondary" wire:click="clearFilters">
                    <i class="bx bx-reset me-1"></i> Reset Filters
                </button>
            </div>

            <div class="row g-3">
                <div class="col-lg-3">
                    <div class="filter-field">
                        <div class="d-flex justify-content-between align-items-center">
                            <label class="filter-label" for="firstTimeCustomerSearch">Customer Search</label>
                            <span class="filter-hint">Name, phone, contract, passport</span>
                        </div>
                        <input id="firstTimeCustomerSearch" type="search" class="form-control" placeholder="Start typing..."
                            wire:model.live.debounce.350ms="search">
                    </div>
                </div>
                <div class="col-lg-2">
                    <div class="filter-field">
                        <label class="filter-label" for="firstTimeCustomerDateField">First Contract Basis</label>
                        <select id="firstTimeCustomerDateField" class="form-select" wire:model.live="dateField">
                            <option value="pickup_date">Pickup Date</option>
                            <option value="created_at">Request Date</option>
                            <option value="return_date">Return Date</option>
                        </select>
                    </div>
                </div>
                <div class="col-lg-2">
                    <div class="filter-field">
                        <label class="filter-label" for="firstTimeCustomerDateFrom">Date From</label>
                        <input id="firstTimeCustomerDateFrom" type="date" class="form-control" wire:model.live="dateFrom">
                    </div>
                </div>
                <div class="col-lg-2">
                    <div class="filter-field">
                        <label class="filter-label" for="firstTimeCustomerDateTo">Date To</label>
                        <input id="firstTimeCustomerDateTo" type="date" class="form-control" wire:model.live="dateTo">
                    </div>
                </div>
                <div class="col-lg-3">
                    <div class="filter-field">
                        <label class="filter-label" for="firstTimeCustomerStatus">Status</label>
                        <select id="firstTimeCustomerStatus" class="form-select" wire:model.live="status">
                            @foreach ($statusOptions as $option)
                                <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-lg-3">
                    <div class="filter-field">
                        <label class="filter-label" for="firstTimeCustomerAgent">Sales Agent</label>
                        <select id="firstTimeCustomerAgent" class="form-select" wire:model.live="agentId">
                            <option value="">All agents</option>
                            @foreach ($agents as $agent)
                                <option value="{{ $agent['id'] }}">{{ $agent['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-lg-3">
                    <div class="filter-field">
                        <label class="filter-label" for="firstTimeCustomerKardo">KARDO</label>
                        <select id="firstTimeCustomerKardo" class="form-select" wire:model.live="kardo">
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
                <h5 class="mb-1">New customer contracts</h5>
                <p class="text-muted mb-0">{{ number_format($summary['first_contracts']) }} first-time contracts match the current filters.</p>
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
                            <th>First Contract</th>
                            <th>Customer</th>
                            <th>Vehicle & Route</th>
                            <th>Financial Snapshot</th>
                            <th>Acquisition & Ops</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                        @foreach ($rows as $row)
                            <tr>
                                <td>
                                    <span class="cell-title">{{ $row['first_contract_basis'] }}</span>
                                    <span class="cell-subtitle">{{ $row['first_contract_date'] }}</span>
                                    <span class="cell-subtitle">Contract #{{ $row['contract_id'] }}</span>
                                    <span class="cell-subtitle">Request {{ $row['request_date'] }}</span>
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
                                    <span class="cell-subtitle">Pickup {{ $row['pickup_date'] }} | Return {{ $row['return_date'] }}</span>
                                </td>
                                <td>
                                    <span class="cell-metric">{{ number_format($row['total_price'], 2) }} AED total</span>
                                    <span class="cell-subtitle">Paid: {{ number_format($row['net_payments'], 2) }} AED</span>
                                    <span class="cell-subtitle">Outstanding: {{ number_format($row['remaining_balance'], 2) }} AED</span>
                                    <span class="cell-subtitle">Rate: {{ $row['rental_rate'] !== null ? number_format($row['rental_rate'], 2) . ' AED/day' : '—' }}</span>
                                </td>
                                <td>
                                    <span class="cell-title">{{ $row['submitted_by'] }}</span>
                                    <span class="cell-subtitle">Sales: {{ $row['sales_agent'] }}</span>
                                    <span class="cell-subtitle">Expert: {{ $row['assigned_expert'] }}</span>
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
                    <i class="bx bx-user-plus"></i>
                    <h5 class="mt-3 mb-2">No first-time customers found</h5>
                    <p class="mb-0">Adjust the date window or search terms to broaden the acquisition slice.</p>
                </div>
            </div>
        @endif
    </section>
</div>
