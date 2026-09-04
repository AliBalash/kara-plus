<?php

namespace App\Livewire\Pages\Panel\Expert\VehicleCatalog;

use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\Car;
use App\Models\VehicleCatalogItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;

class VehicleCatalogList extends Component
{
    use InteractsWithToasts;
    use WithPagination;

    public string $search = '';
    public ?int $editingId = null;
    public string $code = '';
    public string $websiteSlug = '';
    public string $displayName = '';
    public string $brand = '';
    public string $model = '';
    public string $matchBrand = '';
    public string $matchModel = '';
    public string $manufacturingYear = '';
    public string $trim = '';
    public bool $isActive = true;
    public ?string $expandedFamilyKey = null;

    public function render()
    {
        $variants = VehicleCatalogItem::query()
            ->when(trim($this->search) !== '', function ($query) {
                $term = '%' . trim($this->search) . '%';
                $query->where(function ($scoped) use ($term) {
                    $scoped->where('code', 'like', $term)
                        ->orWhere('display_name', 'like', $term)
                        ->orWhere('website_slug', 'like', $term)
                        ->orWhere('brand', 'like', $term)
                        ->orWhere('model', 'like', $term)
                        ->orWhere('match_brand', 'like', $term)
                        ->orWhere('match_model', 'like', $term)
                        ->orWhereRaw('CAST(manufacturing_year AS CHAR) like ?', [$term]);
                });
            })
            ->orderBy('manufacturing_year')
            ->get();

        $fleetCars = Car::query()->where('status', '!=', Car::STATUS_SOLD)->with('carModel')->get();
        $variants->each(function (VehicleCatalogItem $variant) use ($fleetCars): void {
            $variant->setAttribute('price_signatures', $fleetCars
                ->filter(fn (Car $car): bool => $variant->appliesToCar($car))
                ->map(static fn (Car $car): string => sprintf(
                    '%s / %s / %s',
                    number_format((float) $car->price_per_day_short, 2),
                    number_format((float) ($car->price_per_day_mid ?? $car->price_per_day_short), 2),
                    number_format((float) ($car->price_per_day_long ?? $car->price_per_day_mid ?? $car->price_per_day_short), 2),
                ))
                ->unique()->values()->all());
        });

        $families = $variants->groupBy(static fn (VehicleCatalogItem $item) => $item->familyKey())
            ->map(function ($variants, string $familyKey): array {
                $variants = $variants->sortBy('manufacturing_year')->values();
                $representative = $variants->first();

                return [
                    'key' => $familyKey,
                    'name' => trim($representative->brand.' '.$representative->model),
                    'match_brand' => $representative->match_brand,
                    'match_model' => $representative->match_model,
                    'years' => $variants->pluck('manufacturing_year')->unique()->sort()->values(),
                    'variant_count' => $variants->count(),
                    'active_count' => $variants->where('is_active', true)->count(),
                    'public_mode' => $variants->every('is_active') ? 'all_years' : 'latest_year_only',
                    'latest_year' => (int) $variants->last()->manufacturing_year,
                    'variants' => $variants,
                ];
            })->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values();

        $page = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 20;
        $items = new LengthAwarePaginator(
            $families->forPage($page, $perPage)->values(),
            $families->count(), $perPage, $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath()]
        );

        return view('livewire.pages.panel.expert.vehicle-catalog.vehicle-catalog-list', [
            'items' => $items,
        ]);
    }

    public function toggleFamily(string $familyKey): void
    {
        $this->expandedFamilyKey = $this->expandedFamilyKey === $familyKey ? null : $familyKey;
    }

    /**
     * A public reservation setting belongs to a model family, never to a
     * plate or an individually managed year row. We keep the year variants
     * intact and express the setting through their existing active flags.
     */
    public function setFamilyYearMode(string $familyKey, bool $showAllYears): void
    {
        $variants = VehicleCatalogItem::query()->get()
            ->filter(static fn (VehicleCatalogItem $item): bool => $item->familyKey() === $familyKey)
            ->sortByDesc('manufacturing_year')
            ->values();

        if ($variants->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($variants, $showAllYears): void {
            VehicleCatalogItem::query()->whereIn('id', $variants->pluck('id'))->update([
                'is_active' => $showAllYears,
            ]);

            // "Latest year only" always leaves a valid public product rather
            // than hiding the model family completely.
            if (! $showAllYears) {
                VehicleCatalogItem::query()->whereKey($variants->first()->id)->update(['is_active' => true]);
            }
        });

        $this->toast('success', $showAllYears
            ? 'All model years are available for public reservation.'
            : 'Only the latest model year is available for public reservation.');
    }

    public function edit(int $id): void
    {
        $item = VehicleCatalogItem::findOrFail($id);
        $this->editingId = $item->id;
        $this->code = $item->code;
        $this->websiteSlug = $item->website_slug;
        $this->displayName = $item->display_name;
        $this->brand = $item->brand;
        $this->model = $item->model;
        $this->matchBrand = $item->match_brand;
        $this->matchModel = $item->match_model;
        $this->manufacturingYear = (string) $item->manufacturing_year;
        $this->trim = (string) $item->trim;
        $this->isActive = $item->is_active;
        $this->resetValidation();
    }

    public function resetEditor(): void
    {
        $this->reset(['editingId', 'code', 'websiteSlug', 'displayName', 'brand', 'model', 'matchBrand', 'matchModel', 'manufacturingYear', 'trim']);
        $this->isActive = true;
        $this->resetValidation();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'code' => ['required', 'string', 'max:40', 'regex:/^[A-Z0-9-]+$/', Rule::unique('vehicle_catalog_items', 'code')->ignore($this->editingId)],
            'websiteSlug' => ['required', 'string', 'max:120', 'regex:/^[a-z0-9-]+$/', Rule::unique('vehicle_catalog_items', 'website_slug')->ignore($this->editingId)],
            'displayName' => ['required', 'string', 'max:255'],
            'brand' => ['required', 'string', 'max:100'],
            'model' => ['required', 'string', 'max:100'],
            'matchBrand' => ['required', 'string', 'max:100'],
            'matchModel' => ['required', 'string', 'max:100'],
            'manufacturingYear' => ['required', 'integer', 'between:1990,2100'],
            'trim' => ['nullable', 'string', 'max:100'],
            'isActive' => ['boolean'],
        ]);

        $attributes = [
            'code' => strtoupper(trim($validated['code'])),
            'website_slug' => strtolower(trim($validated['websiteSlug'])),
            'display_name' => trim($validated['displayName']),
            'brand' => trim($validated['brand']),
            'model' => trim($validated['model']),
            'match_brand' => trim($validated['matchBrand']),
            'match_model' => trim($validated['matchModel']),
            'manufacturing_year' => (int) $validated['manufacturingYear'],
            'trim' => filled($validated['trim']) ? trim($validated['trim']) : null,
            'is_active' => (bool) $validated['isActive'],
        ];

        VehicleCatalogItem::updateOrCreate(['id' => $this->editingId], $attributes);
        $this->toast('success', $this->editingId ? 'Vehicle catalogue item updated.' : 'Vehicle catalogue item created.');
        $this->resetEditor();
    }
}
