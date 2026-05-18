<?php

namespace App\Livewire\Pages\Panel\Expert;

use App\Models\Car;
use App\Models\Contract;
use App\Models\ContractStatus;
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

    private const AVAILABLE_FLEET_SCOPES = ['our', 'all', 'partners'];
    private const AVAILABLE_READINESS_FILTERS = ['available', 'available_pre_reserved', 'unavailable'];
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

        $this->buildAnalytics();
        $this->buildFleetStatusSummary();
        $this->normalizeAvailableFleetFilters();
        $this->prepareAvailableBrands();
    }


    public function render()
    {
        $data = ['title' => $this->title];

        if (! $this->isDriver) {
            $this->normalizeAvailableFleetFilters();
            $this->buildFleetStatusSummary();
            $this->prepareAvailableBrands();
            $this->loadMonthlyContractsReport();
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

        $maintenanceStatuses = ['under_maintenance', 'sold', 'maintenance', 'repair', 'service'];

        $this->totalCars = Car::count();
        $this->offlineVehicles = Car::whereIn('status', $maintenanceStatuses)->count();

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

        $availableVehicles = $this->totalCars - ($this->activeVehicles + $this->offlineVehicles);
        if ($availableVehicles < 0) {
            $availableVehicles = 0;
        }

        $this->fleetBreakdown = [
            'labels' => ['Active', 'Available', 'Maintenance'],
            'series' => [
                $this->activeVehicles,
                $availableVehicles,
                $this->offlineVehicles,
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
        $underMaintenance = (int) (clone $carsInScope)->where('cars.status', 'under_maintenance')->count('cars.id');
        $booked = (int) (clone $carsInScope)->byOperationalStatus('reserved')->count('cars.id')
            + (int) (clone $carsInScope)->byOperationalStatus('pre_reserved')->count('cars.id');
        $unavailable = max($total - ($available + $booked), 0);

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
            'booked' => $booked,
            'unavailable' => $unavailable,
            'under_maintenance' => $underMaintenance,
            'availability_rate' => $total > 0 ? (int) round(($available / $total) * 100) : 0,
            'active_reservations' => $activeReservations,
            'upcoming_pickups' => $upcomingPickups,
        ];
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
            ->with(['carModel'])
            ->with(['upcomingReservation' => function ($builder) {
                $builder->select([
                    'id',
                    'car_id',
                    'pickup_date',
                    'pickup_location',
                ]);
            }]);

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

    protected function normalizeAvailableFleetFilters(): void
    {
        $this->availableSearch = trim($this->availableSearch);

        if (! in_array($this->availableFleetScope, self::AVAILABLE_FLEET_SCOPES, true)) {
            $this->availableFleetScope = 'our';
        }

        if (! in_array($this->availableReadiness, self::AVAILABLE_READINESS_FILTERS, true)) {
            $this->availableReadiness = 'available';
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
            })
            ->withoutActiveReservations();

        $this->applyAvailableFleetScope($query, $scope);

        return $query;
    }

    protected function applyAvailableFleetReadiness(Builder $query, ?string $readiness = null): Builder
    {
        $readiness ??= $this->availableReadiness;

        if ($readiness === 'available') {
            return $query->where('cars.availability', true)
                ->where('cars.status', 'available');
        }

        if ($readiness === 'available_pre_reserved') {
            return $query->where('cars.availability', true)
                ->whereIn('cars.status', ['available', 'pre_reserved']);
        }

        return $query->where(function (Builder $builder) {
            $builder->where('cars.availability', false)
                ->orWhereNotIn('cars.status', ['available', 'pre_reserved'])
                ->orWhereNull('cars.status');
        });
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
}
