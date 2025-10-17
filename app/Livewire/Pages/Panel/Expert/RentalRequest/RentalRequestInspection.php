<?php

namespace App\Livewire\Pages\Panel\Expert\RentalRequest;

use App\Models\Contract;
use App\Models\PickupDocument;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;

class RentalRequestInspection extends Component
{
    public $contractId;
    public $contract;
    public $pickupDocument;
    public $existingFiles = [];
    public array $customerDocuments = [];

    public function mount($contractId)
    {
        $this->contractId = $contractId;
        $this->contract = Contract::findOrFail($contractId);
        $this->pickupDocument = PickupDocument::firstOrNew([
            'contract_id' => $contractId,
        ]);

        $storage = Storage::disk('myimage');
        $this->existingFiles = [
            'tarsContract' => $storage->exists("PickupDocument/tars_contract_{$contractId}.jpg")
                ? Storage::url("PickupDocument/tars_contract_{$contractId}.jpg")
                : null,
            'kardoContract' => $storage->exists("PickupDocument/kardo_contract_{$contractId}.jpg")
                ? Storage::url("PickupDocument/kardo_contract_{$contractId}.jpg")
                : null,
            'factorContract' => $storage->exists("PickupDocument/factor_contract_{$contractId}.jpg")
                ? Storage::url("PickupDocument/factor_contract_{$contractId}.jpg")
                : null,
            'carDashboard' => $storage->exists("PickupDocument/car_dashboard_{$contractId}.jpg")
                ? Storage::url("PickupDocument/car_dashboard_{$contractId}.jpg")
                : null,
        ];

        $this->prepareCustomerDocuments();
    }

    public function approveTars()
    {
        if ($this->pickupDocument->tars_contract) {
            $this->pickupDocument->tars_approved_at = now();
            $this->pickupDocument->tars_approved_by = auth()->id();
            $this->pickupDocument->save();
            session()->flash('success', 'TARS approved successfully.');
        } else {
            session()->flash('error', 'TARS contract not uploaded.');
        }
    }

    public function approveKardo()
    {
        if (!$this->contract->kardo_required) {
            session()->flash('info', 'KARDO is not required for this contract.');
            return;
        }
        if ($this->pickupDocument->kardo_contract) {
            $this->pickupDocument->kardo_approved_at = now();
            $this->pickupDocument->kardo_approved_by = auth()->id();
            $this->pickupDocument->save();
            session()->flash('success', 'KARDO approved successfully.');
        } else {
            session()->flash('error', 'KARDO contract not uploaded.');
        }
    }

    public function completeInspection()
    {
        if (!$this->pickupDocument->tars_approved_at) {
            session()->flash('error', 'Please approve TARS first.');
            return;
        }
        if ($this->contract->kardo_required && !$this->pickupDocument->kardo_approved_at) {
            session()->flash('error', 'Please approve KARDO first.');
            return;
        }

        // Set status to agreement_inspection first
        $this->contract->changeStatus('agreement_inspection', auth()->id());
        // Then immediately set to awaiting_return
        $this->contract->changeStatus('awaiting_return', auth()->id());
        session()->flash('success', 'Inspection completed and status changed to awaiting_return.');
    }

    public function render()
    {
        return view('livewire.pages.panel.expert.rental-request.rental-request-inspection');
    }

    private function prepareCustomerDocuments(): void
    {
        $this->customerDocuments = [
            'passport' => [],
            'license' => [],
            'visa' => [],
            'ticket' => [],
        ];

        $customerDocument = $this->contract->customerDocument;

        if (!$customerDocument) {
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

    private function formatDocumentFiles($value, string $categoryLabel): array
    {
        $storedFiles = $this->normalizeDocumentFiles($value);

        if (empty($storedFiles)) {
            return [];
        }

        $disk = Storage::disk('myimage');

        return collect($storedFiles)
            ->map(function ($path, $variant) use ($disk, $categoryLabel) {
                if (!is_string($path) || $path === '') {
                    return null;
                }

                if (!$disk->exists($path)) {
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

    private function normalizeDocumentFiles($value): array
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

    private function humanReadableDocumentLabel($variant, string $categoryLabel): string
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
