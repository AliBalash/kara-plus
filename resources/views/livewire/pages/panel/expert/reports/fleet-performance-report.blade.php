@php
    $summary = $report['summary'];
@endphp

<div>
    @include('livewire.pages.panel.expert.reports.partials.styles')

    <section class="card report-hero mb-4">
        <div class="card-body p-4 p-lg-5 position-relative">
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
                <div>
                    <span class="report-eyebrow"><i class="bx bx-car"></i> Reports / Fleet</span>
                    <h3 class="mt-3 mb-2 text-white">Fleet Performance Window</h3>
                    <p class="report-subtitle">
                        Measure car-by-car productivity across a rolling time window, compare fleet ownership groups,
                        and expose utilization, revenue density, and live readiness in a single operational view.
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
                <span class="metric-label">Cars In Scope</span>
                <div class="metric-value mt-2">{{ number_format($summary['cars_in_report']) }}</div>
                <p class="metric-note mt-2">Vehicles with matched activity.</p>
            </div>
        </div>
        <div class="col-md-6 col-xl-2">
            <div class="report-kpi card card-body">
                <span class="metric-label">Contracts</span>
                <div class="metric-value mt-2">{{ number_format($summary['contract_count']) }}</div>
                <p class="metric-note mt-2">Rental records in the window.</p>
            </div>
        </div>
        <div class="col-md-6 col-xl-2">
            <div class="report-kpi card card-body">
                <span class="metric-label">Customers</span>
                <div class="metric-value mt-2">{{ number_format($summary['unique_customers']) }}</div>
                <p class="metric-note mt-2">Unique drivers served.</p>
            </div>
        </div>
        <div class="col-md-6 col-xl-2">
            <div class="report-kpi card card-body">
                <span class="metric-label">Revenue</span>
                <div class="metric-value mt-2">{{ number_format($summary['revenue'], 0) }}</div>
                <p class="metric-note mt-2">AED contract value.</p>
            </div>
        </div>
        <div class="col-md-6 col-xl-2">
            <div class="report-kpi card card-body">
                <span class="metric-label">Booked Days</span>
                <div class="metric-value mt-2">{{ number_format($summary['booked_days'], 1) }}</div>
                <p class="metric-note mt-2">Total occupied rental days.</p>
            </div>
        </div>
        <div class="col-md-6 col-xl-2">
            <div class="report-kpi card card-body">
                <span class="metric-label">Avg Utilization</span>
                <div class="metric-value mt-2">{{ number_format($summary['average_utilization'], 1) }}%</div>
                <p class="metric-note mt-2">Utilization across included cars.</p>
            </div>
        </div>
    </div>

    <section class="card report-filter-card mb-4">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                <div>
                    <h5 class="mb-1">Set the fleet window</h5>
                    <p class="text-muted mb-0">Default view is the last 90 days. Narrow by vehicle or ownership group.</p>
                </div>
                <button type="button" class="btn btn-outline-secondary" wire:click="clearFilters">
                    <i class="bx bx-reset me-1"></i> Reset Filters
                </button>
            </div>

            <div class="row g-3">
                <div class="col-lg-4">
                    <div class="filter-field">
                        <div class="d-flex justify-content-between align-items-center">
                            <label class="filter-label" for="fleetSearch">Vehicle Search</label>
                            <span class="filter-hint">Brand, model or plate</span>
                        </div>
                        <input id="fleetSearch" type="search" class="form-control" placeholder="Start typing..."
                            wire:model.live.debounce.350ms="search">
                    </div>
                </div>
                <div class="col-lg-2">
                    <div class="filter-field">
                        <label class="filter-label" for="fleetDateFrom">Date From</label>
                        <input id="fleetDateFrom" type="date" class="form-control" wire:model.live="dateFrom">
                    </div>
                </div>
                <div class="col-lg-2">
                    <div class="filter-field">
                        <label class="filter-label" for="fleetDateTo">Date To</label>
                        <input id="fleetDateTo" type="date" class="form-control" wire:model.live="dateTo">
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="filter-field">
                        <label class="filter-label" for="fleetOwnership">Fleet Scope</label>
                        <select id="fleetOwnership" class="form-select" wire:model.live="ownership">
                            @foreach ($ownershipOptions as $option)
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
                <h5 class="mb-1">Vehicle performance table</h5>
                <p class="text-muted mb-0">{{ number_format($summary['cars_in_report']) }} vehicles compared inside the active fleet window.</p>
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
                            <th>Vehicle</th>
                            <th>Fleet & Availability</th>
                            <th>Performance</th>
                            <th>Revenue Density</th>
                            <th>Latest Activity</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                        @foreach ($rows as $row)
                            <tr>
                                <td>
                                    <span class="cell-title">{{ $row['car_name'] }}</span>
                                    <span class="cell-subtitle">Plate: {{ $row['plate_number'] }}</span>
                                    <span class="cell-subtitle">Current customer: {{ $row['current_customer'] }}</span>
                                </td>
                                <td>
                                    <span class="cell-title">{{ $row['ownership'] }}</span>
                                    <span class="cell-subtitle">{{ $row['availability'] }}</span>
                                </td>
                                <td>
                                    <span class="cell-metric">{{ number_format($row['contracts_count']) }} contracts</span>
                                    <span class="cell-subtitle">Customers: {{ number_format($row['unique_customers']) }}</span>
                                    <span class="cell-subtitle">Booked days: {{ number_format($row['booked_days'], 1) }}</span>
                                    <span class="cell-subtitle">Utilization: {{ number_format($row['utilization_pct'], 1) }}%</span>
                                </td>
                                <td>
                                    <span class="cell-metric">{{ number_format($row['revenue'], 2) }} AED</span>
                                    <span class="cell-subtitle">Payments: {{ number_format($row['recorded_payments'], 2) }} AED</span>
                                    <span class="cell-subtitle">Avg contract: {{ number_format($row['average_contract_value'], 2) }} AED</span>
                                    <span class="cell-subtitle">Avg day: {{ number_format($row['average_daily_revenue'], 2) }} AED</span>
                                </td>
                                <td>
                                    <span class="cell-title">Pickup {{ $row['last_pickup_date'] }}</span>
                                    <span class="cell-subtitle">Return {{ $row['last_return_date'] }}</span>
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
                    <i class="bx bx-car"></i>
                    <h5 class="mt-3 mb-2">No fleet activity found</h5>
                    <p class="mb-0">Widen the date window or switch the fleet scope.</p>
                </div>
            </div>
        @endif
    </section>
</div>
