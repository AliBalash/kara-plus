<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\ContractBalanceTransfer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BalanceTransferController extends Controller
{
    public function store(Request $request, int $contractId): RedirectResponse
    {
        $data = $request->validate([
            'mode' => ['required', 'in:send,receive'],
            'target_contract_id' => ['required', 'integer'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'reference' => ['nullable', 'string', 'max:190'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        if ((int) $data['target_contract_id'] === $contractId) {
            throw ValidationException::withMessages([
                'target_contract_id' => 'Source and destination cannot be the same contract.',
            ]);
        }

        DB::transaction(function () use ($data, $contractId): void {
            // Lock both contracts in a stable order so concurrent transfers cannot overdraw a balance.
            $contracts = Contract::query()
                ->with(['payments', 'incomingBalanceTransfers', 'outgoingBalanceTransfers'])
                ->whereIn('id', [$contractId, (int) $data['target_contract_id']])
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $current = $contracts->get($contractId);
            $target = $contracts->get((int) $data['target_contract_id']);

            if (!$current || !$target || $current->customer_id !== $target->customer_id) {
                throw ValidationException::withMessages([
                    'target_contract_id' => 'Selected contract is not valid for this customer.',
                ]);
            }

            $amount = (float) $data['amount'];
            $source = $data['mode'] === 'send' ? $current : $target;
            $destination = $data['mode'] === 'send' ? $target : $current;
            $available = max((float) $source->calculateRemainingBalance($source->payments), 0);

            if ($available <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'The source contract has no outstanding amount to transfer.',
                ]);
            }

            if ($amount > $available) {
                throw ValidationException::withMessages([
                    'amount' => 'Amount exceeds source outstanding balance (' . number_format($available, 2) . ').',
                ]);
            }

            ContractBalanceTransfer::create([
                'from_contract_id' => $source->id,
                'to_contract_id' => $destination->id,
                'customer_id' => $current->customer_id,
                'created_by' => Auth::id(),
                'amount' => $amount,
                'currency' => $current->currency ?? 'AED',
                'reference' => $data['reference'] ?: null,
                'meta' => ['channel' => 'panel', 'reason' => 'settlement-adjustment'],
                'notes' => $data['notes'] ?: null,
                'transferred_at' => now(),
            ]);
        });

        return to_route('rental-requests.balance-transfer', $contractId)
            ->with('success', 'Balance transfer created successfully.');
    }

    public function destroy(int $contractId, ContractBalanceTransfer $transfer): RedirectResponse
    {
        if ($transfer->from_contract_id !== $contractId && $transfer->to_contract_id !== $contractId) {
            abort(404);
        }

        DB::transaction(function () use ($transfer): void {
            Contract::query()
                ->whereIn('id', [$transfer->from_contract_id, $transfer->to_contract_id])
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $transfer->delete();
        });

        return to_route('rental-requests.balance-transfer', $contractId)
            ->with('success', 'Transfer removed.');
    }
}
