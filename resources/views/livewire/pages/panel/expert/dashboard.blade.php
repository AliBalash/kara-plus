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
                        <div class="text-muted small mb-1"><i class="bx bx-time me-1"></i>{{ $taskMoment->format('d M Y · H:i') }}</div>
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
                                    <div class="text-muted small"><i class="bx bx-time me-1"></i>{{ \Carbon\Carbon::parse($pickup->pickup_date)->format('d M · H:i') }}</div>
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
                                    <div class="text-muted small"><i class="bx bx-time me-1"></i>{{ \Carbon\Carbon::parse($returnContract->return_date)->format('d M · H:i') }}</div>
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
                    'label' => 'Total Discount Codes',
                    'value' => $discountCodesCount,
                    'icon' => 'ticket-perforated-fill',
                    'color' => 'primary',
                ],
                [
                    'label' => 'Used Discount Codes',
                    'value' => "$usedDiscountCodes ($usageRate%)",
                    'icon' => 'check-circle-fill',
                    'color' => 'success',
                ],
                [
                    'label' => 'Average Discount %',
                    'value' => $averageDiscount ? number_format($averageDiscount, 1) . '%' : '0%',
                    'icon' => 'percent',
                    'color' => 'info',
                ],
                [
                    'label' => 'My Discount Codes',
                    'value' => $userDiscountCodes
                        ? $userUsedDiscountCodes . ' used / ' . $userDiscountCodes . ' total'
                        : '0',
                    'icon' => 'gift-fill',
                    'color' => 'warning',
                ],
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

            $usagePercent = min(max($usageRate, 0), 100);

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
            $fleetBooked = (int) ($fleetStatusSummary['booked'] ?? 0);
            $fleetUnavailable = (int) ($fleetStatusSummary['unavailable'] ?? 0);
            $fleetReservations = (int) ($fleetStatusSummary['active_reservations'] ?? 0);
            $fleetUpcomingPickups = (int) ($fleetStatusSummary['upcoming_pickups'] ?? 0);
            $fleetTotal = (int) ($fleetStatusSummary['total'] ?? 0);
            $fleetAvailabilityRate = (int) ($fleetStatusSummary['availability_rate'] ?? 0);
            $fleetScopeLabel = 'Our Fleet';

            $fleetSummaryCards = [
                [
                    'label' => 'Available Cars',
                    'value' => $fleetAvailable,
                    'hint' => 'Ready for pickup now',
                    'icon' => 'bi bi-check2-circle',
                    'tone' => 'available',
                ],
                [
                    'label' => 'Unavailable Cars',
                    'value' => $fleetUnavailable,
                    'hint' => 'Maintenance or blocked',
                    'icon' => 'bi bi-slash-circle',
                    'tone' => 'unavailable',
                ],
                [
                    'label' => 'Booked Cars',
                    'value' => $fleetBooked,
                    'hint' => 'Reserved or pre-reserved',
                    'icon' => 'bi bi-calendar2-check',
                    'tone' => 'booked',
                ],
                [
                    'label' => 'Reservations',
                    'value' => $fleetReservations,
                    'hint' => 'Active reservation contracts',
                    'icon' => 'bi bi-journal-check',
                    'tone' => 'reservations',
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
                    <span class="badge bg-light text-dark px-3 py-2"><i class="bi bi-clock-history me-1"></i>Updated {{ now()->format('d M Y - H:i') }}</span>
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
                            Availability <strong>{{ $fleetAvailabilityRate }}%</strong>
                        </span>
                        <span class="fleet-status-hero__pill">
                            <i class="bi bi-calendar-week me-1"></i>
                            Upcoming Pickups <strong>{{ number_format($fleetUpcomingPickups) }}</strong>
                        </span>
                    </div>
                </div>

                <div class="row g-3">
                    @foreach ($fleetSummaryCards as $summaryCard)
                        <div class="col-12 col-sm-6 col-xl-3">
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
            </div>
        </div>

        <div class="card shadow-lg border-0 rounded-4 mb-4">
            <div class="card-header border-0 bg-transparent pt-4 px-4 d-flex flex-wrap gap-3 justify-content-between align-items-center">
                <div>
                    <h5 class="fw-bold mb-1"><i class="bi bi-ev-front text-primary me-2"></i>Fleet Inventory</h5>
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
                                <option value="available">Available Only</option>
                                <option value="available_pre_reserved">Available + Pre-Reserved</option>
                                <option value="unavailable">Unavailable</option>
                            </select>

                            <select class="form-select form-select-sm" wire:model.defer="availableBrand">
                                <option value="all">All Brands</option>
                                @foreach ($availableBrands as $brand)
                                    <option value="{{ $brand }}">{{ $brand }}</option>
                                @endforeach
                            </select>
                            <select class="form-select form-select-sm" wire:model.defer="availableSort">
                                <option value="returned_latest">Sort: Latest Return</option>
                                <option value="returned_oldest">Sort: Oldest Return</option>
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
                        wire:key="available-fleet-table-{{ $availableFleetScope }}-{{ $availableReadiness }}-{{ $availableBrand }}-{{ $availableSort }}-{{ md5((string) $availableSearch) }}"
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
                                    <th scope="col">Plate</th>
                                    <th scope="col">Returned At</th>
                                    <th scope="col">Availability</th>
                                    <th scope="col">Next Reservation</th>
                                    <th scope="col">Last Service</th>
                                    <th scope="col" class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($availableCars as $car)
                                    @php
                                        $brand = optional($car->carModel)->brand;
                                        $model = optional($car->carModel)->model;
                                        $serviceDue = $car->service_due_date ? \Carbon\Carbon::parse($car->service_due_date)->format('d M Y') : 'N/A';
                                        $upcomingReservation = $car->upcomingReservation;
                                        $returnedAt = $car->latest_returned_at ? \Carbon\Carbon::parse($car->latest_returned_at) : null;
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ trim(($brand ? $brand . ' ' : '') . ($model ?? 'Vehicle')) }}</div>
                                            <div class="text-muted small">{{ ucfirst($car->color ?? '—') }} · {{ $car->manufacturing_year ?? '—' }}</div>
                                        </td>
                                        <td>
                                            <x-car-ownership-badge :car="$car" />
                                        </td>
                                        <td>{{ $car->plate_number ?? '—' }}</td>
                                        <td>
                                            @if ($returnedAt)
                                                <div class="fw-semibold">{{ $returnedAt->format('d M Y · H:i') }}</div>
                                                <div class="text-muted small">{{ $returnedAt->diffForHumans() }}</div>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($car->status === 'pre_reserved' && $car->availability)
                                                <span class="badge bg-info-subtle text-info">Available now · booked next</span>
                                            @elseif ($car->status === 'available' && $car->availability)
                                                <span class="badge bg-success-subtle text-success">Ready to rent</span>
                                            @else
                                                <span class="badge bg-danger-subtle text-danger">Unavailable</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($upcomingReservation)
                                                <div class="d-flex flex-column gap-1">
                                                    <div class="fw-semibold">
                                                        {{ optional($upcomingReservation->pickup_date)->format('d M Y · H:i') }}
                                                    </div>
                                                    <div class="text-muted small">
                                                        <i class="bi bi-geo-alt me-1"></i>{{ $upcomingReservation->pickup_location ?? 'Location TBD' }}
                                                    </div>
                                                </div>
                                            @else
                                                <span class="badge bg-success-subtle text-success">No upcoming booking</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-primary-subtle text-primary">{{ $serviceDue }}</span>
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('car.edit', $car->id) }}" class="btn btn-sm btn-outline-primary">
                                                <i class="bx bx-edit"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
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
                                                    <span class="booking-timeline__date">{{ $pickupAt ? $pickupAt->format('d M Y · H:i') : 'TBD' }}</span>
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
                                                    <span class="booking-timeline__date">{{ $returnAt ? $returnAt->format('d M Y · H:i') : 'TBD' }}</span>
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
                                                    <span class="booking-timeline__date">{{ $pickupAt ? $pickupAt->format('d M Y · H:i') : 'TBD' }}</span>
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
                                                    <span class="booking-timeline__date">{{ $returnAt ? $returnAt->format('d M Y · H:i') : 'TBD' }}</span>
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

        <div class="row g-3 row-cols-1 row-cols-sm-2 row-cols-xl-4 mb-4">
            @foreach ($stats as $stat)
                <div class="col">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body d-flex align-items-center gap-3">
                            <span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-{{ $stat['color'] }} bg-opacity-10 text-{{ $stat['color'] }}" style="width: 52px; height: 52px;">
                                <i class="bi bi-{{ $stat['icon'] }} fs-4"></i>
                            </span>
                            <div class="flex-grow-1">
                                <div class="text-muted small text-uppercase">{{ $stat['label'] }}</div>
                                <div class="fs-5 fw-bold mt-1">{{ $stat['value'] }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="row g-3 row-cols-2 row-cols-lg-4 mb-4">
            @foreach ($insights as $insight)
                <div class="col">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body d-flex align-items-center gap-3">
                            <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-{{ $insight['color'] }} bg-opacity-10 text-{{ $insight['color'] }}" style="width: 48px; height: 48px;">
                                <i class="bi bi-{{ $insight['icon'] }} fs-5"></i>
                            </span>
                            <div>
                                <div class="text-muted small text-uppercase">{{ $insight['label'] }}</div>
                                <div class="fw-semibold fs-6 mt-1">{{ $insight['value'] }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>


        <div class="row g-4 mb-4">
            <div class="col-12 col-xxl-5">
                <div class="card shadow-lg border-0 rounded-4 h-100">
                    <div class="card-header border-0 bg-transparent pt-4 px-4">
                        <h5 class="fw-bold mb-1"><i class="bi bi-percent text-warning me-2"></i>Discount Momentum</h5>
                        <span class="text-muted small">Creation vs usage trend</span>
                    </div>
                    <div class="card-body pt-0 pb-4 px-4 d-flex flex-column gap-4">
                        <div id="discountUsageChart" style="min-height: 280px;" wire:ignore></div>
                        <div class="d-flex flex-column gap-2">
                            @forelse ($latestDiscountCodes as $code)
                                <div class="d-flex align-items-center justify-content-between border rounded-4 px-3 py-2">
                                    <div>
                                        <div class="fw-semibold">{{ $code->code }}</div>
                                        <div class="text-muted small">{{ $code->created_at->format('d M Y') }}</div>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge {{ $code->contacted ? 'bg-success' : 'bg-light text-dark' }}">{{ $code->contacted ? 'Used' : 'Unused' }}</span>
                                        <div class="text-warning fw-semibold">{{ $code->discount_percentage }}%</div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center text-muted py-3">No discount codes generated yet.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-xxl-7">
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
                                            <span class="text-muted small"><i class="bi bi-calendar-event me-1"></i>{{ $contract->created_at->format('d M Y') }}</span>
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
                'discount' => $discountTrend,
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
