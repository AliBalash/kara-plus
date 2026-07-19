<div class="container-xl py-4">
    <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1"><span class="text-muted fw-light">Cars /</span> Unavailable Overview</h4>
            <p class="text-muted mb-0">A read-only view of unavailable records. Create, edit, or release unavailable status only from the car edit page.</p>
        </div>
        <div class="d-flex flex-column flex-sm-row gap-2 align-items-sm-center">
            <div class="input-group">
                <span class="input-group-text bg-white"><i class="bx bx-search"></i></span>
                <input type="search" class="form-control border-start-0" placeholder="Search car, reason, note"
                    wire:model.live.debounce.400ms="searchInput" autocomplete="off" enterkeyhint="search">
            </div>
            <button class="btn btn-outline-secondary" type="button" wire:click="resetFilters">
                <i class="bx bx-reset me-1"></i> Reset
            </button>
        </div>
    </div>

    @if (! $databaseReady)
        <div class="alert alert-warning border-0 shadow-sm mb-4">
            <div class="fw-semibold mb-1">Database update required</div>
            <div class="small mb-0">The `car_unavailability_periods` table does not exist yet. Run the SQL or migration first, then this desk becomes active.</div>
        </div>
    @endif

    <div class="alert alert-info border-0 shadow-sm mb-4">
        <div class="fw-semibold mb-1">How to read this page</div>
        <div class="small mb-0">
            <strong>Dated records</strong> have a reason plus start/end date and are the current system.
            <strong>Need Action</strong> means the car has an overdue open contract or an expired unavailable window and must be reviewed before release.
            <strong>Needs review</strong> means old unavailable cars without dates; their reason may be only a legacy default and should be corrected from Edit Car.
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-0 d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3">
            <div>
                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                    <h5 class="card-title mb-0">Need Action Queue</h5>
                    <span class="badge bg-danger-subtle text-danger">{{ number_format($needActionCars->total()) }} cars</span>
                </div>
                <p class="text-muted small mb-0">Overdue contracts and expired unavailable windows. Confirm the next status from Edit Car or resolve the contract.</p>
            </div>
            <div class="d-flex flex-column flex-sm-row gap-2 align-items-sm-center">
                <select class="form-select form-select-sm" wire:model.live="needActionFutureFilter">
                    <option value="all">All Need Action</option>
                    <option value="with_upcoming">Has upcoming booking</option>
                    <option value="without_upcoming">No upcoming booking</option>
                </select>
                <a href="{{ route('expert.dashboard', ['availableReadiness' => 'need_action']) }}" class="btn btn-sm btn-outline-dark text-nowrap">
                    View on dashboard
                </a>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Car</th>
                        <th>Current Contract</th>
                        <th>Customer</th>
                        <th>Upcoming</th>
                        <th>System Note</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($needActionCars as $needActionCar)
                        @php
                            $currentContract = $needActionCar->currentContract;
                            $upcomingReservation = $needActionCar->upcomingReservation;
                        @endphp
                        <tr class="table-danger-subtle">
                            <td>
                                <div class="fw-semibold">{{ $needActionCar->fullName() }}</div>
                                <div class="text-muted small">
                                    {{ optional($needActionCar->carModel)->brand }} {{ optional($needActionCar->carModel)->model }}
                                    @if ($needActionCar->plate_number)
                                        · {{ $needActionCar->plate_number }}
                                    @endif
                                </div>
                            </td>
                            <td>
                                @if ($currentContract)
                                    <div class="fw-semibold">#{{ $currentContract->id }}</div>
                                    <div class="text-muted small">
                                        Return {{ $currentContract->return_date ? \Carbon\Carbon::parse($currentContract->return_date)->format('Y-m-d H:i') : '—' }}
                                    </div>
                                    @if ($currentContract->return_date)
                                        <div class="small text-danger">{{ \Carbon\Carbon::parse($currentContract->return_date)->diffForHumans() }}</div>
                                    @endif
                                @else
                                    <span class="text-muted small">No active contract relation loaded</span>
                                @endif
                            </td>
                            <td>
                                <div class="fw-semibold small">{{ $currentContract?->customer?->fullName() ?? '—' }}</div>
                                @if ($currentContract?->customer?->phone)
                                    <div class="text-muted small">{{ $currentContract->customer->phone }}</div>
                                @endif
                            </td>
                            <td>
                                @if ($upcomingReservation)
                                    <span class="badge bg-info-subtle text-info">Upcoming exists</span>
                                    <div class="text-muted small mt-1">
                                        {{ $upcomingReservation->pickup_date ? \Carbon\Carbon::parse($upcomingReservation->pickup_date)->format('Y-m-d H:i') : '—' }}
                                    </div>
                                    @if ($upcomingReservation->customer)
                                        <div class="text-muted small">{{ $upcomingReservation->customer->fullName() }}</div>
                                    @endif
                                @else
                                    <span class="badge bg-light text-dark">No upcoming booking</span>
                                @endif
                            </td>
                            <td style="min-width: 240px;">
                                <div class="fw-semibold text-danger small">Need Action Required</div>
                                <div class="text-muted small">{{ $needActionCar->needActionAlertMessage() }}</div>
                                @if ($needActionCar->operationalStatusContextNote())
                                    <div class="small text-warning mt-1">{{ $needActionCar->operationalStatusContextNote() }}</div>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    @if ($currentContract)
                                        <a href="{{ route('rental-requests.details', $currentContract->id) }}" class="btn btn-sm btn-outline-dark">
                                            Contract
                                        </a>
                                    @endif
                                    <a href="{{ route('car.edit', $needActionCar->id) }}" class="btn btn-sm btn-danger">
                                        Edit Car
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                No Need Action cars match the current filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white border-0">
            {{ $needActionCars->links(data: ['scrollTo' => false]) }}
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6 col-xl-3">
            <button type="button" class="card border-0 shadow-sm h-100 text-start w-100 {{ $stateFilter === 'active' ? 'border border-danger-subtle' : '' }}"
                wire:click="setStateFilter('active')">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <span class="badge bg-danger-subtle text-danger">Now unavailable</span>
                        <i class="bx bx-block fs-4 text-danger"></i>
                    </div>
                    <div class="display-6 fw-bold text-dark mb-1">{{ number_format($summary['active'] ?? 0) }}</div>
                    <div class="small fw-semibold">Active Dated Records</div>
                    <div class="small text-muted mt-1">Unavailable now with dates</div>
                </div>
            </button>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
            <button type="button" class="card border-0 shadow-sm h-100 text-start w-100 {{ $stateFilter === 'upcoming' ? 'border border-info-subtle' : '' }}"
                wire:click="setStateFilter('upcoming')">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <span class="badge bg-info-subtle text-info">Planned</span>
                        <i class="bx bx-calendar-event fs-4 text-info"></i>
                    </div>
                    <div class="display-6 fw-bold text-dark mb-1">{{ number_format($summary['upcoming'] ?? 0) }}</div>
                    <div class="small fw-semibold">Upcoming Dated Records</div>
                    <div class="small text-muted mt-1">Will become unavailable later</div>
                </div>
            </button>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
            <button type="button" class="card border-0 shadow-sm h-100 text-start w-100 {{ $stateFilter === 'completed' ? 'border border-secondary-subtle' : '' }}"
                wire:click="setStateFilter('completed')">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <span class="badge bg-secondary-subtle text-secondary">History</span>
                        <i class="bx bx-history fs-4 text-secondary"></i>
                    </div>
                    <div class="display-6 fw-bold text-dark mb-1">{{ number_format($summary['completed'] ?? 0) }}</div>
                    <div class="small fw-semibold">Completed Records</div>
                    <div class="small text-muted mt-1">History with dates</div>
                </div>
            </button>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
            <button type="button" class="card border-0 shadow-sm h-100 text-start w-100 {{ $stateFilter === 'cancelled' ? 'border border-warning-subtle' : '' }}"
                wire:click="setStateFilter('cancelled')">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <span class="badge bg-warning-subtle text-warning">History</span>
                        <i class="bx bx-x-circle fs-4 text-warning"></i>
                    </div>
                    <div class="display-6 fw-bold text-dark mb-1">{{ number_format($summary['cancelled'] ?? 0) }}</div>
                    <div class="small fw-semibold">Cancelled Records</div>
                    <div class="small text-muted mt-1">Kept for audit, not blocking cars</div>
                </div>
            </button>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <span class="badge bg-warning-subtle text-warning">Attention</span>
                        <i class="bx bx-timer fs-4 text-warning"></i>
                    </div>
                    <div class="display-6 fw-bold text-dark mb-1">{{ number_format($summary['ending_soon'] ?? 0) }}</div>
                    <div class="small fw-semibold">Ending Soon</div>
                    <div class="small text-muted mt-1">Review before release or extension</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
            <button type="button" class="card border-0 shadow-sm h-100 text-start w-100 {{ $stateFilter === 'active' && ($summary['manual'] ?? 0) > 0 ? 'border border-dark-subtle' : '' }}"
                wire:click="setStateFilter('active')">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <span class="badge bg-dark-subtle text-dark">Open-ended</span>
                        <i class="bx bx-pin fs-4 text-dark"></i>
                    </div>
                    <div class="display-6 fw-bold text-dark mb-1">{{ number_format($summary['manual'] ?? 0) }}</div>
                    <div class="small fw-semibold">Needs Review</div>
                    <div class="small text-muted mt-1">Old records without dates</div>
                </div>
            </button>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-3">
                <div>
                    <h5 class="card-title mb-1">Filters</h5>
                    <p class="text-muted small mb-0">Filter dated unavailable records by status, reason, car, and date range.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-sm {{ $stateFilter === 'all' ? 'btn-dark' : 'btn-outline-dark' }}" wire:click="setStateFilter('all')">All</button>
                    <button type="button" class="btn btn-sm {{ $stateFilter === 'active' ? 'btn-danger' : 'btn-outline-danger' }}" wire:click="setStateFilter('active')">Active</button>
                    <button type="button" class="btn btn-sm {{ $stateFilter === 'upcoming' ? 'btn-info text-white' : 'btn-outline-info' }}" wire:click="setStateFilter('upcoming')">Upcoming</button>
                    <button type="button" class="btn btn-sm {{ $stateFilter === 'completed' ? 'btn-secondary' : 'btn-outline-secondary' }}" wire:click="setStateFilter('completed')">Completed</button>
                    <button type="button" class="btn btn-sm {{ $stateFilter === 'cancelled' ? 'btn-warning' : 'btn-outline-warning' }}" wire:click="setStateFilter('cancelled')">Cancelled</button>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-6 col-xl-3">
                    <label class="form-label small text-muted mb-1">Reason</label>
                    <select class="form-select" wire:model.live="reasonFilter">
                        <option value="">All reasons</option>
                        @foreach (\App\Models\Car::scheduledUnavailabilityReasonLabels() as $reasonValue => $reasonLabel)
                            <option value="{{ $reasonValue }}">{{ $reasonLabel }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6 col-xl-3">
                    <label class="form-label small text-muted mb-1">Car</label>
                    <select class="form-select" wire:model.live="carFilter">
                        <option value="">All cars</option>
                        @foreach ($cars as $car)
                            <option value="{{ $car->id }}">{{ $car->fullName() }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6 col-xl-2">
                    <label class="form-label small text-muted mb-1">Date from</label>
                    <input type="date" class="form-control" wire:model.live="dateFrom">
                </div>

                <div class="col-md-6 col-xl-2">
                    <label class="form-label small text-muted mb-1">Date to</label>
                    <input type="date" class="form-control" wire:model.live="dateTo">
                </div>

                <div class="col-md-6 col-xl-2">
                    <label class="form-label small text-muted mb-1">Sort</label>
                    <select class="form-select" wire:model.live="sort">
                        <option value="active_first">Active first</option>
                        <option value="end_soonest">Ending soonest</option>
                        <option value="start_latest">Latest start</option>
                        <option value="latest_created">Latest created</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        @if ($manualHolds->isNotEmpty() && in_array($stateFilter, ['all', 'active'], true))
            <div class="col-12">
                <div class="card border-0 shadow-sm mb-0">
                    <div class="card-header bg-white border-0 d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                        <div>
                            <h5 class="card-title mb-1">Needs Review: Old Unavailable Cars Without Dates</h5>
                            <p class="text-muted small mb-0">These are legacy unavailable cars. Their reason, often Maintenance, came from old data and may not be correct. Open Edit Car to set the real reason and date range, or return the car to Available.</p>
                        </div>
                        <span class="badge bg-dark-subtle text-dark">{{ $manualHolds->count() }} need review</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Car</th>
                                    <th>Reason</th>
                                    <th>Record Type</th>
                                    <th>Car Note</th>
                                    <th>Last Update</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($manualHolds as $manualCar)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $manualCar->fullName() }}</div>
                                            <div class="text-muted small">
                                                {{ optional($manualCar->carModel)->brand }} {{ optional($manualCar->carModel)->model }}
                                                @if ($manualCar->plate_number)
                                                    · {{ $manualCar->plate_number }}
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-label-danger">
                                                {{ \App\Models\Car::unavailabilityReasonLabelFor($manualCar->resolvedManualUnavailabilityReason()) ?? 'Unavailable' }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-dark-subtle text-dark">Old data / No dates</span>
                                        </td>
                                        <td class="text-muted small" style="min-width: 220px;">
                                            {{ $manualCar->notes ?: '—' }}
                                        </td>
                                        <td>
                                            <div class="small text-muted">{{ optional($manualCar->updated_at)->format('Y-m-d H:i') ?: '—' }}</div>
                                        </td>
                                        <td class="text-end">
                                            <div class="d-flex justify-content-end gap-2">
                                                <a href="{{ route('car.edit', $manualCar->id) }}" class="btn btn-sm btn-outline-primary">
                                                    <i class="bx bx-edit me-1"></i>Review in Edit Car
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        <div class="col-12">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                    <div>
                        <h5 class="card-title mb-1">Dated Unavailable Records</h5>
                        <p class="text-muted small mb-0">{{ $periods->total() }} dated record{{ $periods->total() === 1 ? '' : 's' }} matched current filters.</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="badge bg-light text-dark"><i class="bx bx-filter-alt me-1"></i>{{ ucfirst($stateFilter) }}</span>
                        @if ($reasonFilter !== '')
                            <span class="badge bg-danger-subtle text-danger">{{ \App\Models\Car::scheduledUnavailabilityReasonLabels()[$reasonFilter] ?? $reasonFilter }}</span>
                        @endif
                        @if ($carFilter !== '')
                            @php $selectedFilterCar = $cars->firstWhere('id', (int) $carFilter); @endphp
                            <span class="badge bg-info-subtle text-info">{{ $selectedFilterCar?->fullName() ?? 'Selected car' }}</span>
                        @endif
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Car</th>
                                <th>Reason</th>
                                <th>Date Range</th>
                                <th>State</th>
                                <th>Note</th>
                                <th>Audit</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($periods as $period)
                                <tr>
                                    <td>{{ $period->id }}</td>
                                    <td>
                                        <div class="fw-semibold">{{ $period->car?->fullName() ?? 'Vehicle' }}</div>
                                        <div class="text-muted small">
                                            {{ optional($period->car?->carModel)->brand }} {{ optional($period->car?->carModel)->model }}
                                            @if ($period->car?->plate_number)
                                                · {{ $period->car->plate_number }}
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-label-danger">{{ $period->reasonLabel() ?? 'Unavailable' }}</span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ $period->dateWindowLabel() }}</div>
                                        <div class="text-muted small">
                                            {{ $period->start_date?->format('D, d M') }} to {{ $period->end_date?->format('D, d M') }}
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge {{ $period->stateBadgeClass() }}">{{ $period->stateLabel() }}</span>
                                        <div class="text-muted small mt-1">
                                            @if ($period->state() === 'active')
                                                Ends {{ $period->end_date?->diffForHumans() }}
                                            @elseif ($period->state() === 'upcoming')
                                                Starts {{ $period->start_date?->diffForHumans() }}
                                            @elseif ($period->state() === 'cancelled')
                                                Cancelled {{ $period->cancelled_at?->diffForHumans() }}
                                            @elseif ($period->state() === 'needs_action')
                                                Awaiting review since {{ $period->end_date?->diffForHumans() }}
                                            @else
                                                Resolved {{ $period->resolved_at?->diffForHumans() }}
                                            @endif
                                        </div>
                                    </td>
                                    <td style="min-width: 220px;">
                                        <div class="text-muted small">{{ $period->note ?: '—' }}</div>
                                    </td>
                                    <td>
                                        <div class="small">
                                            <div><span class="text-muted">Created:</span> {{ $period->creator?->name ?? '—' }}</div>
                                            <div><span class="text-muted">Updated:</span> {{ $period->updater?->name ?? '—' }}</div>
                                            @if ($period->isCancelled())
                                                <div><span class="text-muted">Cancelled:</span> {{ $period->canceller?->name ?? '—' }}</div>
                                            @elseif ($period->isResolved())
                                                <div><span class="text-muted">Resolved:</span> {{ $period->resolver?->name ?? '—' }}</div>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group">
                                            @if ($period->car)
                                                <a class="btn btn-sm btn-outline-primary" href="{{ route('car.edit', $period->car->id) }}">
                                                    <i class="bx bx-edit"></i>
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-5">
                                        No dated unavailable records found for the current filters.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="card-footer bg-white border-0">
                    {{ $periods->links(data: ['scrollTo' => false]) }}
                </div>
            </div>
        </div>
    </div>
</div>
