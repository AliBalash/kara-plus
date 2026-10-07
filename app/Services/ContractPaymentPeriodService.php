<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractPaymentPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/** Saved payment-date views; never writes payments or commercial contract terms. */
class ContractPaymentPeriodService
{
    public function save(int $contractId, int $actorId, array $data, ?int $periodId = null): ContractPaymentPeriod
    {
        $data = Validator::make($data, [
            'title' => ['nullable', 'string', 'max:100'],
            'starts_on' => ['required', 'date_format:Y-m-d'],
            'ends_on' => ['required', 'date_format:Y-m-d', 'after:starts_on'],
            'is_default' => ['required', 'boolean'],
        ], [
            'ends_on.after' => 'The end date must be after the start date. The end date is not included.',
        ])->validate();

        return DB::transaction(function () use ($contractId, $actorId, $data, $periodId) {
            // Serialize every period change on the contract, including when no periods exist yet.
            $contract = Contract::query()->lockForUpdate()->findOrFail($contractId);
            $period = $periodId === null
                ? new ContractPaymentPeriod(['contract_id' => $contract->id, 'created_by' => $actorId])
                : $contract->paymentPeriods()->findOrFail($periodId);

            if ($periodId !== null) {
                $this->assertLastPeriod($contract, $period);
            }

            $this->assertNoOverlap($contract, $data['starts_on'], $data['ends_on'], $periodId);

            if ($periodId !== null && $contract->paymentPeriods()->where('id', '!=', $periodId)
                ->where('ends_on', '>', $data['starts_on'])->exists()) {
                throw ValidationException::withMessages([
                    'starts_on' => 'The last range must stay after the other saved ranges. Its start must be on or after their end date.',
                ]);
            }

            if ($data['is_default']) {
                $this->clearDefault($contract, $actorId, $periodId);
            }

            $period->fill([
                ...$data,
                'title' => trim($data['title'] ?? '') ?: null,
                'updated_by' => $actorId,
            ])->save();

            return $period;
        });
    }

    public function editablePeriod(int $contractId, int $periodId): ContractPaymentPeriod
    {
        $contract = Contract::query()->findOrFail($contractId);
        $period = $contract->paymentPeriods()->findOrFail($periodId);
        $this->assertLastPeriod($contract, $period);

        return $period;
    }

    public function setDefault(int $contractId, int $actorId, ?int $periodId): void
    {
        DB::transaction(function () use ($contractId, $actorId, $periodId) {
            $contract = Contract::query()->lockForUpdate()->findOrFail($contractId);
            $period = $periodId === null ? null : $contract->paymentPeriods()->findOrFail($periodId);
            $this->clearDefault($contract, $actorId, $periodId);
            $period?->fill(['is_default' => true, 'updated_by' => $actorId])->save();
        });
    }

    public function archive(int $contractId, int $actorId, int $periodId): void
    {
        DB::transaction(function () use ($contractId, $actorId, $periodId) {
            $contract = Contract::query()->lockForUpdate()->findOrFail($contractId);
            $period = $contract->paymentPeriods()->findOrFail($periodId);
            $period->fill(['is_default' => false, 'updated_by' => $actorId, 'archived_by' => $actorId])->save();
            $period->delete();
        });
    }

    public function restore(int $contractId, int $actorId, int $periodId): void
    {
        DB::transaction(function () use ($contractId, $actorId, $periodId) {
            $contract = Contract::query()->lockForUpdate()->findOrFail($contractId);
            $period = $contract->paymentPeriods()->onlyTrashed()->findOrFail($periodId);
            $this->assertNoOverlap($contract, $period->starts_on->toDateString(), $period->ends_on->toDateString());
            $period->fill(['is_default' => false, 'updated_by' => $actorId, 'archived_by' => null]);
            $period->restore();
        });
    }

    private function clearDefault(Contract $contract, int $actorId, ?int $exceptId = null): void
    {
        // Use model saves so the existing audit observer captures default changes too.
        foreach ($contract->paymentPeriods()->where('is_default', true)
            ->when($exceptId !== null, fn ($query) => $query->where('id', '!=', $exceptId))->get() as $period) {
            $period->fill(['is_default' => false, 'updated_by' => $actorId])->save();
        }
    }

    private function assertLastPeriod(Contract $contract, ContractPaymentPeriod $period): void
    {
        $lastId = $contract->paymentPeriods()->orderByDesc('ends_on')->orderByDesc('id')->value('id');

        if ((int) $lastId !== $period->id) {
            throw ValidationException::withMessages([
                'period' => 'Only the last active range by date can be edited. A later range exists; refresh the ranges and try again.',
            ]);
        }
    }

    private function assertNoOverlap(Contract $contract, string $start, string $end, ?int $exceptId = null): void
    {
        $overlap = $contract->paymentPeriods()
            ->when($exceptId !== null, fn ($query) => $query->where('id', '!=', $exceptId))
            ->where('starts_on', '<', $end)
            ->where('ends_on', '>', $start)
            ->orderBy('starts_on')
            ->first();

        if ($overlap) {
            throw ValidationException::withMessages([
                'ends_on' => sprintf('This range overlaps a saved range (%s to before %s). Choose dates outside that range.',
                    $overlap->starts_on->toDateString(), $overlap->ends_on->toDateString()),
            ]);
        }
    }
}
