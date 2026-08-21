<?php

namespace App\Livewire\Pages\Panel\Expert\VehicleCatalog;

use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\VehicleCatalogItem;
use Illuminate\Validation\Rule;
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

    public function render()
    {
        $items = VehicleCatalogItem::query()
            ->when(trim($this->search) !== '', function ($query) {
                $term = '%' . trim($this->search) . '%';
                $query->where(function ($scoped) use ($term) {
                    $scoped->where('code', 'like', $term)
                        ->orWhere('display_name', 'like', $term)
                        ->orWhere('website_slug', 'like', $term);
                });
            })
            ->orderBy('brand')
            ->orderBy('model')
            ->orderBy('manufacturing_year')
            ->paginate(20);

        return view('livewire.pages.panel.expert.vehicle-catalog.vehicle-catalog-list', [
            'items' => $items,
            'reservationUrl' => rtrim((string) config('reservation.public_url'), '/'),
        ]);
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
