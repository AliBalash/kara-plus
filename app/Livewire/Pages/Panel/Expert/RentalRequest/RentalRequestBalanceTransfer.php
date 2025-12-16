<?php

namespace App\Livewire\Pages\Panel\Expert\RentalRequest;

use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\Contract;
use App\Models\ContractBalanceTransfer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class RentalRequestBalanceTransfer extends Component
{
    use InteractsWithToasts;

    public Contract $contract;

    public float $currentOutstanding = 0.0;

    public array $contractsList = [];

    public array $transferForm = [
        'mode' => 'send',
        'target_contract_id' => null,
        'amount' => '',
        'reference' => null,
        'notes' => null,
    ];

    public array $metadataRows = [];

    public array $transfers = [];

    public function mount(int $contractId): void
    {
        $this->contract = Contract::with([
            'customer',
            'car',
            'payments',
            'incomingBalanceTransfers',
            'outgoingBalanceTransfers',
        ])->findOrFail($contractId);

        $this->metadataRows = [
            ['key' => 'channel', 'value' => 'panel'],
            ['key' => 'reason', 'value' => 'settlement-adjustment'],
        ];

        $this->refreshState();
    }

    public function updated($propertyName): void
    {
        if ($propertyName === 'transferForm.mode') {
            $this->transferForm['target_contract_id'] = $this->contractsList[0]['id'] ?? null;
        }
    }

    public function addMetaRow(): void
    {
        $this->metadataRows[] = ['key' => '', 'value' => ''];
    }

    public function removeMetaRow(int $index): void
    {
        if (isset($this->metadataRows[$index])) {
            unset($this->metadataRows[$index]);
            $this->metadataRows = array_values($this->metadataRows);
        }
    }

    public function transferBalance(): void
    {
        if (empty($this->contractsList)) {
            $this->addError('transferForm.target_contract_id', 'No other contract is available for transfer.');
            return;
        }

        $this->validate([
            'transferForm.mode' => 'required|in:send,receive',
            'transferForm.target_contract_id' => 'required|integer',
            'transferForm.amount' => 'required|numeric|gt:0',
            'transferForm.reference' => 'nullable|string|max:190',
            'transferForm.notes' => 'nullable|string|max:2000',
        ]);

        $target = $this->findContractOption((int) $this->transferForm['target_contract_id']);

        if (!$target) {
            $this->addError('transferForm.target_contract_id', 'Selected contract is not valid.');
            return;
        }

        $amount = (float) $this->transferForm['amount'];

        if ($this->transferForm['mode'] === 'send') {
            $this->assertSendCapacity($amount);
            $fromContractId = $this->contract->id;
            $toContractId = $target['id'];
        } else {
            $this->assertReceiveCapacity($amount, $target);
            $fromContractId = $target['id'];
            $toContractId = $this->contract->id;
        }

        if ($fromContractId === $toContractId) {
            $this->addError('transferForm.target_contract_id', 'Source and destination cannot be the same contract.');
            return;
        }

        $meta = collect($this->metadataRows)
            ->filter(fn($row) => filled($row['key']))
            ->mapWithKeys(fn($row) => [$row['key'] => $row['value']])
            ->all();

        ContractBalanceTransfer::create([
            'from_contract_id' => $fromContractId,
            'to_contract_id' => $toContractId,
            'customer_id' => $this->contract->customer_id,
            'created_by' => Auth::id(),
            'amount' => $amount,
            'currency' => $this->contract->currency ?? 'AED',
            'reference' => $this->transferForm['reference'] ?: null,
            'meta' => $meta ?: null,
            'notes' => $this->transferForm['notes'] ?: null,
            'transferred_at' => now(),
        ]);

        $this->transferForm['amount'] = '';
        $this->transferForm['reference'] = null;
        $this->transferForm['notes'] = null;

        $this->refreshState();

        $this->toast('success', 'Balance transfer created successfully.');
    }

    public function deleteTransfer(int $transferId): void
    {
        $transfer = ContractBalanceTransfer::findOrFail($transferId);

        if ($transfer->from_contract_id !== $this->contract->id && $transfer->to_contract_id !== $this->contract->id) {
            $this->toast('error', 'Transfer does not belong to this contract.');
            return;
        }

        $transfer->delete();
        $this->refreshState();
        $this->toast('success', 'Transfer removed.');
    }

    public function render()
    {
        return view('livewire.pages.panel.expert.rental-request.rental-request-balance-transfer');
    }

    protected function refreshState(): void
    {
        $this->contract->load([
            'payments',
            'incomingBalanceTransfers',
            'outgoingBalanceTransfers',
            'car',
            'customer',
        ]);

        $this->currentOutstanding = round($this->contract->calculateRemainingBalance($this->contract->payments), 2);

        $this->contractsList = Contract::with(['car', 'payments', 'incomingBalanceTransfers', 'outgoingBalanceTransfers'])
            ->where('customer_id', $this->contract->customer_id)
            ->where('id', '!=', $this->contract->id)
            ->latest()
            ->get()
            ->map(function (Contract $contract) {
                $balance = round($contract->calculateRemainingBalance($contract->payments), 2);

                return [
                    'id' => $contract->id,
                    'label' => 'Contract #' . $contract->id . ' · ' . ($contract->car?->fullName() ?? 'Vehicle'),
                    'status' => $contract->current_status,
                    'outstanding' => $balance,
                ];
            })
            ->values()
            ->all();

        if (!$this->transferForm['target_contract_id'] && !empty($this->contractsList)) {
            $this->transferForm['target_contract_id'] = $this->contractsList[0]['id'];
        }

        $this->transfers = ContractBalanceTransfer::with(['fromContract.car', 'toContract.car', 'createdBy'])
            ->where(function ($query) {
                $query->where('from_contract_id', $this->contract->id)
                    ->orWhere('to_contract_id', $this->contract->id);
            })
            ->latest('transferred_at')
            ->latest()
            ->get()
            ->map(function (ContractBalanceTransfer $transfer) {
                $direction = $transfer->from_contract_id === $this->contract->id ? 'outgoing' : 'incoming';
                $signedAmount = $direction === 'outgoing' ? -1 * (float) $transfer->amount : (float) $transfer->amount;

                return [
                    'id' => $transfer->id,
                    'direction' => $direction,
                    'amount' => abs($signedAmount),
                    'signed_amount' => $signedAmount,
                    'reference' => $transfer->reference,
                    'notes' => $transfer->notes,
                    'meta' => $transfer->meta ?? [],
                    'created_by' => $transfer->createdBy?->shortName() ?? $transfer->createdBy?->name,
                    'transferred_at' => optional($transfer->transferred_at ?? $transfer->created_at)->format('d M Y · H:i'),
                    'from_contract' => $transfer->fromContract,
                    'to_contract' => $transfer->toContract,
                ];
            })
            ->all();
    }

    protected function assertSendCapacity(float $amount): void
    {
        $available = max($this->currentOutstanding, 0);

        if ($available <= 0) {
            throw ValidationException::withMessages([
                'transferForm.amount' => 'There is no outstanding amount to transfer from this contract.',
            ]);
        }

        if ($amount > $available) {
            throw ValidationException::withMessages([
                'transferForm.amount' => 'Amount exceeds outstanding balance (' . number_format($available, 2) . ').',
            ]);
        }
    }

    protected function assertReceiveCapacity(float $amount, array $target): void
    {
        $available = max($target['outstanding'], 0);

        if ($available <= 0) {
            throw ValidationException::withMessages([
                'transferForm.target_contract_id' => 'Selected contract has no outstanding balance to transfer.',
            ]);
        }

        if ($amount > $available) {
            throw ValidationException::withMessages([
                'transferForm.amount' => 'Amount exceeds source outstanding (' . number_format($available, 2) . ').',
            ]);
        }
    }

    protected function findContractOption(int $contractId): ?array
    {
        foreach ($this->contractsList as $contract) {
            if ($contract['id'] === $contractId) {
                return $contract;
            }
        }

        return null;
    }
}
