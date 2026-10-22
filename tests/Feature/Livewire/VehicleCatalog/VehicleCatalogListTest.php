<?php

namespace Tests\Feature\Livewire\VehicleCatalog;

use App\Livewire\Pages\Panel\Expert\VehicleCatalog\VehicleCatalogList;
use App\Models\VehicleCatalogItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
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

    public function test_reservation_links_are_visible_for_families_and_each_expanded_year(): void
    {
        config(['reservation.public_url' => 'https://reservation.example.test/']);
        $older = $this->variant('ELANTRA-23', 2023, true);
        $this->variant('ELANTRA-24', 2024, true);
        Livewire::test(VehicleCatalogList::class)
            ->set('search', 'ELANTRA')
            ->assertSee('Reservation link')
            ->assertSeeHtml('href="https://reservation.example.test/?vehicle=ELANTRA-24"')
            ->call('toggleFamily', $older->familyKey())
            ->assertSeeHtml('href="https://reservation.example.test/?vehicle=ELANTRA-23"')
            ->assertSee('Open link · 2023')
            ->assertSee('Open link · 2024');
    }

    public function test_family_link_uses_latest_included_year_instead_of_an_inactive_variant(): void
    {
        $older = $this->variant('ELANTRA-23', 2023, true);
        $this->variant('ELANTRA-24', 2024, false);
        $component = app(VehicleCatalogList::class);
        $component->search = 'ELANTRA';
        $families = $component->render()->getData()['items'];

        $this->assertSame('ELANTRA-23', $families->getCollection()->firstWhere('key', $older->familyKey())['reservation_code']);
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
