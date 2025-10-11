<?php

namespace Tests\Feature\Livewire\RentalRequest;

use App\Livewire\Pages\Panel\Expert\RentalRequest\RentalRequestPayment;
use App\Models\Car;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\CustomerDocument;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class RentalRequestPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('myimage');
    }

    public function test_submit_payment_persists_payment_record(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $customer = Customer::factory()->create(['email' => 'payment@example.com']);
        $contract = Contract::factory()
            ->for($user)
            ->for($customer)
            ->for(Car::factory())
            ->status('payment')
            ->create(['total_price' => 1500, 'meta' => []]);

        CustomerDocument::factory()->for($customer)->for($contract)->create();

        $component = Mockery::mock(RentalRequestPayment::class)->makePartial();
        $component->shouldAllowMockingProtectedMethods();
        $component->mount($contract->id, $customer->id);

        $component->amount = 500;
        $component->currency = 'AED';
        $component->payment_type = 'rental_fee';
        $component->payment_date = now()->toDateString();
        $component->payment_method = 'cash';
        $component->is_refundable = false;
        $component->rate = null;
        $component->receipt = null;

        $component->shouldReceive('validate')->once()->andReturn([
            'amount' => 500,
            'currency' => 'AED',
            'payment_type' => 'rental_fee',
            'payment_date' => now()->toDateString(),
            'payment_method' => 'cash',
            'is_refundable' => false,
            'rate' => null,
            'receipt' => null,
        ]);

        $component->submitPayment();

        $payment = Payment::where('contract_id', $contract->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals(500, (float) $payment->amount);
        $this->assertEquals('AED', $payment->currency);
        $this->assertEquals($user->id, $payment->user_id);
        $this->assertEquals('pending', $payment->approval_status);
        $this->assertEquals('Payment was successfully added!', session('message'));
    }

    public function test_submit_deposit_stores_security_note_in_meta(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $customer = Customer::factory()->create();
        $contract = Contract::factory()
            ->for($user)
            ->for($customer)
            ->for(Car::factory())
            ->status('payment')
            ->create(['meta' => []]);

        $component = app(RentalRequestPayment::class);
        $component->mount($contract->id, $customer->id);
        $component->security_note = 'Hold AED 2000 until return inspection';
        $component->submitDeposit();

        $contract->refresh();
        $this->assertEquals('Hold AED 2000 until return inspection', $contract->meta['security_deposit_note']);
        $this->assertEquals('Security deposit information was successfully saved.', session('message'));
        $this->assertEquals('', $component->security_note);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
