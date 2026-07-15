@php
    $summary = $report['summary'];
@endphp

<div>
    @include('livewire.pages.panel.expert.reports.partials.styles')

    <section class="card report-hero mb-4">
        <div class="card-body p-4 p-lg-5 position-relative">
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
                <div>
                    <span class="report-eyebrow"><i class="bx bx-share-alt"></i> Reports / Leads</span>
                    <h3 class="mt-3 mb-2 text-white">Lead Source Intelligence</h3>
                    <p class="report-subtitle">
                        Audit lead generation by communication channel and discovery source across any date range,
                        then export both lead-level detail and channel performance in one Excel workbook.
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
                <span class="metric-label">Leads</span>
                <div class="metric-value mt-2">{{ number_format($summary['matching_leads']) }}</div>
                <p class="metric-note mt-2">Lead rows in the current scope.</p>
            </div>
        </div>
        <div class="col-md-6 col-xl-2">
            <div class="report-kpi card card-body">
                <span class="metric-label">Converted</span>
                <div class="metric-value mt-2">{{ number_format($summary['converted_leads']) }}</div>
                <p class="metric-note mt-2">Leads already turned into customers.</p>
            </div>
        </div>
        <div class="col-md-6 col-xl-2">
            <div class="report-kpi card card-body">
                <span class="metric-label">Conversion Rate</span>
                <div class="metric-value mt-2">{{ number_format($summary['conversion_rate'], 1) }}%</div>
                <p class="metric-note mt-2">Converted leads over matched leads.</p>
            </div>
        </div>
        <div class="col-md-6 col-xl-2">
            <div class="report-kpi card card-body">
                <span class="metric-label">Due Follow-ups</span>
                <div class="metric-value mt-2">{{ number_format($summary['due_follow_ups']) }}</div>
                <p class="metric-note mt-2">Open leads with overdue follow-up.</p>
            </div>
        </div>
        <div class="col-md-6 col-xl-2">
            <div class="report-kpi card card-body">
                <span class="metric-label">Channels</span>
                <div class="metric-value mt-2">{{ number_format($summary['unique_channels']) }}</div>
                <p class="metric-note mt-2">Distinct channels represented.</p>
            </div>
        </div>
        <div class="col-md-6 col-xl-2">
            <div class="report-kpi card card-body">
                <span class="metric-label">Top Channel</span>
                <div class="metric-value mt-2" style="font-size:1.1rem">{{ $summary['top_channel'] }}</div>
                <p class="metric-note mt-2">{{ $summary['top_discovery_source'] }}</p>
            </div>
        </div>
    </div>

    <section class="card report-filter-card mb-4">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                <div>
                    <h5 class="mb-1">Filter the lead pipeline</h5>
                    <p class="text-muted mb-0">Search lead details, focus one communication channel, and narrow by date window.</p>
                </div>
                <button type="button" class="btn btn-outline-secondary" wire:click="clearFilters">
                    <i class="bx bx-reset me-1"></i> Reset Filters
                </button>
            </div>

            <div class="row g-3">
                <div class="col-lg-3">
                    <div class="filter-field">
                        <div class="d-flex justify-content-between align-items-center">
                            <label class="filter-label" for="leadSourceSearch">Search</label>
                            <span class="filter-hint">Lead, phone, discovery, model</span>
                        </div>
                        <input id="leadSourceSearch" type="search" class="form-control" placeholder="Start typing..."
                            wire:model.live.debounce.350ms="search">
                    </div>
                </div>
                <div class="col-lg-2">
                    <div class="filter-field">
                        <label class="filter-label" for="leadSourceDateField">Date Basis</label>
                        <select id="leadSourceDateField" class="form-select" wire:model.live="dateField">
                            <option value="request_date">Request Date</option>
                            <option value="created_at">Created At</option>
                            <option value="converted_at">Converted At</option>
                            <option value="next_follow_up_at">Next Follow-up</option>
                            <option value="last_contacted_at">Last Contact</option>
                            <option value="pickup_date">Pickup Date</option>
                            <option value="return_date">Return Date</option>
                        </select>
                    </div>
                </div>
                <div class="col-lg-2">
                    <div class="filter-field">
                        <label class="filter-label" for="leadSourceDateFrom">Date From</label>
                        <input id="leadSourceDateFrom" type="date" class="form-control" wire:model.live="dateFrom">
                    </div>
                </div>
                <div class="col-lg-2">
                    <div class="filter-field">
                        <label class="filter-label" for="leadSourceDateTo">Date To</label>
                        <input id="leadSourceDateTo" type="date" class="form-control" wire:model.live="dateTo">
                    </div>
                </div>
                <div class="col-lg-3">
                    <div class="filter-field">
                        <label class="filter-label" for="leadSourceChannel">Communication Channel</label>
                        <select id="leadSourceChannel" class="form-select" wire:model.live="source">
                            @foreach ($sourceOptions as $option)
                                <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-lg-3">
                    <div class="filter-field">
                        <label class="filter-label" for="leadSourceStatus">Lead Status</label>
                        <select id="leadSourceStatus" class="form-select" wire:model.live="status">
                            @foreach ($statusOptions as $option)
                                <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-lg-3">
                    <div class="filter-field">
                        <label class="filter-label" for="leadSourcePriority">Priority</label>
                        <select id="leadSourcePriority" class="form-select" wire:model.live="priority">
                            @foreach ($priorityOptions as $option)
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
                <h5 class="mb-1">Lead acquisition detail</h5>
                <p class="text-muted mb-0">{{ number_format($summary['matching_leads']) }} leads available for review and export.</p>
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
                            <th>Lead</th>
                            <th>Requested Vehicle</th>
                            <th>Channel & Discovery</th>
                            <th>Follow-up & Owner</th>
                            <th>Outcome</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                        @foreach ($rows as $row)
                            <tr>
                                <td>
                                    <span class="cell-title">#{{ $row['lead_id'] }} {{ $row['lead_name'] }}</span>
                                    <span class="cell-subtitle">{{ $row['phone'] }}</span>
                                    <span class="cell-subtitle">{{ $row['email'] }}</span>
                                    <span class="cell-subtitle">{{ $row['selected_date_basis'] }}: {{ $row['selected_date'] }}</span>
                                </td>
                                <td>
                                    <span class="cell-title">{{ $row['requested_vehicle'] }}</span>
                                    <span class="cell-subtitle">Request: {{ $row['request_date'] }}</span>
                                    <span class="cell-subtitle">Pickup: {{ $row['pickup_date'] }}</span>
                                    <span class="cell-subtitle">Return: {{ $row['return_date'] }}</span>
                                </td>
                                <td>
                                    <span class="cell-title">{{ $row['source_label'] }}</span>
                                    <span class="cell-subtitle">{{ $row['discovery_source'] }}</span>
                                    <span class="cell-subtitle">{{ $row['status_label'] }} | {{ $row['priority_label'] }}</span>
                                </td>
                                <td>
                                    <span class="cell-title">{{ $row['assigned_to'] }}</span>
                                    <span class="cell-subtitle">Created by: {{ $row['created_by'] }}</span>
                                    <span class="cell-subtitle">Next: {{ $row['next_follow_up_at'] }}</span>
                                    <span class="cell-subtitle">Last: {{ $row['last_contacted_at'] }}</span>
                                </td>
                                <td>
                                    <span class="badge {{ $row['is_converted'] ? 'bg-label-success' : 'bg-label-warning' }}">{{ $row['is_converted_label'] }}</span>
                                    <span class="cell-subtitle mt-2">Converted at: {{ $row['converted_at'] }}</span>
                                    <span class="cell-subtitle">Customer: {{ $row['customer_name'] }}</span>
                                    <span class="cell-subtitle">{{ $row['notes'] }}</span>
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
                    <i class="bx bx-share-alt"></i>
                    <h5 class="mt-3 mb-2">No leads found</h5>
                    <p class="mb-0">Broaden the date range or relax the source, status, or priority filters.</p>
                </div>
            </div>
        @endif
    </section>
</div>
