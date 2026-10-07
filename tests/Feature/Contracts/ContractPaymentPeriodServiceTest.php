<?php

namespace Tests\Feature\Contracts;

use App\Models\Car;
use App\Models\Contract;
use App\Models\ContractPaymentPeriod;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\User;
use App\Services\ContractPaymentPeriodService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ContractPaymentPeriodServiceTest extends TestCase
{
    use RefreshDatabase;

    private function context(): array
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $contract = Contract::factory()->for($user)->for(Customer::factory())->for(Car::factory())
            ->status('payment')->create(['pickup_date' => '2026-01-14', 'return_date' => '2026-03-30']);

        return [$contract, $user, app(ContractPaymentPeriodService::class)];
    }

    private function range(string $start = '2026-01-14', string $end = '2026-01-30', bool $default = false): array
    {
        return ['starts_on' => $start, 'ends_on' => $end, 'is_default' => $default];
    }

    public function test_adjacent_and_gapped_ranges_are_allowed_and_all_forms_of_overlap_are_rejected(): void
    {
        [$contract, $user, $service] = $this->context();
        $first = $service->save($contract->id, $user->id, $this->range(default: true));
        $service->save($contract->id, $user->id, $this->range('2026-01-30', '2026-02-28'));
        $service->save($contract->id, $user->id, $this->range('2026-03-10', '2026-03-30'));
        foreach ([['2026-01-14', '2026-01-30'], ['2026-01-15', '2026-01-20'],
            ['2026-01-01', '2026-03-31'], ['2026-01-01', '2026-01-15'], ['2026-01-29', '2026-02-01']] as [$start, $end]) {
            try {
                $service->save($contract->id, $user->id, $this->range($start, $end, true));
                $this->fail('Overlapping dates must be rejected.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('ends_on', $exception->errors());
            }
        }
        $this->assertSame(3, $contract->paymentPeriods()->count());
        $this->assertTrue($first->fresh()->is_default);
    }

    public function test_invalid_dates_and_empty_or_reversed_ranges_are_rejected(): void
    {
        [$contract, $user, $service] = $this->context();
        foreach ([['', '2026-01-30'], ['2026-02-30', '2026-03-10'], ['2026-01-30', '2026-01-30'],
            ['2026-01-30', '2026-01-14'], ['2026-01-14', 'not-a-date']] as [$start, $end]) {
            try {
                $service->save($contract->id, $user->id, $this->range($start, $end));
                $this->fail('Invalid dates must be rejected.');
            } catch (ValidationException $exception) {
                $this->assertNotEmpty($exception->errors());
            }
        }
        $this->assertSame(0, $contract->paymentPeriods()->count());
    }

    public function test_default_can_be_changed_cleared_and_preserved_when_editing_the_default_range(): void
    {
        [$contract, $user, $service] = $this->context();
        $first = $service->save($contract->id, $user->id, $this->range(default: true));
        $second = $service->save($contract->id, $user->id, $this->range('2026-01-30', '2026-02-28', true));
        $this->assertFalse($first->fresh()->is_default);
        $this->assertTrue($second->fresh()->is_default);
        $service->save($contract->id, $user->id, [...$this->range('2026-01-30', '2026-02-28', true), 'title' => 'Finance'], $second->id);
        $this->assertTrue($second->fresh()->is_default);
        $service->setDefault($contract->id, $user->id, $second->id);
        $this->assertTrue($second->fresh()->is_default);
        $service->setDefault($contract->id, $user->id, $first->id);
        $this->assertSame([$first->id], $contract->paymentPeriods()->where('is_default', true)->pluck('id')->all());
        $service->setDefault($contract->id, $user->id, null);
        $this->assertSame(0, $contract->paymentPeriods()->where('is_default', true)->count());
    }

    public function test_edit_rejects_overlap_and_keeps_the_original_range_and_default(): void
    {
        [$contract, $user, $service] = $this->context();
        $first = $service->save($contract->id, $user->id, $this->range(default: true));
        $second = $service->save($contract->id, $user->id, $this->range('2026-01-30', '2026-02-28'));
        try {
            $service->save($contract->id, $user->id, $this->range('2026-01-29', '2026-02-28', true), $second->id);
            $this->fail('An overlapping edit must fail.');
        } catch (ValidationException) {
            $this->assertSame('2026-01-30', $second->fresh()->starts_on->toDateString());
            $this->assertTrue($first->fresh()->is_default);
            $this->assertFalse($second->fresh()->is_default);
        }
    }

    public function test_only_the_last_active_range_by_date_can_be_edited_regardless_of_creation_order(): void
    {
        [$contract, $user, $service] = $this->context();
        $last = $service->save($contract->id, $user->id, $this->range('2026-01-30', '2026-02-28'));
        $earlier = $service->save($contract->id, $user->id, $this->range(default: true));
        $before = $earlier->fresh()->getAttributes();

        try {
            $service->editablePeriod($contract->id, $earlier->id);
            $this->fail('Opening an earlier range for editing must fail.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('period', $exception->errors());
        }

        try {
            $service->save($contract->id, $user->id, $this->range('2026-01-15', '2026-01-30'), $earlier->id);
            $this->fail('An earlier range must not be updated directly.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('period', $exception->errors());
            $this->assertSame($before, $earlier->fresh()->getAttributes());
        }

        $this->assertSame($last->id, $service->editablePeriod($contract->id, $last->id)->id);
        $service->save($contract->id, $user->id, $this->range('2026-01-30', '2026-03-10'), $last->id);
        $this->assertSame('2026-03-10', $last->fresh()->ends_on->toDateString());
        $this->assertTrue($earlier->fresh()->is_default);
    }

    public function test_edit_cannot_move_the_last_range_before_other_ranges_even_without_overlap(): void
    {
        [$contract, $user, $service] = $this->context();
        $service->save($contract->id, $user->id, $this->range());
        $last = $service->save($contract->id, $user->id, $this->range('2026-02-10', '2026-02-28', true));
        $before = $last->fresh()->getAttributes();

        try {
            $service->save($contract->id, $user->id, $this->range('2026-01-01', '2026-01-10'), $last->id);
            $this->fail('The edited range must remain chronologically last.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('starts_on', $exception->errors());
            $this->assertSame($before, $last->fresh()->getAttributes());
        }
    }

    public function test_deleting_and_restoring_the_last_range_updates_which_range_is_editable(): void
    {
        [$contract, $user, $service] = $this->context();
        $first = $service->save($contract->id, $user->id, $this->range());
        $last = $service->save($contract->id, $user->id, $this->range('2026-01-30', '2026-02-28'));
        $service->archive($contract->id, $user->id, $last->id);
        $this->assertSame($first->id, $service->editablePeriod($contract->id, $first->id)->id);
        $service->save($contract->id, $user->id, $this->range('2026-01-15', '2026-01-30'), $first->id);
        $service->restore($contract->id, $user->id, $last->id);

        $this->expectException(ValidationException::class);
        $service->editablePeriod($contract->id, $first->id);
    }

    public function test_archive_and_restore_keep_history_and_do_not_modify_payments_or_contract_finances(): void
    {
        [$contract, $user, $service] = $this->context();
        $payment = Payment::factory()->for($contract)->for($contract->customer)->for($contract->car)->for($user)->create();
        $paymentBefore = $payment->fresh()->getAttributes();
        $contractBefore = $contract->fresh()->getAttributes();
        $balance = $contract->calculateRemainingBalance();
        $period = $service->save($contract->id, $user->id, $this->range(default: true));
        $createdAt = $period->created_at->toDateTimeString();
        $service->archive($contract->id, $user->id, $period->id);
        $this->assertSoftDeleted($period);
        $archived = ContractPaymentPeriod::withTrashed()->findOrFail($period->id);
        $this->assertSame($user->id, $archived->archived_by);
        $this->assertFalse($archived->is_default);
        $service->restore($contract->id, $user->id, $period->id);
        $restored = $period->fresh();
        $this->assertNull($restored->deleted_at);
        $this->assertNull($restored->archived_by);
        $this->assertFalse($restored->is_default);
        $this->assertSame($createdAt, $restored->created_at->toDateTimeString());
        $this->assertSame($paymentBefore, $payment->fresh()->getAttributes());
        $this->assertSame($contractBefore, $contract->fresh()->getAttributes());
        $this->assertEquals($balance, $contract->fresh()->calculateRemainingBalance());
    }

    public function test_restore_rejects_overlap_without_losing_the_archived_record(): void
    {
        [$contract, $user, $service] = $this->context();
        $period = $service->save($contract->id, $user->id, $this->range());
        $service->archive($contract->id, $user->id, $period->id);
        $service->save($contract->id, $user->id, $this->range(default: true));
        try {
            $service->restore($contract->id, $user->id, $period->id);
            $this->fail('An overlapping archived range must not be restored.');
        } catch (ValidationException) {
            $this->assertSoftDeleted($period);
            $this->assertSame(1, $contract->paymentPeriods()->where('is_default', true)->count());
        }
    }

    public function test_period_ids_cannot_be_used_to_modify_another_contract(): void
    {
        [$contract, $user, $service] = $this->context();
        [$otherContract] = $this->context();
        $period = $service->save($contract->id, $user->id, $this->range(default: true));
        foreach (['save', 'setDefault', 'archive'] as $action) {
            try {
                if ($action === 'save') {
                    $service->save($otherContract->id, $user->id, $this->range(), $period->id);
                } else {
                    $service->{$action}($otherContract->id, $user->id, $period->id);
                }
                $this->fail('Cross-contract modification must fail.');
            } catch (ModelNotFoundException) {
                $this->assertTrue($period->fresh()->is_default);
            }
        }
        $service->archive($contract->id, $user->id, $period->id);
        try {
            $service->restore($otherContract->id, $user->id, $period->id);
            $this->fail('Cross-contract restore must fail.');
        } catch (ModelNotFoundException) {
            $this->assertSoftDeleted($period);
        }
        // Identical dates in separate contracts are independent.
        $service->save($otherContract->id, $user->id, $this->range());
        $this->assertSame(1, $otherContract->paymentPeriods()->count());
    }
}
