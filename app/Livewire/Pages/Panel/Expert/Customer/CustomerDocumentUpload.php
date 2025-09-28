<?php

namespace App\Livewire\Pages\Panel\Expert\Customer;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use App\Models\CustomerDocument;
use App\Models\Payment;
use Illuminate\Support\Str;


class CustomerDocumentUpload extends Component
{
    use WithFileUploads;

    public $customerId;
    public $contractId;
    public array $visa = [];
    public array $passport = [];
    public array $license = [];
    public array $ticket = [];
    public $hasCustomerDocument;
    public $hasPayments;
    public array $existingFiles = [];


    public $hotel_name;
    public $hotel_address;

    protected array $documentTypes = ['visa', 'passport', 'license', 'ticket'];
    protected array $orderedLabels = ['front', 'back'];

    public function mount($customerId, $contractId)
    {
        $this->customerId = $customerId;
        $this->contractId = $contractId;
        $customerDocument = CustomerDocument::where('customer_id', $this->customerId)
            ->where('contract_id', $this->contractId)
            ->first();

        // بررسی وجود اسناد مشتری
        $this->hasCustomerDocument = CustomerDocument::where('customer_id', $this->customerId)
            ->where('contract_id', $this->contractId)
            ->exists();

        // بررسی وجود پرداخت‌ها
        $this->hasPayments = Payment::where('customer_id', $this->customerId)
            ->where('contract_id', $this->contractId)
            ->exists();

        if (!empty($customerDocument)) {

            $this->hotel_name = $customerDocument->hotel_name;
            $this->hotel_address = $customerDocument->hotel_address;
        }

        $this->loadExistingFiles($customerDocument);
    }


    public function uploadDocument()
    {
        $validationRules = [
            'hotel_name' => 'required|string',
            'hotel_address' => 'required|string',
            'visa' => 'nullable|array|max:3',
            'visa.*' => 'file|mimes:jpg,jpeg,png,pdf|max:8000',
            'passport' => 'nullable|array|max:3',
            'passport.*' => 'file|mimes:jpg,jpeg,png,pdf|max:8000',
            'license' => 'nullable|array|max:3',
            'license.*' => 'file|mimes:jpg,jpeg,png,pdf|max:8000',
            'ticket' => 'nullable|array|max:3',
            'ticket.*' => 'file|mimes:jpg,jpeg,png,pdf|max:8000',
        ];

        foreach ($this->documentTypes as $type) {
            $existingCount = count($this->existingFiles[$type] ?? []);
            $newCount = count($this->{$type} ?? []);

            if ($existingCount + $newCount > 3) {
                $this->addError($type, 'You can upload up to 3 files per document type.');
                return;
            }
        }

        $this->validate($validationRules);

        $customerDocument = CustomerDocument::updateOrCreate(
            ['customer_id' => $this->customerId, 'contract_id' => $this->contractId],
            []
        );

        foreach ($this->documentTypes as $type) {
            if (!empty($this->{$type})) {
                $storedFiles = $this->storeUploadedFiles($customerDocument, $type, $this->{$type});
                $customerDocument->{$type} = $storedFiles;
            }
        }

        $customerDocument->hotel_name = $this->hotel_name;
        $customerDocument->hotel_address = $this->hotel_address;

        $customerDocument->save();

        $this->reset(['visa', 'passport', 'license', 'ticket']);

        session()->flash('message', 'Documents uploaded successfully.');
        $this->loadExistingFiles($customerDocument);
    }


    public function removeFile($fileType, $label)
    {
        if (!in_array($fileType, $this->documentTypes)) {
            return;
        }

        $customerDocument = CustomerDocument::where('customer_id', $this->customerId)
            ->where('contract_id', $this->contractId)
            ->first();

        if (!$customerDocument) {
            return;
        }

        $storedFiles = $this->getStoredFilesFromColumn($customerDocument->{$fileType});

        if (!array_key_exists($label, $storedFiles)) {
            return;
        }

        $path = $storedFiles[$label];

        if ($path && Storage::disk('myimage')->exists($path)) {
            Storage::disk('myimage')->delete($path);
        }

        unset($storedFiles[$label]);

        $customerDocument->{$fileType} = $storedFiles ?: null;
        $customerDocument->save();

        $this->loadExistingFiles($customerDocument);

        session()->flash('message', ucfirst($fileType) . ' file removed.');
    }

    public function render()
    {
        return view('livewire.pages.panel.expert.customer.customer-document-upload');
    }

    private function loadExistingFiles(?CustomerDocument $customerDocument): void
    {
        $files = [];

        foreach ($this->documentTypes as $type) {
            $storedFiles = $customerDocument ? $this->getStoredFilesFromColumn($customerDocument->{$type}) : [];
            $files[$type] = $this->formatFilesForDisplay($type, $storedFiles);
        }

        $this->existingFiles = $files;
        $this->hasCustomerDocument = $customerDocument?->exists ?? false;
    }

    private function getStoredFilesFromColumn($columnValue): array
    {
        if (is_array($columnValue)) {
            return $columnValue;
        }

        if (is_string($columnValue) && $columnValue !== '') {
            $decoded = json_decode($columnValue, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }

            return ['front' => $columnValue];
        }

        return [];
    }

    private function formatFilesForDisplay(string $type, array $storedFiles): array
    {
        return collect($storedFiles)
            ->map(function ($path, $label) {
                $disk = Storage::disk('myimage');
                $normalizedLabel = $this->humanReadableLabel($label);
                $exists = $path && $disk->exists($path);

                return [
                    'label' => $normalizedLabel,
                    'raw_label' => $label,
                    'path' => $path,
                    'url' => $exists ? $this->buildPublicUrl($path) : null,
                    'is_pdf' => Str::endsWith(Str::lower($path), '.pdf'),
                ];
            })
            ->values()
            ->toArray();
    }

    private function storeUploadedFiles(CustomerDocument $customerDocument, string $type, array $uploads): array
    {
        $storedFiles = $this->getStoredFilesFromColumn($customerDocument->{$type});

        foreach ($uploads as $file) {
            $label = $this->nextAvailableLabel($storedFiles);
            $extension = Str::lower($file->getClientOriginalExtension());
            $fileName = $this->buildFileName($type, $label, $extension);
            $path = $file->storeAs('CustomerDocument', $fileName, 'myimage');
            $storedFiles[$label] = $path;
        }

        return $storedFiles;
    }

    private function buildFileName(string $type, string $label, string $extension): string
    {
        $timestamp = now()->format('YmdHis');
        return sprintf(
            '%s_%s_%s_%s_%s.%s',
            $type,
            $this->customerId,
            $this->contractId,
            $label,
            $timestamp,
            $extension
        );
    }

    private function nextAvailableLabel(array $storedFiles): string
    {
        foreach ($this->orderedLabels as $label) {
            if (!array_key_exists($label, $storedFiles)) {
                return $label;
            }
        }

        $index = 1;

        while (array_key_exists("extra_{$index}", $storedFiles)) {
            $index++;
        }

        return "extra_{$index}";
    }

    private function humanReadableLabel(string $label): string
    {
        return match (true) {
            $label === 'front' => 'Front',
            $label === 'back' => 'Back',
            Str::startsWith($label, 'extra_') => 'Additional ' . Str::after($label, 'extra_'),
            default => Str::headline($label),
        };
    }

    private function buildPublicUrl(string $path): string
    {
        $trimmed = ltrim($path, '/');
        return url('storage/' . $trimmed);
    }
}
