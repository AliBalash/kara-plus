<?php

namespace App\Livewire\Pages\Panel\Expert\RentalRequest;

use App\Models\Contract;
use App\Models\PickupDocument;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class RentalRequestPickupDocument extends Component
{

    use WithFileUploads;

    public $contractId;
    public $tarsContract;
    public $kardoContract;
    public $agreement_number;
    public $factorContract;
    public $carDashboard;
    public array $carInsidePhotos = [];
    public array $carOutsidePhotos = [];
    public array $existingGalleries = [
        'inside' => [],
        'outside' => [],
    ];
    public $fuelLevel  = 50;
    public $mileage;
    public $note;
    public $driverNote;
    public $existingFiles = [];

    public $remainingBalance = 0;

    public $contract;
    public array $customerDocuments = [
        'passport' => [],
        'license' => [],
    ];
    public array $costBreakdown = [];
    public array $costSummary = [
        'subtotal' => 0,
        'tax' => 0,
        'total' => 0,
        'remaining' => 0,
    ];



    public function mount($contractId)
    {
        $this->contractId = $contractId;
        $this->contract = Contract::with(['customerDocument', 'charges', 'payments'])->findOrFail($contractId);

        $this->contractId = $contractId;
        $pickup = PickupDocument::where('contract_id', $contractId)->first();
        if (!empty($pickup)) {
            $this->fuelLevel = $pickup->fuelLevel;
            $this->mileage = $pickup->mileage;
            $this->note = $pickup->note;
            $this->driverNote = $pickup->driver_note;
            $this->agreement_number = $pickup->agreement_number;
        }
        if (empty($pickup)) {
            $this->agreement_number = null;
        }
        $this->existingFiles = [
            'tarsContract' => Storage::disk('myimage')->exists("PickupDocument/tars_contract_{$this->contractId}.jpg")
                ? Storage::url("PickupDocument/tars_contract_{$this->contractId}.jpg")
                : null,
            'kardoContract' => Storage::disk('myimage')->exists("PickupDocument/kardo_contract_{$this->contractId}.jpg")
                ? Storage::url("PickupDocument/kardo_contract_{$this->contractId}.jpg")
                : null,
            'factorContract' => Storage::disk('myimage')->exists("PickupDocument/factor_contract_{$this->contractId}.jpg")
                ? Storage::url("PickupDocument/factor_contract_{$this->contractId}.jpg")
                : null,
            'carDashboard' => Storage::disk('myimage')->exists("PickupDocument/car_dashboard_{$this->contractId}.jpg")
                ? Storage::url("PickupDocument/car_dashboard_{$this->contractId}.jpg")
                : null,
        ];

        $this->existingGalleries = [
            'inside' => $this->mapGalleryMedia($pickup?->car_inside_photos ?? []),
            'outside' => $this->mapGalleryMedia($pickup?->car_outside_photos ?? []),
        ];

        $this->carInsidePhotos = [];
        $this->carOutsidePhotos = [];

        $payments = $this->contract->relationLoaded('payments') ? $this->contract->payments : null;
        $this->remainingBalance = $this->contract->calculateRemainingBalance($payments);

        $this->prepareCustomerDocuments();
        $this->prepareCostBreakdown();
    }

    public function uploadDocuments()
    {
        $validationRules = [
            'fuelLevel' => 'required',
            'mileage' => 'required',
            'note' => 'nullable|string|max:1000',
            'driverNote' => 'nullable|string|max:1000',
        ];

        $this->agreement_number = $this->agreement_number !== null
            ? trim((string) $this->agreement_number)
            : null;

        // Tars Contract Validation
        if ($this->tarsContract || empty($this->existingFiles['tarsContract'])) {
            $validationRules['tarsContract'] = 'required|image|max:8048';
        }

        // Kardo Contract Validation
        if ($this->contract->kardo_required) {
            if ($this->kardoContract || empty($this->existingFiles['kardoContract'])) {
                $validationRules['kardoContract'] = 'required|image|max:8048';
            }
            $validationRules['agreement_number'] = ['required', 'regex:/^\d{1,30}$/'];
        } elseif ($this->kardoContract) {
            $validationRules['kardoContract'] = 'image|max:8048';
            if (! empty($this->agreement_number)) {
                $validationRules['agreement_number'] = ['nullable', 'regex:/^\d{1,30}$/'];
            }
        }
        if (! $this->contract->kardo_required && empty($validationRules['agreement_number'])) {
            $this->agreement_number = null;
        }


        // Factor Contract Validation
        if ($this->contract->payment_on_delivery) {
            if ($this->factorContract) {
                $validationRules['factorContract'] = 'image|max:8048';
            }
        } else {
            $this->factorContract = null;
        }



        // Car Dashboard Validation
        if ($this->carDashboard || empty($this->existingFiles['carDashboard'])) {
            $validationRules['carDashboard'] = 'required|image|max:8048';
        }


        $insideHasExisting = ! empty($this->existingGalleries['inside']);
        $outsideHasExisting = ! empty($this->existingGalleries['outside']);

        $maxGalleryItems = 12;

        $insideUploadCount = is_array($this->carInsidePhotos) ? count($this->carInsidePhotos) : 0;
        $insideRemainingSlots = max($maxGalleryItems - count($this->existingGalleries['inside']), 0);
        if ($insideRemainingSlots === 0 && $insideUploadCount > 0) {
            throw ValidationException::withMessages([
                'carInsidePhotos' => 'You have reached the maximum number of inside photos.',
            ]);
        }

        if (! $insideHasExisting && $insideUploadCount === 0) {
            $validationRules['carInsidePhotos'] = 'required|array|min:1|max:' . $maxGalleryItems;
        } elseif ($insideUploadCount > 0) {
            $validationRules['carInsidePhotos'] = 'array|min:1|max:' . $insideRemainingSlots;
        }

        if (array_key_exists('carInsidePhotos', $validationRules)) {
            $validationRules['carInsidePhotos.*'] = 'image|mimes:jpeg,jpg,png,webp|max:8048';
        }

        $outsideUploadCount = is_array($this->carOutsidePhotos) ? count($this->carOutsidePhotos) : 0;
        $outsideRemainingSlots = max($maxGalleryItems - count($this->existingGalleries['outside']), 0);
        if ($outsideRemainingSlots === 0 && $outsideUploadCount > 0) {
            throw ValidationException::withMessages([
                'carOutsidePhotos' => 'You have reached the maximum number of outside photos.',
            ]);
        }

        if (! $outsideHasExisting && $outsideUploadCount === 0) {
            $validationRules['carOutsidePhotos'] = 'required|array|min:1|max:' . $maxGalleryItems;
        } elseif ($outsideUploadCount > 0) {
            $validationRules['carOutsidePhotos'] = 'array|min:1|max:' . $outsideRemainingSlots;
        }

        if (array_key_exists('carOutsidePhotos', $validationRules)) {
            $validationRules['carOutsidePhotos.*'] = 'image|mimes:jpeg,jpg,png,webp|max:8048';
        }



        if ($validationRules) {
            $this->validate($validationRules);
        }


        // Start Database Transaction
        DB::beginTransaction();
        $uploadedPaths = [];

        try {
            $pickupDocument = PickupDocument::updateOrCreate(
                ['contract_id' => $this->contractId],
                ['user_id' => auth()->id()]
            );
            $pickupDocument->fuelLevel = $this->fuelLevel;
            $pickupDocument->mileage = $this->mileage;
            $pickupDocument->note = $this->note;
            $pickupDocument->driver_note = $this->driverNote;
            $pickupDocument->agreement_number = $this->contract->kardo_required
                ? $this->agreement_number
                : null;

            // Tars Contract Upload
            if ($this->tarsContract) {
                $tarsPath = $this->tarsContract->storeAs('PickupDocument', "tars_contract_{$this->contractId}.jpg", 'myimage');
                if (!$tarsPath) throw new \Exception('Error uploading Tars contract.');
                $pickupDocument->tars_contract = $tarsPath;
                $uploadedPaths[] = $tarsPath;
            }

            // Kardo Contract Upload
            if ($this->kardoContract) {
                $kardoPath = $this->kardoContract->storeAs('PickupDocument', "kardo_contract_{$this->contractId}.jpg", 'myimage');
                if (!$kardoPath) throw new \Exception('Error uploading Kardo contract.');
                $pickupDocument->kardo_contract = $kardoPath;
                $uploadedPaths[] = $kardoPath;
            }

            // Factor Contract Upload
            if ($this->contract->payment_on_delivery && $this->factorContract) {
                $factorPath = $this->factorContract->storeAs('PickupDocument', "factor_contract_{$this->contractId}.jpg", 'myimage');
                if (!$factorPath) throw new \Exception('Error uploading factor contract.');
                $pickupDocument->factor_contract = $factorPath;
                $uploadedPaths[] = $factorPath;
            }


            // Car Dashboard  Upload
            if ($this->carDashboard) {
                $carDashboardPath = $this->carDashboard->storeAs('PickupDocument', "car_dashboard_{$this->contractId}.jpg", 'myimage');
                if (!$carDashboardPath) throw new \Exception('Error uploading dashboard photo.');
                $pickupDocument->car_dashboard = $carDashboardPath;
                $uploadedPaths[] = $carDashboardPath;
            }

            if ($insideUploadCount > 0) {
                $existingInside = is_array($pickupDocument->car_inside_photos)
                    ? $pickupDocument->car_inside_photos
                    : ($pickupDocument->car_inside_photos ? json_decode($pickupDocument->car_inside_photos, true) : []);

                foreach ($this->carInsidePhotos as $photo) {
                    $storedPath = $this->storeGalleryPhoto($photo, 'inside');
                    if (!$storedPath) {
                        throw new \Exception('Error uploading inside cabin photo.');
                    }
                    $existingInside[] = $storedPath;
                    $uploadedPaths[] = $storedPath;
                }

                $pickupDocument->car_inside_photos = $this->sanitizeGalleryArray($existingInside);
            }

            if ($outsideUploadCount > 0) {
                $existingOutside = is_array($pickupDocument->car_outside_photos)
                    ? $pickupDocument->car_outside_photos
                    : ($pickupDocument->car_outside_photos ? json_decode($pickupDocument->car_outside_photos, true) : []);

                foreach ($this->carOutsidePhotos as $photo) {
                    $storedPath = $this->storeGalleryPhoto($photo, 'outside');
                    if (!$storedPath) {
                        throw new \Exception('Error uploading exterior photo.');
                    }
                    $existingOutside[] = $storedPath;
                    $uploadedPaths[] = $storedPath;
                }

                $pickupDocument->car_outside_photos = $this->sanitizeGalleryArray($existingOutside);
            }
            $pickupDocument->user_id = auth()->id();
            $pickupDocument->save();



            DB::commit();

            session()->flash('message', 'Documents uploaded successfully.');
            $this->mount($this->contractId);
        } catch (\Exception $e) {
            DB::rollBack();

            // حذف فایل‌های آپلود شده در صورت خطا
            foreach ($uploadedPaths as $path) {
                Storage::disk('myimage')->delete($path);
            }

            session()->flash('error', 'Error uploading documents: ' . $e->getMessage());
        }
    }


    public function removeFile($fileType)
    {
        // نگاشت جدید با کلیدهای منطبق بر view
        $mapping = [
            'tars_contract' => ['db_field' => 'tars_contract', 'extension' => 'jpg'],
            'kardo_contract' => ['db_field' => 'kardo_contract', 'extension' => 'jpg'],
            'factor_contract' => ['db_field' => 'factor_contract', 'extension' => 'jpg'],
            'car_dashboard' => ['db_field' => 'car_dashboard', 'extension' => 'jpg'],
        ];

        if (!array_key_exists($fileType, $mapping)) {
            session()->flash('error', 'The file type "' . $fileType . '" is not valid.');
            return;
        }

        $dbField = $mapping[$fileType]['db_field'];
        $extension = $mapping[$fileType]['extension'];

        // ساخت مسیر فایل با استفاده از fileType (نه db_field)
        $filePath = "PickupDocument/{$fileType}_{$this->contractId}.{$extension}";

        if (Storage::disk('myimage')->exists($filePath)) {
            Storage::disk('myimage')->delete($filePath);
        }

        $pickupDocument = PickupDocument::where('contract_id', $this->contractId)->first();
        if ($pickupDocument) {
            $pickupDocument->$dbField = null;
            $pickupDocument->save();
        }

        session()->flash('message', 'File deleted successfully.');
        $this->mount($this->contractId);
    }

    public function removeGalleryItem(string $section, string $path): void
    {
        if (! in_array($section, ['inside', 'outside'], true)) {
            session()->flash('error', 'The requested gallery section is not valid.');
            return;
        }

        $column = $section === 'inside' ? 'car_inside_photos' : 'car_outside_photos';

        $pickupDocument = PickupDocument::where('contract_id', $this->contractId)->first();
        if (! $pickupDocument) {
            session()->flash('error', 'Pickup document not found.');
            return;
        }

        $gallery = is_array($pickupDocument->$column)
            ? $pickupDocument->$column
            : ($pickupDocument->$column ? json_decode($pickupDocument->$column, true) : []);

        if (! is_array($gallery) || empty($gallery)) {
            session()->flash('error', 'No photos available to remove.');
            return;
        }

        $filteredGallery = array_values(array_filter($gallery, fn ($storedPath) => $storedPath !== $path));

        if (count($filteredGallery) === count($gallery)) {
            session()->flash('error', 'The selected photo was not found.');
            return;
        }

        if (Storage::disk('myimage')->exists($path)) {
            Storage::disk('myimage')->delete($path);
        }

        $pickupDocument->$column = $filteredGallery;
        $pickupDocument->save();

        session()->flash('message', 'Photo removed successfully.');
        $this->mount($this->contractId);
    }

    public function render()
    {
        return view('livewire.pages.panel.expert.rental-request.rental-request-pickup-document');
    }

    public function changeStatusToDelivery($contractId)
    {
        $contract = Contract::findOrFail($contractId);

        // تغییر وضعیت به 'delivery'
        $contract->changeStatus('delivery', auth()->id());

        session()->flash('message', 'Status changed to Delivery successfully.');
    }

    private function storeGalleryPhoto(TemporaryUploadedFile $photo, string $section): string
    {
        $extension = Str::lower($photo->getClientOriginalExtension() ?: $photo->guessExtension() ?: 'jpg');
        if (! in_array($extension, ['jpeg', 'jpg', 'png', 'webp'])) {
            $extension = 'jpg';
        }

        $filename = sprintf(
            '%s_%s_%s.%s',
            $section,
            $this->contractId,
            Str::uuid(),
            $extension
        );

        return $photo->storeAs(
            "PickupDocument/{$section}/{$this->contractId}",
            $filename,
            'myimage'
        );
    }

    private function sanitizeGalleryArray($items): array
    {
        if (is_string($items)) {
            $decoded = json_decode($items, true);
            $items = json_last_error() === JSON_ERROR_NONE ? $decoded : [$items];
        }

        if (! is_array($items)) {
            return [];
        }

        return collect($items)
            ->filter(fn ($path) => is_string($path) && trim($path) !== '')
            ->unique()
            ->values()
            ->toArray();
    }

    private function mapGalleryMedia($items): array
    {
        if (empty($items)) {
            return [];
        }

        if (is_string($items)) {
            $decoded = json_decode($items, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $items = $decoded;
            } else {
                $items = [$items];
            }
        }

        if (! is_array($items)) {
            return [];
        }

        return collect($items)
            ->filter(fn ($path) => is_string($path) && Storage::disk('myimage')->exists($path))
            ->map(function ($path) {
                return [
                    'path' => $path,
                    'url' => Storage::url($path),
                    'name' => basename($path),
                ];
            })
            ->values()
            ->toArray();
    }

    private function prepareCustomerDocuments(): void
    {
        $customerDocument = $this->contract->customerDocument;

        if (!$customerDocument) {
            $this->customerDocuments = [
                'passport' => [],
                'license' => [],
            ];
            return;
        }

        $this->customerDocuments = [
            'passport' => $this->formatDocumentFiles($customerDocument->passport ?? []),
            'license' => $this->formatDocumentFiles($customerDocument->license ?? []),
        ];
    }

    private function formatDocumentFiles($value): array
    {
        $storedFiles = $this->normalizeDocumentFiles($value);

        if (empty($storedFiles)) {
            return [];
        }

        return collect($storedFiles)
            ->map(function ($path, $label) {
                $disk = Storage::disk('myimage');
                $exists = $path && $disk->exists($path);

                if (! $exists) {
                    return null;
                }

                $normalizedPath = ltrim($path, '/');
                if (Str::startsWith($normalizedPath, 'public/')) {
                    $normalizedPath = Str::after($normalizedPath, 'public/');
                }

                $url = asset('storage/' . $normalizedPath);

                return [
                    'label' => $this->humanReadableLabel($label),
                    'url' => $url,
                    'is_pdf' => Str::endsWith(Str::lower($path), '.pdf'),
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

            return ['front' => $value];
        }

        return [];
    }

    private function humanReadableLabel(string $label): string
    {
        return match (true) {
            $label === 'front' => 'Front',
            $label === 'back' => 'Back',
            Str::startsWith($label, 'extra_') => 'Additional ' . Str::after($label, 'extra_'),
            default => Str::of($label)->replace('_', ' ')->title(),
        };
    }

    private function prepareCostBreakdown(): void
    {
        $charges = $this->contract->relationLoaded('charges')
            ? $this->contract->charges
            : $this->contract->charges()->get();

        if ($charges->isEmpty()) {
            $this->costBreakdown = [];
            $this->costSummary = [
                'subtotal' => (float) ($this->contract->total_price ?? 0),
                'tax' => 0.0,
                'total' => (float) ($this->contract->total_price ?? 0),
                'remaining' => (float) $this->remainingBalance,
            ];

            return;
        }

        $chargeRows = $charges->reject(fn($charge) => $charge->type === 'tax');

        $this->costBreakdown = $chargeRows->map(function ($charge) {
            return [
                'label' => $this->humanReadableChargeTitle($charge->title),
                'description' => $charge->description,
                'amount' => (float) $charge->amount,
            ];
        })->toArray();

        $tax = (float) $charges->where('type', 'tax')->sum('amount');
        $total = (float) $charges->sum('amount');
        $subtotal = $total - $tax;

        $this->costSummary = [
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $total,
            'remaining' => (float) $this->remainingBalance,
        ];
    }

    private function humanReadableChargeTitle(string $title): string
    {
        $labels = [
            'base_rental' => 'Base Rental',
            'pickup_transfer' => 'Pickup Transfer',
            'return_transfer' => 'Return Transfer',
            'ldw_insurance' => 'LDW Insurance',
            'scdw_insurance' => 'SCDW Insurance',
            'tax' => 'Tax (5%)',
        ];

        if (array_key_exists($title, $labels)) {
            return $labels[$title];
        }

        return Str::of($title)->replace('_', ' ')->title();
    }
}
