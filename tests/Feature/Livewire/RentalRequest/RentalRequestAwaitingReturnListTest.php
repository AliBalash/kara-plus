<?php

namespace Tests\Feature\Livewire\RentalRequest;

use App\Livewire\Pages\Panel\Expert\RentalRequest\RentalRequestAwaitingReturnList;
use App\Models\Contract;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RentalRequestAwaitingReturnListTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::firstOrCreate(['name' => 'driver', 'guard_name' => 'web']);
    }

    public function test_driver_can_assign_return_to_self(): void
    {
        $driver = User::factory()->create();
        $driver->assignRole('driver');

        $contract = Contract::factory()->status('awaiting_return')->create([
            'return_driver_id' => null,
        ]);

        $this->actingAs($driver);

        $component = app(RentalRequestAwaitingReturnList::class);
        $component->mount();
        $component->assignReturnToDriver($contract->id);

        $freshContract = $contract->fresh();
        $this->assertEquals($driver->id, $freshContract->return_driver_id);
        $this->assertEquals('Return assigned to you successfully.', session('success'));
    }

    public function test_driver_cannot_take_return_assigned_to_someone_else(): void
    {
        $primaryDriver = User::factory()->create();
        $primaryDriver->assignRole('driver');

        $otherDriver = User::factory()->create();
        $otherDriver->assignRole('driver');

        $contract = Contract::factory()->status('awaiting_return')->create([
            'return_driver_id' => $primaryDriver->id,
        ]);

        $this->actingAs($otherDriver);

        $component = app(RentalRequestAwaitingReturnList::class);
        $component->mount();
        $component->assignReturnToDriver($contract->id);

        $this->assertEquals($primaryDriver->id, $contract->fresh()->return_driver_id);
        $this->assertEquals('This return is already assigned to another driver.', session('error'));
    }

    public function test_non_driver_cannot_claim_return(): void
    {
        $user = User::factory()->create();
        $contract = Contract::factory()->status('awaiting_return')->create([
            'return_driver_id' => null,
        ]);

        $this->actingAs($user);

        $component = app(RentalRequestAwaitingReturnList::class);
        $component->mount();
        $component->assignReturnToDriver($contract->id);

        $this->assertNull($contract->fresh()->return_driver_id);
        $this->assertEquals('Only drivers can claim return tasks.', session('error'));
    }
}
