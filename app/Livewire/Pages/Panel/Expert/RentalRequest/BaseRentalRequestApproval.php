<?php

namespace App\Livewire\Pages\Panel\Expert\RentalRequest;

use App\Models\Contract;
use App\Models\PickupDocument;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;

abstract class BaseRentalRequestApproval extends Component
{
    public $contractId;
    public Contract $contract;
    public PickupDocument $pickupDocument;
    public array $existingFiles = [];
    public array $customerDocuments = [];

    public function mount($contractId): void
    {
        $this->contractId = $contractId;
        $this->contract = Contract::with(['customer', 'car.carModel', 'pickupDocument'])->findOrFail($contractId);
        $this->pickupDocument = PickupDocument::firstOrNew([
            'contract_id' => $contractId,
        ]);

        $this->existingFiles = $this->resolveExistingFiles();
        $this->prepareCustomerDocuments();
    }

    protected function resolveExistingFiles(): array
    {
        $storage = Storage::disk('myimage');

        $paths = [
            'tarsContract' => "PickupDocument/tars_contract_{$this->contractId}.jpg",
            'kardoContract' => "PickupDocument/kardo_contract_{$this->contractId}.jpg",
            'factorContract' => "PickupDocument/factor_contract_{$this->contractId}.jpg",
            'carDashboard' => "PickupDocument/car_dashboard_{$this->contractId}.jpg",
        ];

        return collect($paths)
            ->map(fn ($path) => $storage->exists($path) ? Storage::url($path) : null)
            ->toArray();
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
}
