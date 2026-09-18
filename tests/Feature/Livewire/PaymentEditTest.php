<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Pages\Panel\Expert\Payments\PaymentEdit;
use App\Models\Car;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentEditTest extends TestCase
{
    use RefreshDatabase;

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
        $component->note = 'Legacy record reviewed';
        $component->updatePayment();

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'discount_reason' => null,
            'note' => 'Legacy record reviewed',
        ]);
    }
}
