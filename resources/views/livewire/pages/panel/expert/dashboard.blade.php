<div class="dashboard-page">
@if ($isDriver)
    <div class="container-xl py-4 driver-dashboard">
        <div class="row g-3 align-items-center mb-4">
            <div class="col-12 col-xl-8">
                <div class="d-flex flex-column gap-1">
                    <span class="text-muted text-uppercase small">Welcome back</span>
                    <h2 class="fw-bold mb-0">{{ Auth::user()->name ?? 'Rider' }}</h2>
                    <p class="text-muted mb-0">Review your upcoming pickups and returns for today.</p>
                </div>
            </div>
            <div class="col-12 col-xl-4">
                <div class="next-task-card h-100">
                    @if ($driverNextTask)
                        @php
                            $taskContract = $driverNextTask['contract'];
                            $taskMoment = $driverNextTask['datetime'];
                            $isPickupTask = $driverNextTask['type'] === 'pickup';
                        @endphp
                        <span class="next-task-label {{ $isPickupTask ? 'next-task-label--pickup' : 'next-task-label--return' }}">
                            Next {{ $isPickupTask ? 'Pickup' : 'Return' }}
                        </span>
                        <div class="d-flex align-items-center gap-2 mt-2 mb-1">
                            <h5 class="mb-0">{{ optional($taskContract->car)->fullName() ?? 'Vehicle' }}</h5>
                            <x-car-ownership-badge :car="$taskContract->car" />
                        </div>
                        <div class="text-muted small mb-1"><i class="bx bx-user-circle me-1"></i>{{ optional($taskContract->customer)->fullName() ?? 'Customer TBD' }}</div>
                        <div class="text-muted small mb-1"><i class="bx bx-time me-1"></i>{{ $taskMoment->format('Y-m-d H:i') }}</div>
                        <div class="text-muted small"><i class="bx bx-map me-1"></i>{{ $isPickupTask ? ($taskContract->pickup_location ?? 'Pickup location TBD') : ($taskContract->return_location ?? 'Return location TBD') }}</div>
                    @else
                        <span class="next-task-label next-task-label--idle">No upcoming tasks</span>
                        <h5 class="mt-2 mb-1">You're all caught up!</h5>
                        <p class="text-muted small mb-0">Dispatch will add new jobs here as soon as they are scheduled.</p>
                    @endif
                </div>
            </div>
        </div>

        @php
            $driverSummaryCards = [
                [
                    'label' => 'Pickups today',
                    'value' => $driverStats['pickupsToday'] ?? 0,
                    'icon' => 'bx bx-log-in',
                    'accent' => 'accent-pickup',
                ],
                [
                    'label' => 'Returns today',
                    'value' => $driverStats['returnsToday'] ?? 0,
                    'icon' => 'bx bx-log-out',
                    'accent' => 'accent-return',
                ],
                [
                    'label' => 'Active assignments',
                    'value' => $driverStats['activeAssignments'] ?? 0,
                    'icon' => 'bx bx-task',
                    'accent' => 'accent-active',
                ],
                [
                    'label' => 'Overdue returns',
                    'value' => $driverStats['overdueReturns'] ?? 0,
                    'icon' => 'bx bx-error',
                    'accent' => 'accent-warning',
                ],
            ];
        @endphp

        <div class="row g-3 mb-4">
            @foreach ($driverSummaryCards as $card)
                <div class="col-6 col-md-3">
                    <div class="driver-stat-card {{ $card['accent'] }}">
                        <span class="stat-icon"><i class="{{ $card['icon'] }}"></i></span>
                        <div class="stat-meta">
                            <span class="stat-value">{{ $card['value'] }}</span>
                            <span class="stat-label">{{ $card['label'] }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="row g-4">
            <div class="col-12 col-xl-6">
                <div class="driver-task-card card shadow-sm border-0 h-100">
                    <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="fw-bold mb-1">Upcoming Pickups</h5>
                            <span class="text-muted small">Stay ahead of today’s handovers</span>
                        </div>
                        <span class="badge bg-primary-subtle text-primary">{{ $driverPickups->count() }}</span>
                    </div>
                    <div class="card-body pt-0">
                        @forelse ($driverPickups as $pickup)
                            <div class="task-item d-flex align-items-start justify-content-between">
                                <div class="task-info">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <h6 class="mb-0">{{ optional($pickup->car)->fullName() ?? 'Vehicle' }}</h6>
                                        <x-car-ownership-badge :car="$pickup->car" />
                                    </div>
                                    <div class="text-muted small"><i class="bx bx-user-circle me-1"></i>{{ optional($pickup->customer)->fullName() ?? 'Customer TBD' }}</div>
                                    <div class="text-muted small"><i class="bx bx-time me-1"></i>{{ \Carbon\Carbon::parse($pickup->pickup_date)->format('Y-m-d H:i') }}</div>
                                    <div class="text-muted small"><i class="bx bx-map me-1"></i>{{ $pickup->pickup_location ?? 'Pickup location TBD' }}</div>
                                </div>
                                <a href="{{ route('rental-requests.pickup-document', [$pickup->id]) }}" class="btn btn-sm btn-outline-primary">Open</a>
                            </div>
                        @empty
                            <div class="text-center text-muted py-4 small">No pickups scheduled yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>
            <div class="col-12 col-xl-6">
                <div class="driver-task-card card shadow-sm border-0 h-100">
                    <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="fw-bold mb-1">Upcoming Returns</h5>
                            <span class="text-muted small">Plan for drop-offs and inspections</span>
                        </div>
                        <span class="badge bg-success-subtle text-success">{{ $driverReturns->count() }}</span>
                    </div>
                    <div class="card-body pt-0">
                        @forelse ($driverReturns as $returnContract)
                            <div class="task-item d-flex align-items-start justify-content-between">
                                <div class="task-info">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <h6 class="mb-0">{{ optional($returnContract->car)->fullName() ?? 'Vehicle' }}</h6>
                                        <x-car-ownership-badge :car="$returnContract->car" />
                                    </div>
                                    <div class="text-muted small"><i class="bx bx-user-circle me-1"></i>{{ optional($returnContract->customer)->fullName() ?? 'Customer TBD' }}</div>
                                    <div class="text-muted small"><i class="bx bx-time me-1"></i>{{ \Carbon\Carbon::parse($returnContract->return_date)->format('Y-m-d H:i') }}</div>
                                    <div class="text-muted small"><i class="bx bx-map me-1"></i>{{ $returnContract->return_location ?? 'Return location TBD' }}</div>
                                </div>
                                <a href="{{ route('rental-requests.return-document', [$returnContract->id]) }}" class="btn btn-sm btn-outline-success">Open</a>
                            </div>
                        @empty
                            <div class="text-center text-muted py-4 small">No returns scheduled yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
@else
    <div class="container-xl py-4">
    @cannot('car')
        @php
            $stats = [
                [
                    'label' => 'Total Contracts',
                    'value' => $totalContracts,
                    'icon' => 'file-earmark-text-fill',
                    'color' => 'primary',
                ],
                [
                    'label' => 'Active Contracts',
                    'value' => $activeContracts,
                    'icon' => 'play-circle-fill',
                    'color' => 'success',
                ],
                [
                    'label' => 'Completed Contracts',
                    'value' => $completedContracts,
                    'icon' => 'check2-circle',
                    'color' => 'info',
                ],
                [
                    'label' => 'Cancelled Contracts',
                    'value' => $cancelledContracts,
                    'icon' => 'x-circle-fill',
                    'color' => 'danger',
                ],
                [
                    'label' => 'Contracts Under Review',
                    'value' => $underReviewContracts,
                    'icon' => 'search',
                    'color' => 'warning',
                ],
                [
                    'label' => 'Avg Contract Price',
                    'value' => '$' . number_format($averageTotalPrice, 2),
                    'icon' => 'cash-stack',
                    'color' => 'dark',
                ],
            ];

            $totalContractsSafe = max($totalContracts, 1);
            $contractSnapshot = [
                [
                    'label' => 'Active',
                    'count' => $activeContracts,
                    'percent' => round(($activeContracts / $totalContractsSafe) * 100),
                    'color' => 'success',
                ],
                [
                    'label' => 'Completed',
                    'count' => $completedContracts,
                    'percent' => round(($completedContracts / $totalContractsSafe) * 100),
                    'color' => 'primary',
                ],
                [
                    'label' => 'Cancelled',
                    'count' => $cancelledContracts,
                    'percent' => round(($cancelledContracts / $totalContractsSafe) * 100),
                    'color' => 'danger',
                ],
                [
                    'label' => 'Under Review',
                    'count' => $underReviewContracts,
                    'percent' => round(($underReviewContracts / $totalContractsSafe) * 100),
                    'color' => 'warning',
                ],
            ];

            $insights = [
                [
                    'label' => 'Avg Rental Duration',
                    'value' => number_format($averageRentalDuration, 1) . ' days',
                    'icon' => 'clock-history',
                    'color' => 'primary',
                ],
                [
                    'label' => 'Returns (next 7 days)',
                    'value' => $upcomingReturns,
                    'icon' => 'calendar-event',
                    'color' => 'info',
                ],
                [
                    'label' => 'Overdue Contracts',
                    'value' => $overdueContracts,
                    'icon' => 'exclamation-octagon',
                    'color' => 'danger',
                ],
                [
                    'label' => 'Service Checks Due',
                    'value' => $serviceAlerts,
                    'icon' => 'tools',
                    'color' => 'warning',
                ],
            ];

            $fleetAvailable = (int) ($fleetStatusSummary['available'] ?? 0);
            $fleetPreReserved = (int) ($fleetStatusSummary['pre_reserved'] ?? 0);
            $fleetReserved = (int) ($fleetStatusSummary['reserved'] ?? 0);
            $fleetUnavailable = (int) ($fleetStatusSummary['unavailable'] ?? 0);
            $fleetManualUnavailable = (int) ($fleetStatusSummary['manual_unavailable'] ?? 0);
            $fleetNeedAction = (int) ($fleetStatusSummary['need_action'] ?? 0);
            $fleetSold = (int) ($fleetStatusSummary['sold'] ?? 0);
            $fleetUnderMaintenance = (int) ($fleetStatusSummary['under_maintenance'] ?? 0);
            $fleetReservations = (int) ($fleetStatusSummary['active_reservations'] ?? 0);
            $fleetUpcomingPickups = (int) ($fleetStatusSummary['upcoming_pickups'] ?? 0);
            $fleetTotal = (int) ($fleetStatusSummary['total'] ?? 0);
            $fleetAvailabilityRate = (int) ($fleetStatusSummary['availability_rate'] ?? 0);
            $fleetDispatchableRate = (int) ($fleetStatusSummary['dispatchable_rate'] ?? 0);
            $fleetReasonBreakdown = collect($fleetStatusSummary['reason_breakdown'] ?? [])->values();
            $fleetScopeLabel = 'Our Fleet';
            $fleetUtilizationValue = (int) ($fleetUtilization ?? 0);
            $activeVehiclesValue = (int) ($activeVehicles ?? 0);
            $offlineVehiclesValue = (int) ($offlineVehicles ?? 0);
            $currentMonthRevenueValue = (float) ($currentMonthRevenue ?? 0);
            $currentMonthContractsValue = (int) ($currentMonthContracts ?? 0);

            $operationsHighlights = [
                [
                    'label' => 'Fleet Utilization',
                    'value' => $fleetUtilizationValue . '%',
                    'sub' => $activeVehiclesValue . ' active of ' . number_format($fleetTotal) . ' vehicles',
                    'icon' => 'bi bi-speedometer2',
                    'tone' => 'primary',
                ],
                [
                    'label' => 'Current Month Revenue',
                    'value' => '$' . number_format($currentMonthRevenueValue, 2),
                    'sub' => number_format($currentMonthContractsValue) . ' contracts this month',
                    'icon' => 'bi bi-cash-coin',
                    'tone' => 'success',
                ],
                [
                    'label' => 'Upcoming Pickups',
                    'value' => number_format($fleetUpcomingPickups),
                    'sub' => 'Scheduled handovers ahead',
                    'icon' => 'bi bi-calendar2-check',
                    'tone' => 'info',
                ],
                [
                    'label' => 'Need Action',
                    'value' => number_format($fleetNeedAction),
                    'sub' => 'Overdue returns requiring a decision',
                    'icon' => 'bi bi-wrench-adjustable-circle',
                    'tone' => 'warning',
                ],
                [
                    'label' => 'Avg Rental Duration',
                    'value' => number_format($averageRentalDuration, 1) . ' days',
                    'sub' => 'Average contract time span',
                    'icon' => 'bi bi-clock-history',
                    'tone' => 'primary',
                ],
                [
                    'label' => 'Overdue Contracts',
                    'value' => number_format($overdueContracts),
                    'sub' => 'Requires immediate follow-up',
                    'icon' => 'bi bi-exclamation-octagon',
                    'tone' => 'warning',
                ],
                [
                    'label' => 'Returns (next 7 days)',
                    'value' => number_format($upcomingReturns),
                    'sub' => 'Planned incoming vehicles',
                    'icon' => 'bi bi-calendar-event',
                    'tone' => 'info',
                ],
                [
                    'label' => 'Service Checks Due',
                    'value' => number_format($serviceAlerts),
                    'sub' => 'Cars due for service inspection',
                    'icon' => 'bi bi-tools',
                    'tone' => 'success',
                ],
            ];

            $fleetSummaryCards = [
                [
                    'label' => 'Available Now',
                    'value' => $fleetAvailable,
                    'hint' => 'Ready for pickup now',
                    'icon' => 'bi bi-check2-circle',
                    'tone' => 'available',
                ],
                [
                    'label' => 'Upcoming Booking',
                    'value' => $fleetPreReserved,
                    'hint' => 'Future reservation already exists',
                    'icon' => 'bi bi-calendar2-check',
                    'tone' => 'reservations',
                ],
                [
                    'label' => 'Active Booking',
                    'value' => $fleetReserved,
                    'hint' => 'Currently in an active rental window',
                    'icon' => 'bi bi-journal-check',
                    'tone' => 'booked',
                ],
                [
                    'label' => 'Need Action',
                    'value' => $fleetNeedAction,
                    'hint' => 'Contract is overdue and needs a decision',
                    'icon' => 'bi bi-exclamation-diamond',
                    'tone' => 'unavailable',
                ],
                [
                    'label' => 'Manual Unavailable',
                    'value' => $fleetManualUnavailable,
                    'hint' => 'Blocked by a selected unavailable reason',
                    'icon' => 'bi bi-slash-circle',
                    'tone' => 'unavailable',
                ],
                [
                    'label' => 'Sold',
                    'value' => $fleetSold,
                    'hint' => 'Removed from operating fleet',
                    'icon' => 'bi bi-ban',
                    'tone' => 'sold',
                ],
            ];

            $cancellationRate = $totalContracts > 0
                ? round(($cancelledContracts / $totalContracts) * 100, 1)
                : 0;
            $activeShare = $totalContracts > 0
                ? round(($activeContracts / $totalContracts) * 100, 1)
                : 0;
            $readyCars = $fleetAvailable + $fleetPreReserved;
            $readinessGap = max($fleetTotal - $readyCars, 0);

            $operationsWatchlist = [
                [
                    'label' => 'Under Review Queue',
                    'value' => number_format($underReviewContracts),
                    'hint' => 'Contracts waiting for final decision',
                    'icon' => 'search',
                    'color' => 'warning',
                ],
                [
                    'label' => 'Cancellation Rate',
                    'value' => $cancellationRate . '%',
                    'hint' => number_format($cancelledContracts) . ' cancelled out of ' . number_format($totalContracts),
                    'icon' => 'x-octagon',
                    'color' => 'danger',
                ],
                [
                    'label' => 'Active Contract Share',
                    'value' => $activeShare . '%',
                    'hint' => number_format($activeContracts) . ' active contracts now',
                    'icon' => 'pie-chart',
                    'color' => 'info',
                ],
                [
                    'label' => 'Fleet Readiness Gap',
                    'value' => number_format($readinessGap),
                    'hint' => 'Vehicles not immediately ready for dispatch',
                    'icon' => 'shield-exclamation',
                    'color' => 'primary',
                ],
            ];
        @endphp

        <div class="row g-3 align-items-center mb-4">
            <div class="col-12 col-md">
                <div class="d-flex flex-column gap-1">
                    <span class="text-muted text-uppercase small">Welcome back</span>
                    <h2 class="fw-bold mb-0">{{ Auth::user()->name ?? 'Expert' }}</h2>
                    <p class="text-muted mb-0">Monitor revenue, fleet performance, and contract flow at a glance.</p>
                </div>
            </div>
            <div class="col-12 col-md-auto">
                <div class="d-flex flex-column flex-sm-row gap-2 align-items-sm-center">
                    <span class="badge bg-light text-dark px-3 py-2"><i class="bi bi-clock-history me-1"></i>Updated {{ now()->format('Y-m-d H:i') }}</span>
                    <a href="{{ route('rental-requests.list') }}" class="btn btn-dark btn-sm px-3"><i class="bi bi-card-list me-1"></i>View Requests</a>
                </div>
            </div>
        </div>

        <div class="card shadow-lg border-0 rounded-4 mb-4 fleet-status-hero">
            <div class="card-body p-4">
                <div class="fleet-status-hero__header mb-3">
                    <div>
                        <span class="fleet-status-hero__eyebrow">Fleet Status Report</span>
                        <h5 class="fw-bold mb-1">Live machine readiness snapshot</h5>
                        <p class="text-muted mb-0">Instant view of availability, unavailability, and reservation pressure for {{ $fleetScopeLabel }}.</p>
                    </div>
                    <div class="fleet-status-hero__meta">
                        <span class="fleet-status-hero__pill">
                            <i class="bi bi-funnel me-1"></i>
                            Scope <strong>{{ $fleetScopeLabel }}</strong>
                        </span>
                        <span class="fleet-status-hero__pill">
                            <i class="bi bi-car-front me-1"></i>
                            Total Fleet <strong>{{ number_format($fleetTotal) }}</strong>
                        </span>
                        <span class="fleet-status-hero__pill">
                            <i class="bi bi-graph-up-arrow me-1"></i>
                            Available Now <strong>{{ $fleetAvailabilityRate }}%</strong>
                        </span>
                        <span class="fleet-status-hero__pill">
                            <i class="bi bi-send-check me-1"></i>
                            Dispatchable <strong>{{ $fleetDispatchableRate }}%</strong>
                        </span>
                        <span class="fleet-status-hero__pill">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            Need Action <strong>{{ number_format($fleetNeedAction) }}</strong>
                        </span>
                    </div>
                </div>

                <div class="row g-3">
                    @foreach ($fleetSummaryCards as $summaryCard)
                        <div class="col-12 col-sm-6 col-xl-4">
                            <div class="fleet-status-card fleet-status-card--{{ $summaryCard['tone'] }}">
                                <span class="fleet-status-card__icon">
                                    <i class="{{ $summaryCard['icon'] }}"></i>
                                </span>
                                <div class="fleet-status-card__content">
                                    <div class="fleet-status-card__value">{{ number_format($summaryCard['value']) }}</div>
                                    <div class="fleet-status-card__label">{{ $summaryCard['label'] }}</div>
                                    <div class="fleet-status-card__hint">{{ $summaryCard['hint'] }}</div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if ($fleetReasonBreakdown->isNotEmpty())
                    <div class="fleet-reason-strip mt-3">
                        <div class="fleet-reason-strip__label">Unavailable reasons in scope</div>
                        <div class="fleet-reason-strip__items">
                            @foreach ($fleetReasonBreakdown as $reason)
                                <span class="fleet-reason-chip">
                                    <strong>{{ $reason['count'] }}</strong>
                                    {{ $reason['label'] }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="card shadow-lg border-0 rounded-4 mb-4">
            <div class="card-header border-0 bg-transparent pt-4 px-4 d-flex flex-wrap gap-3 justify-content-between align-items-center">
                <div>
                    <h5 class="fw-bold mb-1"><i class="bi bi-ev-front text-primary me-2"></i>Fleet Board</h5>
                    <span class="text-muted small">{{ $availableCarsTotal }} vehicle{{ $availableCarsTotal === 1 ? '' : 's' }} currently matching the selected filters</span>
                </div>
                <a href="{{ route('car.list') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-card-checklist me-1"></i>Manage Cars
                </a>
            </div>
            <div class="card-body pt-0 px-4 pb-4">
                <form class="available-fleet-toolbar mb-3" wire:submit.prevent="applyAvailableFleetFilters">
                    <div class="available-fleet-toolbar__search">
                        <i class="bi bi-search"></i>
                        <input type="text" class="form-control border-0 shadow-none"
                            placeholder="Search by plate, brand, model, color"
                            wire:model.defer="availableSearch">
                    </div>
                    <div class="available-fleet-toolbar__panel">
                        <div class="available-fleet-toolbar__filters">
                            <select class="form-select form-select-sm" wire:model.defer="availableFleetScope">
                                <option value="our">Our Fleet (Default)</option>
                                <option value="all">All Fleets</option>
                                <option value="partners">Partner Fleets Only</option>
                            </select>

                            <select class="form-select form-select-sm" wire:model.defer="availableReadiness">
                                <option value="available">Available Now</option>
                                <option value="available_pre_reserved">Dispatchable (Available + Upcoming)</option>
                                <option value="pre_reserved">Upcoming Booking</option>
                                <option value="reserved">Active Booking</option>
                                <option value="unavailable">Unavailable</option>
                                <option value="need_action">Need Action</option>
                                <option value="sold">Sold</option>
                                <option value="all">All Operational Statuses</option>
                            </select>

                            <select class="form-select form-select-sm" wire:model.defer="availableReason">
                                <option value="all">All Unavailable Reasons</option>
                                @foreach (\App\Models\Car::operationalUnavailabilityReasonLabels() as $reasonValue => $reasonLabel)
                                    <option value="{{ $reasonValue }}">{{ $reasonLabel }}</option>
                                @endforeach
                            </select>

                            <select class="form-select form-select-sm" wire:model.defer="availableBrand">
                                <option value="all">All Brands</option>
                                @foreach ($availableBrands as $brand)
                                    <option value="{{ $brand }}">{{ $brand }}</option>
                                @endforeach
                            </select>
                            <select class="form-select form-select-sm" wire:model.defer="availableSort">
                                <option value="returned_oldest">Sort: Oldest Return</option>
                                <option value="returned_latest">Sort: Latest Return</option>
                                <option value="service_due_soon">Sort: Service Due Soon</option>
                                <option value="service_due_late">Sort: Service Due Late</option>
                                <option value="year_newest">Sort: Newest Year</option>
                                <option value="year_oldest">Sort: Oldest Year</option>
                            </select>
                        </div>
                        <div class="available-fleet-toolbar__actions">
                            <button type="submit" class="btn btn-sm btn-dark">
                                <span wire:loading.remove wire:target="applyAvailableFleetFilters">Apply Filters</span>
                                <span wire:loading wire:target="applyAvailableFleetFilters">Applying...</span>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-dark available-fleet-toolbar__reset" wire:click="resetAvailableFleetFilters">
                                <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
                            </button>
                        </div>
                    </div>
                </form>

                <div class="available-fleet-feedback mb-2 d-flex align-items-center justify-content-between">
                    <span>Applied Results</span>
                    <strong>{{ $availableCarsTotal }} matched</strong>
                </div>

                @if ($availableCars->isEmpty())
                    <div class="text-center text-muted py-5">No vehicles found for the selected filters.</div>
                @else
                    <div class="table-responsive position-relative"
                        wire:key="available-fleet-table-{{ $availableFleetScope }}-{{ $availableReadiness }}-{{ $availableReason }}-{{ $availableBrand }}-{{ $availableSort }}-{{ md5((string) $availableSearch) }}"
                        style="max-height: 380px; overflow-y: auto;">
                        <div class="available-fleet-loading" wire:loading.flex
                            wire:target="applyAvailableFleetFilters,resetAvailableFleetFilters">
                            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                            Applying filters...
                        </div>
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th scope="col">Vehicle</th>
                                    <th scope="col">Fleet</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Contract Window</th>
                                    <th scope="col">Operational Notes</th>
                                    <th scope="col">Service / Return</th>
                                    <th scope="col" class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($availableCars as $car)
                                    @php
                                        $brand = optional($car->carModel)->brand;
                                        $model = optional($car->carModel)->model;
                                        $serviceDue = $car->service_due_date ? \Carbon\Carbon::parse($car->service_due_date)->format('Y-m-d') : '—';
                                        $currentContract = $car->currentContract;
                                        $upcomingReservation = $car->upcomingReservation;
                                        $returnedAt = $car->latest_returned_at ? \Carbon\Carbon::parse($car->latest_returned_at) : null;
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ trim(($brand ? $brand . ' ' : '') . ($model ?? 'Vehicle')) }}</div>
                                            <div class="text-muted small">{{ $car->plate_number ?? '—' }} · {{ ucfirst($car->color ?? '—') }} · {{ $car->manufacturing_year ?? '—' }}</div>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column gap-1">
                                                <x-car-ownership-badge :car="$car" />
                                                @if ($car->ownershipLabel())
                                                    <span class="text-muted small">{{ $car->ownershipLabel() }}</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge {{ $car->operationalStatusSubtleBadgeClass() }}">
                                                {{ $car->operationalStatusLabel() }}
                                            </span>
                                            @if ($car->operationalStatus() === \App\Models\Car::STATUS_UNAVAILABLE && $car->unavailabilityReasonLabel())
                                                <div class="small text-muted mt-1">{{ $car->unavailabilityReasonLabel() }}</div>
                                            @endif
                                            @if ($car->activeScheduledUnavailabilityWindowLabel())
                                                <div class="small text-danger mt-1">Hold {{ $car->activeScheduledUnavailabilityWindowLabel() }}</div>
                                            @endif
                                            @if ($car->operationalStatusContextNote())
                                                <div class="small text-warning mt-1">{{ $car->operationalStatusContextNote() }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($currentContract)
                                                <div class="d-flex flex-column gap-1">
                                                    <div class="fw-semibold">Pickup {{ $currentContract->pickup_date ? \Carbon\Carbon::parse($currentContract->pickup_date)->format('Y-m-d H:i') : '—' }}</div>
                                                    <div class="text-muted small">Return {{ $currentContract->return_date ? \Carbon\Carbon::parse($currentContract->return_date)->format('Y-m-d H:i') : '—' }}</div>
                                                    <div class="text-muted small">
                                                        <i class="bi bi-person me-1"></i>{{ optional($currentContract->customer)->fullName() ?? 'Customer TBD' }}
                                                    </div>
                                                </div>
                                            @elseif ($upcomingReservation)
                                                <div class="d-flex flex-column gap-1">
                                                    <div class="fw-semibold">Next pickup {{ $upcomingReservation->pickup_date ? \Carbon\Carbon::parse($upcomingReservation->pickup_date)->format('Y-m-d H:i') : '—' }}</div>
                                                    <div class="text-muted small">
                                                        <i class="bi bi-person me-1"></i>{{ optional($upcomingReservation->customer)->fullName() ?? 'Customer TBD' }}
                                                    </div>
                                                    <div class="text-muted small">
                                                        <i class="bi bi-geo-alt me-1"></i>{{ $upcomingReservation->pickup_location ?? 'Location TBD' }}
                                                    </div>
                                                </div>
                                            @else
                                                <span class="badge bg-success-subtle text-success">No active or upcoming booking</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column gap-1">
                                                <span class="text-muted small">Reason / note</span>
                                                <span class="fw-semibold">
                                                    {{ $car->unavailabilityReasonLabel() ?? ($car->operationalStatus() === \App\Models\Car::STATUS_PRE_RESERVED ? 'Upcoming booking is already planned' : ($car->operationalStatus() === \App\Models\Car::STATUS_RESERVED ? 'Currently inside rental window' : 'Ready for dispatch')) }}
                                                </span>
                                                @if ($car->activeScheduledUnavailabilityWindowLabel())
                                                    <span class="text-danger small">Hold {{ $car->activeScheduledUnavailabilityWindowLabel() }}</span>
                                                @endif
                                                @if ($upcomingReservation && $car->operationalStatus() !== \App\Models\Car::STATUS_PRE_RESERVED)
                                                    <span class="text-muted small">Next pickup {{ $upcomingReservation->pickup_date ? \Carbon\Carbon::parse($upcomingReservation->pickup_date)->format('Y-m-d H:i') : '—' }}</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column gap-1">
                                                <span class="badge bg-primary-subtle text-primary">Service {{ $serviceDue }}</span>
                                                @if ($returnedAt)
                                                    <div class="text-muted small">Last return {{ $returnedAt->format('Y-m-d H:i') }}</div>
                                                    <div class="text-muted small">{{ $returnedAt->diffForHumans() }}</div>
                                                @else
                                                    <div class="text-muted small">No recorded return yet</div>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            <div class="d-flex justify-content-end gap-2">
                                                @if ($currentContract)
                                                    <a href="{{ route('rental-requests.details', $currentContract->id) }}" class="btn btn-sm btn-outline-dark">
                                                        <i class="bx bx-detail"></i>
                                                    </a>
                                                @endif
                                                <a href="{{ route('car.edit', $car->id) }}" class="btn btn-sm btn-outline-primary">
                                                    <i class="bx bx-edit"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <div class="card shadow-lg border-0 rounded-4 mb-4">
            <div class="card-header border-0 bg-transparent pt-4 px-4 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-bold mb-1"><i class="bi bi-exclamation-circle text-danger me-2"></i>Attention Queue</h5>
                    <span class="text-muted small">Vehicles that are unavailable, sold, or require immediate follow-up.</span>
                </div>
                <span class="badge bg-danger-subtle text-danger">{{ count($fleetAttentionCars) }} items</span>
            </div>
            <div class="card-body pt-0 px-4 pb-4">
                @if (empty($fleetAttentionCars))
                    <div class="text-center text-muted py-5">No attention items in our fleet right now.</div>
                @else
                    <div class="row g-3">
                        @foreach ($fleetAttentionCars as $attentionCar)
                            <div class="col-12 col-xl-6">
                                <div class="attention-queue-card">
                                    <div class="d-flex justify-content-between align-items-start gap-3">
                                        <div>
                                            <div class="fw-semibold">{{ $attentionCar['car_name'] }}</div>
                                            <div class="text-muted small">{{ $attentionCar['ownership_label'] }}</div>
                                        </div>
                                        <span class="badge {{ $attentionCar['status_badge_class'] }}">{{ $attentionCar['status_label'] }}</span>
                                    </div>

                                    @if ($attentionCar['reason_label'])
                                        <div class="attention-queue-card__reason">{{ $attentionCar['reason_label'] }}</div>
                                    @endif

                                    @if ($attentionCar['active_window_label'])
                                        <div class="attention-queue-card__note">Hold {{ $attentionCar['active_window_label'] }}</div>
                                    @endif

                                    @if ($attentionCar['active_window_note'])
                                        <div class="text-muted small mt-1">{{ $attentionCar['active_window_note'] }}</div>
                                    @endif

                                    @if ($attentionCar['context_note'])
                                        <div class="attention-queue-card__note">{{ $attentionCar['context_note'] }}</div>
                                    @endif

                                    <div class="attention-queue-card__action">{{ $attentionCar['action_label'] }}</div>

                                    <div class="attention-queue-card__meta">
                                        @if ($attentionCar['current_contract_id'])
                                            <span>Contract #{{ $attentionCar['current_contract_id'] }} until {{ $attentionCar['current_return_at'] }}</span>
                                        @elseif ($attentionCar['last_returned_at'])
                                            <span>Last return {{ $attentionCar['last_returned_at'] }}</span>
                                        @else
                                            <span>No prior return log</span>
                                        @endif

                                        @if ($attentionCar['next_pickup_at'])
                                            <span>Next pickup {{ $attentionCar['next_pickup_at'] }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        @php
            $insuranceCompliance = $complianceReport['insurance'] ?? ['summary' => [], 'rows' => []];
            $passingCompliance = $complianceReport['passing'] ?? ['summary' => [], 'rows' => []];
            $insuranceComplianceRows = collect($insuranceCompliance['rows'] ?? []);
            $passingComplianceRows = collect($passingCompliance['rows'] ?? []);
            $complianceStatusClasses = [
                'done' => 'bg-label-success',
                'pending' => 'bg-label-warning',
                'failed' => 'bg-label-danger',
            ];
        @endphp
        <div class="card shadow-lg border-0 rounded-4 mb-4 compliance-monitor-card">
            <div class="card-body p-4">
                <div class="compliance-monitor__header mb-4">
                    <div>
                        <span class="compliance-monitor__eyebrow">Renewal Monitor</span>
                        <h5 class="fw-bold mb-1">Insurance & Passing Report</h5>
                        <p class="text-muted mb-0">Insurance counts use policy expiry dates. Passing counts use the passing date plus its validity days.</p>
                    </div>
                    <div class="compliance-monitor__legend">
                        <span class="compliance-monitor__legend-item"><i class="bi bi-calendar2-week"></i> Due this month</span>
                        <span class="compliance-monitor__legend-item"><i class="bi bi-alarm"></i> 5 days or less</span>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="compliance-kpi compliance-kpi--insurance-month">
                            <span class="compliance-kpi__icon"><i class="bi bi-shield-check"></i></span>
                            <div>
                                <div class="compliance-kpi__value">{{ number_format((int) ($insuranceCompliance['summary']['due_this_month'] ?? 0)) }}</div>
                                <div class="compliance-kpi__label">Insurance due in {{ now()->format('F') }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="compliance-kpi compliance-kpi--insurance-urgent">
                            <span class="compliance-kpi__icon"><i class="bi bi-exclamation-triangle"></i></span>
                            <div>
                                <div class="compliance-kpi__value">{{ number_format((int) ($insuranceCompliance['summary']['due_in_five_days'] ?? 0)) }}</div>
                                <div class="compliance-kpi__label">Insurance in 5 days or less</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="compliance-kpi compliance-kpi--passing-month">
                            <span class="compliance-kpi__icon"><i class="bi bi-clipboard2-check"></i></span>
                            <div>
                                <div class="compliance-kpi__value">{{ number_format((int) ($passingCompliance['summary']['due_this_month'] ?? 0)) }}</div>
                                <div class="compliance-kpi__label">Passing due in {{ now()->format('F') }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="compliance-kpi compliance-kpi--passing-urgent">
                            <span class="compliance-kpi__icon"><i class="bi bi-stopwatch"></i></span>
                            <div>
                                <div class="compliance-kpi__value">{{ number_format((int) ($passingCompliance['summary']['due_in_five_days'] ?? 0)) }}</div>
                                <div class="compliance-kpi__label">Passing in 5 days or less</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-12 col-xl-6">
                        <div class="compliance-list-card h-100">
                            <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                                <div>
                                    <h6 class="fw-bold mb-1">Insurance queue</h6>
                                    <p class="text-muted small mb-0">Vehicles that expire this month or within the next 5 days.</p>
                                </div>
                                <span class="badge compliance-list-card__badge">{{ $insuranceComplianceRows->count() }}</span>
                            </div>

                            @if ($insuranceComplianceRows->count())
                                <div class="compliance-list-card__body">
                                    @foreach ($insuranceComplianceRows as $row)
                                        <div class="compliance-item">
                                            <div class="d-flex align-items-start justify-content-between gap-3">
                                                <div class="min-w-0">
                                                    <div class="fw-semibold text-truncate">{{ $row['car_name'] }}</div>
                                                    <div class="text-muted small">Plate {{ $row['plate_number'] }} · {{ $row['ownership_label'] }}</div>
                                                </div>
                                                <span class="compliance-item__countdown {{ $row['is_overdue'] ? 'is-overdue' : ($row['is_urgent'] ? 'is-urgent' : '') }}">
                                                    {{ $row['days_remaining_label'] }}
                                                </span>
                                            </div>
                                            <div class="d-flex flex-wrap gap-2 mt-3">
                                                <span class="badge compliance-chip"><i class="bi bi-calendar-event me-1"></i>{{ $row['expires_at'] }}</span>
                                                @if ($row['is_due_this_month'])
                                                    <span class="badge compliance-chip compliance-chip--month">This month</span>
                                                @endif
                                                @if ($row['is_urgent'])
                                                    <span class="badge compliance-chip compliance-chip--urgent">Urgent</span>
                                                @endif
                                                @if ($row['is_overdue'])
                                                    <span class="badge compliance-chip compliance-chip--overdue">Expired</span>
                                                @endif
                                                <span class="badge {{ $complianceStatusClasses[$row['status']] ?? 'bg-label-secondary' }}">
                                                    {{ ucfirst($row['status']) }}
                                                </span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center text-muted py-5">No insurance renewals are currently due.</div>
                            @endif
                        </div>
                    </div>

                    <div class="col-12 col-xl-6">
                        <div class="compliance-list-card h-100">
                            <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                                <div>
                                    <h6 class="fw-bold mb-1">Passing queue</h6>
                                    <p class="text-muted small mb-0">Vehicles whose passing validity ends this month or within the next 5 days.</p>
                                </div>
                                <span class="badge compliance-list-card__badge">{{ $passingComplianceRows->count() }}</span>
                            </div>

                            @if ($passingComplianceRows->count())
                                <div class="compliance-list-card__body">
                                    @foreach ($passingComplianceRows as $row)
                                        <div class="compliance-item">
                                            <div class="d-flex align-items-start justify-content-between gap-3">
                                                <div class="min-w-0">
                                                    <div class="fw-semibold text-truncate">{{ $row['car_name'] }}</div>
                                                    <div class="text-muted small">Plate {{ $row['plate_number'] }} · {{ $row['ownership_label'] }}</div>
                                                    @if (!empty($row['recorded_at']))
                                                        <div class="text-muted small">Recorded {{ $row['recorded_at'] }}</div>
                                                    @endif
                                                </div>
                                                <span class="compliance-item__countdown {{ $row['is_overdue'] ? 'is-overdue' : ($row['is_urgent'] ? 'is-urgent' : '') }}">
                                                    {{ $row['days_remaining_label'] }}
                                                </span>
                                            </div>
                                            <div class="d-flex flex-wrap gap-2 mt-3">
                                                <span class="badge compliance-chip"><i class="bi bi-calendar-event me-1"></i>{{ $row['expires_at'] }}</span>
                                                @if ($row['is_due_this_month'])
                                                    <span class="badge compliance-chip compliance-chip--month">This month</span>
                                                @endif
                                                @if ($row['is_urgent'])
                                                    <span class="badge compliance-chip compliance-chip--urgent">Urgent</span>
                                                @endif
                                                @if ($row['is_overdue'])
                                                    <span class="badge compliance-chip compliance-chip--overdue">Expired</span>
                                                @endif
                                                <span class="badge {{ $complianceStatusClasses[$row['status']] ?? 'bg-label-secondary' }}">
                                                    {{ ucfirst($row['status']) }}
                                                </span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center text-muted py-5">No passing renewals are currently due.</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @php
            $monthlyRows = $monthlyContractsReport['rows'] ?? collect();
            $monthlySummary = $monthlyContractsReport['summary'] ?? [];
            $monthlyFilterSummary = $monthlyContractsReport['filter_summary'] ?? [];
            $monthlyOwnershipOptions = [
                'all' => 'All fleets',
                'company' => 'Our fleet',
                'golden_key' => 'Golden Key',
                'liverpool' => 'Liverpool',
                'safe_drive' => 'Safe Drive',
                'other' => 'Other fleet',
            ];
            $monthlyDateFieldOptions = [
                'created_at' => 'Request Date',
                'pickup_date' => 'Pickup Date',
                'return_date' => 'Return Date',
            ];
            $currentMonthLabel = now()->format('F');
        @endphp
        <div class="card shadow-lg border-0 rounded-4 mb-4 monthly-contracts-card">
            <div class="card-body p-4">
                <div class="monthly-contracts-hero mb-3">
                    <div>
                        <span class="monthly-contracts-hero__eyebrow">Monthly Contracts</span>
                        <h5 class="fw-bold mb-1">Monthly Contracts Overview</h5>
                        <p class="text-muted mb-0">Contracts with a rental duration of 30 days or more.</p>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-12 col-md-4">
                        <div class="monthly-kpi-card monthly-kpi-card--primary">
                            <span class="monthly-kpi-card__icon"><i class="bi bi-journal-text"></i></span>
                            <div>
                                <div class="monthly-kpi-card__value">{{ number_format((int) ($monthlySummary['total_monthly_contracts'] ?? 0)) }}</div>
                                <div class="monthly-kpi-card__label">Total Monthly Contracts</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="monthly-kpi-card monthly-kpi-card--success">
                            <span class="monthly-kpi-card__icon"><i class="bi bi-calendar3"></i></span>
                            <div>
                                <div class="monthly-kpi-card__value">{{ number_format((int) ($monthlySummary['current_month_monthly_contracts'] ?? 0)) }}</div>
                                <div class="monthly-kpi-card__label">Current Month ({{ $currentMonthLabel }})</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="monthly-kpi-card monthly-kpi-card--warning">
                            <span class="monthly-kpi-card__icon"><i class="bi bi-hourglass-split"></i></span>
                            <div>
                                <div class="monthly-kpi-card__value">{{ number_format((int) ($monthlySummary['ending_in_three_days_or_less'] ?? 0)) }}</div>
                                <div class="monthly-kpi-card__label">Ending In 3 Days Or Less</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-12 col-lg-3">
                        <div class="upcoming-filter-field">
                            <label class="small text-uppercase text-muted fw-semibold mb-1">Search</label>
                            <input type="search" class="form-control" placeholder="Customer, contract, plate, passport"
                                wire:model.defer="monthlyContractsSearch">
                        </div>
                    </div>
                    <div class="col-12 col-lg-2">
                        <div class="upcoming-filter-field">
                            <label class="small text-uppercase text-muted fw-semibold mb-1">Date Basis</label>
                            <select class="form-select" wire:model.defer="monthlyContractsDateField">
                                @foreach ($monthlyDateFieldOptions as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-6 col-lg-2">
                        <div class="upcoming-filter-field">
                            <label class="small text-uppercase text-muted fw-semibold mb-1">Date From</label>
                            <input type="date" class="form-control" wire:model.defer="monthlyContractsDateFrom">
                        </div>
                    </div>
                    <div class="col-6 col-lg-2">
                        <div class="upcoming-filter-field">
                            <label class="small text-uppercase text-muted fw-semibold mb-1">Date To</label>
                            <input type="date" class="form-control" wire:model.defer="monthlyContractsDateTo">
                        </div>
                    </div>
                    <div class="col-12 col-lg-2">
                        <div class="upcoming-filter-field">
                            <label class="small text-uppercase text-muted fw-semibold mb-1">Fleet Scope</label>
                            <select class="form-select" wire:model.defer="monthlyContractsOwnership">
                                @foreach ($monthlyOwnershipOptions as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-12 col-lg-1 d-grid d-lg-flex flex-lg-column gap-2 justify-content-lg-end">
                        <button type="button" class="btn btn-dark btn-sm" wire:click="applyMonthlyContractsFilters">
                            Apply
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="resetMonthlyContractsFilters">
                            Reset
                        </button>
                    </div>
                </div>

                @if (!empty($monthlyFilterSummary))
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        @foreach ($monthlyFilterSummary as $label => $value)
                            <span class="badge upcoming-filter-badge">{{ $label }}: {{ $value }}</span>
                        @endforeach
                    </div>
                @endif

                @if ($monthlyRows->count())
                    <div class="table-responsive" style="max-height: 460px; overflow-y: auto;">
                        <table class="table table-hover align-middle mb-0 upcoming-delivery-table">
                            <thead class="bg-light">
                                <tr>
                                    <th>Contract</th>
                                    <th>Customer</th>
                                    <th>Vehicle</th>
                                    <th>Rental Window</th>
                                    <th>Duration</th>
                                    <th>Ends In</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($monthlyRows as $row)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">#{{ $row['contract_id'] }}</div>
                                            <div class="text-muted small">Request {{ $row['request_date'] }}</div>
                                            <div class="text-muted small">Agreement {{ $row['agreement_number'] }}</div>
                                        </td>
                                        <td>
                                            <div class="fw-semibold">{{ $row['customer_name'] }}</div>
                                            <div class="text-muted small">{{ $row['customer_phone'] }}</div>
                                            <div class="text-muted small">Sales: {{ $row['sales_agent'] }}</div>
                                        </td>
                                        <td>
                                            <div class="fw-semibold">{{ $row['car_name'] }}</div>
                                            <div class="text-muted small">Plate: {{ $row['plate_number'] }} | {{ $row['ownership'] }}</div>
                                            <div class="text-muted small">{{ $row['pickup_location'] }} → {{ $row['return_location'] }}</div>
                                        </td>
                                        <td>
                                            <div class="fw-semibold">Pickup {{ $row['pickup_date'] }}</div>
                                            <div class="text-muted small">Return {{ $row['return_date'] }}</div>
                                            <div class="text-muted small">Lead time {{ number_format($row['lead_time_days']) }} day(s)</div>
                                        </td>
                                        <td>
                                            <span class="badge bg-label-primary">{{ number_format($row['duration_days']) }} days</span>
                                        </td>
                                        <td>
                                            <span class="badge {{ $row['is_ending_soon'] ? 'bg-label-warning' : 'bg-label-secondary' }}">
                                                {{ is_numeric($row['days_until_end']) ? $row['days_until_end'] . ' day(s)' : $row['days_until_end'] }}
                                            </span>
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
                @else
                    <div class="text-center text-muted py-5">No monthly contracts found for the current filters.</div>
                @endif
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-12 col-xxl-6">
                <div class="card shadow-lg border-0 rounded-4 h-100">
                    <div class="card-header border-0 bg-transparent pt-4 px-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="fw-bold mb-1"><i class="bi bi-car-front-fill text-warning me-2"></i>Booking Cars</h5>
                                <span class="text-muted small">{{ $reservedCars->count() }} active reservations</span>
                            </div>
                            <span class="badge bg-warning text-dark">Live</span>
                        </div>
                    </div>
                    <div class="card-body pt-0 pb-4 px-4" style="max-height: 420px; overflow-y: auto;" data-simplebar>
                        @forelse ($reservedCars as $contract)
                            @php
                                $carImage = optional($contract->car)?->primaryImageUrl() ?? asset('assets/car-pics/car test.webp');
                                $pickupAt = $contract->pickup_date ? \Carbon\Carbon::parse($contract->pickup_date) : null;
                                $returnAt = $contract->return_date ? \Carbon\Carbon::parse($contract->return_date) : null;
                                $agreementNumber = optional($contract->pickupDocument)->agreement_number;
                                $rawId = $contract->id;
                                $requestRef = $rawId ? str_pad((string) $rawId, 5, '0', STR_PAD_LEFT) : '—';
                                $durationLabel = null;

                                if ($pickupAt && $returnAt) {
                                    $diffHours = $pickupAt->diffInHours($returnAt);
                                    $days = intdiv($diffHours, 24);
                                    $hours = $diffHours % 24;
                                    $durationBits = [];

                                    if ($days > 0) {
                                        $durationBits[] = $days . 'd';
                                    }

                                    if ($hours > 0) {
                                        $durationBits[] = $hours . 'h';
                                    }

                                    if (empty($durationBits)) {
                                        $durationBits[] = $pickupAt->diffInMinutes($returnAt) . 'm';
                                    }

                                    $durationLabel = implode(' ', $durationBits);
                                }

                                $durationLabel = $durationLabel ?? '—';
                                $statusLabel = ucfirst(str_replace('_', ' ', $contract->current_status));
                            @endphp
                            <div class="border rounded-4 p-3 mb-3 shadow-sm booking-card booking-card--reserved">
                                <div class="d-flex align-items-start gap-3">
                                    <img src="{{ $carImage }}" alt="Car" class="rounded-3 booking-card__image"
                                        width="70" height="70" loading="lazy" decoding="async" fetchpriority="low">
                                    <div class="flex-grow-1">
                                        <div class="d-flex flex-column gap-3">
                                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <h6 class="fw-bold mb-0">{{ optional($contract->car)->fullname() ?? 'Vehicle' }}</h6>
                                                        <x-car-ownership-badge :car="$contract->car" />
                                                    </div>
                                                    <span class="badge bg-warning text-dark">Booking</span>
                                                </div>
                                                <span class="meta-chip meta-chip--status">
                                                    <i class="bi bi-lightning-charge me-1"></i>
                                                    {{ $statusLabel }}
                                                </span>
                                            </div>

                                            <div class="d-flex flex-wrap gap-2">
                                                <span class="meta-chip meta-chip--request">
                                                    <i class="bi bi-hash me-1"></i>
                                                    Request #{{ $requestRef }}
                                                </span>

                                                @if ($agreementNumber)
                                                    <span class="meta-chip meta-chip--agreement">
                                                        <i class="bi bi-file-earmark-text me-1"></i>
                                                        Agreement {{ $agreementNumber }}
                                                    </span>
                                                @endif

                                                <span class="meta-chip meta-chip--person">
                                                    <i class="bi bi-person-circle me-1"></i>
                                                    {{ optional($contract->customer)->fullName() ?? 'Customer TBD' }}
                                                </span>

                                                @if ($durationLabel !== '—')
                                                    <span class="meta-chip meta-chip--duration">
                                                        <i class="bi bi-hourglass-split me-1"></i>
                                                        {{ $durationLabel }}
                                                    </span>
                                                @endif
                                            </div>

                                            <div class="booking-timeline">
                                                <div class="booking-timeline__leg">
                                                    <span class="booking-timeline__label">Pickup</span>
                                                    <span class="booking-timeline__date">{{ $pickupAt ? $pickupAt->format('Y-m-d H:i') : '—' }}</span>
                                                    <span class="booking-timeline__meta">
                                                        <i class="bi bi-geo-alt me-1"></i>{{ $contract->pickup_location ?? 'Pickup TBD' }}
                                                    </span>
                                                </div>
                                                <div class="booking-timeline__divider">
                                                    <span class="booking-timeline__distance">{{ $durationLabel }}</span>
                                                    <i class="bi bi-arrow-right-short"></i>
                                                </div>
                                                <div class="booking-timeline__leg booking-timeline__leg--accent">
                                                    <span class="booking-timeline__label">Return</span>
                                                    <span class="booking-timeline__date">{{ $returnAt ? $returnAt->format('Y-m-d H:i') : '—' }}</span>
                                                    <span class="booking-timeline__meta">
                                                        <i class="bi bi-flag me-1"></i>{{ $contract->return_location ?? 'Return TBD' }}
                                                    </span>
                                                </div>
                                            </div>

                                            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                                                <div class="text-muted small d-flex align-items-center gap-2">
                                                    <i class="bi bi-clock-history text-warning"></i>
                                                    <span>{{ $pickupAt ? 'Pickup ' . $pickupAt->diffForHumans() : 'Pickup timing pending' }}</span>
                                                </div>
                                                <a href="{{ route('rental-requests.details', $contract->id) }}" class="btn btn-sm btn-dark shadow-sm">
                                                    <span class="me-1">Open request</span>
                                                    <i class="bx bx-right-arrow-alt"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center text-muted py-4">No reserved cars right now.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="col-12 col-xxl-6">
                <div class="card shadow-lg border-0 rounded-4 h-100">
                    <div class="card-header border-0 bg-transparent pt-4 px-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="fw-bold mb-1"><i class="bi bi-arrow-counterclockwise text-success me-2"></i>Return</h5>
                                <span class="text-muted small">{{ $returnedCars->count() }} pending drop-offs</span>
                            </div>
                            <span class="badge bg-success bg-opacity-10 text-white">Live</span>
                        </div>
                    </div>
                    <div class="card-body pt-0 pb-4 px-4" style="max-height: 420px; overflow-y: auto;" data-simplebar>
                        @forelse ($returnedCars as $contract)
                            @php
                                $carImage = optional($contract->car)?->primaryImageUrl() ?? asset('assets/car-pics/car test.webp');
                                $pickupAt = $contract->pickup_date ? \Carbon\Carbon::parse($contract->pickup_date) : null;
                                $returnAt = $contract->return_date ? \Carbon\Carbon::parse($contract->return_date) : null;
                                $agreementNumber = optional($contract->pickupDocument)->agreement_number;
                                $rawId = $contract->id;
                                $requestRef = $rawId ? str_pad((string) $rawId, 5, '0', STR_PAD_LEFT) : '—';
                                $durationLabel = null;

                                if ($pickupAt && $returnAt) {
                                    $diffHours = $pickupAt->diffInHours($returnAt);
                                    $days = intdiv($diffHours, 24);
                                    $hours = $diffHours % 24;
                                    $durationBits = [];

                                    if ($days > 0) {
                                        $durationBits[] = $days . 'd';
                                    }

                                    if ($hours > 0) {
                                        $durationBits[] = $hours . 'h';
                                    }

                                    if (empty($durationBits)) {
                                        $durationBits[] = $pickupAt->diffInMinutes($returnAt) . 'm';
                                    }

                                    $durationLabel = implode(' ', $durationBits);
                                }

                                $durationLabel = $durationLabel ?? '—';
                                $statusLabel = ucfirst(str_replace('_', ' ', $contract->current_status));
                            @endphp
                            <div class="border rounded-4 p-3 mb-3 shadow-sm booking-card booking-card--return">
                                <div class="d-flex align-items-start gap-3">
                                    <img src="{{ $carImage }}" alt="Car" class="rounded-3 booking-card__image"
                                        width="70" height="70" loading="lazy" decoding="async" fetchpriority="low">
                                    <div class="flex-grow-1">
                                        <div class="d-flex flex-column gap-3">
                                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <h6 class="fw-bold mb-0">{{ optional($contract->car)->fullname() ?? 'Vehicle' }}</h6>
                                                        <x-car-ownership-badge :car="$contract->car" />
                                                    </div>
                                                    <span class="badge bg-success">Return</span>
                                                </div>
                                                <span class="meta-chip meta-chip--status">
                                                    <i class="bi bi-arrow-clockwise me-1"></i>
                                                    {{ $statusLabel }}
                                                </span>
                                            </div>

                                            <div class="d-flex flex-wrap gap-2">
                                                <span class="meta-chip meta-chip--request">
                                                    <i class="bi bi-hash me-1"></i>
                                                    Request #{{ $requestRef }}
                                                </span>

                                                @if ($agreementNumber)
                                                    <span class="meta-chip meta-chip--agreement">
                                                        <i class="bi bi-file-earmark-text me-1"></i>
                                                        Agreement {{ $agreementNumber }}
                                                    </span>
                                                @endif

                                                <span class="meta-chip meta-chip--person">
                                                    <i class="bi bi-person-circle me-1"></i>
                                                    {{ optional($contract->customer)->fullName() ?? 'Customer TBD' }}
                                                </span>

                                                @if ($durationLabel !== '—')
                                                    <span class="meta-chip meta-chip--duration">
                                                        <i class="bi bi-hourglass-split me-1"></i>
                                                        {{ $durationLabel }}
                                                    </span>
                                                @endif
                                            </div>

                                            <div class="booking-timeline">
                                                <div class="booking-timeline__leg">
                                                    <span class="booking-timeline__label">Pickup</span>
                                                    <span class="booking-timeline__date">{{ $pickupAt ? $pickupAt->format('Y-m-d H:i') : '—' }}</span>
                                                    <span class="booking-timeline__meta">
                                                        <i class="bi bi-geo-alt me-1"></i>{{ $contract->pickup_location ?? 'Pickup TBD' }}
                                                    </span>
                                                </div>
                                                <div class="booking-timeline__divider">
                                                    <span class="booking-timeline__distance">{{ $durationLabel }}</span>
                                                    <i class="bi bi-arrow-right-short"></i>
                                                </div>
                                                <div class="booking-timeline__leg booking-timeline__leg--accent">
                                                    <span class="booking-timeline__label">Drop-off</span>
                                                    <span class="booking-timeline__date">{{ $returnAt ? $returnAt->format('Y-m-d H:i') : '—' }}</span>
                                                    <span class="booking-timeline__meta">
                                                        <i class="bi bi-flag me-1"></i>{{ $contract->return_location ?? 'Drop-off TBD' }}
                                                    </span>
                                                </div>
                                            </div>

                                            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                                                <div class="text-muted small d-flex align-items-center gap-2">
                                                    <i class="bi bi-stopwatch text-success"></i>
                                                    <span>{{ $returnAt ? 'Return ' . $returnAt->diffForHumans() : 'Return timing pending' }}</span>
                                                </div>
                                                <a href="{{ route('rental-requests.details', $contract->id) }}" class="btn btn-sm btn-success shadow-sm">
                                                    <span class="me-1">Review return</span>
                                                    <i class="bx bx-right-arrow-alt"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center text-muted py-4">No cars awaiting return.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-lg border-0 rounded-4 mb-4 operations-hero">
            <div class="card-body p-4">
                <div class="operations-hero__header mb-3">
                    <div>
                        <span class="operations-hero__eyebrow">Operations Intelligence</span>
                        <h5 class="fw-bold mb-1"><i class="bi bi-graph-up-arrow text-primary me-2"></i>Operational Highlights</h5>
                        <p class="text-muted mb-0">Critical KPIs for today’s fleet and contract operations.</p>
                    </div>
                    <div class="operations-hero__meta">
                        <span class="operations-hero__pill">
                            <i class="bi bi-activity me-1"></i>
                            Live snapshot
                        </span>
                        <span class="operations-hero__pill">
                            <i class="bi bi-lightning-charge me-1"></i>
                            Decision ready
                        </span>
                    </div>
                </div>

                <div class="row g-3 row-cols-1 row-cols-sm-2 row-cols-xl-4">
                    @foreach ($operationsHighlights as $highlight)
                        <div class="col">
                            <div class="card border-0 rounded-4 h-100 dashboard-highlight-card dashboard-highlight-card--{{ $highlight['tone'] }}">
                                <div class="card-body d-flex align-items-center gap-3">
                                    <span class="d-inline-flex align-items-center justify-content-center rounded-3 dashboard-highlight-card__icon">
                                        <i class="{{ $highlight['icon'] }} fs-4"></i>
                                    </span>
                                    <div class="flex-grow-1">
                                        <div class="text-muted small text-uppercase">{{ $highlight['label'] }}</div>
                                        <div class="fs-5 fw-bold mt-1">{{ $highlight['value'] }}</div>
                                        <div class="small text-muted mt-1">{{ $highlight['sub'] }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-12 col-xxl-8">
                <div class="card shadow-lg border-0 rounded-4 h-100">
                    <div class="card-header border-0 bg-transparent pt-4 px-4">
                        <h5 class="fw-bold mb-1"><i class="bi bi-clock-history text-info me-2"></i>Latest Contracts</h5>
                        <span class="text-muted small">Follow up on the five most recent agreements</span>
                    </div>
                    <div class="card-body pt-0 pb-4 px-4">
                        <ul class="list-group list-group-flush">
                            @forelse ($latestContracts as $contract)
                                <li class="list-group-item px-0 py-3">
                                    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge rounded-pill bg-light text-dark"><i class="bi bi-person-fill me-1"></i>{{ optional($contract->customer)->name ?? 'Customer' }}</span>
                                            <span class="text-muted small"><i class="bi bi-calendar-event me-1"></i>{{ $contract->created_at->format('Y-m-d') }}</span>
                                        </div>
                                        <div class="d-flex align-items-center gap-3">
                                            <x-status-badge :status="$contract->current_status" />
                                            <span class="fw-bold text-success">${{ number_format($contract->total_price, 2) }}</span>
                                            <a href="{{ route('rental-requests.edit', $contract->id) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                                        </div>
                                    </div>
                                </li>
                            @empty
                                <li class="list-group-item text-muted text-center py-4">No recent contracts available.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-12 col-xxl-4">
                <div class="card shadow-lg border-0 rounded-4 h-100">
                    <div class="card-header border-0 bg-transparent pt-4 px-4">
                        <h5 class="fw-bold mb-1"><i class="bi bi-binoculars-fill text-primary me-2"></i>Operations Watchlist</h5>
                        <span class="text-muted small">High-signal indicators for the next actions</span>
                    </div>
                    <div class="card-body pt-0 pb-4 px-4">
                        <div class="d-flex flex-column gap-3">
                            @foreach ($operationsWatchlist as $item)
                                <div class="d-flex align-items-center justify-content-between rounded-4 border px-3 py-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-{{ $item['color'] }} bg-opacity-10 text-{{ $item['color'] }}" style="width: 42px; height: 42px;">
                                            <i class="bi bi-{{ $item['icon'] }}"></i>
                                        </span>
                                        <div>
                                            <div class="text-muted small text-uppercase">{{ $item['label'] }}</div>
                                            <div class="fw-semibold">{{ $item['value'] }}</div>
                                            <div class="text-muted small">{{ $item['hint'] }}</div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-12">
                <div class="card shadow-lg border-0 rounded-4">
                    <div class="card-header border-0 bg-transparent pt-4 px-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="fw-bold mb-1"><i class="bi bi-trophy-fill text-primary me-2"></i>Top Performing Cars</h5>
                                <span class="text-muted small">Contracts closed by brand and model</span>
                            </div>
                            <span class="badge bg-primary bg-opacity-10">Top 3</span>
                        </div>
                    </div>
                    <div class="card-body pt-0 pb-4 px-4">
                        <div class="row g-4 align-items-center">
                            <div class="col-12 col-lg-5">
                                <div id="topModelsChart" style="min-height: 260px;" wire:ignore></div>
                            </div>
                            <div class="col-12 col-lg-7">
                                <div class="table-responsive">
                                    <table class="table align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="text-uppercase small text-muted">Car</th>
                                                <th class="text-uppercase small text-muted">Contracts</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($topBrands as $car)
                                                <tr>
                                                    <td class="fw-semibold">{{ $car['brand'] }}</td>
                                                    <td class="fw-bold">{{ $car['total'] }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="2" class="text-center text-muted">No contract history available yet.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @php
            $dashboardMetrics = [
                'revenue' => $revenueTrend,
                'statusTrend' => $contractStatusTrend,
                'fleet' => $fleetBreakdown,
                'topBrands' => $topBrandsChart,
            ];
        @endphp

        <script type="application/json" id="dashboard-metrics-data">{!! json_encode($dashboardMetrics, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
    @endcannot
    </div>
@endif
</div>

@once
    @push('styles')
        @include('livewire.pages.panel.expert.dashboard.partials.styles')
    @endpush
@endonce

@once
    @push('scripts')
        @include('livewire.pages.panel.expert.dashboard.partials.scripts')
    @endpush
@endonce
