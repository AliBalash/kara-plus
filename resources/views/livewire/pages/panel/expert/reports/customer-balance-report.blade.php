@php
    $summary = $report['summary'];
@endphp

<div>
    @include('livewire.pages.panel.expert.reports.partials.styles')

    <section class="card report-hero mb-4">
        <div class="card-body p-4 p-lg-5 position-relative">
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
                <div>
                    <span class="report-eyebrow"><i class="bx bx-wallet-alt"></i> Reports / Customer Finance</span>
                    <h3 class="mt-3 mb-2 text-white">Customer Balance Monitor</h3>
                    <p class="report-subtitle">
                        Consolidate customer-level exposure across multiple contracts, surface overdue balances,
                        detect credit positions, and export a finance-friendly Excel workbook with one click.
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
                <span class="metric-label">Customers</span>
                <div class="metric-value mt-2">{{ number_format($summary['matching_customers']) }}</div>
                <p class="metric-note mt-2">Customers in the current financial scope.</p>
            </div>
        </div>
        <div class="col-md-6 col-xl-2">
            <div class="report-kpi card card-body">
                <span class="metric-label">Open Balance</span>
                <div class="metric-value mt-2">{{ number_format($summary['customers_with_open_balance']) }}</div>
                <p class="metric-note mt-2">Customers still carrying debt.</p>
            </div>
        </div>
        <div class="col-md-6 col-xl-2">
            <div class="report-kpi card card-body">
                <span class="metric-label">Overdue</span>
                <div class="metric-value mt-2">{{ number_format($summary['overdue_customers']) }}</div>
                <p class="metric-note mt-2">Customers with overdue exposure.</p>
            </div>
        </div>
        <div class="col-md-6 col-xl-2">
            <div class="report-kpi card card-body">
                <span class="metric-label">Outstanding</span>
                <div class="metric-value mt-2">{{ number_format($summary['total_outstanding'], 0) }}</div>
                <p class="metric-note mt-2">Open AED still collectible.</p>
            </div>
        </div>
        <div class="col-md-6 col-xl-2">
            <div class="report-kpi card card-body">
                <span class="metric-label">Customer Credit</span>
                <div class="metric-value mt-2">{{ number_format($summary['customer_credit'], 0) }}</div>
                <p class="metric-note mt-2">Overpayments and negative balances.</p>
            </div>
        </div>
        <div class="col-md-6 col-xl-2">
            <div class="report-kpi card card-body">
                <span class="metric-label">Contract Value</span>
                <div class="metric-value mt-2">{{ number_format($summary['gross_contract_value'], 0) }}</div>
                <p class="metric-note mt-2">Gross AED in the filtered portfolio.</p>
            </div>
        </div>
    </div>

    <section class="card report-filter-card mb-4">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                <div>
                    <h5 class="mb-1">Refine the balance portfolio</h5>
                    <p class="text-muted mb-0">Search customers and narrow the balance state and date logic.</p>
                </div>
                <button type="button" class="btn btn-outline-secondary" wire:click="clearFilters">
                    <i class="bx bx-reset me-1"></i> Reset Filters
                </button>
            </div>

            <div class="row g-3">
                <div class="col-lg-4">
                    <div class="filter-field">
                        <div class="d-flex justify-content-between align-items-center">
                            <label class="filter-label" for="customerBalanceSearch">Customer Search</label>
                            <span class="filter-hint">Name, phone, passport, license</span>
                        </div>
                        <input id="customerBalanceSearch" type="search" class="form-control" placeholder="Start typing..."
                            wire:model.live.debounce.350ms="search">
                    </div>
                </div>
                <div class="col-lg-2">
                    <div class="filter-field">
                        <label class="filter-label" for="customerBalanceDateField">Date Basis</label>
                        <select id="customerBalanceDateField" class="form-select" wire:model.live="dateField">
                            <option value="pickup_date">Pickup Date</option>
                            <option value="created_at">Request Date</option>
                            <option value="return_date">Return Date</option>
                        </select>
                    </div>
                </div>
                <div class="col-lg-2">
                    <div class="filter-field">
                        <label class="filter-label" for="customerBalanceDateFrom">Date From</label>
                        <input id="customerBalanceDateFrom" type="date" class="form-control" wire:model.live="dateFrom">
                    </div>
                </div>
                <div class="col-lg-2">
                    <div class="filter-field">
                        <label class="filter-label" for="customerBalanceDateTo">Date To</label>
                        <input id="customerBalanceDateTo" type="date" class="form-control" wire:model.live="dateTo">
                    </div>
                </div>
                <div class="col-lg-2">
                    <div class="filter-field">
                        <label class="filter-label" for="customerBalanceStatus">Balance State</label>
                        <select id="customerBalanceStatus" class="form-select" wire:model.live="balanceStatus">
                            @foreach ($balanceOptions as $option)
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
                <h5 class="mb-1">Customer exposure table</h5>
                <p class="text-muted mb-0">{{ number_format($summary['matching_customers']) }} customer records aggregated from the current filter scope.</p>
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
                            <th>Customer</th>
                            <th>Portfolio</th>
                            <th>Exposure</th>
                            <th>Signals</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                        @foreach ($rows as $row)
                            <tr>
                                <td>
                                    <span class="cell-title">{{ $row['customer_name'] }}</span>
                                    <span class="cell-subtitle">{{ $row['phone'] }}</span>
                                    <span class="cell-subtitle">{{ $row['nationality'] }}</span>
                                    <span class="cell-subtitle">Top car: {{ $row['top_car'] }}</span>
                                </td>
                                <td>
                                    <span class="cell-metric">{{ number_format($row['contracts_count']) }} contracts</span>
                                    <span class="cell-subtitle">Active: {{ number_format($row['active_contracts']) }}</span>
                                    <span class="cell-subtitle">Latest: {{ $row['latest_contract_date'] }}</span>
                                    <span class="cell-subtitle">Status: {{ $row['latest_contract_status'] }}</span>
                                </td>
                                <td>
                                    <span class="cell-metric">{{ number_format($row['outstanding_balance'], 2) }} AED outstanding</span>
                                    <span class="cell-subtitle">Overdue: {{ number_format($row['overdue_balance'], 2) }} AED</span>
                                    <span class="cell-subtitle">Credit: {{ number_format($row['credit_balance'], 2) }} AED</span>
                                    <span class="cell-subtitle">Payments: {{ number_format($row['recorded_payments'], 2) }} AED</span>
                                </td>
                                <td>
                                    <span class="cell-title">{{ number_format($row['gross_contract_value'], 2) }} AED gross</span>
                                    <span class="cell-subtitle">Deposits: {{ number_format($row['deposits_paid'], 2) }} AED</span>
                                    <span class="cell-subtitle">Extras: {{ number_format($row['extras_paid'], 2) }} AED</span>
                                    <span class="cell-subtitle">Open contracts: {{ $row['open_contract_ids'] }}</span>
                                </td>
                                <td>
                                    @php
                                        $badgeClass = match ($row['status']) {
                                            'overdue' => 'bg-label-danger',
                                            'open' => 'bg-label-warning',
                                            'credit' => 'bg-label-info',
                                            default => 'bg-label-success',
                                        };
                                    @endphp
                                    <span class="badge {{ $badgeClass }}">{{ $row['status_label'] }}</span>
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
                    <i class="bx bx-wallet"></i>
                    <h5 class="mt-3 mb-2">No customer balances found</h5>
                    <p class="mb-0">Broaden the search or change the balance status and date basis.</p>
                </div>
            </div>
        @endif
    </section>
</div>
