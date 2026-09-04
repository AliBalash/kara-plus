<?php

namespace Tests\Feature\Livewire\VehicleCatalog;

use App\Livewire\Pages\Panel\Expert\VehicleCatalog\VehicleCatalogList;
use App\Models\VehicleCatalogItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleCatalogListTest extends TestCase
{
    use RefreshDatabase;

    public function test_family_visibility_switches_between_all_years_and_latest_year_only(): void
    {
        $older = $this->variant('ELANTRA-23', 2023, true);
        $newer = $this->variant('ELANTRA-24', 2024, true);
        $component = app(VehicleCatalogList::class);

        $component->setFamilyYearMode($older->familyKey(), false);

        $this->assertDatabaseHas('vehicle_catalog_items', ['id' => $older->id, 'is_active' => false]);
        $this->assertDatabaseHas('vehicle_catalog_items', ['id' => $newer->id, 'is_active' => true]);

        $component->setFamilyYearMode($older->familyKey(), true);

        $this->assertDatabaseHas('vehicle_catalog_items', ['id' => $older->id, 'is_active' => true]);
        $this->assertDatabaseHas('vehicle_catalog_items', ['id' => $newer->id, 'is_active' => true]);
    }

    private function variant(string $code, int $year, bool $active): VehicleCatalogItem
    {
        return VehicleCatalogItem::query()->create([
            'code' => $code,
            'website_slug' => strtolower($code),
            'display_name' => 'Test Hyundai Family',
            'brand' => 'Test Hyundai',
            'model' => 'Family',
            'match_brand' => 'TEST HYUNDAI',
            'match_model' => 'FAMILY',
            'manufacturing_year' => $year,
            'is_active' => $active,
        ]);
    }
}
