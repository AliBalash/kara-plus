<?php

namespace App\Livewire\Pages\Panel\Expert\RentalRequest;

use App\Models\Contract;
use App\Models\ContractAmendment;
use App\Services\ContractAmendmentService;
use App\Services\RentalPricingService;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class RentalRequestExtension extends Component
{
    public Contract $contract;

    public string $newReturnAt = '';

    public string $pricingPolicy = RentalPricingService::DEFAULT_POLICY;

    public ?string $reason = null;

    public ?string $notes = null;

    public string $idempotencyKey = '';

    public array $quote = [];

    public function mount(int $contractId): void
    {
        $this->contract = Contract::with(['car', 'amendments.charges'])->findOrFail($contractId);
        $this->newReturnAt = optional($this->contract->return_date)->format('Y-m-d\\TH:i') ?? '';
        $this->idempotencyKey = (string) Str::uuid();
    }

    public function preview(RentalPricingService $pricing): void
    {
        $this->resetErrorBag();
        $this->validateInput();

        try {
            $this->quote = $pricing->quoteExtension($this->contract->fresh('car'), $this->newReturnAt, $this->pricingPolicy);
        } catch (ValidationException $exception) {
            $this->captureValidationErrors($exception);
        }
    }

    public function request(ContractAmendmentService $service): void
    {
        $this->resetErrorBag();
        $this->validateInput();

        try {
            $service->requestExtension($this->contract, $this->newReturnAt, auth()->id(), $this->idempotencyKey, $this->pricingPolicy, $this->reason, $this->notes);
            $this->reloadContract();
            $this->idempotencyKey = (string) Str::uuid();
            $this->quote = [];
            session()->flash('message', 'Extension request created and awaits approval.');
        } catch (ValidationException $exception) {
            $this->captureValidationErrors($exception);
        }
    }

    public function approve(int $amendmentId, ContractAmendmentService $service): void
    {
        $amendment = $this->contract->amendments()->findOrFail($amendmentId);
        try {
            $service->approve($amendment, auth()->id());
            $this->reloadContract();
            $this->newReturnAt = optional($this->contract->return_date)->format('Y-m-d\\TH:i') ?? '';
            session()->flash('message', 'Extension approved. The contract remains a single rental.');
        } catch (ValidationException $exception) {
            $this->captureValidationErrors($exception);
        }
    }

    public function reject(int $amendmentId, ContractAmendmentService $service): void
    {
        $this->closeAmendment($amendmentId, 'reject', $service);
    }

    public function cancel(int $amendmentId, ContractAmendmentService $service): void
    {
        $this->closeAmendment($amendmentId, 'cancel', $service);
    }

    public function canRequestExtension(): bool
    {
        return in_array($this->contract->current_status, Contract::AMENDABLE_STATUSES, true)
            && ! $this->contract->amendments->contains(fn (ContractAmendment $amendment) => $amendment->isPending());
    }

    private function closeAmendment(int $amendmentId, string $action, ContractAmendmentService $service): void
    {
        try {
            $amendment = $this->contract->amendments()->findOrFail($amendmentId);
            $service->{$action}($amendment, auth()->id(), $this->notes);
            $this->reloadContract();
            session()->flash('message', 'Extension request '.$action.'ed.');
        } catch (ValidationException $exception) {
            $this->captureValidationErrors($exception);
        }
    }

    private function reloadContract(): void
    {
        $this->contract->refresh()->load(['car', 'amendments.charges']);
    }

    private function validateInput(): void
    {
        $this->validate([
            'newReturnAt' => ['required', 'date'],
            'pricingPolicy' => ['required', 'in:daily_ceiling,hourly,prorated_daily,grace_then_daily'],
            'reason' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'idempotencyKey' => ['required', 'uuid'],
        ]);
    }

    private function captureValidationErrors(ValidationException $exception): void
    {
        foreach ($exception->errors() as $field => $messages) {
            $componentField = match ($field) {
                'new_return_at' => 'newReturnAt',
                'pricing_policy' => 'pricingPolicy',
                'idempotency_key' => 'idempotencyKey',
                default => $field,
            };
            foreach ($messages as $message) {
                $this->addError($componentField, $message);
            }
        }
    }

    public function render()
    {
        return view('livewire.pages.panel.expert.rental-request.rental-request-extension');
    }
}
