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
                                    <a href="{{ route('rental-requests.edit', $contract->id) }}" class="btn btn-sm btn-outline-dark"><i class="bi bi-eye"></i></a>
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
                            <span class="badge bg-success bg-opacity-10 text-success">Action</span>
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
                                    <a href="{{ route('rental-requests.edit', $contract->id) }}" class="btn btn-sm btn-outline-success"><i class="bi bi-arrow-right"></i></a>
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
        </div>

        <div class="row g-4 mb-4">
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
                                            <span class="badge bg-secondary">{{ ucfirst($contract->current_status) }}</span>
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
