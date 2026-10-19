<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Pages\Panel\Expert\Payments\PaymentEdit;
use App\Models\Car;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\TestCase;

class PaymentEditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        URL::forceRootUrl('http://localhost');
    }

    public function test_discount_reason_is_validated_and_saved_when_editing_a_discount(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $car = Car::factory()->create();
        $contract = Contract::factory()->for($user)->for($customer)->for($car)->create();
        $payment = Payment::factory()->for($contract)->for($customer)->for($car)->create([
            'payment_type' => 'discount',
            'discount_reason' => 'extension_discount',
            'currency' => 'AED',
            'amount' => 100,
            'amount_in_aed' => 100,
        ]);

        $this->actingAs($user);
        $component = app(PaymentEdit::class);
        $component->mount($payment->id);
        $component->discount_reason = 'invalid-reason';

        try {
            $component->updatePayment();
            $this->fail('Invalid discount reason should be rejected.');
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->assertArrayHasKey('discount_reason', $exception->errors());
        }

        $component->discount_reason = 'management_discount';
        $component->updatePayment();

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'payment_type' => 'discount',
            'discount_reason' => 'management_discount',
        ]);
    }

    public function test_legacy_discount_without_reason_can_still_be_saved(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $car = Car::factory()->create();
        $contract = Contract::factory()->for($user)->for($customer)->for($car)->create();
        $payment = Payment::factory()->for($contract)->for($customer)->for($car)->create([
            'payment_type' => 'discount',
            'discount_reason' => null,
            'currency' => 'AED',
            'amount' => 100,
            'amount_in_aed' => 100,
        ]);

        $this->actingAs($user);
        $component = app(PaymentEdit::class);
        $component->mount($payment->id);
        $component->discount_reason = '';
        $component->note = 'Legacy record reviewed';
        $component->updatePayment();

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'discount_reason' => null,
            'note' => 'Legacy record reviewed',
        ]);
    }

    public function test_editing_payment_type_requires_and_clears_discount_reasons(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $payment = Payment::factory()->create([
            'payment_type' => 'rental_fee', 'currency' => 'AED',
            'amount' => 100, 'amount_in_aed' => 100,
        ]);

        Livewire::test(PaymentEdit::class, ['paymentId' => $payment->id])
            ->assertDontSee('Select discount reason')
            ->set('payment_type', 'discount')
            ->assertSee('Select discount reason')
            ->assertDontSee('Item Owner')
            ->call('updatePayment')
            ->assertHasErrors(['discount_reason' => 'required'])
            ->set('discount_reason', 'extension_discount')
            ->set('payment_type', 'rental_fee')
            ->assertSet('discount_reason', null)
            ->assertHasNoErrors('discount_reason')
            ->set('discount_reason', 'invalid-stale-reason')
            ->call('updatePayment')
            ->assertHasNoErrors();

        $this->assertNull($payment->fresh()->discount_reason);
        $this->assertSame('rental_fee', $payment->fresh()->payment_type);

        Livewire::test(PaymentEdit::class, ['paymentId' => $payment->id])
            ->set('payment_type', 'discount')
            ->set('discount_reason', 'delivery_return_discount')
            ->call('updatePayment')
            ->assertHasNoErrors();
        $this->assertSame('delivery_return_discount', $payment->fresh()->discount_reason);
        $this->assertSame('discount', $payment->fresh()->payment_type);
        Livewire::test(PaymentEdit::class, ['paymentId' => $payment->id])
            ->set('payment_type', 'rental_fee')
            ->call('updatePayment')
            ->assertHasNoErrors();
        $this->assertNull($payment->fresh()->discount_reason);
    }

    public function test_legacy_financial_edit_requires_a_reason_and_preserves_discount_calculation(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $contract = Contract::factory()->create(['total_price' => 1000]);
        $payment = Payment::factory()->for($contract)->create([
            'payment_type' => 'discount', 'discount_reason' => null,
            'currency' => 'AED', 'amount' => 100, 'amount_in_aed' => 100,
        ]);

        Livewire::test(PaymentEdit::class, ['paymentId' => $payment->id])
            ->assertSee('Reason not recorded (legacy)')
            ->set('amount', 150)
            ->call('updatePayment')
            ->assertHasErrors(['discount_reason' => 'required'])
            ->set('discount_reason', 'rental_rate_discount')
            ->call('updatePayment')
            ->assertHasNoErrors();

        $this->assertSame('rental_rate_discount', $payment->fresh()->discount_reason);
        $this->assertSame(850.0, $contract->fresh()->calculateRemainingBalance());
    }

    public function test_existing_classification_cannot_be_cleared_but_legacy_classification_can_be_retained(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $payment = Payment::factory()->create([
            'payment_type' => 'discount', 'discount_reason' => 'extension_discount', 'currency' => 'AED',
        ]);
        Livewire::test(PaymentEdit::class, ['paymentId' => $payment->id])
            ->set('discount_reason', '')
            ->call('updatePayment')
            ->assertHasErrors(['discount_reason' => 'required']);

        $payment->update(['discount_reason' => 'item_owner_discount']);
        Livewire::test(PaymentEdit::class, ['paymentId' => $payment->id])
            ->assertSee('Item Owner (legacy)')
            ->set('note', 'Keep historical classification')
            ->call('updatePayment')
            ->assertHasNoErrors();
        $this->assertSame('item_owner_discount', $payment->fresh()->discount_reason);
    }

    public function test_classifying_a_legacy_discount_or_switching_its_type_removes_blank_exemption(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $payment = Payment::factory()->create([
            'payment_type' => 'discount', 'discount_reason' => null, 'currency' => 'AED',
        ]);
        Livewire::test(PaymentEdit::class, ['paymentId' => $payment->id])
            ->set('discount_reason', 'extension_discount')
            ->set('discount_reason', '')
            ->call('updatePayment')
            ->assertHasErrors(['discount_reason' => 'required']);

        Livewire::test(PaymentEdit::class, ['paymentId' => $payment->id])
            ->set('payment_type', 'rental_fee')
            ->set('payment_type', 'discount')
            ->call('updatePayment')
            ->assertHasErrors(['discount_reason' => 'required']);
    }
}
