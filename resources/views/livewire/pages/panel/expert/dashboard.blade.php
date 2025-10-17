@php
    $isDriver = auth()->user()?->hasRole('driver');
@endphp

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
                        <h5 class="mt-2 mb-1">{{ optional($taskContract->car)->fullName() ?? 'Vehicle' }}</h5>
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
                                    <h6 class="mb-1">{{ optional($pickup->car)->fullName() ?? 'Vehicle' }}</h6>
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
                                    <h6 class="mb-1">{{ optional($returnContract->car)->fullName() ?? 'Vehicle' }}</h6>
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
    </div>

    @once
        @push('styles')
            <style>
                .driver-dashboard .next-task-card {
                    border: 1px solid #e0e6ef;
                    border-radius: 1rem;
                    background: #f7f9fc;
                    padding: 1.1rem 1.25rem;
                    box-shadow: 0 10px 24px rgba(32, 56, 90, 0.12);
                    min-height: 170px;
                }

                .next-task-label {
                    display: inline-flex;
                    align-items: center;
                    gap: 0.35rem;
                    padding: 0.25rem 0.7rem;
                    border-radius: 999px;
                    font-size: 0.7rem;
                    font-weight: 600;
                    text-transform: uppercase;
                    letter-spacing: 0.08em;
                }

                .next-task-label--pickup {
                    background: rgba(58, 134, 255, 0.15);
                    color: #1f57d6;
                }

                .next-task-label--return {
                    background: rgba(46, 204, 113, 0.2);
                    color: #1f8a49;
                }

                .next-task-label--idle {
                    background: rgba(133, 146, 163, 0.18);
                    color: #5c6b7a;
                }

                .driver-stat-card {
                    display: flex;
                    align-items: center;
                    gap: 0.75rem;
                    border-radius: 1rem;
                    padding: 0.85rem 1rem;
                    border: 1px solid rgba(224, 230, 239, 0.7);
                    background: #fff;
                    box-shadow: 0 8px 20px rgba(32, 56, 90, 0.06);
                    min-height: 92px;
                }

                .driver-stat-card .stat-icon {
                    width: 2.4rem;
                    height: 2.4rem;
                    border-radius: 50%;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 1.2rem;
                    color: inherit;
                    background: rgba(33, 56, 90, 0.08);
                }

                .driver-stat-card .stat-meta {
                    display: flex;
                    flex-direction: column;
                    gap: 0.2rem;
                }

                .driver-stat-card .stat-value {
                    font-weight: 700;
                    font-size: 1.2rem;
                }

                .driver-stat-card .stat-label {
                    font-size: 0.78rem;
                    text-transform: uppercase;
                    letter-spacing: 0.06em;
                    color: #6f7f92;
                }

                .driver-stat-card.accent-pickup {
                    border-color: rgba(58, 134, 255, 0.25);
                    color: #1f57d6;
                }

                .driver-stat-card.accent-return {
                    border-color: rgba(46, 204, 113, 0.3);
                    color: #1f8a49;
                }

                .driver-stat-card.accent-active {
                    border-color: rgba(17, 138, 178, 0.28);
                    color: #107dac;
                }

                .driver-stat-card.accent-warning {
                    border-color: rgba(255, 152, 0, 0.28);
                    color: #c17200;
                }

                .driver-task-card .task-item {
                    padding: 0.9rem 0;
                    border-bottom: 1px dashed rgba(224, 230, 239, 0.8);
                }

                .driver-task-card .task-item:last-child {
                    border-bottom: none;
                }

                .driver-task-card .task-info h6 {
                    font-weight: 600;
                }

                @media (max-width: 575.98px) {
                    .driver-stat-card {
                        padding: 0.75rem 0.9rem;
                    }

                    .driver-dashboard .next-task-card {
                        min-height: 150px;
                    }
                }
            </style>
        @endpush
    @endonce
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
                                $carModel = optional(optional($contract->car)->carModel);
                                $carImage = $carModel && $carModel->image
                                    ? asset('assets/car-pics/' . $carModel->image->file_name)
                                    : asset('assets/car-pics/car test.webp');
                            @endphp
                            <div class="border rounded-4 p-3 mb-3 shadow-sm">
                                <div class="d-flex align-items-center gap-3">
                                    <img src="{{ $carImage }}" alt="Car" class="rounded-3" style="width: 70px; height: 70px; object-fit: cover;">
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <h6 class="fw-bold mb-0">{{ optional($contract->car)->fullname() ?? 'Vehicle' }}</h6>
                                            <span class="badge bg-warning text-dark">Booking</span>
                                        </div>
                                        <div class="text-muted small mt-2">
                                            <span class="d-block"><i class="bi bi-calendar me-1"></i>{{ \Carbon\Carbon::parse($contract->pickup_date)->format('d M') }} - {{ \Carbon\Carbon::parse($contract->return_date)->format('d M') }}</span>
                                            <span class="d-block"><i class="bi bi-person-circle me-1"></i>{{ optional($contract->customer)->fullName() }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <span class="fw-bold text-success">${{ number_format($contract->total_price, 2) }}</span>
                                    <a href="{{ route('rental-requests.edit', $contract->id) }}" class="btn btn-sm btn-outline-dark"><i class="bx bx-show"></i></a>
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
                                $carModel = optional(optional($contract->car)->carModel);
                                $carImage = $carModel && $carModel->image
                                    ? asset('assets/car-pics/' . $carModel->image->file_name)
                                    : asset('assets/car-pics/car test.webp');
                            @endphp
                            <div class="border rounded-4 p-3 mb-3 shadow-sm">
                                <div class="d-flex align-items-center gap-3">
                                    <img src="{{ $carImage }}" alt="Car" class="rounded-3" style="width: 70px; height: 70px; object-fit: cover;">
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <h6 class="fw-bold mb-0">{{ optional($contract->car)->fullname() ?? 'Vehicle' }}</h6>
                                            <span class="badge bg-success">Return</span>
                                        </div>
                                        <div class="text-muted small mt-2">
                                            <span class="d-block"><i class="bi bi-calendar-check me-1"></i>{{ \Carbon\Carbon::parse($contract->pickup_date)->format('d M') }} - {{ \Carbon\Carbon::parse($contract->return_date)->format('d M') }}</span>
                                            <span class="d-block"><i class="bi bi-person-circle me-1"></i>{{ optional($contract->customer)->fullName() }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <span class="fw-bold text-success">${{ number_format($contract->total_price, 2) }} <small class="text-muted">AED</small></span>
                                    <a href="{{ route('rental-requests.edit', $contract->id) }}" class="btn btn-sm btn-outline-success"><i class="bx bx-right-arrow-alt"></i></a>
                                </div>
                            </div>
                        @empty
                            <div class="text-center text-muted py-4">No cars awaiting return.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-lg border-0 rounded-4 mb-4">
            <div class="card-header border-0 bg-transparent pt-4 px-4 d-flex flex-wrap gap-3 justify-content-between align-items-center">
                <div>
                    <h5 class="fw-bold mb-1"><i class="bi bi-ev-front text-primary me-2"></i>Available Fleet</h5>
                    <span class="text-muted small">{{ $availableCarsTotal }} vehicle{{ $availableCarsTotal === 1 ? '' : 's' }} ready for the next assignment</span>
                </div>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <div class="input-group input-group-sm" style="width: 220px;">
                        <span class="input-group-text bg-light border-0"><i class="bi bi-funnel"></i></span>
                        <select class="form-select border-0" wire:model.live="availableBrand">
                            <option value="all">All brands</option>
                            @foreach ($availableBrands as $brand)
                                <option value="{{ $brand }}">{{ $brand }}</option>
                            @endforeach
                        </select>
                    </div>
                    <a href="{{ route('car.list') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-card-checklist me-1"></i>Manage Cars
                    </a>
                </div>
            </div>
            <div class="card-body pt-0 px-0 pb-4">
                @if ($availableCars->isEmpty())
                    <div class="text-center text-muted py-5">No vehicles are currently marked as available.</div>
                @else
                    <div class="table-responsive" style="max-height: 360px;" data-simplebar>
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th scope="col">Vehicle</th>
                                    <th scope="col">Plate</th>
                                    <th scope="col">Year</th>
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
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ trim(($brand ? $brand . ' ' : '') . ($model ?? 'Vehicle')) }}</div>
                                            <div class="text-muted small">{{ ucfirst($car->color ?? '—') }}</div>
                                        </td>
                                        <td>{{ $car->plate_number ?? '—' }}</td>
                                        <td>{{ $car->manufacturing_year ?? '—' }}</td>
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

        {{-- <div class="row g-4 mb-4">
            <div class="col-12 col-xxl-8">
                <div class="card shadow-lg border-0 rounded-4 h-100">
                    <div class="card-header border-0 bg-transparent pt-4 px-4">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h5 class="fw-bold mb-1"><i class="bi bi-graph-up-arrow text-primary me-2"></i>Revenue & Volume</h5>
                                <span class="text-muted small">Rolling 6-month performance</span>
                            </div>
                            <div class="text-end">
                                <span class="text-muted small text-uppercase">This month</span>
                                <div class="h4 fw-bold mb-0">${{ number_format($currentMonthRevenue, 2) }}</div>
                                <div class="text-muted small">{{ now()->format('M Y') }} - {{ $currentMonthContracts }} contracts</div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body pt-0 pb-4 px-4">
                        <div class="row g-3 mb-4">
                            <div class="col-6 col-md">
                                <div class="text-muted small text-uppercase">Fleet utilization</div>
                                <div class="fw-semibold fs-6 mt-1">{{ $fleetUtilization }}%</div>
                            </div>
                            <div class="col-6 col-md">
                                <div class="text-muted small text-uppercase">Active vehicles</div>
                                <div class="fw-semibold fs-6 mt-1">{{ $activeVehicles }} / {{ $totalCars }}</div>
                            </div>
                            <div class="col-6 col-md">
                                <div class="text-muted small text-uppercase">Maintenance queue</div>
                                <div class="fw-semibold fs-6 mt-1">{{ $offlineVehicles }}</div>
                            </div>
                        </div>
                        <div id="revenueTrendChart" style="min-height: 320px;" wire:ignore></div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-xxl-4">
                <div class="card shadow-lg border-0 rounded-4 h-100">
                    <div class="card-header border-0 bg-transparent pt-4 px-4">
                        <h5 class="fw-bold mb-1"><i class="bi bi-speedometer2 text-success me-2"></i>Fleet Health</h5>
                        <span class="text-muted small">Availability across the fleet</span>
                    </div>
                    <div class="card-body pt-0 pb-4 px-4 d-flex flex-column align-items-center gap-3">
                        <div id="fleetDistributionChart" style="min-height: 300px; width: 100%;" wire:ignore></div>
                        <div class="row g-2 w-100 text-center">
                            <div class="col">
                                <span class="badge bg-success bg-opacity-10 text-success">{{ $activeVehicles }} Active</span>
                            </div>
                            <div class="col">
                                <span class="badge bg-info bg-opacity-10 text-info">{{ max($totalCars - ($activeVehicles + $offlineVehicles), 0) }} Available</span>
                            </div>
                            <div class="col">
                                <span class="badge bg-warning bg-opacity-10 text-warning">{{ $offlineVehicles }} Maintenance</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div> --}}

        {{-- <div class="row g-4 mb-4">
            <div class="col-12 col-xxl-8">
                <div class="card shadow-lg border-0 rounded-4 h-100">
                    <div class="card-header border-0 bg-transparent pt-4 px-4">
                        <h5 class="fw-bold mb-1"><i class="bi bi-arrow-repeat text-primary me-2"></i>Status Progression</h5>
                        <span class="text-muted small">How contracts move through each stage</span>
                    </div>
                    <div class="card-body pt-0 pb-4 px-4">
                        <div id="statusTrendChart" style="min-height: 320px;" wire:ignore></div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-xxl-4">
                <div class="card shadow-lg border-0 rounded-4 h-100">
                    <div class="card-header border-0 bg-transparent pt-4 px-4">
                        <h5 class="fw-bold mb-1"><i class="bi bi-clipboard-data text-info me-2"></i>Contract Snapshot</h5>
                        <span class="text-muted small">Live breakdown and personal activity</span>
                    </div>
                    <div class="card-body pt-0 pb-4 px-4 d-flex flex-column gap-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted small text-uppercase">Average total</span>
                                <div class="h4 fw-bold mb-0">${{ number_format($averageTotalPrice, 2) }}</div>
                            </div>
                            <div class="text-end">
                                <span class="text-muted small text-uppercase">Usage rate</span>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height: 6px; width: 110px;">
                                        <div class="progress-bar bg-info" role="progressbar" style="width: {{ $usagePercent }}%" aria-valuenow="{{ $usagePercent }}" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <span class="fw-semibold text-info">{{ $usagePercent }}%</span>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex flex-column gap-3">
                            @foreach ($contractSnapshot as $item)
                                <div>
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="fw-semibold">{{ $item['label'] }}</span>
                                        <span class="text-muted small">{{ $item['count'] }} contracts</span>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-{{ $item['color'] }}" role="progressbar" style="width: {{ min($item['percent'], 100) }}%" aria-valuenow="{{ $item['percent'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="border-top pt-3">
                            <div class="d-flex flex-column gap-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="text-muted small text-uppercase">My last contract</span>
                                        <div class="fw-bold">{{ $lastUserContractStatus ? ucfirst($lastUserContractStatus) : 'No activity yet' }}</div>
                                    </div>
                                    @if ($lastUserDiscountCode)
                                        <div class="text-end">
                                            <span class="text-muted small text-uppercase">Last discount used</span>
                                            <div class="badge bg-primary bg-opacity-10 text-primary">{{ $lastUserDiscountCode->code }}</div>
                                        </div>
                                    @endif
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="text-muted small text-uppercase">Overdue returns</span>
                                        <div class="fw-bold text-danger">{{ $overdueContracts }}</div>
                                    </div>
                                    <div class="text-end">
                                        <span class="text-muted small text-uppercase">Upcoming returns</span>
                                        <div class="fw-bold text-warning">{{ $upcomingReturns }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div> --}}

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
                            <span class="badge bg-primary bg-opacity-10 text-primary">Top 3</span>
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

@push('scripts')
<script>
(function () {
    const chartInstances = {};

    const ensureNumber = (value, fallback = 0) => {
        const numeric = Number(value);
        return Number.isFinite(numeric) ? numeric : fallback;
    };

    const normaliseSeries = (input) => {
        if (!Array.isArray(input)) {
            return [];
        }

        return input.map((value) => ensureNumber(value));
    };

    const formatCurrency = (value) => {
        const numeric = ensureNumber(value);
        return '$' + numeric.toLocaleString();
    };

    const formatInteger = (value) => {
        const numeric = ensureNumber(value);
        return numeric.toLocaleString();
    };

    const resolve = (source, path, fallback) => {
        let cursor = source;
        for (let index = 0; index < path.length; index++) {
            const key = path[index];
            if (!cursor || typeof cursor !== 'object' || !(key in cursor)) {
                return fallback;
            }
            cursor = cursor[key];
        }

        return cursor;
    };

    const getMetrics = () => {
        const metricsEl = document.getElementById('dashboard-metrics-data');
        if (!metricsEl) {
            return null;
        }

        try {
            return JSON.parse(metricsEl.textContent || '{}');
        } catch (error) {
            console.error('Failed to parse dashboard metrics payload', error);
            return null;
        }
    };

    const createChart = (selector, options) => {
        const element = document.querySelector(selector);
        if (!element || typeof ApexCharts === 'undefined') {
            return;
        }

        if (chartInstances[selector]) {
            chartInstances[selector].destroy();
            delete chartInstances[selector];
        }

        const chart = new ApexCharts(element, options);
        chart.render();
        chartInstances[selector] = chart;
    };

    const renderCharts = (data) => {
        if (typeof ApexCharts === 'undefined') {
            return;
        }

        const configColors = window.config && window.config.colors ? window.config.colors : {};
        const palette = {
            primary: configColors.primary || '#696cff',
            success: configColors.success || '#71dd37',
            warning: configColors.warning || '#ffab00',
            info: configColors.info || '#03c3ec',
            danger: configColors.danger || '#ff3e1d',
            secondary: configColors.secondary || '#8592a3',
        };
        const fallbackNoDataColor = palette.secondary || '#8592a3';
        const noData = {
            text: 'No data available',
            align: 'center',
            verticalAlign: 'middle',
            style: {
                color: fallbackNoDataColor,
                fontWeight: 500,
            }
        };

        const revenueLabels = resolve(data, ['revenue', 'labels'], []);
        const revenueSeries = normaliseSeries(resolve(data, ['revenue', 'revenue'], []));
        const contractsSeries = normaliseSeries(resolve(data, ['revenue', 'contracts'], []));

        createChart('#revenueTrendChart', {
            chart: {
                height: 320,
                type: 'line',
                stacked: false,
                toolbar: { show: false }
            },
            noData,
            series: [
                {
                    name: 'Revenue',
                    type: 'area',
                    data: revenueSeries
                },
                {
                    name: 'Contracts',
                    type: 'line',
                    data: contractsSeries
                }
            ],
            stroke: {
                width: [3, 3],
                curve: 'smooth'
            },
            dataLabels: { enabled: false },
            colors: [palette.primary, palette.info],
            fill: {
                type: ['gradient', 'solid'],
                gradient: {
                    shadeIntensity: 0.3,
                    opacityFrom: 0.4,
                    opacityTo: 0.05,
                    stops: [0, 90, 100]
                }
            },
            markers: {
                size: 4,
                strokeColors: '#fff',
                strokeWidth: 2,
                hover: { sizeOffset: 2 }
            },
            labels: revenueLabels,
            xaxis: {
                categories: revenueLabels,
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            yaxis: [
                {
                    labels: {
                        formatter: (val) => formatCurrency(val)
                    }
                },
                {
                    opposite: true,
                    labels: {
                        formatter: (val) => formatInteger(val)
                    }
                }
            ],
            tooltip: {
                shared: true,
                intersect: false,
                y: [
                    {
                        formatter: (val) => formatCurrency(val)
                    },
                    {
                        formatter: (val) => formatInteger(val)
                    }
                ]
            },
            grid: {
                borderColor: (palette.secondary || '#8592a3') + '33',
                strokeDashArray: 4,
                padding: { left: 12, right: 12 }
            },
            legend: {
                show: true,
                horizontalAlign: 'left',
                offsetY: 8
            }
        });

        const statusSeries = resolve(data, ['statusTrend'], []).map((series) => ({
            name: series && series.name ? series.name : '',
            data: normaliseSeries(series && series.data ? series.data : [])
        }));
        createChart('#statusTrendChart', {
            chart: {
                type: 'bar',
                height: 320,
                stacked: true,
                toolbar: { show: false }
            },
            noData,
            series: statusSeries,
            colors: [palette.primary, palette.success, palette.warning, palette.info, palette.danger],
            plotOptions: {
                bar: {
                    columnWidth: '45%',
                    borderRadius: 8,
                    endingShape: 'rounded'
                }
            },
            dataLabels: { enabled: false },
            xaxis: {
                categories: revenueLabels,
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            yaxis: {
                labels: {
                    formatter: (val) => formatInteger(val)
                }
            },
            grid: {
                borderColor: (palette.secondary || '#8592a3') + '33',
                strokeDashArray: 4
            },
            legend: {
                position: 'top',
                horizontalAlign: 'left'
            },
            tooltip: {
                shared: true,
                intersect: false,
                y: {
                    formatter: (val) => formatInteger(val)
                }
            }
        });

        const discountLabels = resolve(data, ['discount', 'labels'], []);
        const discountCreated = normaliseSeries(resolve(data, ['discount', 'created'], []));
        const discountUsed = normaliseSeries(resolve(data, ['discount', 'used'], []));
        createChart('#discountUsageChart', {
            chart: {
                type: 'line',
                height: 280,
                toolbar: { show: false }
            },
            noData,
            series: [
                {
                    name: 'Created',
                    type: 'area',
                    data: discountCreated
                },
                {
                    name: 'Used',
                    type: 'line',
                    data: discountUsed
                }
            ],
            colors: [palette.warning, palette.success],
            stroke: {
                width: [2, 3],
                curve: 'smooth'
            },
            dataLabels: { enabled: false },
            fill: {
                type: ['gradient', 'solid'],
                gradient: {
                    shadeIntensity: 0.2,
                    opacityFrom: 0.35,
                    opacityTo: 0.05,
                    stops: [0, 90, 100]
                }
            },
            xaxis: {
                categories: discountLabels,
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            yaxis: {
                labels: {
                    formatter: (val) => formatInteger(val)
                }
            },
            grid: {
                borderColor: (palette.secondary || '#8592a3') + '33',
                strokeDashArray: 4
            },
            legend: {
                position: 'top',
                horizontalAlign: 'left'
            },
            tooltip: {
                shared: true,
                intersect: false,
                y: {
                    formatter: (val) => formatInteger(val)
                }
            }
        });

        const fleetSeries = normaliseSeries(resolve(data, ['fleet', 'series'], []));
        const fleetLabels = resolve(data, ['fleet', 'labels'], []);
        createChart('#fleetDistributionChart', {
            chart: {
                type: 'donut',
                height: 300
            },
            noData,
            series: fleetSeries,
            labels: fleetLabels,
            colors: [palette.success, palette.info, palette.warning],
            stroke: {
                colors: ['transparent']
            },
            dataLabels: {
                enabled: true,
                formatter: (val) => ensureNumber(val).toFixed(1) + '%'
            },
            legend: {
                position: 'bottom'
            }
        });

        const topLabels = resolve(data, ['topBrands', 'labels'], []);
        const topSeries = normaliseSeries(resolve(data, ['topBrands', 'series'], []));
        createChart('#topModelsChart', {
            chart: {
                type: 'bar',
                height: 260,
                toolbar: { show: false }
            },
            noData,
            series: [
                {
                    name: 'Contracts',
                    data: topSeries
                }
            ],
            colors: [palette.primary],
            plotOptions: {
                bar: {
                    borderRadius: 8,
                    horizontal: true,
                    barHeight: '60%'
                }
            },
            dataLabels: { enabled: false },
            xaxis: {
                categories: topLabels,
                labels: {
                    formatter: (val) => formatInteger(val)
                }
            },
            grid: {
                borderColor: (palette.secondary || '#8592a3') + '33',
                strokeDashArray: 4
            }
        });
    };

    const boot = () => {
        const metrics = getMetrics();
        if (!metrics) {
            return;
        }

        renderCharts(metrics);
    };

    const registerLivewireHook = () => {
        if (!window.Livewire || registerLivewireHook.initialized) {
            return;
        }

        Livewire.hook('message.processed', (message, component) => {
            if (component && component.fingerprint && component.fingerprint.name === 'pages.panel.expert.dashboard') {
                boot();
            }
        });

        registerLivewireHook.initialized = true;
    };
    registerLivewireHook.initialized = false;

    const init = () => {
        boot();
        registerLivewireHook();
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    document.addEventListener('livewire:init', init);
    document.addEventListener('livewire:navigated', init);
})();
</script>
@endpush
