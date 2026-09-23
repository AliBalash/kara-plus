<?php

namespace Tests\Feature\Livewire\RentalRequest;

use App\Livewire\Pages\Panel\Expert\RentalRequest\RentalRequestPayment;
use App\Models\Car;
use App\Models\Contract;
use App\Models\ContractAmendment;
use App\Models\ContractBalanceTransfer;
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

    public function test_discount_payment_requires_a_valid_discount_reason_and_persists_it(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $contract = Contract::factory()->for($user)->for($customer)->for(Car::factory())->status('payment')->create();

        $baseFields = [
            'amount' => 125,
            'currency' => 'AED',
            'payment_type' => 'discount',
            'payment_date' => now()->toDateString(),
            'payment_method' => 'cash',
            'is_refundable' => false,
        ];

        $this->actingAs($user);
        $component = app(RentalRequestPayment::class);
        $component->mount($contract->id, $customer->id);
        foreach ($baseFields as $field => $value) {
            $component->{$field} = $value;
        }

        try {
            $component->submitPayment();
            $this->fail('Discount reason should be required.');
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->assertArrayHasKey('discount_reason', $exception->errors());
        }

        $component->discount_reason = 'not-a-reason';
        try {
            $component->submitPayment();
            $this->fail('Invalid discount reason should be rejected.');
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->assertArrayHasKey('discount_reason', $exception->errors());
        }

        $component->discount_reason = 'extension_discount';
        $component->submitPayment();

        $this->assertDatabaseHas('payments', [
            'contract_id' => $contract->id,
            'payment_type' => 'discount',
            'discount_reason' => 'extension_discount',
        ]);
    }

    public function test_non_discount_payment_clears_discount_reason(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $contract = Contract::factory()->for($user)->for($customer)->for(Car::factory())->status('payment')->create();

        $this->actingAs($user);
        $component = app(RentalRequestPayment::class);
        $component->mount($contract->id, $customer->id);
        $component->payment_type = 'discount';
        $component->discount_reason = 'management_discount';
        $component->updatedPaymentType('rental_fee');
        $component->payment_type = 'rental_fee';
        $component->amount = 100;
        $component->currency = 'AED';
        $component->payment_date = now()->toDateString();
        $component->payment_method = 'cash';
        $component->is_refundable = false;
        $component->submitPayment();

        $this->assertDatabaseHas('payments', [
            'contract_id' => $contract->id,
            'payment_type' => 'rental_fee',
            'discount_reason' => null,
        ]);
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
        $this->assertEquals($contract->customer_id, $payment->customer_id);
        $this->assertEquals($contract->car_id, $payment->car_id);
        $this->assertEquals('pending', $payment->approval_status);
        $this->assertEquals('Payment was successfully added!', session('message'));
    }

    public function test_payment_ignores_a_mismatched_route_customer_and_uses_the_contract_relations(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $contractCustomer = Customer::factory()->create();
        $unrelatedCustomer = Customer::factory()->create();
        $car = Car::factory()->create();
        $contract = Contract::factory()
            ->for($user)
            ->for($contractCustomer)
            ->for($car)
            ->status('payment')
            ->create(['total_price' => 100]);

        $component = app(RentalRequestPayment::class);
        $component->mount($contract->id, $unrelatedCustomer->id);
        $this->assertSame($contractCustomer->id, $component->customerId);
        $component->amount = 100;
        $component->currency = 'AED';
        $component->payment_type = 'rental_fee';
        $component->payment_date = now()->toDateString();
        $component->payment_method = 'cash';
        $component->submitPayment();

        $payment = Payment::query()->where('contract_id', $contract->id)->sole();
        $this->assertSame($contractCustomer->id, $payment->customer_id);
        $this->assertSame($car->id, $payment->car_id);
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

    public function test_submit_damage_payment_stores_up_to_five_damage_images(): void
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

        CustomerDocument::factory()->for($customer)->for($contract)->create();

        $storedPaths = collect(range(1, 5))
            ->map(fn (int $index) => "payment_receipts/damage-{$index}.jpg")
            ->all();

        $mockUploader = Mockery::mock(DeferredImageUploadService::class);
        $mockUploader->shouldReceive('store')
            ->times(5)
            ->andReturn(...$storedPaths);

        $this->app->instance(DeferredImageUploadService::class, $mockUploader);

        $component = app(RentalRequestPayment::class);
        $component->mount($contract->id, $customer->id);
        $component->amount = 450;
        $component->currency = 'AED';
        $component->payment_type = 'damage';
        $component->payment_date = now()->toDateString();
        $component->payment_method = 'cash';
        $component->is_refundable = false;
        $component->damageReceipts = [
            UploadedFile::fake()->image('damage-1.jpg'),
            UploadedFile::fake()->image('damage-2.jpg'),
            UploadedFile::fake()->image('damage-3.jpg'),
            UploadedFile::fake()->image('damage-4.jpg'),
            UploadedFile::fake()->image('damage-5.jpg'),
        ];

        $component->submitPayment();

        $payment = Payment::where('contract_id', $contract->id)
            ->where('payment_type', 'damage')
            ->first();

        $this->assertNotNull($payment);
        $this->assertSame($storedPaths[0], $payment->receipt);
        $this->assertSame($storedPaths, $payment->damage_images);
        $this->assertEquals('Payment was successfully added!', session('message'));
    }

    public function test_submit_damage_payment_allows_missing_damage_images(): void
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

        CustomerDocument::factory()->for($customer)->for($contract)->create();

        $component = app(RentalRequestPayment::class);
        $component->mount($contract->id, $customer->id);
        $component->amount = 200;
        $component->currency = 'AED';
        $component->payment_type = 'damage';
        $component->payment_date = now()->toDateString();
        $component->payment_method = 'cash';
        $component->is_refundable = false;
        $component->damageReceipts = [];

        $component->submitPayment();

        $payment = Payment::where('contract_id', $contract->id)
            ->where('payment_type', 'damage')
            ->first();

        $this->assertNotNull($payment);
        $this->assertNull($payment->receipt);
        $this->assertNull($payment->damage_images);
        $this->assertEquals('Payment was successfully added!', session('message'));
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

    public function test_delete_damage_payment_removes_all_damage_images(): void
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
                'receipt' => 'payments/damage-1.webp',
                'damage_images' => [
                    'payments/damage-1.webp',
                    'payments/damage-2.webp',
                    'payments/damage-3.webp',
                ],
                'payment_type' => 'damage',
            ]);

        Storage::disk('myimage')->put('payments/damage-1.webp', 'one');
        Storage::disk('myimage')->put('payments/damage-2.webp', 'two');
        Storage::disk('myimage')->put('payments/damage-3.webp', 'three');

        $component = app(RentalRequestPayment::class);
        $component->mount($contract->id, $customer->id);
        $component->deletePayment($payment->id);

        $this->assertDatabaseMissing('payments', ['id' => $payment->id]);
        Storage::disk('myimage')->assertMissing('payments/damage-1.webp');
        Storage::disk('myimage')->assertMissing('payments/damage-2.webp');
        Storage::disk('myimage')->assertMissing('payments/damage-3.webp');
    }

    public function test_existing_payments_table_uses_a_single_all_entries_ledger(): void
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
                'created_at' => now()->setTime(10, 15, 0),
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
                'created_at' => now()->setTime(12, 45, 0),
            ]);

        $html = view('livewire.pages.panel.expert.rental-request.partials.existing-payments-table', [
            'existingPayments' => collect([$customerPayment, $chargePayment]),
            'showActions' => false,
        ])->render();
        $normalizedHtml = preg_replace('/\s+/', ' ', $html);

        $this->assertStringContainsString('Payment ledger', $normalizedHtml);
        $this->assertStringContainsString('All Entries', $normalizedHtml);
        $this->assertStringContainsString('20,000,000.00', $normalizedHtml);
        $this->assertStringContainsString('Deducted from balance: 51.60 AED', $normalizedHtml);
        $this->assertStringContainsString('520.00', $normalizedHtml);
        $this->assertStringContainsString('Charge in balance: 520.00 AED', $normalizedHtml);
        $this->assertStringContainsString('Registered: '.now()->setTime(10, 15, 0)->format('Y-m-d H:i'), $normalizedHtml);
        $this->assertStringContainsString('Registered: '.now()->setTime(12, 45, 0)->format('Y-m-d H:i'), $normalizedHtml);
    }

    public function test_accounting_periods_are_rolling_30_day_blocks_regardless_of_extension_count(): void
    {
        foreach ([20 => [20], 30 => [30], 31 => [30, 1], 60 => [30, 30], 61 => [30, 30, 1], 100 => [30, 30, 30, 10]] as $days => $expectedDurations) {
            [$component, $contract, $user] = $this->paymentPeriodComponent($days);

            if ($days === 20) {
                foreach ([1, 2, 3] as $sequence) {
                    ContractAmendment::create([
                        'contract_id' => $contract->id,
                        'sequence_no' => $sequence,
                        'type' => ContractAmendment::TYPE_EXTENSION,
                        'status' => 'approved',
                        'requested_by' => $user->id,
                        'approved_by' => $user->id,
                        'approved_at' => now(),
                        'idempotency_key' => (string) \Illuminate\Support\Str::uuid(),
                    ]);
                }

                $component->loadData();
            }

            $this->assertSame($expectedDurations, collect($component->paymentPeriods)->pluck('duration_days')->all());
        }
    }

    public function test_payments_are_assigned_once_by_payment_date_with_safe_out_of_range_handling(): void
    {
        [$component, $contract, $user, $customer, $pickup] = $this->paymentPeriodComponent(100, true);
        $payments = collect([
            ['rental_fee', $pickup->copy()->subDay()],
            ['fine', $pickup->copy()->addDays(9)],
            ['no_deposit_fee', $pickup->copy()->addDays(29)], // final day of Period 1
            ['parking', $pickup->copy()->addDays(30)], // exact Period 2 boundary
            ['damage', $pickup->copy()->addDays(44)],
            ['salik_4_aed', $pickup->copy()->addDays(74)],
            ['discount', $pickup->copy()->addDays(100)], // after return; final period
        ])->map(fn (array $entry) => Payment::factory()
            ->for($contract)->for($customer)->for($user)->for($contract->car)
            ->create(['payment_type' => $entry[0], 'payment_date' => $entry[1], 'amount' => 10, 'amount_in_aed' => 10]));

        $component->loadData();
        $periods = collect($component->paymentPeriods);
        $periodPaymentIds = $periods->flatMap(fn (array $period) => $period['payments']->pluck('id'));

        $this->assertSame([3, 2, 1, 1], $periods->pluck('entry_count')->all());
        $this->assertSame($payments->pluck('id')->sort()->values()->all(), $periodPaymentIds->sort()->values()->all());
        $this->assertSame($periodPaymentIds->count(), $periodPaymentIds->unique()->count());
        $this->assertSame($payments->first()->id, $periods[0]['payments']->first()->id);
        $this->assertSame($payments[3]->id, $periods[1]['payments']->first()->id);
        $this->assertSame($payments->last()->id, $periods[3]['payments']->first()->id);
    }

    public function test_periods_use_contract_billable_days_and_date_boundaries(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $pickup = now()->setDate(2026, 1, 1)->setTime(14, 10);
        $contract = Contract::factory()->for($user)->for($customer)->for(Car::factory())->status('payment')->create([
            'pickup_date' => $pickup,
            'return_date' => $pickup->copy()->addDays(37)->addHours(2)->addMinutes(50),
            'meta' => [],
        ]);

        $periodOnePayment = Payment::factory()->for($contract)->for($customer)->for($user)->for($contract->car)->create([
            'payment_date' => '2026-01-30',
        ]);
        $periodTwoPayment = Payment::factory()->for($contract)->for($customer)->for($user)->for($contract->car)->create([
            'payment_date' => '2026-01-31',
        ]);

        $component = app(RentalRequestPayment::class);
        $component->mount($contract->id, $customer->id);
        $periods = collect($component->paymentPeriods);

        $this->assertSame([30, 8], $periods->pluck('duration_days')->all());
        $this->assertSame('2026-01-30', $periods[0]['display_ends_at']->toDateString());
        $this->assertSame('2026-02-07', $periods[1]['display_ends_at']->toDateString());
        $this->assertSame([$periodOnePayment->id], $periods[0]['payments']->pluck('id')->all());
        $this->assertSame([$periodTwoPayment->id], $periods[1]['payments']->pluck('id')->all());
    }

    private function paymentPeriodComponent(int $days, bool $includeContext = false): array
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $pickup = now()->startOfDay()->setDate(2026, 1, 1);
        $contract = Contract::factory()->for($user)->for($customer)->for(Car::factory())->status('payment')->create([
            'pickup_date' => $pickup,
            'return_date' => $pickup->copy()->addDays($days),
            'total_price' => 1000,
        ]);
        $component = app(RentalRequestPayment::class);
        $component->mount($contract->id, $customer->id);

        return $includeContext ? [$component, $contract, $user, $customer, $pickup] : [$component, $contract, $user];
    }

    public function test_zero_remaining_balance_is_not_serialized_as_negative_zero(): void
    {
        config(['audit.capture.business_reads' => false]);

        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $contract = Contract::factory()
            ->for($user)
            ->for($customer)
            ->for(Car::factory())
            ->status('payment')
            ->create(['total_price' => 363.97, 'meta' => []]);

        foreach ([
            ['rental_fee', 536.33],
            ['discount', 60.33],
            ['salik_4_aed', 8.00],
            ['salik_6_aed', 12.00],
            ['salik_other_revenue', 4.00],
            ['damage', 50.00],
            ['fuel', 35.00],
        ] as [$type, $amount]) {
            Payment::factory()
                ->for($contract)
                ->for($customer)
                ->for($user)
                ->create([
                    'payment_type' => $type,
                    'amount' => $amount,
                    'amount_in_aed' => $amount,
                ]);
        }

        $sourceContract = Contract::factory()
            ->for($user)
            ->for($customer)
            ->for(Car::factory())
            ->create();

        ContractBalanceTransfer::create([
            'from_contract_id' => $sourceContract->id,
            'to_contract_id' => $contract->id,
            'customer_id' => $customer->id,
            'created_by' => $user->id,
            'amount' => 123.69,
            'currency' => 'AED',
            'transferred_at' => now(),
        ]);

        $component = app(RentalRequestPayment::class);
        $component->mount($contract->id, $customer->id);

        $this->assertSame(0.0, $component->remainingBalance);
        $this->assertSame('0', json_encode($component->remainingBalance));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
