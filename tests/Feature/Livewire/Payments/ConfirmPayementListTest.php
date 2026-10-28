<?php

namespace Tests\Feature\Livewire\Payments;

use App\Livewire\Pages\Panel\Expert\Payments\ConfirmPayementList;
use App\Models\Car;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ConfirmPayementListTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('myimage');
        URL::forceRootUrl('http://localhost');
        config(['ai.enabled' => false]);
    }

    public function test_rental_fee_link_defaults_to_pending_and_excludes_processed_payments_from_the_same_contract(): void
    {
        $this->paymentStatuses();

        Livewire::withQueryParams(['paymentTypeFilter' => 'rental_fee'])->test(ConfirmPayementList::class)
            ->assertSet('paymentTypeFilter', 'rental_fee')->assertSet('statusFilter', 'pending')
            ->assertSee('MARKER-PENDING')->assertDontSee('MARKER-APPROVED')->assertDontSee('MARKER-REJECTED');
    }

    public function test_approval_saves_the_status_and_removes_only_that_payment_from_pending(): void
    {
        $payments = $this->paymentStatuses();
        $anotherPending = $payments['approved']->replicate();
        $anotherPending->fill(['approval_status' => 'pending', 'is_paid' => false, 'note' => 'ANOTHER-PENDING']);
        $anotherPending->save();

        Livewire::test(ConfirmPayementList::class)
            ->call('approve', $payments['pending']->id)
            ->assertSet('statusFilter', 'pending')->assertDontSee('MARKER-PENDING')
            ->assertDontSee('MARKER-APPROVED')->assertSee('ANOTHER-PENDING');

        $this->assertDatabaseHas('payments', ['id' => $payments['pending']->id, 'approval_status' => 'approved', 'is_paid' => true]);
        $this->assertDatabaseHas('payments', ['id' => $anotherPending->id, 'approval_status' => 'pending', 'is_paid' => false]);
    }

    public function test_rejection_removes_the_payment_from_pending_without_deleting_it(): void
    {
        $payments = $this->paymentStatuses();

        Livewire::test(ConfirmPayementList::class)
            ->call('reject', $payments['pending']->id)
            ->assertDontSee('MARKER-PENDING')->assertSee('No payments found.');

        $this->assertDatabaseHas('payments', ['id' => $payments['pending']->id, 'approval_status' => 'rejected', 'is_paid' => false]);
    }

    public function test_clear_filters_restores_pending_and_the_first_page(): void
    {
        $this->paymentStatuses();

        Livewire::test(ConfirmPayementList::class)
            ->set('statusFilter', 'approved')->assertSee('MARKER-APPROVED')
            ->set('currencyFilter', 'USD')->set('paymentTypeFilter', 'rental_fee')
            ->call('setPage', 2)->call('clearFilters')
            ->assertSet('statusFilter', 'pending')->assertSet('currencyFilter', '')
            ->assertSet('paymentTypeFilter', '')->assertSet('paginators.page', 1)
            ->assertSee('MARKER-PENDING')->assertDontSee('MARKER-APPROVED')->assertDontSee('MARKER-REJECTED');
    }

    #[DataProvider('invalidStatuses')]
    public function test_empty_or_invalid_status_in_a_saved_link_falls_back_to_pending(string $status): void
    {
        $this->paymentStatuses();

        Livewire::withQueryParams(['statusFilter' => $status])->test(ConfirmPayementList::class)
            ->assertSet('statusFilter', 'pending')->assertSee('MARKER-PENDING')
            ->assertDontSee('MARKER-APPROVED')->assertDontSee('MARKER-REJECTED');
    }

    public static function invalidStatuses(): array
    {
        return ['empty legacy link' => [''], 'unsupported all status' => ['all'], 'invalid status' => ['unknown']];
    }

    public function test_status_changes_reset_pagination_and_empty_updates_cannot_disable_the_filter(): void
    {
        $this->paymentStatuses();

        Livewire::test(ConfirmPayementList::class)
            ->call('setPage', 2)->set('statusFilter', 'approved')
            ->assertSet('paginators.page', 1)->assertSee('MARKER-APPROVED')->assertDontSee('MARKER-PENDING')
            ->set('statusFilter', '')->assertSet('statusFilter', 'pending')
            ->assertSee('MARKER-PENDING')->assertDontSee('MARKER-APPROVED');
    }

    public function test_explicit_approved_and_rejected_filters_remain_available(): void
    {
        $this->paymentStatuses();

        foreach (['approved', 'rejected'] as $status) {
            Livewire::withQueryParams(['statusFilter' => $status])->test(ConfirmPayementList::class)
                ->assertSet('statusFilter', $status)->assertSee('MARKER-'.strtoupper($status))
                ->assertDontSee('MARKER-PENDING');
        }
    }

    private function paymentStatuses(): array
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $contract = Contract::factory()->for($user)->for(Customer::factory())->for(Car::factory())->create();
        $payments = [];
        foreach (['pending', 'approved', 'rejected'] as $status) {
            $payments[$status] = Payment::factory()->for($user)->for($contract)->for($contract->customer)->for($contract->car)->create([
                'payment_type' => 'rental_fee', 'currency' => 'AED',
                'approval_status' => $status, 'is_paid' => $status === 'approved',
                'note' => 'MARKER-'.strtoupper($status),
            ]);
        }

        return $payments;
    }

    public function test_delete_payment_removes_receipt_file_after_record_deletion(): void
    {
        $payment = Payment::factory()->create([
            'receipt' => 'payments/confirm-list-receipt.webp',
        ]);

        Storage::disk('myimage')->put('payments/confirm-list-receipt.webp', 'receipt');

        app(ConfirmPayementList::class)->deletePayment($payment->id);

        $this->assertDatabaseMissing('payments', ['id' => $payment->id]);
        Storage::disk('myimage')->assertMissing('payments/confirm-list-receipt.webp');
    }
}
