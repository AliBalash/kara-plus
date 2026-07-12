<?php

namespace Tests\Feature\Livewire\RentalRequest;

use App\Livewire\Pages\Panel\Expert\RentalRequest\RentalRequestPayment;
use App\Models\Car;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\CustomerDocument;
use App\Models\Payment;
use App\Models\User;
use App\Services\Media\DeferredImageUploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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
            ->create(['total_price' => 1500.75, 'meta' => []]);

        CustomerDocument::factory()->for($customer)->for($contract)->create();

        $component = Mockery::mock(RentalRequestPayment::class)->makePartial();
        $component->shouldAllowMockingProtectedMethods();
        $component->mount($contract->id, $customer->id);

        $component->amount = 500.5;
        $component->currency = 'AED';
        $component->payment_type = 'rental_fee';
        $component->payment_date = now()->toDateString();
        $component->payment_method = 'cash';
        $component->is_refundable = false;
        $component->rate = null;
        $component->receipt = null;

        $component->shouldReceive('validate')->once()->andReturn([
            'amount' => 500.5,
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
        $this->assertEqualsWithDelta(500.5, (float) $payment->amount, 0.01);
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

    public function test_submit_deposit_stores_image_when_provided(): void
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

        $mockUploader = Mockery::mock(DeferredImageUploadService::class);
        $mockUploader->shouldReceive('store')
            ->once()
            ->andReturn('security_deposits/test-image.webp');

        $this->app->instance(DeferredImageUploadService::class, $mockUploader);

        $component = app(RentalRequestPayment::class);
        $component->mount($contract->id, $customer->id);
        $component->security_deposit_image = UploadedFile::fake()->image('deposit.jpg');
        $component->submitDeposit();

        $contract->refresh();

        $this->assertEquals('security_deposits/test-image.webp', $contract->meta['security_deposit_image']);
        $this->assertEquals('Security deposit information was successfully saved.', session('message'));
        $this->assertNull($component->security_deposit_image);
    }

    public function test_delete_payment_removes_receipt_file_after_record_is_deleted(): void
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

        $payment = Payment::factory()
            ->for($contract)
            ->for($customer)
            ->for($user)
            ->for($contract->car)
            ->create([
                'receipt' => 'payments/existing-receipt.webp',
                'payment_type' => 'rental_fee',
            ]);

        Storage::disk('myimage')->put('payments/existing-receipt.webp', 'receipt-content');

        $component = app(RentalRequestPayment::class);
        $component->mount($contract->id, $customer->id);
        $component->deletePayment($payment->id);

        $this->assertDatabaseMissing('payments', ['id' => $payment->id]);
        Storage::disk('myimage')->assertMissing('payments/existing-receipt.webp');
    }

    public function test_existing_payments_table_groups_customer_payments_and_charges(): void
    {
        $user = User::factory()->create();
        $customerPayment = Payment::factory()
            ->for($user)
            ->make([
                'id' => 123,
                'amount' => 20_000_000,
                'currency' => 'IRR',
                'rate' => 387597,
                'amount_in_aed' => 51.60,
                'payment_type' => 'rental_fee',
            ]);

        $chargePayment = Payment::factory()
            ->for($user)
            ->make([
                'id' => 124,
                'amount' => 520,
                'currency' => 'AED',
                'rate' => null,
                'amount_in_aed' => 520,
                'payment_type' => 'fine',
            ]);

        $html = view('livewire.pages.panel.expert.rental-request.partials.existing-payments-table', [
            'existingPayments' => collect([$customerPayment, $chargePayment]),
            'showActions' => false,
        ])->render();
        $normalizedHtml = preg_replace('/\s+/', ' ', $html);

        $this->assertStringContainsString('Customer Payments', $normalizedHtml);
        $this->assertStringContainsString('Charges &amp; Costs', $normalizedHtml);
        $this->assertStringContainsString('20,000,000.00', $normalizedHtml);
        $this->assertStringContainsString('Deducted from balance: 51.60 AED', $normalizedHtml);
        $this->assertStringContainsString('520.00', $normalizedHtml);
        $this->assertStringContainsString('Charge in balance: 520.00 AED', $normalizedHtml);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
