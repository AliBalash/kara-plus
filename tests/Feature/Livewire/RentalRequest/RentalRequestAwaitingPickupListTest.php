<?php

namespace Tests\Feature\Livewire\RentalRequest;

use App\Livewire\Pages\Panel\Expert\RentalRequest\RentalRequestAwaitingPickupList;
use App\Models\Contract;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RentalRequestAwaitingPickupListTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::firstOrCreate(['name' => 'driver', 'guard_name' => 'web']);
    }

    public function test_driver_can_assign_contract_to_self(): void
    {
        $driver = User::factory()->create();
        $driver->assignRole('driver');

        $contract = Contract::factory()
            ->status('delivery')
            ->create(['delivery_driver_id' => null]);

        $this->actingAs($driver);

        $component = app(RentalRequestAwaitingPickupList::class);
        $component->mount();
        $component->assignToDriver($contract->id);

        $this->assertEquals($driver->id, $contract->fresh()->delivery_driver_id);
        $this->assertEquals('Delivery assigned to you successfully.', session('success'));
    }

    public function test_driver_cannot_take_contract_assigned_to_another_driver(): void
    {
        $primaryDriver = User::factory()->create();
        $primaryDriver->assignRole('driver');

        $otherDriver = User::factory()->create();
        $otherDriver->assignRole('driver');

        $contract = Contract::factory()
            ->status('delivery')
            ->create(['delivery_driver_id' => $primaryDriver->id]);

        $this->actingAs($otherDriver);

        $component = app(RentalRequestAwaitingPickupList::class);
        $component->mount();
        $component->assignToDriver($contract->id);

        $this->assertEquals($primaryDriver->id, $contract->fresh()->delivery_driver_id);
        $this->assertEquals('This delivery is already assigned to another driver.', session('error'));
    }

    public function test_non_driver_cannot_claim_delivery(): void
    {
        $user = User::factory()->create();
        $contract = Contract::factory()->status('delivery')->create(['delivery_driver_id' => null]);

        $this->actingAs($user);

        $component = app(RentalRequestAwaitingPickupList::class);
        $component->mount();
        $component->assignToDriver($contract->id);

        $this->assertNull($contract->fresh()->delivery_driver_id);
        $this->assertEquals('Only drivers can claim delivery tasks.', session('error'));
    }
}
