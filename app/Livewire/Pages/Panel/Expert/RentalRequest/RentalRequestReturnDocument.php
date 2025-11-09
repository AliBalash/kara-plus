<?php

namespace App\Livewire\Pages\Panel\Expert\RentalRequest;

use App\Models\Contract;
use App\Models\ReturnDocument;
use App\Services\Media\OptimizedUploadService;
use App\Livewire\Concerns\InteractsWithToasts;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\Component;

class RentalRequestReturnDocument extends Component
{

    use WithFileUploads;
    use InteractsWithToasts;

    public $contractId;
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

    public $remainingBalance;
    public $contract;
    public $agreementNumber;

    protected OptimizedUploadService $imageUploader;
    public array $pendingInsideUploads = [];
    public array $pendingOutsideUploads = [];

    protected array $messages = [
        'fuelLevel.required' => 'Please record the fuel level at return.',
        'fuelLevel.integer' => 'Fuel level must be provided as a whole number between 0 and 100.',
        'fuelLevel.between' => 'Fuel level must be between 0% and 100%.',
        'mileage.required' => 'Please enter the odometer reading at return.',
        'mileage.integer' => 'Mileage may only contain numbers.',
        'mileage.min' => 'Mileage cannot be negative.',
        'mileage.max' => 'Mileage looks too large. Please check the value.',
        'factorContract.image' => 'Watcher receipt must be an image file.',
        'factorContract.mimes' => 'Watcher receipt must be a JPG, PNG, or WEBP file.',
        'carDashboard.required' => 'Uploading a dashboard photo is mandatory on return.',
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
    ];

    protected array $validationAttributes = [
        'carInsidePhotos' => 'interior photos',
        'carInsidePhotos.*' => 'interior photo',
        'carOutsidePhotos' => 'exterior photos',
        'carOutsidePhotos.*' => 'exterior photo',
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
            'pickupDocument',
        ])->findOrFail($contractId);

        $this->contractId = $contractId;
        $return = ReturnDocument::where('contract_id', $contractId)->first();
        if (!empty($return)) {
            $this->fuelLevel = $return->fuelLevel;
            $this->mileage = $return->mileage;
            $this->note = $return->note;
            $this->driverNote = $return->driver_note;
        }
        $this->existingFiles = [
            'factorContract' => $this->resolveDocumentUrl("ReturnDocument/factor_contract_{$this->contractId}"),
            'carDashboard' => $this->resolveDocumentUrl("ReturnDocument/car_dashboard_{$this->contractId}"),
        ];

        $this->existingGalleries = [
            'inside' => $this->mapGalleryMedia($return?->car_inside_photos ?? []),
            'outside' => $this->mapGalleryMedia($return?->car_outside_photos ?? []),
        ];

        $this->carInsidePhotos = [];
        $this->carOutsidePhotos = [];
        $this->pendingInsideUploads = [];
        $this->pendingOutsideUploads = [];

        $this->remainingBalance = $this->contract->calculateRemainingBalance();
        $this->agreementNumber = optional($this->contract->pickupDocument)->agreement_number;
    }

    public function uploadDocuments()
    {
        $validationRules = [
            'fuelLevel' => 'required',
            'mileage' => 'required',
            'note' => 'nullable|string|max:1000',
            'driverNote' => 'nullable|string|max:1000',
        ];

        // Factor Contract Validation
        if ($this->factorContract) {
            $validationRules['factorContract'] = 'image|max:8048';
        }



        // Car Dashboard  Validation
        if ($this->carDashboard) {
            $validationRules['carDashboard'] = 'required|image|max:8048';
        } elseif (!$this->carDashboard && empty($this->existingFiles['carDashboard'])) {
            $validationRules['carDashboard'] = 'image|max:8048';
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
            $returnDocument = ReturnDocument::updateOrCreate(
                ['contract_id' => $this->contractId],
                ['user_id' => auth()->id()]
            );
            $returnDocument->fuelLevel = $this->fuelLevel;
            $returnDocument->mileage = $this->mileage;
            $returnDocument->note = $this->note;
            $returnDocument->driver_note = $this->driverNote;

            // Factor Contract Upload
            if ($this->factorContract) {
                $factorPath = "ReturnDocument/factor_contract_{$this->contractId}.webp";
                $this->imageUploader->store($this->factorContract, $factorPath, 'myimage');
                $returnDocument->factor_contract = $factorPath;
                $uploadedPaths[] = $factorPath;
            }


            // Car Dashboard  Upload
            if ($this->carDashboard) {
                $carDashboardPath = "ReturnDocument/car_dashboard_{$this->contractId}.webp";
                $this->imageUploader->store($this->carDashboard, $carDashboardPath, 'myimage');
                $returnDocument->car_dashboard = $carDashboardPath;
                $uploadedPaths[] = $carDashboardPath;
            }

            if ($insideUploadCount > 0) {
                $existingInside = is_array($returnDocument->car_inside_photos)
                    ? $returnDocument->car_inside_photos
                    : ($returnDocument->car_inside_photos ? json_decode($returnDocument->car_inside_photos, true) : []);

                foreach ($this->carInsidePhotos as $photo) {
                    $storedPath = $this->storeGalleryPhoto($photo, 'inside');
                    if (! $storedPath) {
                        throw new \Exception('Error uploading inside cabin photo.');
                    }
                    $existingInside[] = $storedPath;
                    $uploadedPaths[] = $storedPath;
                }

                $returnDocument->car_inside_photos = $this->sanitizeGalleryArray($existingInside);
            }

            if ($outsideUploadCount > 0) {
                $existingOutside = is_array($returnDocument->car_outside_photos)
                    ? $returnDocument->car_outside_photos
                    : ($returnDocument->car_outside_photos ? json_decode($returnDocument->car_outside_photos, true) : []);

                foreach ($this->carOutsidePhotos as $photo) {
                    $storedPath = $this->storeGalleryPhoto($photo, 'outside');
                    if (! $storedPath) {
                        throw new \Exception('Error uploading exterior photo.');
                    }
                    $existingOutside[] = $storedPath;
                    $uploadedPaths[] = $storedPath;
                }

                $returnDocument->car_outside_photos = $this->sanitizeGalleryArray($existingOutside);
            }
            $returnDocument->user_id = auth()->id();
            $returnDocument->save();



            DB::commit();

            $this->toast('success', 'Documents uploaded successfully.');
            $this->mount($this->contractId);
        } catch (\Exception $e) {
            DB::rollBack();

            // حذف فایل‌های آپلود شده در صورت خطا
            foreach ($uploadedPaths as $path) {
                Storage::disk('myimage')->delete($path);
            }

            $this->toast('error', 'Error uploading documents: ' . $e->getMessage(), false);
        }
    }


    public function removeFile($fileType)
    {
        $mapping = [
            'factor_contract' => ['db_field' => 'factor_contract', 'view_key' => 'factorContract'],
            'car_dashboard' => ['db_field' => 'car_dashboard', 'view_key' => 'carDashboard'],
        ];

        if (! array_key_exists($fileType, $mapping)) {
            $this->toast('error', 'The file type "' . $fileType . '" is not valid.', false);
            return;
        }

        $dbField = $mapping[$fileType]['db_field'];
        $viewKey = $mapping[$fileType]['view_key'];
        $storedPath = $this->resolveStoredPath("ReturnDocument/{$fileType}_{$this->contractId}");

        if ($storedPath && Storage::disk('myimage')->exists($storedPath)) {
            Storage::disk('myimage')->delete($storedPath);
        }

        // پاک کردن مقدار از دیتابیس
        $returnDocument = ReturnDocument::where('contract_id', $this->contractId)->first();
        if ($returnDocument) {
            $returnDocument->$dbField = null;
            $returnDocument->save();
        }

        $this->existingFiles[$viewKey] = null;

        $this->toast('success', ucfirst($fileType) . ' successfully removed.');

        // بارگذاری مجدد
        $this->mount($this->contractId);
    }


    public function render()
    {
        return view('livewire.pages.panel.expert.rental-request.rental-request-return-document');
    }

    public function removeGalleryItem(string $section, string $path): void
    {
        if (! in_array($section, ['inside', 'outside'], true)) {
            $this->toast('error', 'The requested gallery section is not valid.', false);
            return;
        }

        $column = $section === 'inside' ? 'car_inside_photos' : 'car_outside_photos';

        $returnDocument = ReturnDocument::where('contract_id', $this->contractId)->first();
        if (! $returnDocument) {
            $this->toast('error', 'Return document not found.', false);
            return;
        }

        $gallery = is_array($returnDocument->$column)
            ? $returnDocument->$column
            : ($returnDocument->$column ? json_decode($returnDocument->$column, true) : []);

        if (! is_array($gallery) || empty($gallery)) {
            $this->toast('error', 'No photos available to remove.', false);
            return;
        }

        $filteredGallery = array_values(array_filter($gallery, fn ($storedPath) => $storedPath !== $path));

        if (count($filteredGallery) === count($gallery)) {
            $this->toast('error', 'The selected photo was not found.', false);
            return;
        }

        if (Storage::disk('myimage')->exists($path)) {
            Storage::disk('myimage')->delete($path);
        }

        $returnDocument->$column = $filteredGallery;
        $returnDocument->save();

        $this->toast('success', 'Photo removed successfully.');
        $this->mount($this->contractId);
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


    public function changeStatusToPayment($contractId)
    {
        $contract = Contract::findOrFail($contractId);

        // اگر کنترکت قبلاً وضعیت payment دارد، کاری نکن
        if ($contract->current_status === 'payment') {
            $this->toast('success', 'Contract is already in payment status.');
            return;
        }

        DB::beginTransaction();
        try {

            $contract->changeStatus('returned', auth()->id());
            // سپس به payment
            $contract->changeStatus('payment', auth()->id());
            DB::commit();
            $this->toast('success', 'Status changed to Returned then Payment successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->toast('error', 'Error changing status: ' . $e->getMessage(), false);
        }
    }

    private function storeGalleryPhoto(TemporaryUploadedFile $photo, string $section): string
    {
        $filename = sprintf(
            '%s_%s_%s.webp',
            $section,
            $this->contractId,
            Str::uuid()
        );

        $path = "ReturnDocument/{$section}/{$this->contractId}/{$filename}";
        $this->imageUploader->store($photo, $path, 'myimage');

        return $path;
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
}
