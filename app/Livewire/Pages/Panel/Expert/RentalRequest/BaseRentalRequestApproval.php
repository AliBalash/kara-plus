<?php

namespace App\Livewire\Pages\Panel\Expert\RentalRequest;

use App\Models\Contract;
use App\Models\PickupDocument;
use App\Livewire\Concerns\InteractsWithToasts;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;

abstract class BaseRentalRequestApproval extends Component
{
    use InteractsWithToasts;

    public $contractId;
    public Contract $contract;
    public PickupDocument $pickupDocument;
    public array $existingFiles = [];
    public array $customerDocuments = [];

    public function mount($contractId): void
    {
        $this->contractId = $contractId;
        $this->refreshApprovalState();
    }

    protected function resolveExistingFiles(): array
    {
        return [
            'tarsContract' => $this->resolveDocumentUrl("PickupDocument/tars_contract_{$this->contractId}"),
            'kardoContract' => $this->resolveDocumentUrl("PickupDocument/kardo_contract_{$this->contractId}"),
            'factorContract' => $this->resolveDocumentUrl("PickupDocument/factor_contract_{$this->contractId}"),
            'carDashboard' => $this->resolveDocumentUrl("PickupDocument/car_dashboard_{$this->contractId}"),
        ];
    }

    protected function prepareCustomerDocuments(): void
    {
        $this->customerDocuments = [
            'passport' => [],
            'license' => [],
            'visa' => [],
            'ticket' => [],
        ];

        $customerDocument = $this->contract->customerDocument;

        if (! $customerDocument) {
            return;
        }

        $types = [
            'passport' => 'Passport',
            'license' => 'Driver License',
            'visa' => 'Visa',
            'ticket' => 'Ticket',
        ];

        foreach ($types as $key => $label) {
            $value = $customerDocument->{$key} ?? [];
            $this->customerDocuments[$key] = $this->formatDocumentFiles($value, $label);
        }
    }

    protected function formatDocumentFiles($value, string $categoryLabel): array
    {
        $storedFiles = $this->normalizeDocumentFiles($value);

        if (empty($storedFiles)) {
            return [];
        }

        $disk = Storage::disk('myimage');

        return collect($storedFiles)
            ->map(function ($path, $variant) use ($disk, $categoryLabel) {
                if (! is_string($path) || $path === '') {
                    return null;
                }

                if (! $disk->exists($path)) {
                    return null;
                }

                $normalizedPath = ltrim($path, '/');
                if (Str::startsWith($normalizedPath, 'public/')) {
                    $normalizedPath = Str::after($normalizedPath, 'public/');
                }

                $url = asset('storage/' . $normalizedPath);
                $extension = Str::lower(pathinfo($path, PATHINFO_EXTENSION));
                $isPdf = $extension === 'pdf';

                return [
                    'label' => $this->humanReadableDocumentLabel($variant, $categoryLabel),
                    'url' => $url,
                    'is_pdf' => $isPdf,
                    'filename' => basename($path),
                ];
            })
            ->filter()
            ->values()
            ->toArray();
    }

    protected function normalizeDocumentFiles($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }

            return ['file_1' => $value];
        }

        return [];
    }

    protected function humanReadableDocumentLabel($variant, string $categoryLabel): string
    {
        if (is_numeric($variant)) {
            return $categoryLabel . ' ' . (((int) $variant) + 1);
        }

        $variant = (string) $variant;

        return match (true) {
            $variant === 'front' => $categoryLabel . ' Front',
            $variant === 'back' => $categoryLabel . ' Back',
            Str::startsWith($variant, 'extra_') => $categoryLabel . ' ' . Str::after($variant, 'extra_'),
            default => $categoryLabel . ' ' . Str::of($variant)->replace('_', ' ')->title(),
        };
    }

    protected function resolveDocumentUrl(string $basePath): ?string
    {
        $storedPath = $this->resolveStoredPath($basePath);

        return $storedPath ? Storage::url($storedPath) : null;
    }

    protected function resolveStoredPath(string $basePath): ?string
    {
        foreach (['webp', 'jpg', 'jpeg', 'png'] as $extension) {
            $path = "{$basePath}.{$extension}";
            if (Storage::disk('myimage')->exists($path)) {
                return $path;
            }
        }

        return null;
    }

    protected function completeDeliveryInspection(?int $userId): bool
    {
        if (! $userId) {
            $this->toast('error', 'You need to be logged in to change the status.', false);
            return false;
        }

        $currentStatus = $this->contract->current_status;

        if (in_array($currentStatus, ['agreement_inspection', 'awaiting_return'], true)) {
            $this->toast('info', 'Inspection already completed.', false);
            return false;
        }

        if ($currentStatus !== 'delivery') {
            $this->toast('error', 'Inspection can only be completed from delivery status.', false);
            return false;
        }

        $this->contract->changeStatus('agreement_inspection', $userId);

        return true;
    }

    protected function advanceContractToAwaitingReturn(?int $userId): bool
    {
        if (! $userId) {
            $this->toast('error', 'You need to be logged in to change the status.', false);
            return false;
        }

        $currentStatus = $this->contract->current_status;

        if ($currentStatus === 'awaiting_return') {
            $this->toast('info', 'Contract is already awaiting return.', false);
            return false;
        }

        if ($currentStatus !== 'agreement_inspection') {
            $this->toast('error', 'Complete the inspection before moving to awaiting return.', false);
            return false;
        }

        $this->contract->changeStatus('awaiting_return', $userId);

        return true;
    }

    protected function refreshApprovalState(): void
    {
        $this->contract = Contract::with([
            'customer',
            'car.carModel',
            'pickupDocument',
            'deliveryDriver',
            'returnDriver',
        ])->findOrFail($this->contractId);

        $this->pickupDocument = PickupDocument::firstOrNew([
            'contract_id' => $this->contractId,
        ]);

        $this->existingFiles = $this->resolveExistingFiles();
        $this->prepareCustomerDocuments();
    }
}
