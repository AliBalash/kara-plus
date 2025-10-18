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
        $this->contract = Contract::with(['customer', 'car.carModel', 'pickupDocument'])->findOrFail($contractId);
        $this->pickupDocument = PickupDocument::firstOrNew([
            'contract_id' => $contractId,
        ]);

        $this->existingFiles = $this->resolveExistingFiles();
        $this->prepareCustomerDocuments();
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
}
