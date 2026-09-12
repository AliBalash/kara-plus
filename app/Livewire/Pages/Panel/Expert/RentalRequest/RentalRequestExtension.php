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

    public string $rateSource = RentalPricingService::DEFAULT_RATE_SOURCE;

    public ?string $reason = null;

    public ?string $notes = null;

    public string $idempotencyKey = '';

    public array $quote = [];

    public function mount(int $contractId): void
    {
        $this->contract = Contract::with(['car', 'amendments.charges'])->findOrFail($contractId);
        $this->newReturnAt = optional($this->contract->return_date)->format('Y-m-d\\TH:i') ?? '';
        if (! is_numeric($this->contract->used_daily_rate) || (float) $this->contract->used_daily_rate <= 0) {
            $this->rateSource = RentalPricingService::RATE_SOURCE_CURRENT;
        }
        $this->idempotencyKey = (string) Str::uuid();
    }

    public function preview(RentalPricingService $pricing): void
    {
        $this->resetErrorBag();
        $this->validateInput();

        try {
            $this->quote = $pricing->quoteExtension(
                $this->contract->fresh('car'),
                $this->newReturnAt,
                $this->pricingPolicy,
                $this->rateSource
            );
        } catch (ValidationException $exception) {
            $this->captureValidationErrors($exception);
        }
    }

    public function request(ContractAmendmentService $service, RentalPricingService $pricing): void
    {
        $this->resetErrorBag();
        $this->validateInput();

        try {
            if ($this->quote === []) {
                $this->addError('quote', 'Preview and review the complete extension calculation before submitting the request.');

                return;
            }

            $latestQuote = $pricing->quoteExtension(
                $this->contract->fresh('car'),
                $this->newReturnAt,
                $this->pricingPolicy,
                $this->rateSource
            );
            if ($this->quoteFingerprint($latestQuote) !== $this->quoteFingerprint($this->quote)) {
                $this->quote = $latestQuote;
                $this->addError('quote', 'The price changed after the previous preview. Review the refreshed calculation before submitting.');

                return;
            }

            $service->requestExtension(
                $this->contract,
                $this->newReturnAt,
                auth()->id(),
                $this->idempotencyKey,
                $this->pricingPolicy,
                $this->reason,
                $this->notes,
                $this->rateSource
            );
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

    public function updatedNewReturnAt(): void
    {
        $this->quote = [];
    }

    public function updatedPricingPolicy(): void
    {
        $this->quote = [];
    }

    public function updatedRateSource(): void
    {
        $this->quote = [];
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
            'rateSource' => ['required', 'in:contract_rate,current_tariff'],
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
                'rate_source' => 'rateSource',
                'idempotency_key' => 'idempotencyKey',
                default => $field,
            };
            foreach ($messages as $message) {
                $this->addError($componentField, $message);
            }
        }
    }

    private function quoteFingerprint(array $quote): string
    {
        return hash('sha256', json_encode([
            'duration_minutes' => (int) ($quote['duration_minutes'] ?? 0),
            'billable_days' => (float) ($quote['billable_days'] ?? 0),
            'pricing_policy' => (string) ($quote['pricing_policy'] ?? ''),
            'rate_source' => (string) ($quote['rate_source'] ?? ''),
            'items' => (array) ($quote['items'] ?? []),
            'subtotal' => (float) ($quote['subtotal'] ?? 0),
            'tax' => (float) ($quote['tax'] ?? 0),
            'total' => (float) ($quote['total'] ?? 0),
        ], JSON_THROW_ON_ERROR));
    }

    public function render()
    {
        return view('livewire.pages.panel.expert.rental-request.rental-request-extension');
    }
}
