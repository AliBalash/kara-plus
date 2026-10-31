<?php

namespace Tests\Feature\Livewire\VehicleCatalog;

use App\Livewire\Pages\Panel\Expert\VehicleCatalog\VehicleCatalogList;
use App\Models\Car;
use App\Models\CarModel;
use App\Models\User;
use App\Models\VehicleCatalogItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VehicleCatalogListTest extends TestCase
{
    use RefreshDatabase;

    public function test_visibility_changes_only_variants_of_the_selected_model_year(): void
    {
        $older = $this->variant('ELANTRA-23', 2023, true);
        $trim = $this->variant('ELANTRA-23-SE', 2023, true);
        $newer = $this->variant('ELANTRA-24', 2024, true);
        $unrelated = $this->variant('OTHER-23', 2023, true);
        $unrelated->update(['match_model' => 'Other']);
        $component = app(VehicleCatalogList::class);

        $component->setYearVisibility($older->id, false);

        $this->assertDatabaseHas('vehicle_catalog_items', ['id' => $older->id, 'is_active' => false]);
        $this->assertDatabaseHas('vehicle_catalog_items', ['id' => $trim->id, 'is_active' => false]);
        $this->assertDatabaseHas('vehicle_catalog_items', ['id' => $newer->id, 'is_active' => true]);
        $this->assertTrue($unrelated->fresh()->is_active);

        $component->setYearVisibility($newer->id, false);
        $component->setYearVisibility($older->id, true);

        $this->assertDatabaseHas('vehicle_catalog_items', ['id' => $older->id, 'is_active' => true]);
        $this->assertDatabaseHas('vehicle_catalog_items', ['id' => $trim->id, 'is_active' => true]);
        $this->assertDatabaseHas('vehicle_catalog_items', ['id' => $newer->id, 'is_active' => false]);
    }

    public function test_each_year_has_its_own_reservation_link_and_only_its_own_expanded_variants(): void
    {
        config(['reservation.public_url' => 'https://reservation.example.test/']);
        $older = $this->variant('ELANTRA-23', 2023, true);
        $older->update(['trim' => 'Older SE']);
        $newer = $this->variant('ELANTRA-24', 2024, true);
        $newer->update(['trim' => 'Newer Limited']);
        Livewire::test(VehicleCatalogList::class)
            ->set('search', 'ELANTRA')
            ->assertSee('Reservation link')
            ->assertSeeHtml('href="https://reservation.example.test/?vehicle=ELANTRA-24"')
            ->assertSeeHtml('href="https://reservation.example.test/?vehicle=ELANTRA-23"')
            ->call('toggleYear', $older->id)
            ->assertSee('Older SE')->assertDontSee('Newer Limited')
            ->assertSee('Open link · 2023')
            ->assertSee('Open link · 2024')
            ->call('toggleYear', $newer->id)
            ->assertSee('Newer Limited')->assertDontSee('Older SE');
    }

    public function test_year_link_uses_an_active_variant_of_that_year_and_keeps_inactive_years_separate(): void
    {
        $older = $this->variant('ELANTRA-23', 2023, true);
        $this->variant('ELANTRA-23-A', 2023, false);
        $this->variant('ELANTRA-24', 2024, false);
        $component = app(VehicleCatalogList::class);
        $component->search = 'ELANTRA';
        $groups = $component->render()->getData()['items']->getCollection()
            ->where('name', 'Test Hyundai Family');

        $this->assertCount(2, $groups);
        $this->assertSame('ELANTRA-23', $groups->firstWhere('year', 2023)['reservation_code']);
        $this->assertSame(2, $groups->firstWhere('year', 2023)['variant_count']);
        $this->assertSame(1, $groups->firstWhere('year', 2023)['active_count']);
        $this->assertSame('ELANTRA-24', $groups->firstWhere('year', 2024)['reservation_code']);
        $this->assertSame(0, $groups->firstWhere('year', 2024)['active_count']);
        $this->assertSame($older->familyKey(), $this->variant('SAME-FAMILY-25', 2025, true)->familyKey());
    }

    public function test_year_groups_show_only_matching_year_prices_and_preserve_same_year_variants(): void
    {
        $older = $this->variant('ELANTRA-23', 2023, true);
        $trim = $this->variant('ELANTRA-23-SE', 2023, true);
        $trim->update(['trim' => 'SE', 'match_brand' => ' test hyundai ', 'match_model' => 'family']);
        $this->variant('ELANTRA-24', 2024, true);
        $model = CarModel::factory()->create(['brand' => 'Test Hyundai', 'model' => 'Family']);
        foreach ([2023 => 100, 2024 => 200] as $year => $price) {
            Car::factory()->available()->create([
                'car_model_id' => $model->id, 'manufacturing_year' => $year,
                'price_per_day_short' => $price, 'price_per_day_mid' => $price - 10, 'price_per_day_long' => $price - 20,
            ]);
        }
        $component = app(VehicleCatalogList::class);
        $component->search = 'ELANTRA';
        $groups = $component->render()->getData()['items']->getCollection()->where('name', 'Test Hyundai Family');

        $this->assertSame([2023, 2024], $groups->pluck('year')->all());
        $this->assertSame([$older->id, $trim->id], $groups->firstWhere('year', 2023)['variants']->pluck('id')->all());
        $this->assertSame(['100.00 / 90.00 / 80.00'], $groups->firstWhere('year', 2023)['variants']->first()->price_signatures);
        $this->assertSame(['200.00 / 190.00 / 180.00'], $groups->firstWhere('year', 2024)['variants']->first()->price_signatures);
    }

    public function test_saving_a_variant_keeps_other_years_codes_and_settings_unchanged(): void
    {
        $older = $this->variant('ELANTRA-23', 2023, true);
        $newer = $this->variant('ELANTRA-24', 2024, true);
        $before = $newer->fresh()->getAttributes();
        Livewire::test(VehicleCatalogList::class)
            ->call('edit', $older->id)
            ->set('trim', 'SE')
            ->set('isActive', false)
            ->call('save')->assertHasNoErrors();
        $this->assertSame($before, $newer->fresh()->getAttributes());
        $this->assertSame('ELANTRA-23', $older->fresh()->code);
        $this->assertSame('SE', $older->fresh()->trim);
        $this->assertFalse($older->fresh()->is_active);
    }

    public function test_search_resets_pagination_and_matches_a_variant_trim(): void
    {
        for ($year = 1990; $year <= 2010; $year++) {
            $this->variant('PAGE-'.$year, $year, true);
        }
        $target = $this->variant('TARGET-24', 2024, true);
        $target->update(['trim' => 'Unique Limited']);
        Livewire::test(VehicleCatalogList::class)
            ->set('search', 'PAGE-')
            ->assertViewHas('items', fn ($items) => $items->total() === 21)
            ->call('setPage', 2)
            ->set('search', 'Unique Limited')
            ->assertViewHas('items', fn ($items) => $items->currentPage() === 1 && $items->count() === 1 && $items->first()['year'] === 2024);
    }

    public function test_price_management_links_use_the_exact_year_group_for_non_driver_staff(): void
    {
        $older = $this->variant('ELANTRA-23', 2023, true);
        $newer = $this->variant('ELANTRA-24', 2024, true);
        Livewire::test(VehicleCatalogList::class)->assertDontSee('Manage prices ·');

        $actor = User::factory()->create();
        $this->actingAs($actor);
        Livewire::test(VehicleCatalogList::class)->set('search', 'ELANTRA')
            ->assertSeeHtml('href="'.e(route('car.prices', ['catalogItem' => $older->id])).'"')
            ->assertSeeHtml('href="'.e(route('car.prices', ['catalogItem' => $newer->id])).'"');
    }

    public function test_price_management_links_are_hidden_for_drivers_even_with_pricing_permission(): void
    {
        Role::findOrCreate('driver');
        $actor = User::factory()->create();
        $actor->assignRole('driver');
        $actor->givePermissionTo('manage fleet prices');
        $this->actingAs($actor);

        Livewire::test(VehicleCatalogList::class)->assertDontSee('Manage prices ·');
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
