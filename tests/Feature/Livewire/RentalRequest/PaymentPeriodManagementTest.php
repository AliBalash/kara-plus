<?php

namespace Tests\Feature\Livewire\RentalRequest;

use App\Livewire\Pages\Panel\Expert\RentalRequest\RentalRequestPayment;
use App\Models\Car;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\User;
use App\Services\ContractPaymentPeriodService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Tests\TestCase;

class PaymentPeriodManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['ai.enabled' => false]);
    }

    private function context(): array
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $contract = Contract::factory()->for($user)->for(Customer::factory())->for(Car::factory())
            ->status('payment')->create(['pickup_date' => '2026-01-14', 'return_date' => '2026-03-30']);

        return [$contract, $user];
    }

    private function page(Contract $contract)
    {
        return Livewire::test(RentalRequestPayment::class, ['contractId' => $contract->id, 'customerId' => $contract->customer_id]);
    }

    public function test_ranges_and_default_persist_for_another_user_and_navigation_does_not_change_default(): void
    {
        [$contract, $user] = $this->context();
        $this->page($contract)
            ->assertSee('Create a payment range')
            ->assertDontSee('rolling 30-day')
            ->set('periodForm', ['title' => 'First range', 'starts_on' => '2026-01-14', 'ends_on' => '2026-01-30', 'is_default' => true])
            ->call('savePaymentPeriod')->assertHasNoErrors()
            ->assertSee('First range')->assertSee('before Jan 30, 2026');
        $period = $contract->paymentPeriods()->firstOrFail();
        $this->assertSame($user->id, $period->created_by);
        $this->actingAs(User::factory()->create());
        $page = $this->page($contract)->assertSet('paymentPeriodFilter', 'period-'.$period->id);
        $page->call('selectPaymentPeriod', 'all')->assertSet('paymentPeriodFilter', 'all');
        $this->assertTrue($period->fresh()->is_default);
        $this->page($contract)->assertSet('paymentPeriodFilter', 'period-'.$period->id);
    }

    public function test_form_rejects_overlap_and_suggests_next_start_without_creating_a_range(): void
    {
        [$contract, $user] = $this->context();
        $period = app(ContractPaymentPeriodService::class)->save($contract->id, $user->id, [
            'starts_on' => '2026-01-14', 'ends_on' => '2026-01-30', 'is_default' => true,
        ]);
        $page = $this->page($contract)->assertSet('periodForm.starts_on', '2026-01-30');
        $this->assertSame(1, $contract->paymentPeriods()->count());
        $page->set('periodForm', ['title' => '', 'starts_on' => '2026-01-20', 'ends_on' => '2026-02-10', 'is_default' => true])
            ->call('savePaymentPeriod')->assertHasErrors('periodForm.ends_on')
            ->assertSee('This range overlaps');
        $this->assertSame(1, $contract->paymentPeriods()->count());
        $this->assertTrue($period->fresh()->is_default);
    }

    public function test_edit_archive_restore_and_default_controls_keep_payments_visible(): void
    {
        [$contract, $user] = $this->context();
        Payment::factory()->for($contract)->for($contract->customer)->for($contract->car)->for($user)->create([
            'payment_date' => '2026-01-20', 'note' => 'Keep this payment',
        ]);
        $period = app(ContractPaymentPeriodService::class)->save($contract->id, $user->id, [
            'title' => 'Original range', 'starts_on' => '2026-01-14', 'ends_on' => '2026-01-30', 'is_default' => true,
        ]);
        $page = $this->page($contract)->assertSee('Keep this payment')
            ->call('editPaymentPeriod', $period->id)->assertSet('editingPeriodId', $period->id)
            ->set('periodForm.title', 'Updated range')->call('savePaymentPeriod')->assertHasNoErrors()
            ->assertSee('Updated range')->assertSet('editingPeriodId', null);
        $page->call('deletePaymentPeriod', $period->id)->assertSee('Deleted ranges (history)')
            ->assertSet('paymentPeriodFilter', 'all')->assertSee('Keep this payment');
        $this->page($contract)->assertSet('paymentPeriodFilter', 'all')->assertSee('Updated range');
        $page->call('restorePaymentPeriod', $period->id)->assertHasNoErrors()
            ->call('setDefaultPaymentPeriod', $period->id);
        $this->page($contract)->assertSet('paymentPeriodFilter', 'period-'.$period->id);
        $page->call('setDefaultPaymentPeriod');
        $this->page($contract)->assertSet('paymentPeriodFilter', 'all');
        $this->assertSame(1, $contract->payments()->count());
    }

    public function test_unassigned_filter_shows_only_uncovered_payment_dates_and_keeps_contract_balance(): void
    {
        [$contract, $user] = $this->context();
        foreach ([['2026-01-10', 'Early payment'], ['2026-01-20', 'Covered payment'], ['2026-01-30', 'Boundary payment']] as [$date, $note]) {
            Payment::factory()->for($contract)->for($contract->customer)->for($contract->car)->for($user)->create([
                'payment_type' => 'rental_fee', 'payment_date' => $date, 'note' => $note, 'amount' => 100, 'amount_in_aed' => 100,
            ]);
        }
        $period = app(ContractPaymentPeriodService::class)->save($contract->id, $user->id, [
            'starts_on' => '2026-01-14', 'ends_on' => '2026-01-30', 'is_default' => false,
        ]);
        $balance = round($contract->calculateRemainingBalance(), 2);
        $page = $this->page($contract)->call('selectPaymentPeriod', 'unassigned')
            ->assertSee('Early payment')->assertSee('Boundary payment')->assertDontSee('Covered payment')
            ->assertSet('remainingBalance', $balance);
        $page->call('selectPaymentPeriod', 'period-'.$period->id)
            ->assertSee('Covered payment')->assertDontSee('Early payment')->assertDontSee('Boundary payment')
            ->assertSet('remainingBalance', $balance);
    }

    public function test_failed_restore_displays_an_error_and_keeps_history(): void
    {
        [$contract, $user] = $this->context();
        $service = app(ContractPaymentPeriodService::class);
        $data = ['starts_on' => '2026-01-14', 'ends_on' => '2026-01-30', 'is_default' => false];
        $period = $service->save($contract->id, $user->id, $data);
        $service->archive($contract->id, $user->id, $period->id);
        $service->save($contract->id, $user->id, $data);
        $this->page($contract)->call('restorePaymentPeriod', $period->id)
            ->assertHasErrors('periodAction')->assertSee('This range overlaps')->assertSee('Deleted ranges (history)');
        $this->assertSoftDeleted($period);
    }

    public function test_client_cannot_switch_the_contract_id_during_a_livewire_request(): void
    {
        [$contract] = $this->context();
        $this->expectException(CannotUpdateLockedPropertyException::class);
        $this->page($contract)->set('contractId', $contract->id + 1);
    }

    public function test_archiving_the_selected_range_returns_to_the_remaining_contract_default(): void
    {
        [$contract, $user] = $this->context();
        $service = app(ContractPaymentPeriodService::class);
        $default = $service->save($contract->id, $user->id, [
            'starts_on' => '2026-01-14', 'ends_on' => '2026-01-30', 'is_default' => true,
        ]);
        $other = $service->save($contract->id, $user->id, [
            'starts_on' => '2026-01-30', 'ends_on' => '2026-02-28', 'is_default' => false,
        ]);
        $this->page($contract)->call('selectPaymentPeriod', 'period-'.$other->id)
            ->call('archivePaymentPeriod', $other->id)
            ->assertSet('paymentPeriodFilter', 'period-'.$default->id);
        $this->assertTrue($default->fresh()->is_default);
    }

    public function test_management_buttons_are_visible_without_selecting_a_range_and_earlier_edits_are_rejected(): void
    {
        [$contract, $user] = $this->context();
        $service = app(ContractPaymentPeriodService::class);
        $first = $service->save($contract->id, $user->id, [
            'title' => 'Earlier', 'starts_on' => '2026-01-14', 'ends_on' => '2026-01-30', 'is_default' => false,
        ]);
        $last = $service->save($contract->id, $user->id, [
            'title' => 'Last', 'starts_on' => '2026-01-30', 'ends_on' => '2026-02-28', 'is_default' => false,
        ]);
        $page = $this->page($contract)->assertSet('paymentPeriodFilter', 'all')
            ->assertSeeHtml('wire:click="deletePaymentPeriod('.$first->id.')"')
            ->assertSeeHtml('wire:click="deletePaymentPeriod('.$last->id.')"')
            ->assertSeeHtml('wire:click="editPaymentPeriod('.$last->id.')"')
            ->assertDontSeeHtml('wire:click="editPaymentPeriod('.$first->id.')"')
            ->assertSee('Only the last range can be edited.');
        $page->call('editPaymentPeriod', $first->id)->assertHasErrors('periodAction')
            ->assertSet('editingPeriodId', null);
        $page->call('editPaymentPeriod', $last->id)->assertHasNoErrors()
            ->assertDispatched('payment-range-edit')
            ->assertSet('paymentPeriodFilter', 'period-'.$last->id)
            ->assertSet('periodForm.starts_on', '2026-01-30')
            ->assertSet('periodForm.ends_on', '2026-02-28')
            ->call('resetPeriodForm')->assertSet('editingPeriodId', null);
    }

    public function test_save_rechecks_the_last_range_when_another_user_creates_a_later_range(): void
    {
        [$contract, $user] = $this->context();
        $service = app(ContractPaymentPeriodService::class);
        $period = $service->save($contract->id, $user->id, [
            'title' => 'Original', 'starts_on' => '2026-01-14', 'ends_on' => '2026-01-30', 'is_default' => true,
        ]);
        $page = $this->page($contract)->call('editPaymentPeriod', $period->id)
            ->set('periodForm.title', 'Must not save');
        $service->save($contract->id, User::factory()->create()->id, [
            'starts_on' => '2026-01-30', 'ends_on' => '2026-02-28', 'is_default' => false,
        ]);
        $page->call('savePaymentPeriod')->assertHasErrors('periodForm.period')
            ->assertSee('Only the last active range by date can be edited.');
        $this->assertSame('Original', $period->fresh()->title);
        $this->assertTrue($period->fresh()->is_default);
    }

    public function test_edit_rejects_invalid_and_overlapping_dates_without_losing_the_saved_default(): void
    {
        [$contract, $user] = $this->context();
        $service = app(ContractPaymentPeriodService::class);
        $service->save($contract->id, $user->id, [
            'starts_on' => '2026-01-14', 'ends_on' => '2026-01-30', 'is_default' => false,
        ]);
        $last = $service->save($contract->id, $user->id, [
            'starts_on' => '2026-01-30', 'ends_on' => '2026-02-28', 'is_default' => true,
        ]);
        $before = $last->fresh()->getAttributes();
        $page = $this->page($contract)->call('editPaymentPeriod', $last->id);
        foreach ([['2026-01-29', '2026-02-28'], ['2026-01-30', '2026-01-30'],
            ['2026-02-30', '2026-03-10']] as [$start, $end]) {
            $page->set('periodForm.starts_on', $start)->set('periodForm.ends_on', $end)
                ->call('savePaymentPeriod')->assertHasErrors()
                ->assertSet('editingPeriodId', $last->id);
            $this->assertSame($before, $last->fresh()->getAttributes());
        }
    }

    public function test_editing_dates_updates_the_filter_without_changing_payments_or_finances(): void
    {
        [$contract, $user] = $this->context();
        $payment = Payment::factory()->for($contract)->for($contract->customer)->for($contract->car)->for($user)->create([
            'payment_type' => 'rental_fee', 'payment_date' => '2026-01-30', 'note' => 'Newly included payment',
            'amount' => 100, 'amount_in_aed' => 100,
        ]);
        $period = app(ContractPaymentPeriodService::class)->save($contract->id, $user->id, [
            'starts_on' => '2026-01-14', 'ends_on' => '2026-01-30', 'is_default' => true,
        ]);
        $paymentBefore = $payment->fresh()->getAttributes();
        $contractBefore = $contract->fresh()->getAttributes();
        $balance = round($contract->calculateRemainingBalance(), 2);
        $this->page($contract)->assertDontSee('Newly included payment')
            ->call('editPaymentPeriod', $period->id)
            ->set('periodForm.ends_on', '2026-01-31')->call('savePaymentPeriod')->assertHasNoErrors()
            ->assertSee('Newly included payment')->assertSet('remainingBalance', $balance);
        $this->assertSame($paymentBefore, $payment->fresh()->getAttributes());
        $this->assertSame($contractBefore, $contract->fresh()->getAttributes());
    }

    public function test_deleting_the_default_range_clears_the_filter_and_unlocks_the_previous_range(): void
    {
        [$contract, $user] = $this->context();
        $service = app(ContractPaymentPeriodService::class);
        $first = $service->save($contract->id, $user->id, [
            'starts_on' => '2026-01-14', 'ends_on' => '2026-01-30', 'is_default' => false,
        ]);
        $last = $service->save($contract->id, $user->id, [
            'starts_on' => '2026-01-30', 'ends_on' => '2026-02-28', 'is_default' => true,
        ]);
        $this->page($contract)->call('deletePaymentPeriod', $last->id)
            ->assertSet('paymentPeriodFilter', 'all')->assertSee('Deleted ranges (history)')
            ->assertSeeHtml('wire:click="editPaymentPeriod('.$first->id.')"')
            ->call('editPaymentPeriod', $first->id)->assertHasNoErrors()
            ->assertSet('editingPeriodId', $first->id);
        $this->assertSoftDeleted($last);
        $this->assertSame($user->id, $last->fresh()->archived_by);
        $this->page($contract)->assertSet('paymentPeriodFilter', 'all');
    }
}
