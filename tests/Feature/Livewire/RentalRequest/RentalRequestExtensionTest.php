<?php

namespace Tests\Feature\Livewire\RentalRequest;

use App\Livewire\Pages\Panel\Expert\RentalRequest\RentalRequestExtension;
use App\Models\Car;
use App\Models\Contract;
use App\Models\ContractAmendment;
use App\Models\ContractCharges;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\User;
use App\Services\ContractAmendmentService;
use App\Services\RentalPricingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RentalRequestExtensionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        URL::forceRootUrl('http://localhost');
    }

    public function test_page_previews_all_three_rate_sources_and_requires_review_before_request(): void
    {
        [$contract, $actor] = $this->operationalContract();
        $this->actingAs($actor);

        $component = Livewire::test(RentalRequestExtension::class, ['contractId' => $contract->id])
            ->assertSee('Manage Contract Extensions')
            ->assertSee('Saved contract rate')
            ->assertSee('Current tariff based on extension length')
            ->assertSee('Current automatic daily / weekly / monthly tier')
            ->set('newReturnAt', '2026-09-12T10:00')
            ->set('rateSource', RentalPricingService::RATE_SOURCE_CURRENT_TOTAL_DURATION)
            ->call('preview')
            ->assertHasNoErrors()
            ->assertSet('quote.rate_source', RentalPricingService::RATE_SOURCE_CURRENT_TOTAL_DURATION)
            ->assertSet('quote.resulting_rental_days', 11)
            ->assertSet('quote.impact.rental_days_before', 9)
            ->assertSet('quote.impact.rental_days_after', 11)
            ->call('request')
            ->assertHasErrors(['reviewConfirmed']);

        $this->assertSame(0, ContractAmendment::query()->count());

        $component
            ->set('reviewConfirmed', true)
            ->call('request')
            ->assertHasNoErrors()
            ->assertSee('Pending Approval');

        $amendment = ContractAmendment::query()->sole();
        $this->assertSame('pending_approval', $amendment->status);
        $this->assertSame(RentalPricingService::RATE_SOURCE_CURRENT_TOTAL_DURATION, $amendment->pricing_snapshot['rate_source']);
        $this->assertEqualsWithDelta(1000, (float) $contract->fresh()->total_price, 0.01);
        $this->assertSame('2026-09-10 10:00:00', $contract->fresh()->return_date->format('Y-m-d H:i:s'));
    }

    public function test_pending_edit_and_delete_are_confirmed_and_have_no_contract_financial_effect(): void
    {
        [$contract, $actor] = $this->operationalContract();
        $this->actingAs($actor);

        $component = Livewire::test(RentalRequestExtension::class, ['contractId' => $contract->id])
            ->set('newReturnAt', '2026-09-12T10:00')
            ->call('preview')
            ->set('reviewConfirmed', true)
            ->call('request')
            ->assertHasNoErrors();

        $amendment = ContractAmendment::query()->sole();

        $component
            ->call('edit', $amendment->id)
            ->assertSet('editingAmendmentId', $amendment->id)
            ->set('newReturnAt', '2026-09-13T10:00')
            ->call('preview')
            ->set('reviewConfirmed', true)
            ->call('request')
            ->assertHasNoErrors()
            ->assertSee('Extension #'.$amendment->sequence_no.' is now Pending Approval.')
            ->assertSee('2026-09-13 10:00');

        $this->assertSame('2026-09-13 10:00:00', $amendment->fresh()->new_return_at->format('Y-m-d H:i:s'));
        $this->assertEqualsWithDelta(1000, (float) $contract->fresh()->total_price, 0.01);

        $component
            ->call('prepareDelete', $amendment->id)
            ->assertSet('confirmationAction', 'delete')
            ->assertSet('confirmationImpact.total_delta', 0.0)
            ->call('confirmPreparedAction')
            ->assertHasErrors(['confirmationAccepted']);

        $this->assertNull($amendment->fresh()->deleted_at);

        $component
            ->set('confirmationAccepted', true)
            ->call('confirmPreparedAction')
            ->assertHasNoErrors();

        $this->assertSoftDeleted('contract_amendments', ['id' => $amendment->id]);
        $this->assertEqualsWithDelta(1000, (float) $contract->fresh()->total_price, 0.01);
        $this->assertSame('2026-09-10 10:00:00', $contract->fresh()->return_date->format('Y-m-d H:i:s'));
    }

    public function test_driver_cannot_open_or_invoke_contract_extension_management(): void
    {
        [$contract] = $this->operationalContract();
        Role::findOrCreate('driver', 'web');
        $driver = User::factory()->create();
        $driver->assignRole('driver');

        $this->actingAs($driver)
            ->get(route('rental-requests.extend', $contract->id))
            ->assertRedirect(route('expert.dashboard'));

        Livewire::actingAs($driver)
            ->test(RentalRequestExtension::class, ['contractId' => $contract->id])
            ->assertForbidden();
    }

    public function test_actual_return_blocks_extension_and_explains_why_without_saving(): void
    {
        [$contract, $actor] = $this->operationalContract();
        $contract->update(['actual_return_at' => Carbon::parse('2026-09-10 11:00:00')]);
        $this->actingAs($actor);

        Livewire::test(RentalRequestExtension::class, ['contractId' => $contract->id])
            ->assertSee('Extensions cannot be created or edited after the actual return is recorded.')
            ->set('newReturnAt', '2026-09-12T10:00')
            ->call('preview')
            ->set('reviewConfirmed', true)
            ->call('request')
            ->assertHasErrors(['contract'])
            ->assertSee('This vehicle has already been returned. The extension was not saved.');

        $this->assertSame(0, ContractAmendment::query()->count());
        $this->assertEqualsWithDelta(1000, (float) $contract->fresh()->total_price, 0.01);
    }

    public function test_approved_revision_and_removal_require_confirmation_and_restore_the_contract(): void
    {
        [$contract, $actor] = $this->operationalContract();
        $this->actingAs($actor);
        $pending = app(ContractAmendmentService::class)->requestExtension(
            $contract,
            Carbon::parse('2026-09-12 10:00:00'),
            $actor->id,
        );

        $component = Livewire::test(RentalRequestExtension::class, ['contractId' => $contract->id])
            ->call('prepareApproval', $pending->id)
            ->assertSet('confirmationAction', 'approve')
            ->assertSet('confirmationImpact.return_after', '2026-09-12 10:00')
            ->call('confirmPreparedAction')
            ->assertHasErrors(['confirmationAccepted'])
            ->set('confirmationAccepted', true)
            ->call('confirmPreparedAction')
            ->assertHasNoErrors();

        $approved = $pending->fresh();
        $this->assertSame('approved', $approved->status);
        $approvedTotal = (float) $approved->total_amount;
        $this->assertEqualsWithDelta(1000 + $approvedTotal, (float) $contract->fresh()->total_price, 0.01);

        $component
            ->call('edit', $approved->id)
            ->assertSet('editingApproved', true)
            ->set('newReturnAt', '2026-09-13T10:00')
            ->set('rateSource', RentalPricingService::RATE_SOURCE_CURRENT_TOTAL_DURATION)
            ->call('preview')
            ->set('reviewConfirmed', true)
            ->call('request')
            ->assertHasNoErrors();

        $replacement = ContractAmendment::query()
            ->where('type', ContractAmendment::TYPE_EXTENSION)
            ->where('status', 'approved')
            ->sole();
        $this->assertSame('superseded', $approved->fresh()->status);
        $this->assertSame('2026-09-13 10:00:00', $contract->fresh()->return_date->format('Y-m-d H:i:s'));

        $component
            ->call('prepareDelete', $replacement->id)
            ->assertSet('confirmationAction', 'delete')
            ->assertSet('confirmationImpact.return_after', '2026-09-10 10:00')
            ->set('confirmationAccepted', true)
            ->call('confirmPreparedAction')
            ->assertHasNoErrors()
            ->assertSee('Financial reversal');

        $this->assertSame('voided', $replacement->fresh()->status);
        $this->assertSame('2026-09-10 10:00:00', $contract->fresh()->return_date->format('Y-m-d H:i:s'));
        $this->assertEqualsWithDelta(1000, (float) $contract->fresh()->total_price, 0.01);
        $this->assertEqualsWithDelta(0, (float) $contract->charges()->where('source_type', 'amendment')->sum('amount'), 0.01);
    }

    public function test_confirmation_is_invalidated_when_customer_balance_changes_after_review(): void
    {
        [$contract, $actor] = $this->operationalContract();
        $this->actingAs($actor);
        $pending = app(ContractAmendmentService::class)->requestExtension(
            $contract,
            Carbon::parse('2026-09-12 10:00:00'),
            $actor->id,
        );

        $component = Livewire::test(RentalRequestExtension::class, ['contractId' => $contract->id])
            ->call('prepareApproval', $pending->id);
        $reviewedBalance = (float) $component->get('confirmationImpact.balance_before');

        Payment::factory()->for($contract)->for($contract->customer)->for($contract->car)->paid()->create([
            'payment_type' => 'rental_fee',
            'currency' => 'AED',
            'amount' => 100,
            'amount_in_aed' => 100,
            'payment_date' => '2026-09-10',
        ]);

        $component
            ->set('confirmationAccepted', true)
            ->call('confirmPreparedAction')
            ->assertHasErrors(['amendment'])
            ->assertSet('confirmationAccepted', false)
            ->assertSet('confirmationImpact.balance_before', $reviewedBalance - 100);

        $this->assertSame('pending_approval', $pending->fresh()->status);
        $this->assertEqualsWithDelta(1000, (float) $contract->fresh()->total_price, 0.01);
    }

    private function operationalContract(): array
    {
        $actor = User::factory()->create();
        $car = Car::factory()->available()->create([
            'price_per_day_short' => 300,
            'price_per_day_mid' => 200,
            'price_per_day_long' => 150,
        ]);
        $contract = Contract::factory()->for($actor)->for(Customer::factory())->for($car)->create([
            'current_status' => 'assigned',
            'pickup_date' => Carbon::parse('2026-09-01 10:00:00'),
            'return_date' => Carbon::parse('2026-09-10 10:00:00'),
            'total_price' => 1000,
            'used_daily_rate' => 230,
        ]);
        ContractCharges::factory()->for($contract)->create([
            'title' => 'base_rental',
            'type' => 'base',
            'amount' => 1000,
            'source_type' => 'original',
        ]);
        $contract->update(['current_status' => 'awaiting_return']);

        return [$contract->fresh(), $actor];
    }
}
