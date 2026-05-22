<div>
    @include('livewire.pages.panel.expert.reports.partials.styles')

    <section class="card report-hero mb-4">
        <div class="card-body p-4 p-lg-5">
            <div class="d-flex flex-column flex-lg-row gap-3 justify-content-between align-items-start">
                <div>
                    <span class="report-eyebrow"><i class="bx bx-shield-quarter"></i> Audit Center</span>
                    <h2 class="mt-3 mb-2 fw-bold">Kibana-Style Audit Observatory</h2>
                    <p class="report-subtitle">Complete traceability of requests, model changes, auth events, and operational reads.</p>
                </div>
                <a href="{{ $exportUrl }}" class="btn btn-light">
                    <i class="bx bx-export me-1"></i> Export CSV
                </a>
            </div>
        </div>
    </section>

    @include('livewire.pages.panel.expert.reports.partials.nav')

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="report-kpi p-3">
                <div class="metric-label">Total Events</div>
                <div class="metric-value">{{ number_format($summary['total']) }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="report-kpi p-3">
                <div class="metric-label">Failed Exports</div>
                <div class="metric-value">{{ number_format($summary['failed_exports']) }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="report-kpi p-3">
                <div class="metric-label">Unique Users</div>
                <div class="metric-value">{{ number_format($summary['unique_users']) }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="report-kpi p-3">
                <div class="metric-label">Request Chains</div>
                <div class="metric-value">{{ number_format($summary['unique_requests']) }}</div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-5">
            <section class="card report-results-card h-100">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Top Active Users</h5>
                </div>
                <div class="card-body">
                    @php
                        $maxUserEvents = max(1, collect($dashboard['top_users'])->max('total_events') ?? 1);
                    @endphp
                    @forelse ($dashboard['top_users'] as $row)
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <div class="fw-semibold">{{ $row['name'] }} <span class="text-muted small">#{{ $row['user_id'] }}</span></div>
                                <div class="text-muted small">{{ number_format($row['total_events']) }} events</div>
                            </div>
                            <div class="progress report-progress">
                                <div class="progress-bar bg-primary" role="progressbar" style="width: {{ round(($row['total_events'] / $maxUserEvents) * 100, 2) }}%"></div>
                            </div>
                            <div class="small text-muted mt-1">
                                mutations: {{ number_format($row['mutation_events']) }}
                                @if ($row['last_seen_at'])
                                    | last seen: {{ \Illuminate\Support\Carbon::parse($row['last_seen_at'])->format('Y-m-d H:i') }}
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-muted">No user activity found in this filter.</div>
                    @endforelse
                </div>
            </section>
        </div>
        <div class="col-lg-4">
            <section class="card report-results-card h-100">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Sensitive Entity Mutations</h5>
                </div>
                <div class="card-body">
                    @foreach ($dashboard['focus_entities'] as $entityName => $entityRow)
                        <div class="border rounded-3 p-2 mb-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <strong>{{ $entityName }}</strong>
                                <span class="badge bg-dark-subtle text-dark">{{ number_format($entityRow['total']) }} total</span>
                            </div>
                            <div class="small text-muted mt-1">
                                +{{ number_format($entityRow['created']) }} create
                                | {{ number_format($entityRow['updated']) }} update
                                | -{{ number_format($entityRow['deleted']) }} delete
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>
        <div class="col-lg-3">
            <section class="card report-results-card h-100">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Risk Signals</h5>
                </div>
                <div class="card-body">
                    <div class="risk-item">
                        <span>Failed Logins</span>
                        <strong>{{ number_format($dashboard['risk']['failed_logins']) }}</strong>
                    </div>
                    <div class="risk-item">
                        <span>Delete Actions</span>
                        <strong>{{ number_format($dashboard['risk']['delete_actions']) }}</strong>
                    </div>
                    <div class="risk-item">
                        <span>HTTP Errors (4xx/5xx)</span>
                        <strong>{{ number_format($dashboard['risk']['http_errors']) }}</strong>
                    </div>
                    <div class="risk-item border-0 pb-0 mb-0">
                        <span>Failed Exports</span>
                        <strong class="{{ $dashboard['risk']['failed_exports'] > 0 ? 'text-danger' : 'text-success' }}">{{ number_format($dashboard['risk']['failed_exports']) }}</strong>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-7">
            <section class="card report-results-card h-100">
                <div class="card-header bg-light">
                    <h5 class="mb-0">24h Activity Timeline</h5>
                </div>
                <div class="card-body">
                    @php
                        $maxHourEvents = max(1, collect($dashboard['hourly_timeline'])->max('total_events') ?? 1);
                    @endphp
                    <div class="timeline-grid">
                        @foreach ($dashboard['hourly_timeline'] as $hourRow)
                            <div class="timeline-row">
                                <div class="timeline-label">{{ $hourRow['label'] }}</div>
                                <div class="timeline-bar-wrap">
                                    <div class="timeline-bar" style="width: {{ round(($hourRow['total_events'] / $maxHourEvents) * 100, 2) }}%"></div>
                                </div>
                                <div class="timeline-value">{{ number_format($hourRow['total_events']) }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        </div>
        <div class="col-lg-5">
            <section class="card report-results-card h-100">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Top Actions In Current Filter</h5>
                </div>
                <div class="card-body">
                    @php
                        $maxActionEvents = max(1, collect($dashboard['top_actions'])->max('total') ?? 1);
                    @endphp
                    @forelse ($dashboard['top_actions'] as $actionRow)
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="badge bg-dark-subtle text-dark">{{ $actionRow['action'] }}</span>
                                <span class="small text-muted">{{ number_format($actionRow['total']) }}</span>
                            </div>
                            <div class="progress report-progress">
                                <div class="progress-bar bg-info" role="progressbar" style="width: {{ round(($actionRow['total'] / $maxActionEvents) * 100, 2) }}%"></div>
                            </div>
                        </div>
                    @empty
                        <div class="text-muted">No action data available.</div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>

    <section class="card report-filter-card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3"><input type="text" class="form-control" placeholder="Search" wire:model.live.debounce.300ms="search"></div>
                <div class="col-md-2"><input type="date" class="form-control" wire:model.live="dateFrom"></div>
                <div class="col-md-2"><input type="date" class="form-control" wire:model.live="dateTo"></div>
                <div class="col-md-2">
                    <select class="form-select" wire:model.live="actorUserId">
                        <option value="">All Users</option>
                        @foreach ($actorOptions as $actorOption)
                            <option value="{{ $actorOption['id'] }}">{{ $actorOption['label'] }} (#{{ $actorOption['id'] }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3"><input type="text" class="form-control" placeholder="Route" wire:model.live.debounce.300ms="routeName"></div>

                <div class="col-md-2">
                    <select class="form-select" wire:model.live="actionGroup">
                        <option value="">All Groups</option>
                        <option value="mutations">Mutations (create/update/delete)</option>
                        <option value="auth">Auth Events</option>
                        <option value="reads">Business Reads</option>
                        <option value="requests">Request Lifecycle</option>
                        <option value="errors">Error / Risk Focus</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" wire:model.live="action">
                        <option value="">All Actions</option>
                        @foreach ($actionOptions as $actionOption)
                            <option value="{{ $actionOption }}">{{ $actionOption }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2"><input type="text" class="form-control" placeholder="Entity Type" wire:model.live.debounce.300ms="entityType"></div>
                <div class="col-md-1"><input type="text" class="form-control" placeholder="Status" wire:model.live="statusCode"></div>
                <div class="col-md-3"><input type="text" class="form-control" placeholder="Request UUID" wire:model.live.debounce.300ms="requestId"></div>
                <div class="col-md-2 d-grid"><button class="btn btn-outline-secondary" wire:click="clearFilters">Reset</button></div>
                <div class="col-md-2"><input type="text" class="form-control" placeholder="Contract ID" wire:model.live="contractId"></div>
                <div class="col-md-2"><input type="text" class="form-control" placeholder="Customer ID" wire:model.live="customerId"></div>
                <div class="col-md-2"><input type="text" class="form-control" placeholder="Payment ID" wire:model.live="paymentId"></div>
                <div class="col-md-2">
                    <select class="form-select" wire:model.live="perPage">
                        <option value="25">25 rows</option>
                        <option value="50">50 rows</option>
                        <option value="100">100 rows</option>
                    </select>
                </div>
            </div>
        </div>
    </section>

    <section class="card report-results-card mb-4">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0 report-table">
                <thead class="table-light">
                    <tr>
                        <th>Time</th>
                        <th>Action</th>
                        <th>User</th>
                        <th>Route</th>
                        <th>Status</th>
                        <th>Entity</th>
                        <th>Request</th>
                        <th>Export</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($events as $event)
                        <tr>
                            <td>{{ optional($event->occurred_at)->format('Y-m-d H:i:s') }}</td>
                            <td><span class="badge bg-dark-subtle text-dark">{{ $event->action }}</span></td>
                            <td>{{ $event->actor?->fullName() ?? ('#' . ($event->actor_user_id ?? '-')) }}</td>
                            <td>
                                <div class="cell-title">{{ $event->route_name ?? '-' }}</div>
                                <span class="cell-subtitle">{{ $event->method ?? '-' }}</span>
                            </td>
                            <td>{{ $event->status_code ?? '-' }}</td>
                            <td>
                                <div class="cell-title">{{ $event->entity_type ?? '-' }}</div>
                                <span class="cell-subtitle">{{ $event->entity_id ?? '-' }}</span>
                            </td>
                            <td><code>{{ $event->request_id ?? '-' }}</code></td>
                            <td>
                                @if ($event->export_status === 'exported')
                                    <span class="badge bg-success">exported</span>
                                @elseif ($event->export_status === 'failed')
                                    <span class="badge bg-danger">failed</span>
                                @else
                                    <span class="badge bg-warning text-dark">pending</span>
                                @endif
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" wire:click="selectEvent({{ $event->id }})">Details</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">No events found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $events->links() }}
        </div>
    </section>

    @if ($selectedEvent)
        <section class="card report-results-card mb-4">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Event Detail Drawer</h5>
                <span class="badge bg-primary">{{ $selectedEvent->action }}</span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <h6>Context</h6>
                        <pre class="small bg-body-tertiary p-3 rounded mb-0">{{ json_encode([
                            'event_uuid' => $selectedEvent->event_uuid,
                            'request_id' => $selectedEvent->request_id,
                            'route' => $selectedEvent->route_name,
                            'url' => $selectedEvent->url,
                            'status' => $selectedEvent->status_code,
                            'ip' => $selectedEvent->ip,
                            'user_agent' => $selectedEvent->user_agent,
                            'actor' => $selectedEvent->actor?->fullName(),
                        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                    </div>
                    <div class="col-md-6">
                        <h6>Diff</h6>
                        <pre class="small bg-body-tertiary p-3 rounded mb-0">{{ json_encode([
                            'before' => $selectedEvent->before,
                            'after' => $selectedEvent->after,
                            'changed_fields' => $selectedEvent->changed_fields,
                            'meta' => $selectedEvent->meta,
                        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                    </div>
                </div>
            </div>
        </section>

        <section class="card report-results-card">
            <div class="card-header bg-light">
                <h5 class="mb-0">Correlation Timeline (Request Chain)</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0 report-table">
                    <thead class="table-light">
                        <tr>
                            <th>Time</th>
                            <th>Action</th>
                            <th>User</th>
                            <th>Entity</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($correlationEvents as $correlation)
                            <tr>
                                <td>{{ optional($correlation->occurred_at)->format('Y-m-d H:i:s') }}</td>
                                <td>{{ $correlation->action }}</td>
                                <td>{{ $correlation->actor?->fullName() ?? ('#' . ($correlation->actor_user_id ?? '-')) }}</td>
                                <td>{{ $correlation->entity_type ?? '-' }} / {{ $correlation->entity_id ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-3 text-muted">No correlated request chain.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    @php
        $kibanaBase = config('audit.elasticsearch.dashboard_url');
        $kibanaLink = $kibanaBase ? rtrim($kibanaBase, '/') . '/app/discover' : null;
    @endphp

    @if ($kibanaLink)
        <div class="mt-3 text-end">
            <a href="{{ $kibanaLink }}" target="_blank" rel="noopener" class="btn btn-dark">
                <i class="bx bx-link-external me-1"></i> Open Native Kibana
            </a>
        </div>
    @endif
</div>
