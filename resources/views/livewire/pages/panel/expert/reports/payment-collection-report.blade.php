@php
    $summary = $report['summary'];
@endphp

<div>
    @include('livewire.pages.panel.expert.reports.partials.styles')

    <section class="card report-hero mb-4">
        <div class="card-body p-4 p-lg-5 position-relative">
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
                <div>
                    <span class="report-eyebrow"><i class="bx bx-receipt"></i> Reports / Payments</span>
                    <h3 class="mt-3 mb-2 text-white">Payment Collection Control</h3>
                    <p class="report-subtitle">
                        Review inbound and outbound payment movements, approval posture, refundability, and collection mix
                        from the same dataset used for the downloadable Excel file.
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
                <span class="metric-label">Records</span>
                <div class="metric-value mt-2">{{ number_format($summary['payment_records']) }}</div>
                <p class="metric-note mt-2">Payment rows in the active scope.</p>
            </div>
        </div>
        <div class="col-md-6 col-xl-2">
            <div class="report-kpi card card-body">
                <span class="metric-label">Net Payments</span>
                <div class="metric-value mt-2">{{ number_format($summary['net_recorded_payments'], 0) }}</div>
                <p class="metric-note mt-2">Net AED after refunds.</p>
            </div>
        </div>
        <div class="col-md-6 col-xl-2">
            <div class="report-kpi card card-body">
                <span class="metric-label">Approved</span>
                <div class="metric-value mt-2">{{ number_format($summary['approved_amount'], 0) }}</div>
                <p class="metric-note mt-2">Approved AED total.</p>
            </div>
        </div>
        <div class="col-md-6 col-xl-2">
            <div class="report-kpi card card-body">
                <span class="metric-label">Pending Approval</span>
                <div class="metric-value mt-2">{{ number_format($summary['pending_approval_amount'], 0) }}</div>
                <p class="metric-note mt-2">Awaiting approval.</p>
            </div>
        </div>
        <div class="col-md-6 col-xl-2">
            <div class="report-kpi card card-body">
                <span class="metric-label">Unpaid</span>
                <div class="metric-value mt-2">{{ number_format($summary['unpaid_amount'], 0) }}</div>
                <p class="metric-note mt-2">Unpaid recorded AED.</p>
            </div>
        </div>
        <div class="col-md-6 col-xl-2">
            <div class="report-kpi card card-body">
                <span class="metric-label">Refundable</span>
                <div class="metric-value mt-2">{{ number_format($summary['refundable_amount'], 0) }}</div>
                <p class="metric-note mt-2">Refundable AED exposure.</p>
            </div>
        </div>
    </div>

    <section class="card report-filter-card mb-4">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                <div>
                    <h5 class="mb-1">Audit the payment stream</h5>
                    <p class="text-muted mb-0">Default view starts from the first day of the current month.</p>
                </div>
                <button type="button" class="btn btn-outline-secondary" wire:click="clearFilters">
                    <i class="bx bx-reset me-1"></i> Reset Filters
                </button>
            </div>

            <div class="row g-3">
                <div class="col-lg-4">
                    <div class="filter-field">
                        <div class="d-flex justify-content-between align-items-center">
                            <label class="filter-label" for="paymentCollectionSearch">Search</label>
                            <span class="filter-hint">Customer, contract, plate, note</span>
                        </div>
                        <input id="paymentCollectionSearch" type="search" class="form-control" placeholder="Start typing..."
                            wire:model.live.debounce.350ms="search">
                    </div>
                </div>
                <div class="col-lg-2">
                    <div class="filter-field">
                        <label class="filter-label" for="paymentCollectionDateField">Date Basis</label>
                        <select id="paymentCollectionDateField" class="form-select" wire:model.live="dateField">
                            <option value="payment_date">Payment Date</option>
                            <option value="created_at">Created At</option>
                        </select>
                    </div>
                </div>
                <div class="col-lg-2">
                    <div class="filter-field">
                        <label class="filter-label" for="paymentCollectionDateFrom">Date From</label>
                        <input id="paymentCollectionDateFrom" type="date" class="form-control" wire:model.live="dateFrom">
                    </div>
                </div>
                <div class="col-lg-2">
                    <div class="filter-field">
                        <label class="filter-label" for="paymentCollectionDateTo">Date To</label>
                        <input id="paymentCollectionDateTo" type="date" class="form-control" wire:model.live="dateTo">
                    </div>
                </div>
                <div class="col-lg-2">
                    <div class="filter-field">
                        <label class="filter-label" for="paymentCollectionType">Payment Type</label>
                        <select id="paymentCollectionType" class="form-select" wire:model.live="paymentType">
                            @foreach ($paymentTypes as $option)
                                <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-lg-3">
                    <div class="filter-field">
                        <label class="filter-label" for="paymentCollectionApproval">Approval</label>
                        <select id="paymentCollectionApproval" class="form-select" wire:model.live="approvalStatus">
                            @foreach ($approvalOptions as $option)
                                <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-lg-3">
                    <div class="filter-field">
                        <label class="filter-label" for="paymentCollectionState">Settlement</label>
                        <select id="paymentCollectionState" class="form-select" wire:model.live="paymentState">
                            @foreach ($paymentStates as $option)
                                <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-lg-3">
                    <div class="filter-field">
                        <label class="filter-label" for="paymentCollectionMethod">Method</label>
                        <select id="paymentCollectionMethod" class="form-select" wire:model.live="paymentMethod">
                            @foreach ($paymentMethods as $option)
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
                <h5 class="mb-1">Payment ledger view</h5>
                <p class="text-muted mb-0">{{ number_format($summary['payment_records']) }} payment rows available in the current audit scope.</p>
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
                            <th>Payment</th>
                            <th>Customer & Contract</th>
                            <th>Vehicle & Method</th>
                            <th>Amount</th>
                            <th>Approval</th>
                            <th>Flags</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                        @foreach ($rows as $row)
                            <tr>
                                <td>
                                    <span class="cell-title">#{{ $row['payment_id'] }}</span>
                                    <span class="cell-subtitle">{{ $row['payment_date'] }}</span>
                                    <span class="cell-subtitle">{{ $row['payment_type_label'] }}</span>
                                </td>
                                <td>
                                    <span class="cell-title">{{ $row['customer_name'] }}</span>
                                    <span class="cell-subtitle">{{ $row['customer_phone'] }}</span>
                                    <span class="cell-subtitle">Contract: {{ $row['contract_id'] }}</span>
                                </td>
                                <td>
                                    <span class="cell-title">{{ $row['car_name'] }}</span>
                                    <span class="cell-subtitle">Plate: {{ $row['plate_number'] }}</span>
                                    <span class="cell-subtitle">{{ $row['payment_method_label'] }} | {{ $row['currency'] }}</span>
                                    <span class="cell-subtitle">{{ $row['processed_by'] }}</span>
                                </td>
                                <td>
                                    <span class="cell-metric">{{ number_format($row['amount_in_aed'], 2) }} AED</span>
                                    <span class="cell-subtitle">Original: {{ number_format($row['amount'], 2) }} {{ $row['currency'] }}</span>
                                    <span class="cell-subtitle">{{ $row['description'] }}</span>
                                </td>
                                <td>
                                    @php
                                        $approvalBadge = match ($row['approval_status']) {
                                            'approved' => 'bg-label-success',
                                            'rejected' => 'bg-label-danger',
                                            default => 'bg-label-warning',
                                        };
                                    @endphp
                                    <span class="badge {{ $approvalBadge }}">{{ $row['approval_status_label'] }}</span>
                                    <span class="cell-subtitle mt-2">{{ $row['note'] }}</span>
                                </td>
                                <td>
                                    <span class="badge {{ $row['is_paid'] ? 'bg-label-success' : 'bg-label-danger' }}">{{ $row['is_paid_label'] }}</span>
                                    <span class="badge {{ $row['is_refundable'] ? 'bg-label-info' : 'bg-label-secondary' }} mt-2">{{ $row['is_refundable_label'] }}</span>
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
                    <i class="bx bx-receipt"></i>
                    <h5 class="mt-3 mb-2">No payment rows found</h5>
                    <p class="mb-0">Expand the payment type, approval status, or date window.</p>
                </div>
            </div>
        @endif
    </section>
</div>
