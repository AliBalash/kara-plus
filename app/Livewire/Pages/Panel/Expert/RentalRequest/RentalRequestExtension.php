<?php

namespace App\Livewire\Pages\Panel\Expert\RentalRequest;

use App\Models\Contract;
use App\Models\ContractAmendment;
use App\Services\ContractAmendmentService;
use App\Services\RentalPricingService;
use App\Support\RentalDuration;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;

class RentalRequestExtension extends Component
{
    public Contract $contract;

    public string $newReturnAt = '';

    public string $pricingPolicy = RentalPricingService::DEFAULT_POLICY;

    public string $rateSource = RentalPricingService::DEFAULT_RATE_SOURCE;

    public ?string $reason = null;

    public ?string $notes = null;

    #[Locked]
    public string $idempotencyKey = '';

    #[Locked]
    public array $quote = [];

    #[Locked]
    public ?int $editingAmendmentId = null;

    #[Locked]
    public bool $editingApproved = false;

    public bool $reviewConfirmed = false;

    #[Locked]
    public ?string $confirmationAction = null;

    #[Locked]
    public ?int $confirmationAmendmentId = null;

    #[Locked]
    public array $confirmationImpact = [];

    #[Locked]
    public ?string $confirmationFingerprint = null;

    public bool $confirmationAccepted = false;

    public function mount(int $contractId): void
    {
        $this->contract = Contract::with(['car', 'amendments.charges', 'payments'])->findOrFail($contractId);
        $this->newReturnAt = optional($this->contract->return_date)->format('Y-m-d\TH:i') ?? '';
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
                $this->rateSource,
                $this->extensionQuoteStartAt(),
            );
            $this->quote['impact'] = $this->quoteImpact($this->quote);
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
                $this->addError('quote', 'Preview and review the complete extension impact before submitting.');

                return;
            }
            $this->validateInput(true);

            $latestQuote = $pricing->quoteExtension(
                $this->contract->fresh('car'),
                $this->newReturnAt,
                $this->pricingPolicy,
                $this->rateSource,
                $this->extensionQuoteStartAt(),
            );
            $latestQuote['impact'] = $this->quoteImpact($latestQuote);
            if ($this->quoteFingerprint($latestQuote) !== $this->quoteFingerprint($this->quote)) {
                $this->quote = $latestQuote;
                $this->reviewConfirmed = false;
                $this->addError('quote', 'The dates, tariff, or balance changed after the preview. Review the refreshed impact before submitting.');

                return;
            }

            if ($this->editingAmendmentId !== null) {
                $amendment = $this->contract->amendments()->findOrFail($this->editingAmendmentId);
                if ($amendment->isApproved()) {
                    $service->reviseApprovedExtension($amendment, $this->newReturnAt, auth()->id(), $this->pricingPolicy, $this->rateSource, $this->reason, $this->notes);
                    $message = 'Approved extension revised. The old charge was reversed and retained in history.';
                } else {
                    $service->updatePendingExtension($amendment, $this->newReturnAt, auth()->id(), $this->pricingPolicy, $this->rateSource, $this->reason, $this->notes);
                    $message = 'Pending extension updated after review.';
                }
            } else {
                $service->requestExtension($this->contract, $this->newReturnAt, auth()->id(), $this->idempotencyKey, $this->pricingPolicy, $this->reason, $this->notes, $this->rateSource);
                $message = 'Extension request created and awaits approval.';
            }

            $this->reloadContract();
            $this->resetForm();
            session()->flash('message', $message);
        } catch (ValidationException $exception) {
            $this->captureValidationErrors($exception);
        }
    }

    public function edit(int $amendmentId): void
    {
        $amendment = $this->contract->amendments()->findOrFail($amendmentId);
        if (! $this->canEditAmendment($amendment)) {
            $this->addError('amendment', 'Only a pending extension or the latest effective approved extension can be edited.');

            return;
        }

        $snapshot = (array) $amendment->pricing_snapshot;
        $this->editingAmendmentId = $amendment->id;
        $this->editingApproved = $amendment->isApproved();
        $this->newReturnAt = $amendment->new_return_at?->format('Y-m-d\TH:i') ?? '';
        $this->pricingPolicy = $amendment->pricing_policy ?: RentalPricingService::DEFAULT_POLICY;
        $this->rateSource = (string) ($snapshot['requested_rate_source'] ?? $snapshot['rate_source'] ?? RentalPricingService::DEFAULT_RATE_SOURCE);
        $this->reason = $amendment->reason;
        $this->notes = $amendment->notes;
        $this->quote = [];
        $this->reviewConfirmed = false;
        $this->dismissConfirmation();
        $this->resetErrorBag();
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    public function prepareApproval(int $amendmentId): void
    {
        $amendment = $this->contract->amendments()->findOrFail($amendmentId);
        if (! $amendment->isPending()) {
            $this->addError('amendment', 'Only a pending extension can be approved.');

            return;
        }

        $this->confirmationAction = 'approve';
        $this->confirmationAmendmentId = $amendment->id;
        $this->confirmationImpact = $this->amendmentImpact($amendment, false);
        $this->confirmationFingerprint = $this->impactFingerprint('approve', $amendment, $this->confirmationImpact);
        $this->confirmationAccepted = false;
    }

    public function prepareDelete(int $amendmentId): void
    {
        $amendment = $this->contract->amendments()->findOrFail($amendmentId);
        if (! $this->canDeleteAmendment($amendment)) {
            $this->addError('amendment', 'This extension is dependent history. Remove or revise later effective extensions first.');

            return;
        }

        $this->confirmationAction = 'delete';
        $this->confirmationAmendmentId = $amendment->id;
        $this->confirmationImpact = $this->amendmentImpact($amendment, true);
        $this->confirmationFingerprint = $this->impactFingerprint('delete', $amendment, $this->confirmationImpact);
        $this->confirmationAccepted = false;
    }

    public function confirmPreparedAction(ContractAmendmentService $service): void
    {
        if (! in_array($this->confirmationAction, ['approve', 'delete'], true) || $this->confirmationAmendmentId === null) {
            $this->addError('amendment', 'Choose an extension action and review its consequences first.');

            return;
        }

        $amendment = $this->contract->amendments()->findOrFail($this->confirmationAmendmentId);
        $freshImpact = $this->amendmentImpact($amendment, $this->confirmationAction === 'delete');
        $freshFingerprint = $this->impactFingerprint($this->confirmationAction, $amendment, $freshImpact);
        if (! hash_equals((string) $this->confirmationFingerprint, $freshFingerprint)) {
            $this->confirmationImpact = $freshImpact;
            $this->confirmationFingerprint = $freshFingerprint;
            $this->confirmationAccepted = false;
            $this->addError('amendment', 'The contract, payment balance, or extension changed. Review the refreshed consequences before confirming.');

            return;
        }

        $this->validate(['confirmationAccepted' => ['accepted']]);

        try {
            if ($this->confirmationAction === 'approve') {
                $service->approve($amendment, auth()->id());
                $message = 'Extension approved after impact confirmation.';
            } elseif ($this->confirmationAction === 'delete') {
                $wasApproved = $amendment->isApproved();
                $service->deleteExtension($amendment, auth()->id(), $this->notes);
                $message = $wasApproved
                    ? 'Approved extension voided with a complete financial reversal.'
                    : 'Extension request deleted. Its audit trail remains recoverable.';
            } else {
                return;
            }

            $this->reloadContract();
            $this->resetForm();
            session()->flash('message', $message);
        } catch (ValidationException $exception) {
            $this->captureValidationErrors($exception);
        }
    }

    public function dismissConfirmation(): void
    {
        $this->confirmationAction = null;
        $this->confirmationAmendmentId = null;
        $this->confirmationImpact = [];
        $this->confirmationFingerprint = null;
        $this->confirmationAccepted = false;
        $this->resetValidation('confirmationAccepted');
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

    public function canEditAmendment(ContractAmendment $amendment): bool
    {
        if ($amendment->type !== ContractAmendment::TYPE_EXTENSION) {
            return false;
        }
        if ($amendment->isPending()) {
            return true;
        }

        return $amendment->isApproved() && $this->isLatestEffectiveApprovedExtension($amendment);
    }

    public function canDeleteAmendment(ContractAmendment $amendment): bool
    {
        if ($amendment->type !== ContractAmendment::TYPE_EXTENSION) {
            return false;
        }
        if ($amendment->isApproved()) {
            return $this->isLatestEffectiveApprovedExtension($amendment);
        }

        return in_array($amendment->status, ['draft', 'pending_approval', 'rejected', 'cancelled'], true)
            && ! $amendment->charges()->exists();
    }

    public function updatedNewReturnAt(): void
    {
        $this->invalidatePreview();
    }

    public function updatedPricingPolicy(): void
    {
        $this->invalidatePreview();
    }

    public function updatedRateSource(): void
    {
        $this->invalidatePreview();
    }

    private function closeAmendment(int $amendmentId, string $action, ContractAmendmentService $service): void
    {
        try {
            $amendment = $this->contract->amendments()->findOrFail($amendmentId);
            $service->{$action}($amendment, auth()->id(), $this->notes);
            $this->reloadContract();
            $this->resetForm();
            session()->flash('message', 'Extension request '.$action.'ed.');
        } catch (ValidationException $exception) {
            $this->captureValidationErrors($exception);
        }
    }

    private function reloadContract(): void
    {
        $this->contract->refresh()->load(['car', 'amendments.charges', 'payments']);
    }

    private function validateInput(bool $requireReviewConfirmation = false): void
    {
        $rules = [
            'newReturnAt' => ['required', 'date'],
            'pricingPolicy' => ['required', 'in:daily_ceiling,hourly,prorated_daily,grace_then_daily'],
            'rateSource' => ['required', 'in:contract_rate,current_tariff,current_total_duration_tariff'],
            'reason' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'idempotencyKey' => ['required', 'uuid'],
        ];
        if ($requireReviewConfirmation) {
            $rules['reviewConfirmed'] = ['accepted'];
        }
        $this->validate($rules);
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
            'resulting_rental_days' => (int) ($quote['resulting_rental_days'] ?? 0),
            'pricing_policy' => (string) ($quote['pricing_policy'] ?? ''),
            'rate_source' => (string) ($quote['rate_source'] ?? ''),
            'requested_rate_source' => (string) ($quote['requested_rate_source'] ?? ''),
            'items' => (array) ($quote['items'] ?? []),
            'subtotal' => (float) ($quote['subtotal'] ?? 0),
            'tax' => (float) ($quote['tax'] ?? 0),
            'total' => (float) ($quote['total'] ?? 0),
        ], JSON_THROW_ON_ERROR));
    }

    private function extensionQuoteStartAt(): Carbon|string|null
    {
        if ($this->editingAmendmentId === null || ! $this->editingApproved) {
            return null;
        }

        return $this->contract->amendments()->findOrFail($this->editingAmendmentId)->old_return_at;
    }

    private function quoteImpact(array $quote): array
    {
        $oldExtensionTotal = 0.0;
        if ($this->editingAmendmentId !== null && $this->editingApproved) {
            $oldExtensionTotal = (float) $this->contract->amendments()->findOrFail($this->editingAmendmentId)->total_amount;
        }

        return $this->impactValues(
            $this->contract->return_date,
            Carbon::parse($this->newReturnAt),
            round((float) $quote['total'] - $oldExtensionTotal, 2),
            $oldExtensionTotal,
            (float) $quote['total'],
        );
    }

    private function amendmentImpact(ContractAmendment $amendment, bool $deleting): array
    {
        $delta = $deleting
            ? ($amendment->isApproved() ? -1 * (float) $amendment->total_amount : 0.0)
            : (float) $amendment->total_amount;
        $returnAfter = $deleting
            ? ($amendment->isApproved() ? $amendment->old_return_at : $this->contract->return_date)
            : $amendment->new_return_at;

        return $this->impactValues(
            $this->contract->return_date,
            Carbon::parse($returnAfter),
            $delta,
            $deleting && $amendment->isApproved() ? (float) $amendment->total_amount : 0.0,
            $deleting ? 0.0 : (float) $amendment->total_amount,
        );
    }

    private function impactValues($returnBefore, $returnAfter, float $totalDelta, float $oldExtensionTotal, float $newExtensionTotal): array
    {
        $currentContract = $this->contract->fresh(['payments']);
        $returnBefore = $currentContract->return_date ?? $returnBefore;
        $durationPolicy = RentalDuration::policyFromContractMeta($currentContract->meta);
        $currentTotal = (float) $currentContract->total_price;
        $currentBalance = (float) $currentContract->calculateRemainingBalance();

        return [
            'return_before' => Carbon::parse($returnBefore)->format('Y-m-d H:i'),
            'return_after' => Carbon::parse($returnAfter)->format('Y-m-d H:i'),
            'rental_days_before' => RentalDuration::billableDays($currentContract->pickup_date, $returnBefore, $durationPolicy),
            'rental_days_after' => RentalDuration::billableDays($currentContract->pickup_date, $returnAfter, $durationPolicy),
            'old_extension_total' => round($oldExtensionTotal, 2),
            'new_extension_total' => round($newExtensionTotal, 2),
            'total_delta' => round($totalDelta, 2),
            'contract_total_before' => round($currentTotal, 2),
            'contract_total_after' => round($currentTotal + $totalDelta, 2),
            'balance_before' => round($currentBalance, 2),
            'balance_after' => round($currentBalance + $totalDelta, 2),
        ];
    }

    private function impactFingerprint(string $action, ContractAmendment $amendment, array $impact): string
    {
        $currentContract = $this->contract->fresh();

        return hash('sha256', json_encode([
            'action' => $action,
            'amendment_id' => $amendment->id,
            'status' => $amendment->status,
            'updated_at' => $amendment->updated_at?->toIso8601String(),
            'contract_status' => $currentContract->current_status,
            'contract_updated_at' => $currentContract->updated_at?->toIso8601String(),
            'impact' => $impact,
        ], JSON_THROW_ON_ERROR));
    }

    private function isLatestEffectiveApprovedExtension(ContractAmendment $amendment): bool
    {
        $latestId = $this->contract->amendments
            ->where('type', ContractAmendment::TYPE_EXTENSION)
            ->where('status', 'approved')
            ->sortByDesc('sequence_no')
            ->first()?->id;

        return (int) $latestId === (int) $amendment->id
            && $this->contract->return_date?->equalTo($amendment->new_return_at)
            && in_array($this->contract->current_status, Contract::AMENDABLE_STATUSES, true)
            && $this->contract->actual_return_at === null
            && ! $this->contract->amendments->contains(fn (ContractAmendment $item) => $item->isPending());
    }

    private function invalidatePreview(): void
    {
        $this->quote = [];
        $this->reviewConfirmed = false;
    }

    private function resetForm(): void
    {
        $this->editingAmendmentId = null;
        $this->editingApproved = false;
        $this->newReturnAt = optional($this->contract->return_date)->format('Y-m-d\TH:i') ?? '';
        $this->pricingPolicy = RentalPricingService::DEFAULT_POLICY;
        $this->rateSource = RentalPricingService::DEFAULT_RATE_SOURCE;
        $this->reason = null;
        $this->notes = null;
        $this->idempotencyKey = (string) Str::uuid();
        $this->quote = [];
        $this->reviewConfirmed = false;
        $this->dismissConfirmation();
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.pages.panel.expert.rental-request.rental-request-extension');
    }
}
