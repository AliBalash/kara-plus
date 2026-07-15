<?php

namespace App\Livewire\Pages\Panel\Expert;

use App\Models\Car;
use App\Models\Contract;
use App\Models\ContractStatus;
use App\Models\Insurance;
use App\Services\Reports\OperationsReportService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Dashboard extends Component
{
    public $title = 'Dashboard';

    public $totalContracts;
    public $activeContracts;
    public $completedContracts;
    public $cancelledContracts;
    public $underReviewContracts;
    public $averageTotalPrice;
    public $latestContracts;
    public $lastUserContractStatus;

    public $topBrands;
    public $reservedCars;
    public $returnedCars;

    public $revenueTrend;
    public $currentMonthRevenue;
    public $currentMonthContracts;
    public $contractStatusTrend;
    public $fleetBreakdown;
    public $fleetUtilization;
    public $totalCars;
    public $activeVehicles;
    public $offlineVehicles;
    public array $fleetStatusSummary = [
        'total' => 0,
        'available' => 0,
        'booked' => 0,
        'unavailable' => 0,
        'under_maintenance' => 0,
        'availability_rate' => 0,
        'active_reservations' => 0,
        'upcoming_pickups' => 0,
    ];
    public $averageRentalDuration;
    public $upcomingReturns;
    public $overdueContracts;
    public $serviceAlerts;
    public $topBrandsChart;

    public $isDriver = false;
    public $driverPickups;
    public $driverReturns;
    public $driverStats = [];
    public $driverNextTask = null;

    public $availableBrand = 'all';
    public array $availableBrands = [];
    public string $availableFleetScope = 'our';
    public string $availableReadiness = 'available';
    public string $availableReason = 'all';
    public string $availableSort = 'returned_oldest';
    public string $availableSearch = '';
    public string $monthlyContractsSearch = '';
    public string $monthlyContractsDateField = 'return_date';
    public string $monthlyContractsDateFrom = '';
    public string $monthlyContractsDateTo = '';
    public string $monthlyContractsOwnership = 'all';
    public array $monthlyContractsReport = [
        'summary' => [
            'total_monthly_contracts' => 0,
            'current_month_monthly_contracts' => 0,
            'ending_in_three_days_or_less' => 0,
            'table_results' => 0,
            'unique_customers' => 0,
        ],
        'filter_summary' => [],
        'rows' => [],
    ];
    public array $complianceReport = [
        'insurance' => [
            'summary' => [
                'due_this_month' => 0,
                'due_in_five_days' => 0,
            ],
            'rows' => [],
        ],
        'passing' => [
            'summary' => [
                'due_this_month' => 0,
                'due_in_five_days' => 0,
            ],
            'rows' => [],
        ],
    ];
    public array $fleetAttentionCars = [];

    private const AVAILABLE_FLEET_SCOPES = ['our', 'all', 'partners'];
    private const AVAILABLE_READINESS_FILTERS = [
        'all',
        'available',
        'available_pre_reserved',
        'pre_reserved',
        'reserved',
        'unavailable',
        'need_action',
        'sold',
    ];
    private const AVAILABLE_SORT_OPTIONS = [
        'returned_latest',
        'returned_oldest',
        'service_due_soon',
        'service_due_late',
        'year_newest',
        'year_oldest',
    ];
    private const MONTHLY_CONTRACT_OWNERSHIP_SCOPES = ['all', 'company', 'golden_key', 'liverpool', 'safe_drive', 'other'];
    private const MONTHLY_CONTRACT_DATE_FIELDS = ['created_at', 'pickup_date', 'return_date'];


    public function mount()
    {
        $this->isDriver = Auth::user()?->hasRole('driver');

        if ($this->isDriver) {
            $this->prepareDriverDashboard();
            return;
        }

        // آمار کلی قراردادها
        $this->totalContracts = Contract::count();
        $this->activeContracts = Contract::whereIn('current_status', ['assigned', 'under_review', 'delivery'])->count();
        $this->completedContracts = Contract::where('current_status', 'complete')->count();
        $this->cancelledContracts = Contract::where('current_status', 'cancelled')->count();
        $this->underReviewContracts = Contract::where('current_status', 'under_review')->count();

        // میانگین قیمت کل قراردادها
        $this->averageTotalPrice = Contract::avg('total_price');

        // دریافت آخرین ۵ قرارداد
        $this->latestContracts = Contract::orderBy('created_at', 'desc')->take(5)->get();

        // دریافت وضعیت آخرین قرارداد کاربر جاری
        $lastContract = Contract::where('user_id', Auth::id())->latest()->first();
        $this->lastUserContractStatus = $lastContract ? $lastContract->current_status : null;



        // دریافت ۳ خودرو با بیشترین قرارداد
        $topCars = Contract::selectRaw('car_id, COUNT(*) as total')
            ->groupBy('car_id')
            ->orderByDesc('total')
            ->take(3)
            ->with('car')
            ->get();
        // تبدیل لیست برای نمایش در ویو
        $this->topBrands = $topCars->map(function ($contract) {
            $carName = optional($contract->car)->fullName();

            return [
                'brand' => $carName ?? 'Unknown',
                'total' => (int) $contract->total,
            ];
        })->values();

        $this->topBrandsChart = [
            'labels' => $this->topBrands->pluck('brand')->all(),
            'series' => $this->topBrands->pluck('total')->all(),
        ];


        $this->reservedCars = Contract::with(['car.carModel', 'customer', 'pickupDocument'])
            ->where('current_status', 'reserved')
            ->latest()
            ->get();

        $this->returnedCars = Contract::with(['car.carModel', 'customer', 'pickupDocument'])
            ->where('current_status', 'awaiting_return')
            ->latest()
            ->get();

        $this->buildFleetStatusSummary();
        $this->buildAnalytics();
        $this->buildFleetAttentionCars();
        $this->normalizeAvailableFleetFilters();
        $this->prepareAvailableBrands();
    }


    public function render()
    {
        $data = ['title' => $this->title];

        if (! $this->isDriver) {
            $this->normalizeAvailableFleetFilters();
            $this->buildFleetStatusSummary();
            $this->buildFleetAttentionCars();
            $this->prepareAvailableBrands();
            $this->loadMonthlyContractsReport();
            $this->buildComplianceReport();
            $data['availableCars'] = $this->availableCars;
            $data['availableBrands'] = $this->availableBrands;
            $data['availableCarsTotal'] = $this->availableCarsTotal;
        }

        return view('livewire.pages.panel.expert.dashboard', $data);
    }

    public $count = 1;

    public function increment()
    {
        $this->count++;
    }

    public function decrement()
    {
        $this->count--;
    }


    protected function buildAnalytics(): void
    {
        $months = collect(range(0, 5))
            ->map(fn ($i) => Carbon::now()->subMonths($i)->startOfMonth())
            ->sort()
            ->values();

        $monthKeys = $months->map->format('Y-m');
        $monthLabels = $months->map->format('M');

        $monthBucketPickupDate = $this->monthBucketSelect('pickup_date');
        $monthBucketCreatedAt = $this->monthBucketSelect('created_at');

        $revenueRaw = Contract::whereNotNull('pickup_date')
            ->whereBetween('pickup_date', [$months->first(), $months->last()->copy()->endOfMonth()])
            ->selectRaw("{$monthBucketPickupDate} as month, SUM(total_price) as total")
            ->groupBy('month')
            ->pluck('total', 'month');

        $contractRaw = Contract::whereBetween('created_at', [$months->first(), $months->last()->copy()->endOfMonth()])
            ->selectRaw("{$monthBucketCreatedAt} as month, COUNT(*) as total")
            ->groupBy('month')
            ->pluck('total', 'month');

        $revenueSeries = $monthKeys->map(fn ($month) => round((float) ($revenueRaw[$month] ?? 0), 2));
        $contractsSeries = $monthKeys->map(fn ($month) => (int) ($contractRaw[$month] ?? 0));

        $this->revenueTrend = [
            'labels' => $monthLabels->all(),
            'revenue' => $revenueSeries->all(),
            'contracts' => $contractsSeries->all(),
        ];

        $this->currentMonthRevenue = $revenueSeries->last() ?? 0;
        $this->currentMonthContracts = $contractsSeries->last() ?? 0;

        $statusGrouping = [
            'Reserved' => ['reserved'],
            'Active' => ['assigned', 'under_review', 'delivery'],
            'Awaiting return' => ['awaiting_return'],
            'Completed' => ['complete'],
            'Cancelled' => ['cancelled'],
        ];

        $statusRaw = Contract::whereBetween('created_at', [$months->first(), $months->last()->copy()->endOfMonth()])
            ->selectRaw("{$monthBucketCreatedAt} as month, current_status, COUNT(*) as total")
            ->groupBy('month', 'current_status')
            ->get()
            ->groupBy('month');

        $this->contractStatusTrend = collect($statusGrouping)->map(function ($statuses, $label) use ($monthKeys, $statusRaw) {
            $data = $monthKeys->map(function ($month) use ($statusRaw, $statuses) {
                $monthBucket = $statusRaw[$month] ?? collect();
                return (int) $monthBucket->whereIn('current_status', $statuses)->sum('total');
            });

            return [
                'name' => $label,
                'data' => $data->all(),
            ];
        })->values()->all();

        $this->totalCars = Car::count();
        $this->offlineVehicles = Car::query()->byOperationalStatus(Car::STATUS_UNAVAILABLE)->count()
            + Car::query()->byOperationalStatus(Car::STATUS_SOLD)->count();

        $activeVehicleIds = Contract::whereIn('current_status', Car::reservingStatuses())
            ->whereNotNull('pickup_date')
            ->where('pickup_date', '<=', Carbon::now())
            ->where(function ($query) {
                $query->whereNull('return_date')
                    ->orWhere('return_date', '>=', Carbon::now());
            })
            ->pluck('car_id')
            ->filter()
            ->unique();

        $this->activeVehicles = $activeVehicleIds->count();

        $this->fleetUtilization = $this->totalCars > 0
            ? round(($this->activeVehicles / $this->totalCars) * 100)
            : 0;

        $this->fleetBreakdown = [
            'labels' => ['Available now', 'Upcoming booking', 'Active booking', 'Unavailable / Sold'],
            'series' => [
                (int) ($this->fleetStatusSummary['available'] ?? 0),
                (int) ($this->fleetStatusSummary['pre_reserved'] ?? 0),
                (int) ($this->fleetStatusSummary['reserved'] ?? 0),
                (int) (($this->fleetStatusSummary['unavailable'] ?? 0) + ($this->fleetStatusSummary['sold'] ?? 0)),
            ],
        ];

        $this->averageRentalDuration = (float) (Contract::whereNotNull('pickup_date')
            ->selectRaw($this->averageRentalDurationSelect())
            ->value('avg_days') ?? 0);

        $this->upcomingReturns = Contract::whereNotNull('return_date')
            ->whereBetween('return_date', [Carbon::now(), Carbon::now()->addDays(7)])
            ->whereIn('current_status', ['reserved', 'awaiting_return', 'delivery'])
            ->count();

        $this->overdueContracts = Contract::whereNotNull('return_date')
            ->where('return_date', '<', Carbon::now())
            ->whereIn('current_status', ['reserved', 'awaiting_return', 'delivery'])
            ->count();

        $this->serviceAlerts = Car::needsService()->count();
    }

    protected function prepareDriverDashboard(): void
    {
        $userId = Auth::id();
        $now = Carbon::now();

        $this->driverPickups = Contract::with(['car.carModel', 'customer'])
            ->where('delivery_driver_id', $userId)
            ->whereIn('current_status', ['reserved', 'delivery', 'assigned'])
            ->whereNotNull('pickup_date')
            ->where('pickup_date', '>=', $now->copy()->startOfDay())
            ->orderBy('pickup_date')
            ->take(6)
            ->get();

        $this->driverReturns = Contract::with(['car.carModel', 'customer'])
            ->where('return_driver_id', $userId)
            ->whereIn('current_status', ['awaiting_return', 'delivery'])
            ->whereNotNull('return_date')
            ->where('return_date', '>=', $now->copy()->startOfDay())
            ->orderBy('return_date')
            ->take(6)
            ->get();

        $assignedStatuses = ['reserved', 'delivery', 'awaiting_return', 'assigned'];

        $activeAssignmentsQuery = Contract::query()
            ->whereIn('current_status', $assignedStatuses)
            ->where(function ($query) use ($userId) {
                $query->where('delivery_driver_id', $userId)
                    ->orWhere('return_driver_id', $userId);
            });

        $this->driverStats = [
            'pickupsToday' => Contract::where('delivery_driver_id', $userId)
                ->whereIn('current_status', ['reserved', 'delivery', 'assigned'])
                ->whereDate('pickup_date', $now->toDateString())
                ->count(),
            'returnsToday' => Contract::where('return_driver_id', $userId)
                ->whereIn('current_status', ['awaiting_return', 'delivery'])
                ->whereDate('return_date', $now->toDateString())
                ->count(),
            'activeAssignments' => $activeAssignmentsQuery->count(),
            'overdueReturns' => Contract::where('return_driver_id', $userId)
                ->where('current_status', 'awaiting_return')
                ->where('return_date', '<', $now)
                ->count(),
        ];

        $nextPickup = Contract::with(['car.carModel', 'customer'])
            ->where('delivery_driver_id', $userId)
            ->whereIn('current_status', ['reserved', 'delivery', 'assigned'])
            ->whereNotNull('pickup_date')
            ->where('pickup_date', '>=', $now)
            ->orderBy('pickup_date')
            ->first();

        $nextReturn = Contract::with(['car.carModel', 'customer'])
            ->where('return_driver_id', $userId)
            ->whereIn('current_status', ['awaiting_return', 'delivery'])
            ->whereNotNull('return_date')
            ->where('return_date', '>=', $now)
            ->orderBy('return_date')
            ->first();

        $nextTasks = collect([
            $nextPickup ? [
                'type' => 'pickup',
                'contract' => $nextPickup,
                'datetime' => Carbon::parse($nextPickup->pickup_date),
            ] : null,
            $nextReturn ? [
                'type' => 'return',
                'contract' => $nextReturn,
                'datetime' => Carbon::parse($nextReturn->return_date),
            ] : null,
        ])->filter()->sortBy('datetime');

        $this->driverNextTask = $nextTasks->first();
    }

    protected function buildFleetStatusSummary(): void
    {
        $summaryScope = 'our';

        $carsInScope = Car::query();
        $this->applyAvailableFleetScope($carsInScope, $summaryScope);

        $total = (int) (clone $carsInScope)->count('cars.id');
        $available = (int) $this->applyAvailableFleetReadiness(
            $this->baseAvailableFleetQuery($summaryScope),
            'available'
        )->count('cars.id');
        $preReserved = (int) (clone $carsInScope)->byOperationalStatus(Car::STATUS_PRE_RESERVED)->count('cars.id');
        $reserved = (int) (clone $carsInScope)->byOperationalStatus(Car::STATUS_RESERVED)->count('cars.id');
        $underMaintenance = (int) (clone $carsInScope)->byOperationalStatus(Car::LEGACY_STATUS_UNDER_MAINTENANCE)->count('cars.id');
        $booked = $reserved + $preReserved;
        $unavailable = (int) (clone $carsInScope)->byOperationalStatus(Car::STATUS_UNAVAILABLE)->count('cars.id');
        $sold = (int) (clone $carsInScope)->byOperationalStatus(Car::STATUS_SOLD)->count('cars.id');
        $supportsReasonColumn = Car::hasStatusSupportColumn('unavailability_reason');
        $needAction = $supportsReasonColumn
            ? (int) (clone $carsInScope)->byUnavailabilityReason(Car::UNAVAILABILITY_REASON_NEED_ACTION)->count('cars.id')
            : 0;
        $manualUnavailable = max($unavailable - $needAction, 0);
        $reasonBreakdown = $supportsReasonColumn
            ? collect(Car::operationalUnavailabilityReasonLabels())
                ->mapWithKeys(function (string $label, string $reason) use ($carsInScope) {
                    return [$reason => [
                        'label' => $label,
                        'count' => (int) (clone $carsInScope)->byUnavailabilityReason($reason)->count('cars.id'),
                    ]];
                })
                ->filter(fn (array $item) => $item['count'] > 0)
                ->all()
            : [];

        $reservationStatuses = array_values(array_diff(Car::reservingStatuses(), ['pending']));

        $reservationsInScope = Contract::query()
            ->whereIn('current_status', $reservationStatuses);
        $this->applyAvailableFleetScopeToContracts($reservationsInScope, $summaryScope);

        $activeReservations = (int) (clone $reservationsInScope)
            ->whereNotNull('pickup_date')
            ->where('pickup_date', '<=', Carbon::now())
            ->where(function (Builder $query) {
                $query->whereNull('return_date')
                    ->orWhere('return_date', '>=', Carbon::now());
            })
            ->count();

        $upcomingPickups = (int) (clone $reservationsInScope)
            ->whereNotNull('pickup_date')
            ->where('pickup_date', '>', Carbon::now())
            ->count();

        $this->fleetStatusSummary = [
            'total' => $total,
            'available' => $available,
            'pre_reserved' => $preReserved,
            'reserved' => $reserved,
            'booked' => $booked,
            'unavailable' => $unavailable,
            'manual_unavailable' => $manualUnavailable,
            'need_action' => $needAction,
            'sold' => $sold,
            'under_maintenance' => $underMaintenance,
            'availability_rate' => $total > 0 ? (int) round(($available / $total) * 100) : 0,
            'dispatchable_rate' => $total > 0 ? (int) round((($available + $preReserved) / $total) * 100) : 0,
            'active_reservations' => $activeReservations,
            'upcoming_pickups' => $upcomingPickups,
            'reason_breakdown' => $reasonBreakdown,
        ];
    }

    protected function buildFleetAttentionCars(): void
    {
        $supportsReasonColumn = Car::hasStatusSupportColumn('unavailability_reason');

        $query = Car::query()
            ->with(['carModel', 'currentContract.customer', 'upcomingReservation.customer'])
            ->select('cars.*', 'latest_returns.latest_returned_at')
            ->leftJoinSub($this->latestReturnedAtSubquery(), 'latest_returns', function ($join) {
                $join->on('latest_returns.car_id', '=', 'cars.id');
            });

        if (Car::supportsScheduledUnavailabilityPeriods()) {
            $query->with('unavailabilityPeriods');
        }

        $this->applyAvailableFleetScope($query, 'our');

        $cars = $query
            ->where(function (Builder $builder) use ($supportsReasonColumn) {
                if ($supportsReasonColumn) {
                    $builder->where(function (Builder $needActionBuilder) {
                        $needActionBuilder->byUnavailabilityReason(Car::UNAVAILABILITY_REASON_NEED_ACTION);
                    })->orWhere(function (Builder $unavailableBuilder) {
                        $unavailableBuilder->byOperationalStatus(Car::STATUS_UNAVAILABLE);
                    })->orWhere(function (Builder $soldBuilder) {
                        $soldBuilder->byOperationalStatus(Car::STATUS_SOLD);
                    });

                    return;
                }

                $builder->where(function (Builder $unavailableBuilder) {
                    $unavailableBuilder->byOperationalStatus(Car::STATUS_UNAVAILABLE);
                })->orWhere(function (Builder $soldBuilder) {
                    $soldBuilder->byOperationalStatus(Car::STATUS_SOLD);
                });
            })
            ->orderByDesc('cars.updated_at')
            ->limit(6)
            ->get();

        if ($supportsReasonColumn) {
            $cars = $cars->sortBy(function (Car $car) {
                $priority = match (true) {
                    $car->status === Car::STATUS_UNAVAILABLE && $car->unavailability_reason === Car::UNAVAILABILITY_REASON_NEED_ACTION => '0',
                    $car->status === Car::STATUS_UNAVAILABLE => '1',
                    $car->status === Car::STATUS_SOLD => '2',
                    default => '3',
                };

                $updatedAt = $car->updated_at?->getTimestamp() ?? 0;

                return $priority . '-' . str_pad((string) (9999999999 - $updatedAt), 10, '0', STR_PAD_LEFT);
            })->values();
        }

        $this->fleetAttentionCars = collect($cars)
            ->map(fn (Car $car) => $this->transformFleetAttentionCar($car))
            ->values()
            ->all();
    }

    protected function prepareAvailableBrands(): void
    {
        $brands = $this->availableFleetQuery(
            applyBrandFilter: false,
            applySearchFilter: false
        )
            ->join('car_models as available_car_models', 'available_car_models.id', '=', 'cars.car_model_id')
            ->whereNotNull('available_car_models.brand')
            ->select('available_car_models.brand')
            ->distinct()
            ->orderBy('available_car_models.brand')
            ->pluck('available_car_models.brand')
            ->filter()
            ->values()
            ->all();

        $this->availableBrands = $brands;

        if ($this->availableBrand !== 'all' && ! in_array($this->availableBrand, $this->availableBrands, true)) {
            $this->availableBrand = 'all';
        }
    }

    public function getAvailableCarsProperty()
    {
        $query = $this->availableFleetQuery()
            ->with(['carModel', 'currentContract.customer'])
            ->with(['upcomingReservation' => function ($builder) {
                $builder->select([
                    'id',
                    'car_id',
                    'customer_id',
                    'pickup_date',
                    'return_date',
                    'pickup_location',
                ])->with('customer');
            }]);

        if (Car::supportsScheduledUnavailabilityPeriods()) {
            $query->with('unavailabilityPeriods');
        }

        $this->applyAvailableFleetSort($query);

        return $query->get();
    }

    public function getAvailableCarsTotalProperty()
    {
        $query = $this->availableFleetQuery();

        return (int) $query->count('cars.id');
    }

    public function resetAvailableFleetFilters(): void
    {
        $this->availableBrand = 'all';
        $this->availableFleetScope = 'our';
        $this->availableReadiness = 'available';
        $this->availableReason = 'all';
        $this->availableSort = 'returned_oldest';
        $this->availableSearch = '';
        $this->normalizeAvailableFleetFilters();
        $this->prepareAvailableBrands();
    }

    public function applyAvailableFleetFilters(): void
    {
        $this->normalizeAvailableFleetFilters();
        $this->prepareAvailableBrands();
    }

    public function resetMonthlyContractsFilters(): void
    {
        $this->monthlyContractsSearch = '';
        $this->monthlyContractsDateField = 'return_date';
        $this->monthlyContractsDateFrom = '';
        $this->monthlyContractsDateTo = '';
        $this->monthlyContractsOwnership = 'all';
    }

    public function applyMonthlyContractsFilters(): void
    {
        $this->normalizeMonthlyContractsFilters();
    }

    protected function buildComplianceReport(): void
    {
        $today = Carbon::now()->startOfDay();
        $monthStart = $today->copy()->startOfMonth();
        $monthEnd = $today->copy()->endOfMonth();
        $urgentLimit = $today->copy()->addDays(5);

        $this->complianceReport = [
            'insurance' => $this->buildInsuranceComplianceReport($today, $monthStart, $monthEnd, $urgentLimit),
            'passing' => $this->buildPassingComplianceReport($today, $monthStart, $monthEnd, $urgentLimit),
        ];
    }

    protected function buildInsuranceComplianceReport(
        Carbon $today,
        Carbon $monthStart,
        Carbon $monthEnd,
        Carbon $urgentLimit
    ): array {
        $baseQuery = Insurance::query()
            ->whereNotNull('expiry_date');

        $dueThisMonth = (int) (clone $baseQuery)
            ->whereDate('expiry_date', '>=', $monthStart->toDateString())
            ->whereDate('expiry_date', '<=', $monthEnd->toDateString())
            ->count('insurances.id');

        $dueInFiveDays = (int) (clone $baseQuery)
            ->whereDate('expiry_date', '>=', $today->toDateString())
            ->whereDate('expiry_date', '<=', $urgentLimit->toDateString())
            ->count('insurances.id');

        $rows = Insurance::query()
            ->with(['car.carModel'])
            ->whereNotNull('expiry_date')
            ->where(function (Builder $query) use ($today, $monthStart, $monthEnd, $urgentLimit) {
                $query->where(function (Builder $dateQuery) use ($monthStart, $monthEnd) {
                    $dateQuery->whereDate('expiry_date', '>=', $monthStart->toDateString())
                        ->whereDate('expiry_date', '<=', $monthEnd->toDateString());
                })->orWhere(function (Builder $dateQuery) use ($today, $urgentLimit) {
                    $dateQuery->whereDate('expiry_date', '>=', $today->toDateString())
                        ->whereDate('expiry_date', '<=', $urgentLimit->toDateString());
                });
            })
            ->orderBy('expiry_date')
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn (Insurance $insurance) => $this->transformInsuranceComplianceRow(
                $insurance,
                $today,
                $monthStart,
                $monthEnd,
                $urgentLimit
            ))
            ->values()
            ->all();

        return [
            'summary' => [
                'due_this_month' => $dueThisMonth,
                'due_in_five_days' => $dueInFiveDays,
            ],
            'rows' => $rows,
        ];
    }

    protected function buildPassingComplianceReport(
        Carbon $today,
        Carbon $monthStart,
        Carbon $monthEnd,
        Carbon $urgentLimit
    ): array {
        $dueDateExpression = $this->passingDueDateExpression();

        $baseQuery = Car::query()
            ->whereNotNull('cars.passing_date');

        $dueThisMonth = (int) (clone $baseQuery)
            ->whereRaw(
                "{$dueDateExpression} BETWEEN ? AND ?",
                [$monthStart->toDateString(), $monthEnd->toDateString()]
            )
            ->count('cars.id');

        $dueInFiveDays = (int) (clone $baseQuery)
            ->whereRaw(
                "{$dueDateExpression} BETWEEN ? AND ?",
                [$today->toDateString(), $urgentLimit->toDateString()]
            )
            ->count('cars.id');

        $rows = Car::query()
            ->select('cars.*')
            ->selectRaw("{$dueDateExpression} as passing_due_date")
            ->with('carModel')
            ->whereNotNull('cars.passing_date')
            ->where(function (Builder $query) use ($dueDateExpression, $today, $monthStart, $monthEnd, $urgentLimit) {
                $query->whereRaw(
                    "{$dueDateExpression} BETWEEN ? AND ?",
                    [$monthStart->toDateString(), $monthEnd->toDateString()]
                )->orWhereRaw(
                    "{$dueDateExpression} BETWEEN ? AND ?",
                    [$today->toDateString(), $urgentLimit->toDateString()]
                );
            })
            ->orderByRaw("{$dueDateExpression} asc")
            ->orderByDesc('cars.updated_at')
            ->get()
            ->map(fn (Car $car) => $this->transformPassingComplianceRow(
                $car,
                $today,
                $monthStart,
                $monthEnd,
                $urgentLimit
            ))
            ->values()
            ->all();

        return [
            'summary' => [
                'due_this_month' => $dueThisMonth,
                'due_in_five_days' => $dueInFiveDays,
            ],
            'rows' => $rows,
        ];
    }

    protected function transformInsuranceComplianceRow(
        Insurance $insurance,
        Carbon $today,
        Carbon $monthStart,
        Carbon $monthEnd,
        Carbon $urgentLimit
    ): array {
        $car = $insurance->car;
        $expiryDate = Carbon::parse($insurance->expiry_date)->startOfDay();
        $daysRemaining = $today->diffInDays($expiryDate, false);

        return [
            'record_id' => $insurance->id,
            'car_name' => $car?->fullName() ?? 'Vehicle',
            'plate_number' => $car?->plate_number ?? '—',
            'ownership_label' => $car?->ownershipLabel() ?? '—',
            'status' => $insurance->status ?: 'pending',
            'expires_at' => $expiryDate->format('Y-m-d'),
            'days_remaining' => $daysRemaining,
            'days_remaining_label' => $this->formatComplianceRemainingDays($daysRemaining),
            'is_due_this_month' => $expiryDate->betweenIncluded($monthStart, $monthEnd),
            'is_urgent' => $expiryDate->betweenIncluded($today, $urgentLimit),
            'is_overdue' => $daysRemaining < 0,
        ];
    }

    protected function transformPassingComplianceRow(
        Car $car,
        Carbon $today,
        Carbon $monthStart,
        Carbon $monthEnd,
        Carbon $urgentLimit
    ): array {
        $dueDate = Carbon::parse($car->passing_due_date)->startOfDay();
        $daysRemaining = $today->diffInDays($dueDate, false);

        return [
            'record_id' => $car->id,
            'car_name' => $car->fullName(),
            'plate_number' => $car->plate_number ?? '—',
            'ownership_label' => $car->ownershipLabel(),
            'status' => $car->passing_status ?: 'pending',
            'recorded_at' => $car->passing_date ? Carbon::parse($car->passing_date)->format('Y-m-d') : null,
            'expires_at' => $dueDate->format('Y-m-d'),
            'days_remaining' => $daysRemaining,
            'days_remaining_label' => $this->formatComplianceRemainingDays($daysRemaining),
            'is_due_this_month' => $dueDate->betweenIncluded($monthStart, $monthEnd),
            'is_urgent' => $dueDate->betweenIncluded($today, $urgentLimit),
            'is_overdue' => $daysRemaining < 0,
        ];
    }

    protected function formatComplianceRemainingDays(int $daysRemaining): string
    {
        if ($daysRemaining < 0) {
            return 'Expired ' . abs($daysRemaining) . ' day(s) ago';
        }

        if ($daysRemaining === 0) {
            return 'Due today';
        }

        return $daysRemaining . ' day(s) left';
    }

    protected function normalizeAvailableFleetFilters(): void
    {
        $this->availableSearch = trim($this->availableSearch);

        if (! in_array($this->availableFleetScope, self::AVAILABLE_FLEET_SCOPES, true)) {
            $this->availableFleetScope = 'our';
        }

        if (! in_array($this->availableReadiness, self::AVAILABLE_READINESS_FILTERS, true)) {
            $this->availableReadiness = 'available';
        }

        $validReasons = array_keys(Car::operationalUnavailabilityReasonLabels());

        if ($this->availableReason !== 'all' && ! in_array($this->availableReason, $validReasons, true)) {
            $this->availableReason = 'all';
        }

        if ($this->availableReadiness === 'need_action') {
            $this->availableReason = Car::UNAVAILABILITY_REASON_NEED_ACTION;
        } elseif (
            $this->availableReason !== 'all'
            && ! in_array($this->availableReadiness, ['all', 'unavailable'], true)
        ) {
            $this->availableReadiness = 'unavailable';
        }

        if (! in_array($this->availableSort, self::AVAILABLE_SORT_OPTIONS, true)) {
            $this->availableSort = 'returned_oldest';
        }
    }

    protected function loadMonthlyContractsReport(): void
    {
        $this->normalizeMonthlyContractsFilters();

        $this->monthlyContractsReport = app(OperationsReportService::class)->monthlyContracts([
            'search' => $this->monthlyContractsSearch,
            'date_field' => $this->monthlyContractsDateField,
            'date_from' => $this->monthlyContractsDateFrom,
            'date_to' => $this->monthlyContractsDateTo,
            'ownership' => $this->monthlyContractsOwnership,
        ]);
    }

    protected function normalizeMonthlyContractsFilters(): void
    {
        $this->monthlyContractsSearch = trim($this->monthlyContractsSearch);
        $this->monthlyContractsDateFrom = trim($this->monthlyContractsDateFrom);
        $this->monthlyContractsDateTo = trim($this->monthlyContractsDateTo);

        if (! in_array($this->monthlyContractsDateField, self::MONTHLY_CONTRACT_DATE_FIELDS, true)) {
            $this->monthlyContractsDateField = 'return_date';
        }

        if (! in_array($this->monthlyContractsOwnership, self::MONTHLY_CONTRACT_OWNERSHIP_SCOPES, true)) {
            $this->monthlyContractsOwnership = 'all';
        }
    }

    protected function availableFleetQuery(bool $applyBrandFilter = true, bool $applySearchFilter = true): Builder
    {
        $query = $this->applyAvailableFleetReadiness($this->baseAvailableFleetQuery());
        $query = $this->applyAvailableFleetReasonFilter($query);

        if ($applyBrandFilter && $this->availableBrand !== 'all') {
            $query->whereHas('carModel', function (Builder $builder) {
                $builder->where('brand', $this->availableBrand);
            });
        }

        if ($applySearchFilter && $this->availableSearch !== '') {
            $search = '%' . $this->availableSearch . '%';
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('cars.plate_number', 'like', $search)
                    ->orWhere('cars.color', 'like', $search)
                    ->orWhere('cars.chassis_number', 'like', $search)
                    ->orWhereHas('carModel', function (Builder $carModelQuery) use ($search) {
                        $carModelQuery->where('brand', 'like', $search)
                            ->orWhere('model', 'like', $search);
                    });
            });
        }

        return $query;
    }

    protected function baseAvailableFleetQuery(?string $scope = null): Builder
    {
        $query = Car::query()
            ->select('cars.*', 'latest_returns.latest_returned_at')
            // Keep cars with no historical return visible in the available fleet list.
            ->leftJoinSub($this->latestReturnedAtSubquery(), 'latest_returns', function ($join) {
                $join->on('latest_returns.car_id', '=', 'cars.id');
            });

        $this->applyAvailableFleetScope($query, $scope);

        return $query;
    }

    protected function applyAvailableFleetReadiness(Builder $query, ?string $readiness = null): Builder
    {
        $readiness ??= $this->availableReadiness;

        return match ($readiness) {
            'all' => $query,
            'available' => $query->byOperationalStatus(Car::STATUS_AVAILABLE),
            'available_pre_reserved' => $query->where(function (Builder $builder) {
                $builder->where(function (Builder $availableBuilder) {
                    $availableBuilder->byOperationalStatus(Car::STATUS_AVAILABLE);
                })->orWhere(function (Builder $preReservedBuilder) {
                    $preReservedBuilder->byOperationalStatus(Car::STATUS_PRE_RESERVED);
                });
            }),
            'pre_reserved' => $query->byOperationalStatus(Car::STATUS_PRE_RESERVED),
            'reserved' => $query->byOperationalStatus(Car::STATUS_RESERVED),
            'need_action' => $query->byUnavailabilityReason(Car::UNAVAILABILITY_REASON_NEED_ACTION),
            'sold' => $query->byOperationalStatus(Car::STATUS_SOLD),
            default => $query->byOperationalStatus(Car::STATUS_UNAVAILABLE),
        };
    }

    protected function applyAvailableFleetReasonFilter(Builder $query): Builder
    {
        if ($this->availableReason === 'all') {
            return $query;
        }

        return $query->byUnavailabilityReason($this->availableReason);
    }

    protected function applyAvailableFleetScope(Builder $query, ?string $scope = null): Builder
    {
        $scope ??= $this->availableFleetScope;

        if ($scope === 'our') {
            $query->where(function (Builder $builder) {
                $builder->where('cars.ownership_type', 'company')
                    ->orWhere(function (Builder $fallback) {
                        $fallback->whereNull('cars.ownership_type')
                            ->where('cars.is_company_car', true);
                    });
            });
        } elseif ($scope === 'partners') {
            $query->where(function (Builder $builder) {
                $builder->where(function (Builder $owned) {
                    $owned->whereNotNull('cars.ownership_type')
                        ->where('cars.ownership_type', '!=', 'company');
                })->orWhere(function (Builder $fallback) {
                    $fallback->whereNull('cars.ownership_type')
                        ->where(function (Builder $legacyFlag) {
                            $legacyFlag->where('cars.is_company_car', false)
                                ->orWhereNull('cars.is_company_car');
                        });
                });
            });
        }

        return $query;
    }

    protected function applyAvailableFleetScopeToContracts(Builder $query, ?string $scope = null): Builder
    {
        $scope ??= $this->availableFleetScope;

        if ($scope === 'all') {
            return $query;
        }

        return $query->whereHas('car', function (Builder $builder) use ($scope) {
            $this->applyAvailableFleetScope($builder, $scope);
        });
    }

    protected function latestReturnedAtSubquery(): Builder
    {
        return ContractStatus::query()
            ->from('contract_statuses as cs')
            ->join('contracts as c', 'c.id', '=', 'cs.contract_id')
            ->where('cs.status', 'returned')
            ->selectRaw('c.car_id, MAX(cs.created_at) as latest_returned_at')
            ->groupBy('c.car_id');
    }

    protected function applyAvailableFleetSort(Builder $query): void
    {
        match ($this->availableSort) {
            'returned_oldest' => $query->orderByRaw('latest_returns.latest_returned_at IS NULL, latest_returns.latest_returned_at ASC'),
            'service_due_soon' => $query->orderByRaw('cars.service_due_date IS NULL, cars.service_due_date ASC'),
            'service_due_late' => $query->orderByRaw('cars.service_due_date IS NULL, cars.service_due_date DESC'),
            'year_newest' => $query->orderByDesc('cars.manufacturing_year'),
            'year_oldest' => $query->orderBy('cars.manufacturing_year'),
            default => $query->orderByRaw('latest_returns.latest_returned_at IS NULL, latest_returns.latest_returned_at DESC'),
        };

        $query
            ->orderByDesc('cars.updated_at')
            ->orderByDesc('cars.id');
    }

    protected function transformFleetAttentionCar(Car $car): array
    {
        $currentContract = $car->currentContract;
        $upcomingReservation = $car->upcomingReservation;
        $returnedAt = $car->latest_returned_at ? Carbon::parse($car->latest_returned_at) : null;

        return [
            'id' => $car->id,
            'car_name' => $car->fullName(),
            'ownership_label' => $car->ownershipLabel(),
            'status_label' => $car->operationalStatusLabel(),
            'status_badge_class' => $car->operationalStatusSubtleBadgeClass(),
            'reason_label' => $car->unavailabilityReasonLabel(),
            'active_window_label' => $car->activeScheduledUnavailabilityWindowLabel(),
            'active_window_note' => $car->activeScheduledUnavailabilityPeriod()?->note,
            'context_note' => $car->operationalStatusContextNote(),
            'action_label' => $this->fleetAttentionActionLabel($car),
            'current_contract_id' => $currentContract?->id,
            'current_customer' => $currentContract?->customer?->fullName() ?? '—',
            'current_return_at' => $currentContract?->return_date ? Carbon::parse($currentContract->return_date)->format('Y-m-d H:i') : '—',
            'next_pickup_at' => $upcomingReservation?->pickup_date ? Carbon::parse($upcomingReservation->pickup_date)->format('Y-m-d H:i') : null,
            'last_returned_at' => $returnedAt?->format('Y-m-d H:i'),
        ];
    }

    protected function fleetAttentionActionLabel(Car $car): string
    {
        return match (true) {
            $car->unavailability_reason === Car::UNAVAILABILITY_REASON_NEED_ACTION => 'Close overdue contract and confirm next step',
            $car->operationalStatus() === Car::STATUS_SOLD => 'Keep hidden from dispatch and reservations',
            $car->unavailability_reason === Car::UNAVAILABILITY_REASON_FOR_SALE => 'Hold for sale workflow only',
            $car->unavailability_reason === Car::UNAVAILABILITY_REASON_REGISTRATION => 'Renew registration before reuse',
            $car->unavailability_reason === Car::UNAVAILABILITY_REASON_INSURANCE => 'Renew insurance before dispatch',
            $car->unavailability_reason === Car::UNAVAILABILITY_REASON_CHANGE_PLATE => 'Complete plate change before reuse',
            default => 'Resolve hold reason before dispatch',
        };
    }

    protected function monthBucketSelect(string $column): string
    {
        return match (Contract::query()->getConnection()->getDriverName()) {
            'sqlite' => "strftime('%Y-%m', {$column})",
            default => "DATE_FORMAT({$column}, '%Y-%m')",
        };
    }

    protected function averageRentalDurationSelect(): string
    {
        return match (Contract::query()->getConnection()->getDriverName()) {
            'sqlite' => 'AVG(julianday(COALESCE(return_date, CURRENT_DATE)) - julianday(pickup_date)) as avg_days',
            default => 'AVG(DATEDIFF(COALESCE(return_date, CURRENT_DATE), pickup_date)) as avg_days',
        };
    }

    protected function passingDueDateExpression(string $table = 'cars'): string
    {
        return match (Car::query()->getConnection()->getDriverName()) {
            'sqlite' => "date({$table}.passing_date, '+' || COALESCE({$table}.passing_valid_for_days, 0) || ' days')",
            default => "DATE_ADD({$table}.passing_date, INTERVAL COALESCE({$table}.passing_valid_for_days, 0) DAY)",
        };
    }
}
