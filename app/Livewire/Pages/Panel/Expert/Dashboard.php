<?php

namespace App\Livewire\Pages\Panel\Expert;

use App\Models\Car;
use App\Models\CarModel;
use App\Models\Contract;
use App\Models\DiscountCode;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Dashboard extends Component
{
    public $title = 'Dashboard';
    public $discountCodesCount;
    public $usedDiscountCodes;
    public $usageRate;
    public $averageDiscount;
    public $latestDiscountCodes;
    public $userDiscountCodes;
    public $userUsedDiscountCodes;
    public $lastUserDiscountCode;

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
    public $discountTrend;
    public $fleetBreakdown;
    public $fleetUtilization;
    public $totalCars;
    public $activeVehicles;
    public $offlineVehicles;
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


    public function mount()
    {
        $this->isDriver = Auth::user()?->hasRole('driver');

        if ($this->isDriver) {
            $this->prepareDriverDashboard();
            return;
        }

        $this->discountCodesCount = DiscountCode::count();
        $this->usedDiscountCodes = DiscountCode::where('contacted', true)->count();
        $this->usageRate = $this->discountCodesCount > 0 ? round(($this->usedDiscountCodes / $this->discountCodesCount) * 100, 2) : 0;
        $this->averageDiscount = DiscountCode::avg('discount_percentage');
        $this->latestDiscountCodes = DiscountCode::orderBy('created_at', 'desc')->take(5)->get();
        $this->userDiscountCodes = DiscountCode::where('phone', Auth::user()->phone)->count();
        $this->userUsedDiscountCodes = DiscountCode::where('phone', Auth::user()->phone)->where('contacted', true)->count();
        $this->lastUserDiscountCode = DiscountCode::where('phone', Auth::user()->phone)->latest()->first();


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


        $this->reservedCars = \App\Models\Contract::with(['car.carModel'])
            ->where('current_status', 'reserved')
            ->latest()
            ->get();

        $this->returnedCars = Contract::with(['car.carModel', 'customer'])
            ->where('current_status', 'awaiting_return')
            ->latest()
            ->get();

        $this->buildAnalytics();
        $this->prepareAvailableBrands();
    }


    public function render()
    {
        $data = ['title' => $this->title];

        if (! $this->isDriver) {
            $this->prepareAvailableBrands();
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

        $revenueRaw = Contract::whereNotNull('pickup_date')
            ->whereBetween('pickup_date', [$months->first(), $months->last()->copy()->endOfMonth()])
            ->selectRaw('DATE_FORMAT(pickup_date, "%Y-%m") as month, SUM(total_price) as total')
            ->groupBy('month')
            ->pluck('total', 'month');

        $contractRaw = Contract::whereBetween('created_at', [$months->first(), $months->last()->copy()->endOfMonth()])
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as total')
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
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, current_status, COUNT(*) as total')
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

        $discountCreated = DiscountCode::whereBetween('created_at', [$months->first(), $months->last()->copy()->endOfMonth()])
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as total')
            ->groupBy('month')
            ->pluck('total', 'month');

        $discountUsed = DiscountCode::where('contacted', true)
            ->whereBetween('updated_at', [$months->first(), $months->last()->copy()->endOfMonth()])
            ->selectRaw('DATE_FORMAT(updated_at, "%Y-%m") as month, COUNT(*) as total')
            ->groupBy('month')
            ->pluck('total', 'month');

        $this->discountTrend = [
            'labels' => $monthLabels->all(),
            'created' => $monthKeys->map(fn ($month) => (int) ($discountCreated[$month] ?? 0))->all(),
            'used' => $monthKeys->map(fn ($month) => (int) ($discountUsed[$month] ?? 0))->all(),
        ];

        $maintenanceStatuses = ['maintenance', 'repair', 'service'];

        $this->totalCars = Car::count();
        $this->offlineVehicles = Car::whereIn('status', $maintenanceStatuses)->count();

        $activeVehicleIds = Contract::whereIn('current_status', ['reserved', 'assigned', 'under_review', 'delivery', 'awaiting_return'])
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
            ->selectRaw('AVG(DATEDIFF(COALESCE(return_date, CURRENT_DATE), pickup_date)) as avg_days')
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
            ->where('driver_id', $userId)
            ->whereIn('current_status', ['reserved', 'delivery', 'assigned'])
            ->whereNotNull('pickup_date')
            ->where('pickup_date', '>=', $now->copy()->startOfDay())
            ->orderBy('pickup_date')
            ->take(6)
            ->get();

        $this->driverReturns = Contract::with(['car.carModel', 'customer'])
            ->where('driver_id', $userId)
            ->whereIn('current_status', ['awaiting_return', 'delivery'])
            ->whereNotNull('return_date')
            ->where('return_date', '>=', $now->copy()->startOfDay())
            ->orderBy('return_date')
            ->take(6)
            ->get();

        $assignedStatuses = ['reserved', 'delivery', 'awaiting_return', 'assigned'];

        $this->driverStats = [
            'pickupsToday' => Contract::where('driver_id', $userId)
                ->whereIn('current_status', ['reserved', 'delivery', 'assigned'])
                ->whereDate('pickup_date', $now->toDateString())
                ->count(),
            'returnsToday' => Contract::where('driver_id', $userId)
                ->whereIn('current_status', ['awaiting_return', 'delivery'])
                ->whereDate('return_date', $now->toDateString())
                ->count(),
            'activeAssignments' => Contract::where('driver_id', $userId)
                ->whereIn('current_status', $assignedStatuses)
                ->count(),
            'overdueReturns' => Contract::where('driver_id', $userId)
                ->where('current_status', 'awaiting_return')
                ->where('return_date', '<', $now)
                ->count(),
        ];

        $nextPickup = Contract::with(['car.carModel', 'customer'])
            ->where('driver_id', $userId)
            ->whereIn('current_status', ['reserved', 'delivery', 'assigned'])
            ->whereNotNull('pickup_date')
            ->where('pickup_date', '>=', $now)
            ->orderBy('pickup_date')
            ->first();

        $nextReturn = Contract::with(['car.carModel', 'customer'])
            ->where('driver_id', $userId)
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

    protected function prepareAvailableBrands(): void
    {
        $brands = CarModel::query()
            ->whereHas('cars', function ($query) {
                $query->where('status', 'available')->where('availability', true);
            })
            ->orderBy('brand')
            ->pluck('brand')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $this->availableBrands = $brands;

        if ($this->availableBrand !== 'all' && ! in_array($this->availableBrand, $this->availableBrands, true)) {
            $this->availableBrand = 'all';
        }
    }

    public function getAvailableCarsProperty()
    {
        $query = Car::with(['carModel'])
            ->where('status', 'available')
            ->where('availability', true);

        if ($this->availableBrand !== 'all') {
            $query->whereHas('carModel', function ($builder) {
                $builder->where('brand', $this->availableBrand);
            });
        }

        return $query
            ->orderByDesc('updated_at')
            ->orderByDesc('manufacturing_year')
            ->limit(12)
            ->get();
    }

    public function getAvailableCarsTotalProperty()
    {
        $query = Car::query()
            ->where('status', 'available')
            ->where('availability', true);

        if ($this->availableBrand !== 'all') {
            $query->whereHas('carModel', function ($builder) {
                $builder->where('brand', $this->availableBrand);
            });
        }

        return $query->count();
    }
}
