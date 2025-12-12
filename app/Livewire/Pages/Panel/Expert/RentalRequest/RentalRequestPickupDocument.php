<?php

namespace App\Livewire\Pages\Panel\Expert\RentalRequest;

use App\Models\Contract;
use App\Models\PickupDocument;
use App\Services\Media\OptimizedUploadService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use App\Livewire\Concerns\InteractsWithToasts;

class RentalRequestPickupDocument extends Component
{

    use WithFileUploads;
    use InteractsWithToasts;

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
    public array $pendingInsideUploads = [];
    public array $pendingOutsideUploads = [];
    public $fuelLevel  = 50;
    public $mileage;
    public $note;
    public $driverNote;
    public $existingFiles = [];

    public $remainingBalance = 0;
    public $depositNote;
    public array $depositDetails = [];
    public ?string $depositCategory = null;

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

    protected OptimizedUploadService $imageUploader;


    protected array $messages = [
        'fuelLevel.required' => 'Please select the fuel level before submitting the pickup checklist.',
        'fuelLevel.integer' => 'Fuel level must be provided as a whole number between 0 and 100.',
        'fuelLevel.between' => 'Fuel level must be between 0% and 100%.',
        'mileage.required' => 'Please enter the odometer reading captured at pickup.',
        'mileage.integer' => 'Mileage may only contain numbers.',
        'mileage.min' => 'Mileage cannot be a negative value.',
        'mileage.max' => 'Mileage looks too large. Please double-check and try again.',
        'tarsContract.required' => 'Uploading the Tars contract is mandatory for pickup.',
        'tarsContract.image' => 'Tars contract must be provided as an image file.',
        'tarsContract.mimes' => 'Tars contract must be a JPG, PNG, or WEBP file.',
        'kardoContract.required' => 'Uploading the KARDO inspection contract is mandatory for this booking.',
        'kardoContract.image' => 'KARDO contract must be provided as an image.',
        'kardoContract.mimes' => 'KARDO contract must be a JPG, PNG, or WEBP file.',
        'factorContract.image' => 'Watcher receipt must be provided as an image.',
        'factorContract.mimes' => 'Watcher receipt must be a JPG, PNG, or WEBP file.',
        'carDashboard.required' => 'A dashboard photo showing mileage and fuel level is required.',
        'carDashboard.image' => 'Dashboard photo must be an image.',
        'carDashboard.mimes' => 'Dashboard photo must be a JPG, PNG, or WEBP file.',
        'carInsidePhotos.required' => 'Please upload at least one interior photo.',
        'carInsidePhotos.array' => 'Interior photos must be selected from valid image files.',
        'carInsidePhotos.*.image' => 'Every interior photo must be a valid image.',
        'carInsidePhotos.*.mimes' => 'Interior photos must be JPG, PNG, or WEBP files.',
        'carOutsidePhotos.required' => 'Please upload at least one exterior photo.',
        'carOutsidePhotos.array' => 'Exterior photos must be selected from valid image files.',
        'carOutsidePhotos.*.image' => 'Every exterior photo must be a valid image.',
        'carOutsidePhotos.*.mimes' => 'Exterior photos must be JPG, PNG, or WEBP files.',
        'agreement_number.required' => 'Agreement number is required.',
    ];

    protected array $validationAttributes = [
        'carInsidePhotos' => 'interior photos',
        'carInsidePhotos.*' => 'interior photo',
        'carOutsidePhotos' => 'exterior photos',
        'carOutsidePhotos.*' => 'exterior photo',
        'agreement_number' => 'agreement number',
        'factorContract' => 'watcher receipt',
        'carDashboard' => 'dashboard photo',
        'fuelLevel' => 'fuel level',
        'mileage' => 'mileage',
    ];

    public function boot(OptimizedUploadService $imageUploader): void
    {
        $this->imageUploader = $imageUploader;
    }



    public function mount($contractId)
    {
        $this->contractId = $contractId;
        $this->contract = Contract::with([
            'customer',
            'car.carModel',
            'customerDocument',
            'charges',
            'payments',
            'pickupDocument',
        ])->findOrFail($contractId);

        $this->contractId = $contractId;
        $pickup = PickupDocument::where('contract_id', $contractId)->first();
        $contractMeta = $this->contract->meta ?? [];
        $this->depositCategory = $this->contract->deposit_category;
        $this->depositDetails = $this->buildDepositDetails();
        $this->depositNote = $this->depositDetails['display'] ?? null;
        if (!empty($pickup)) {
            $this->fuelLevel = $pickup->fuelLevel;
            $this->mileage = $pickup->mileage;
            $this->note = $pickup->note;
            $this->driverNote = $pickup->driver_note ?? ($contractMeta['driver_note'] ?? null);
            $this->agreement_number = $pickup->agreement_number;
        }
        if (empty($pickup)) {
            $this->agreement_number = null;
            $this->driverNote = $contractMeta['driver_note'] ?? null;
        }
        $this->existingFiles = [
            'tarsContract' => $this->resolveDocumentUrl("PickupDocument/tars_contract_{$this->contractId}"),
            'kardoContract' => $this->resolveDocumentUrl("PickupDocument/kardo_contract_{$this->contractId}"),
            'factorContract' => $this->resolveDocumentUrl("PickupDocument/factor_contract_{$this->contractId}"),
            'carDashboard' => $this->resolveDocumentUrl("PickupDocument/car_dashboard_{$this->contractId}"),
        ];

        $this->existingGalleries = [
            'inside' => $this->mapGalleryMedia($pickup?->car_inside_photos ?? []),
            'outside' => $this->mapGalleryMedia($pickup?->car_outside_photos ?? []),
        ];

        $this->carInsidePhotos = [];
        $this->carOutsidePhotos = [];
        $this->pendingInsideUploads = [];
        $this->pendingOutsideUploads = [];

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
            'agreement_number' => 'required',
            'note' => 'nullable|string|max:1000',
            'driverNote' => 'nullable|string|max:1000',
        ];

        $this->agreement_number = $this->agreement_number !== null
            ? Str::upper(trim((string) $this->agreement_number))
            : null;

        if ($this->agreement_number === '') {
            $this->agreement_number = null;
        }

        // Tars Contract Validation
        if ($this->tarsContract || empty($this->existingFiles['tarsContract'])) {
            $validationRules['tarsContract'] = 'required|image|max:8048';
        }

        // Kardo Contract Validation
        if ($this->contract->kardo_required) {
            if ($this->kardoContract || empty($this->existingFiles['kardoContract'])) {
                $validationRules['kardoContract'] = 'required|image|max:8048';
            }
            $validationRules['agreement_number'] = ['required'];
        } elseif ($this->kardoContract) {
            $validationRules['kardoContract'] = 'image|max:8048';
            if (! empty($this->agreement_number)) {
                $validationRules['agreement_number'] = ['nullable'];
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
            $this->validateWithScroll($validationRules);
        }

        $this->carInsidePhotos = array_values($this->carInsidePhotos);
        $this->carOutsidePhotos = array_values($this->carOutsidePhotos);


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
            $pickupDocument->agreement_number = $this->agreement_number;

            // Tars Contract Upload
            if ($this->tarsContract) {
                $tarsPath = "PickupDocument/tars_contract_{$this->contractId}.webp";
                $this->imageUploader->store($this->tarsContract, $tarsPath, 'myimage');
                $pickupDocument->tars_contract = $tarsPath;
                $uploadedPaths[] = $tarsPath;
            }

            // Kardo Contract Upload
            if ($this->kardoContract) {
                $kardoPath = "PickupDocument/kardo_contract_{$this->contractId}.webp";
                $this->imageUploader->store($this->kardoContract, $kardoPath, 'myimage');
                $pickupDocument->kardo_contract = $kardoPath;
                $uploadedPaths[] = $kardoPath;
            }

            // Factor Contract Upload
            if ($this->contract->payment_on_delivery && $this->factorContract) {
                $factorPath = "PickupDocument/factor_contract_{$this->contractId}.webp";
                $this->imageUploader->store($this->factorContract, $factorPath, 'myimage');
                $pickupDocument->factor_contract = $factorPath;
                $uploadedPaths[] = $factorPath;
            }


            // Car Dashboard  Upload
            if ($this->carDashboard) {
                $carDashboardPath = "PickupDocument/car_dashboard_{$this->contractId}.webp";
                $this->imageUploader->store($this->carDashboard, $carDashboardPath, 'myimage');
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
            $this->syncContractDriverNote();



            DB::commit();

            $this->toast('success', 'Documents uploaded successfully.');
            $this->mount($this->contractId);
        } catch (\Exception $e) {
            DB::rollBack();

            // حذف فایل‌های آپلود شده در صورت خطا
            foreach ($uploadedPaths as $path) {
                Storage::disk('myimage')->delete($path);
            }

            $this->toast('error', 'Error uploading documents: ' . $e->getMessage());
        }
    }


    public function removeFile($fileType)
    {
        $mapping = [
            'tars_contract' => ['db_field' => 'tars_contract', 'view_key' => 'tarsContract'],
            'kardo_contract' => ['db_field' => 'kardo_contract', 'view_key' => 'kardoContract'],
            'factor_contract' => ['db_field' => 'factor_contract', 'view_key' => 'factorContract'],
            'car_dashboard' => ['db_field' => 'car_dashboard', 'view_key' => 'carDashboard'],
        ];

        if (! array_key_exists($fileType, $mapping)) {
            $this->toast('error', 'The file type "' . $fileType . '" is not valid.');
            return;
        }

        $dbField = $mapping[$fileType]['db_field'];
        $viewKey = $mapping[$fileType]['view_key'];

        $storedPath = $this->resolveStoredPath("PickupDocument/{$fileType}_{$this->contractId}");

        if ($storedPath && Storage::disk('myimage')->exists($storedPath)) {
            Storage::disk('myimage')->delete($storedPath);
        }

        $pickupDocument = PickupDocument::where('contract_id', $this->contractId)->first();
        if ($pickupDocument) {
            $pickupDocument->$dbField = null;
            $pickupDocument->save();
        }

        $this->existingFiles[$viewKey] = null;

        $this->toast('success', 'File deleted successfully.');
        $this->mount($this->contractId);
    }

    private function syncContractDriverNote(): void
    {
        $meta = $this->contract->meta ?? [];

        if (!is_null($this->driverNote) && trim((string) $this->driverNote) !== '') {
            $meta['driver_note'] = $this->driverNote;
        } else {
            unset($meta['driver_note']);
        }

        $this->contract->meta = !empty($meta) ? $meta : null;
        $this->contract->save();
    }

    public function removeGalleryItem(string $section, string $path): void
    {
        if (! in_array($section, ['inside', 'outside'], true)) {
            $this->toast('error', 'The requested gallery section is not valid.');
            return;
        }

        $column = $section === 'inside' ? 'car_inside_photos' : 'car_outside_photos';

        $pickupDocument = PickupDocument::where('contract_id', $this->contractId)->first();
        if (! $pickupDocument) {
            $this->toast('error', 'Pickup document not found.');
            return;
        }

        $gallery = is_array($pickupDocument->$column)
            ? $pickupDocument->$column
            : ($pickupDocument->$column ? json_decode($pickupDocument->$column, true) : []);

        if (! is_array($gallery) || empty($gallery)) {
            $this->toast('error', 'No photos available to remove.');
            return;
        }

        $filteredGallery = array_values(array_filter($gallery, fn ($storedPath) => $storedPath !== $path));

        if (count($filteredGallery) === count($gallery)) {
            $this->toast('error', 'The selected photo was not found.');
            return;
        }

        if (Storage::disk('myimage')->exists($path)) {
            Storage::disk('myimage')->delete($path);
        }

        $pickupDocument->$column = $filteredGallery;
        $pickupDocument->save();

        $this->toast('success', 'Photo removed successfully.');
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

        $this->toast('success', 'Status changed to Delivery successfully.');
    }

    private function storeGalleryPhoto(TemporaryUploadedFile $photo, string $section): string
    {
        $filename = sprintf(
            '%s_%s_%s.webp',
            $section,
            $this->contractId,
            Str::uuid()
        );

        $path = "PickupDocument/{$section}/{$this->contractId}/{$filename}";
        $this->imageUploader->store($photo, $path, 'myimage');

        return $path;
    }

    public function updatedCarInsidePhotos($photos): void
    {
        $this->syncGalleryUploads('carInsidePhotos', 'pendingInsideUploads', $photos);
    }

    public function updatedCarOutsidePhotos($photos): void
    {
        $this->syncGalleryUploads('carOutsidePhotos', 'pendingOutsideUploads', $photos);
    }

    private function mergePendingUploads(array $current, $incoming): array
    {
        $incomingFiles = collect(is_array($incoming) ? $incoming : [])
            ->filter(fn ($file) => $file instanceof TemporaryUploadedFile)
            ->unique(fn (TemporaryUploadedFile $file) => $file->getFilename())
            ->values();

        $existing = collect($current)->filter(fn ($file) => $file instanceof TemporaryUploadedFile);

        return $existing->merge($incomingFiles)->values()->all();
    }

    private function syncGalleryUploads(string $property, string $pendingProperty, $incoming): void
    {
        $merged = $this->mergePendingUploads($this->$pendingProperty, $incoming);

        $this->$property = $merged;
        $this->$pendingProperty = $merged;
    }

    private function validateWithScroll(array $rules): void
    {
        try {
            $this->validate($rules, $this->messages, $this->validationAttributes);
        } catch (ValidationException $exception) {
            $this->dispatch('kara-scroll-to-error', field: $this->firstErrorField($exception));
            throw $exception;
        }
    }

    private function firstErrorField(ValidationException $exception): string
    {
        $errors = $exception->errors();
        $firstKey = array_key_first($errors);

        if (! is_string($firstKey) || $firstKey === '') {
            return '';
        }

        return Str::before($firstKey, '.');
    }

    private function resolveDocumentUrl(string $basePath): ?string
    {
        $storedPath = $this->resolveStoredPath($basePath);

        return $storedPath ? Storage::url($storedPath) : null;
    }

    private function resolveStoredPath(string $basePath): ?string
    {
        foreach (['webp', 'jpg', 'jpeg', 'png'] as $extension) {
            $path = "{$basePath}.{$extension}";
            if (Storage::disk('myimage')->exists($path)) {
                return $path;
            }
        }

        return null;
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

    private function buildDepositDetails(): array
    {
        $category = $this->contract->deposit_category;
        $detail = $this->contract->deposit;

        if (!$category && ($detail === null || $detail === '')) {
            return [];
        }

        $labels = [
            'cash_aed' => 'Cash (based on AED)',
            'cheque' => 'Cheque',
            'transfer_cash_irr' => 'Transfer or Cash (based on IRR)',
        ];

        $label = $labels[$category] ?? 'Deposit';
        $amount = null;
        $formattedDetail = null;

        if ($category === 'cash_aed' && is_numeric($detail)) {
            $amount = (float) $detail;
            $formattedDetail = number_format($amount, 2) . ' AED';
        } elseif (is_string($detail) && trim($detail) !== '') {
            $formattedDetail = trim($detail);
        }

        $display = trim($label . ($formattedDetail ? ' - ' . $formattedDetail : ''));

        return [
            'category' => $category,
            'label' => $label,
            'detail' => $formattedDetail ?? $detail,
            'amount' => $amount,
            'display' => $display !== '' ? $display : $label,
        ];
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
        } else {
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

        $depositAmount = $this->depositDetails['amount'] ?? null;

        if ($depositAmount !== null) {
            $this->costSummary['subtotal'] += $depositAmount;
            $this->costSummary['total'] += $depositAmount;
            $this->costSummary['remaining'] = (float) $this->costSummary['remaining'] + $depositAmount;
        }
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
