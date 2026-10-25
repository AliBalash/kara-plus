<?php

namespace App\Livewire\Pages\Panel\Expert\Car;

use App\Models\Car;
use App\Models\CarModel;
use App\Models\FleetPriceOperation;
use App\Models\FleetPriceWindow;
use App\Models\VehicleCatalogItem;
use App\Services\Fleet\FleetBulkPricingService;
use App\Services\Fleet\FleetDatePricingService;
use App\Support\FleetPriceMath;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class FleetPriceManagement extends Component
{
    use WithPagination;

    public array $filters = ['search' => '', 'brand' => '', 'model' => '', 'year' => '', 'ownership' => '', 'status' => '', 'min' => '', 'max' => ''];

    #[Locked]
    public array $appliedFilters = [];

    #[Locked]
    public array $selectedIds = [];

    #[Locked]
    public string $selectionContext = 'Individual selection';

    public array $operations = [];

    #[Locked]
    public ?string $previewId = null;

    public bool $confirmed = false;

    public bool $showInsurance = false;

    public string $feedback = '';

    public string $mode = 'base';

    public array $window = ['name' => '', 'starts_on' => '', 'ends_on' => '', 'active' => true];

    protected $paginationTheme = 'bootstrap';

    public function mount(): void
    {
        FleetBulkPricingService::authorize(auth()->user());
        if (request()->has('catalogItem')) {
            $input = validator(request()->query(), ['catalogItem' => 'required|integer|min:1|exists:vehicle_catalog_items,id'])->validate();
            $item = VehicleCatalogItem::findOrFail($input['catalogItem']);
            $this->filters['catalogItem'] = $item->id;
            $this->filters['year'] = (string) $item->manufacturing_year;
        }
        $this->appliedFilters = $this->filters;
        foreach (FleetPriceMath::FIELDS as $field => $label) {
            $this->operations[$field] = ['enabled' => false, 'method' => 'set', 'value' => ''];
        }
    }

    public function applyFilters(): void
    {
        FleetBulkPricingService::authorize(auth()->user());
        $this->validate([
            'filters.search' => 'nullable|string|max:100', 'filters.brand' => 'nullable|string|max:100',
            'filters.model' => 'nullable|integer|min:1', 'filters.year' => 'nullable|integer|between:1900,2200',
            'filters.catalogItem' => 'nullable|integer|min:1|exists:vehicle_catalog_items,id',
            'filters.ownership' => 'nullable|in:company,golden_key,liverpool,safe_drive,other',
            'filters.status' => 'nullable|in:available,pre_reserved,reserved,unavailable,sold',
            'filters.min' => 'nullable|numeric|min:0|max:99999999.99',
            'filters.max' => 'nullable|numeric|min:0|max:99999999.99',
        ]);
        if (filled($this->filters['min']) && filled($this->filters['max']) && (float) $this->filters['min'] > (float) $this->filters['max']) {
            $this->addError('filters.max', 'Maximum price must be at least the minimum.');

            return;
        }
        $this->appliedFilters = $this->filters;
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        FleetBulkPricingService::authorize(auth()->user());
        $this->reset('filters');
        $this->appliedFilters = $this->filters;
        $this->resetValidation();
        $this->resetPage();
    }

    protected function carsQuery(): Builder
    {
        $f = $this->appliedFilters;
        $catalogItem = filled($f['catalogItem'] ?? null) ? VehicleCatalogItem::findOrFail($f['catalogItem']) : null;

        return Car::query()->with('carModel')
            ->when($catalogItem, fn ($q) => $q->whereHas('carModel', fn ($model) => $model
                ->whereRaw('LOWER(TRIM(brand)) = ?', [VehicleCatalogItem::normalise($catalogItem->match_brand)])
                ->whereRaw('LOWER(TRIM(model)) = ?', [VehicleCatalogItem::normalise($catalogItem->match_model)])))
            ->when(($f['search'] ?? '') !== '', fn ($q) => $q->where(function ($q) use ($f) {
                $like = '%'.trim($f['search']).'%';
                $q->where('plate_number', 'like', $like)
                    ->when(ctype_digit(trim($f['search'])), fn ($ids) => $ids->orWhere('cars.id', (int) $f['search']))
                    ->orWhereHas('carModel', fn ($m) => $m->where('brand', 'like', $like)->orWhere('model', 'like', $like));
            }))
            ->when(($f['brand'] ?? '') !== '', fn ($q) => $q->whereHas('carModel', fn ($m) => $m->where('brand', $f['brand'])))
            ->when(($f['model'] ?? '') !== '', fn ($q) => $q->where('car_model_id', $f['model']))
            ->when(($f['year'] ?? '') !== '', fn ($q) => $q->where('manufacturing_year', $f['year']))
            ->when(($f['ownership'] ?? '') !== '', fn ($q) => $q->where(function ($q) use ($f) {
                $q->where('ownership_type', $f['ownership']);
                if (in_array($f['ownership'], ['company', 'other'], true)) {
                    $q->orWhere(fn ($legacy) => $legacy->whereNull('ownership_type')->where('is_company_car', $f['ownership'] === 'company'));
                }
            }))
            ->when(($f['status'] ?? '') !== '', fn ($q) => $q->byOperationalStatus($f['status']))
            ->when(($f['min'] ?? '') !== '', fn ($q) => $q->where('price_per_day_short', '>=', $f['min']))
            ->when(($f['max'] ?? '') !== '', fn ($q) => $q->where('price_per_day_short', '<=', $f['max']))
            ->orderBy('cars.id');
    }

    public function toggleSelection(int $id): void
    {
        FleetBulkPricingService::authorize(auth()->user());
        abort_unless(Car::whereKey($id)->exists(), 404);
        $this->selectedIds = in_array($id, $this->selectedIds, true)
            ? array_values(array_diff($this->selectedIds, [$id])) : [...$this->selectedIds, $id];
        $this->selectionContext = 'Individual selection across pages';
        $this->invalidatePreview();
    }

    public function selectPage(): void
    {
        FleetBulkPricingService::authorize(auth()->user());
        $this->selectedIds = array_values(array_unique([...$this->selectedIds, ...$this->carsQuery()->paginate(10)->pluck('id')->all()]));
        $this->selectionContext = 'Current page added to selection';
        $this->invalidatePreview();
    }

    public function selectAllFiltered(): void
    {
        FleetBulkPricingService::authorize(auth()->user());
        $this->selectedIds = array_values(array_unique([...$this->selectedIds, ...$this->carsQuery()->pluck('cars.id')->all()]));
        $this->selectionContext = 'All matching IDs added as a fixed snapshot; filters do not change this selection';
        $this->invalidatePreview();
    }

    public function clearSelection(): void
    {
        FleetBulkPricingService::authorize(auth()->user());
        $this->selectedIds = [];
        $this->selectionContext = 'Individual selection';
        $this->invalidatePreview();
    }

    public function updatedMode(): void
    {
        FleetBulkPricingService::authorize(auth()->user());
        $this->validate(['mode' => 'required|in:base,date']);
        foreach ($this->operations as &$operation) {
            $operation = ['enabled' => false, 'method' => 'set', 'value' => ''];
        }
        $this->invalidatePreview();
    }

    public function updatedWindow(): void
    {
        $this->invalidatePreview();
    }

    public function previewEventState(int $id, bool $active): void
    {
        FleetBulkPricingService::authorize(auth()->user());
        $this->invalidatePreview();
        $this->resetValidation();
        $this->previewId = app(FleetDatePricingService::class)->previewState(auth()->user(), $id, $active)->id;
    }

    public function updatedOperations(): void
    {
        $this->invalidatePreview();
    }

    protected function invalidatePreview(): void
    {
        $this->previewId = null;
        $this->confirmed = false;
        $this->feedback = '';
    }

    public function preview(): void
    {
        FleetBulkPricingService::authorize(auth()->user());
        $this->invalidatePreview();
        $this->resetValidation();
        $this->validate(['mode' => 'required|in:base,date']);
        $operation = $this->mode === 'date'
            ? app(FleetDatePricingService::class)->preview(auth()->user(), $this->selectedIds, $this->operations, $this->window)
            : app(FleetBulkPricingService::class)->preview(auth()->user(), $this->selectedIds, $this->operations);
        $this->previewId = $operation->id;
    }

    public function apply(): void
    {
        FleetBulkPricingService::authorize(auth()->user());
        $this->validate(['confirmed' => 'accepted', 'previewId' => 'required|uuid']);
        try {
            $operation = app(FleetBulkPricingService::class)->apply(auth()->user(), $this->previewId);
        } catch (\Illuminate\Validation\ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            report($exception);
            $this->confirmed = false;
            $this->addError('apply', 'The pricing operation failed and was rolled back. No partial changes were applied. Please try again or generate a fresh preview.');

            return;
        }
        $this->feedback = 'Pricing operation applied successfully. Operation '.$operation->id;
        $this->confirmed = false;
    }

    public function render()
    {
        FleetBulkPricingService::authorize(auth()->user());

        return view('livewire.pages.panel.expert.car.fleet-price-management', [
            'cars' => $this->carsQuery()->paginate(10),
            'catalogItem' => filled($this->appliedFilters['catalogItem'] ?? null) ? VehicleCatalogItem::find($this->appliedFilters['catalogItem']) : null,
            'models' => CarModel::orderBy('brand')->orderBy('model')->get(['id', 'brand', 'model']),
            'fields' => FleetPriceMath::FIELDS,
            'methods' => $this->mode === 'date' ? array_intersect_key(FleetPriceMath::METHODS, array_flip(['set', 'increase_percent', 'decrease_percent'])) : FleetPriceMath::METHODS,
            'priceWindows' => $this->mode === 'date' ? FleetPriceWindow::with('rates')->orderByDesc('id')->paginate(10, ['*'], 'eventsPage') : null,
            'preview' => $this->previewId ? FleetPriceOperation::where('actor_id', auth()->id())->find($this->previewId) : null,
        ]);
    }
}
