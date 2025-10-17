<?php

namespace App\Livewire\Concerns;

use App\Models\Contract;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

trait HandlesContractCancellation
{
    public function cancelContract(int $contractId): void
    {
        try {
            $contract = Contract::findOrFail($contractId);

            if ($contract->current_status === 'cancelled') {
                session()->flash('info', 'Contract is already cancelled.');
                return;
            }

            DB::transaction(function () use ($contract) {
                $contract->changeStatus('cancelled', Auth::id(), 'Cancelled via panel');

                if ($contract->car) {
                    $activeStatuses = [
                        'pending',
                        'assigned',
                        'under_review',
                        'reserved',
                        'delivery',
                        'agreement_inspection',
                        'awaiting_return',
                    ];

                    $hasActiveContracts = Contract::where('car_id', $contract->car_id)
                        ->where('id', '!=', $contract->id)
                        ->whereIn('current_status', $activeStatuses)
                        ->exists();

                    if (! $hasActiveContracts) {
                        $contract->car->update([
                            'status' => 'available',
                            'availability' => true,
                        ]);
                    }
                }
            });

            session()->flash('success', 'Contract cancelled successfully.');

            if (method_exists($this, 'resetPage')) {
                $this->resetPage();
            }

            if (method_exists($this, 'afterContractCancelled')) {
                $this->afterContractCancelled();
            }

            if (method_exists($this, 'dispatch')) {
                $this->dispatch('refreshContracts');
            }
        } catch (Throwable $exception) {
            Log::error('Failed to cancel contract', [
                'contract_id' => $contractId,
                'message' => $exception->getMessage(),
            ]);

            session()->flash('error', 'Failed to cancel contract. Please try again.');
        }
    }
}
