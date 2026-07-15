<?php

namespace App\Livewire\Pages\Panel\Expert\Car;

use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\Car;
use App\Models\CarUnavailabilityPeriod;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;

class CarUnavailableDesk extends Component
{
    use InteractsWithToasts;
    use WithPagination;

    public string $search = '';
    public string $searchInput = '';
    public string $stateFilter = 'active';
    public string $reasonFilter = '';
    public string $carFilter = '';
    public ?string $dateFrom = null;
    public ?string $dateTo = null;
    public string $sort = 'active_first';

    public bool $databaseReady = false;

    protected $queryString = ['search', 'stateFilter', 'reasonFilter', 'carFilter', 'dateFrom', 'dateTo', 'sort'];

    public function mount(): void
    {
        $this->searchInput = $this->search;
        $this->databaseReady = CarUnavailabilityPeriod::tableExists();

        if (! $this->databaseReady) {
            $this->stateFilter = 'all';
        }
    }

    public function updatedSearchInput(): void
    {
        $this->applySearch();
    }

    public function applySearch(): void
    {
        $this->search = trim($this->searchInput);
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'searchInput', 'stateFilter', 'reasonFilter', 'carFilter', 'dateFrom', 'dateTo', 'sort']);
        $this->stateFilter = $this->databaseReady ? 'active' : 'all';
        $this->sort = 'active_first';
        $this->resetPage();
    }

    public function setStateFilter(string $state): void
    {
        $allowed = ['all', 'active', 'upcoming', 'completed', 'cancelled'];

        if (! in_array($state, $allowed, true)) {
            return;
        }

        $this->stateFilter = $state;
        $this->resetPage();
    }

    protected function syncCars(array $carIds): void
    {
        Car::query()
            ->whereIn('id', array_values(array_filter(array_unique($carIds))))
            ->get()
            ->each(fn (Car $car) => $car->syncOperationalState());
    }

    public function render()
    {
        $cars = Car::query()
            ->with('carModel')
            ->where('status', '!=', Car::STATUS_SOLD)
            ->orderByRaw('(select brand from car_models where car_models.id = cars.car_model_id limit 1)')
            ->orderByRaw('(select model from car_models where car_models.id = cars.car_model_id limit 1)')
            ->orderBy('plate_number')
            ->get();

        $summary = [
            'active' => 0,
            'upcoming' => 0,
            'completed' => 0,
            'cancelled' => 0,
            'ending_soon' => 0,
            'manual' => 0,
        ];

        $manualHolds = collect();
        $search = trim($this->search);

        if (! $this->databaseReady) {
            $periods = new LengthAwarePaginator([], 0, 10);

            if (Car::hasStatusSupportColumn('manual_status')) {
                $manualHolds = $this->manualUnavailableCarsQuery($search)->get();
                $summary['manual'] = $manualHolds->count();
            }

            return view('livewire.pages.panel.expert.car.car-unavailable-desk', compact('cars', 'periods', 'summary', 'manualHolds'));
        }

        $likeSearch = '%' . $search . '%';
        $today = Carbon::today();

        $query = CarUnavailabilityPeriod::query()
            ->with(['car.carModel', 'creator', 'updater'])
            ->when($search !== '', function ($builder) use ($likeSearch) {
                $builder->where(function ($searchBuilder) use ($likeSearch) {
                    $searchBuilder->where('note', 'like', $likeSearch)
                        ->orWhere('reason', 'like', $likeSearch)
                        ->orWhereHas('car', function ($carQuery) use ($likeSearch) {
                            $carQuery->where('plate_number', 'like', $likeSearch)
                                ->orWhereHas('carModel', function ($carModelQuery) use ($likeSearch) {
                                    $carModelQuery->where('brand', 'like', $likeSearch)
                                        ->orWhere('model', 'like', $likeSearch);
                                });
                        });
                });
            })
            ->when($this->reasonFilter !== '', fn ($builder) => $builder->where('reason', $this->reasonFilter))
            ->when($this->carFilter !== '', fn ($builder) => $builder->where('car_id', (int) $this->carFilter));

        [$dateFrom, $dateTo] = $this->selectedDateBounds();

        if ($dateFrom && $dateTo) {
            $query
                ->whereDate('start_date', '<=', $dateTo->toDateString())
                ->whereDate('end_date', '>=', $dateFrom->toDateString());
        }

        $summary['active'] = (clone $query)->activeOn($today)->count();
        $summary['upcoming'] = (clone $query)->upcomingFrom($today)->count();
        $summary['completed'] = (clone $query)
            ->open()
            ->whereDate('end_date', '<', $today->toDateString())
            ->count();
        $summary['cancelled'] = (clone $query)->cancelled()->count();
        $summary['ending_soon'] = (clone $query)
            ->activeOn($today)
            ->whereDate('end_date', '<=', $today->copy()->addDays(3)->toDateString())
            ->count();

        if (Car::hasStatusSupportColumn('manual_status')) {
            $manualHolds = $this->manualUnavailableCarsQuery($search)->get();
            $summary['manual'] = $manualHolds->count();
        }

        if ($this->stateFilter === 'active') {
            $query->activeOn($today);
        } elseif ($this->stateFilter === 'upcoming') {
            $query->upcomingFrom($today);
        } elseif ($this->stateFilter === 'completed') {
            $query->open()->whereDate('end_date', '<', $today->toDateString());
        } elseif ($this->stateFilter === 'cancelled') {
            $query->cancelled();
        }

        $periods = $this->applySorting($query, $today)->paginate(10);

        return view('livewire.pages.panel.expert.car.car-unavailable-desk', compact('cars', 'periods', 'summary', 'manualHolds'));
    }

    protected function applySorting($query, Carbon $today)
    {
        return match ($this->sort) {
            'latest_created' => $query->orderByDesc('id'),
            'end_soonest' => $query->orderBy('end_date')->orderBy('start_date'),
            'start_latest' => $query->orderByDesc('start_date')->orderByDesc('id'),
            default => $this->applyActiveFirstSorting($query, $today),
        };
    }

    protected function applyActiveFirstSorting($query, Carbon $today)
    {
        if (CarUnavailabilityPeriod::supportsCancellationColumns()) {
            return $query
                ->orderByRaw("CASE
                    WHEN cancelled_at IS NOT NULL THEN 3
                    WHEN start_date <= ? AND end_date >= ? THEN 0
                    WHEN start_date > ? THEN 1
                    ELSE 2
                END", [$today->toDateString(), $today->toDateString(), $today->toDateString()])
                ->orderBy('start_date')
                ->orderByDesc('id');
        }

        return $query
            ->orderByRaw("CASE
                WHEN start_date <= ? AND end_date >= ? THEN 0
                WHEN start_date > ? THEN 1
                ELSE 2
            END", [$today->toDateString(), $today->toDateString(), $today->toDateString()])
            ->orderBy('start_date')
            ->orderByDesc('id');
    }

    /**
     * @return array{0: Carbon|null, 1: Carbon|null}
     */
    protected function selectedDateBounds(): array
    {
        $from = $this->dateFrom ? Carbon::parse($this->dateFrom)->startOfDay() : null;
        $to = $this->dateTo ? Carbon::parse($this->dateTo)->endOfDay() : null;

        if ($from && $to && $from->gt($to)) {
            return [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        if ($from && ! $to) {
            return [$from, $from->copy()->endOfDay()];
        }

        if (! $from && $to) {
            return [$to->copy()->startOfDay(), $to];
        }

        return [$from, $to];
    }

    protected function manualUnavailableCarsQuery(string $search)
    {
        $likeSearch = '%' . trim($search) . '%';

        return Car::query()
            ->with('carModel')
            ->where(function ($builder) {
                $builder->where('manual_status', Car::MANUAL_STATUS_UNAVAILABLE)
                    ->orWhere(function ($legacyBuilder) {
                        $legacyBuilder
                            ->where('status', Car::STATUS_UNAVAILABLE)
                            ->where('availability', false)
                            ->whereNotNull('unavailability_reason')
                            ->where('unavailability_reason', '!=', Car::UNAVAILABILITY_REASON_NEED_ACTION)
                            ->where(function ($manualBuilder) {
                                $manualBuilder->whereNull('manual_status')
                                    ->orWhere('manual_status', '!=', Car::MANUAL_STATUS_SOLD);
                            });

                        if (Car::supportsScheduledUnavailabilityPeriods()) {
                            $legacyBuilder->whereDoesntHave('unavailabilityPeriods', function ($periodBuilder) {
                                $periodBuilder->activeOn(Carbon::today());
                            });
                        }
                    });
            })
            ->when($this->reasonFilter !== '', function ($builder) {
                $builder->where(function ($reasonBuilder) {
                    $reasonBuilder->where('manual_unavailability_reason', $this->reasonFilter)
                        ->orWhere('unavailability_reason', $this->reasonFilter);
                });
            })
            ->when($this->carFilter !== '', fn ($builder) => $builder->where('id', (int) $this->carFilter))
            ->when($search !== '', function ($builder) use ($likeSearch) {
                $builder->where(function ($searchBuilder) use ($likeSearch) {
                    $searchBuilder->where('plate_number', 'like', $likeSearch)
                        ->orWhere('manual_unavailability_reason', 'like', $likeSearch)
                        ->orWhere('notes', 'like', $likeSearch)
                        ->orWhereHas('carModel', function ($carModelQuery) use ($likeSearch) {
                            $carModelQuery->where('brand', 'like', $likeSearch)
                                ->orWhere('model', 'like', $likeSearch);
                        });
                });
            })
            ->orderByDesc('updated_at')
            ->orderByDesc('id');
    }
}
