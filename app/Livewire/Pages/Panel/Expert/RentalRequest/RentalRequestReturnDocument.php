<?php

namespace App\Livewire\Pages\Panel\Expert\RentalRequest;

use App\Models\Contract;
use App\Models\ReturnDocument;
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


    public function mount($contractId)
    {
        $this->contractId = $contractId;
        $this->contract = Contract::findOrFail($contractId);

        $this->contractId = $contractId;
        $return = ReturnDocument::where('contract_id', $contractId)->first();
        if (!empty($return)) {
            $this->fuelLevel = $return->fuelLevel;
            $this->mileage = $return->mileage;
            $this->note = $return->note;
            $this->driverNote = $return->driver_note;
        }
        $this->existingFiles = [
            'factorContract' => Storage::disk('myimage')->exists("ReturnDocument/factor_contract_{$this->contractId}.jpg")
                ? Storage::url("ReturnDocument/factor_contract_{$this->contractId}.jpg")
                : null,
            'carDashboard' => Storage::disk('myimage')->exists("ReturnDocument/car_dashboard_{$this->contractId}.jpg")
                ? Storage::url("ReturnDocument/car_dashboard_{$this->contractId}.jpg")
                : null,
        ];

        $this->existingGalleries = [
            'inside' => $this->mapGalleryMedia($return?->car_inside_photos ?? []),
            'outside' => $this->mapGalleryMedia($return?->car_outside_photos ?? []),
        ];

        $this->carInsidePhotos = [];
        $this->carOutsidePhotos = [];

        $this->remainingBalance = $this->contract->calculateRemainingBalance();
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
            $this->validate($validationRules);
        }


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
                $factorPath = $this->factorContract->storeAs('ReturnDocument', "factor_contract_{$this->contractId}.jpg", 'myimage');
                if (!$factorPath) throw new \Exception('Error uploading factor contract.');
                $returnDocument->factor_contract = $factorPath;
                $uploadedPaths[] = $factorPath;
            }


            // Car Dashboard  Upload
            if ($this->carDashboard) {
                $carDashboardPath = $this->carDashboard->storeAs('ReturnDocument', "car_dashboard_{$this->contractId}.jpg", 'myimage');
                if (!$carDashboardPath) throw new \Exception('Error uploading dashboard photo.');
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
        // نگاشت بین fileType از view و نام واقعی فیلد در دیتابیس + نوع فایل
        $mapping = [
            'tars_contract' => ['db_field' => 'tars_contract', 'extension' => 'jpg'],
            'kardo_contract' => ['db_field' => 'kardo_contract', 'extension' => 'jpg'],
            'factor_contract' => ['db_field' => 'factor_contract', 'extension' => 'jpg'],
            'car_dashboard' => ['db_field' => 'car_dashboard', 'extension' => 'jpg'],
        ];

        // بررسی اعتبار کلید
        if (!array_key_exists($fileType, $mapping)) {
            session()->flash('error', 'The file type "' . $fileType . '" is not valid.');
            return;
        }

        $dbField = $mapping[$fileType]['db_field'];
        $extension = $mapping[$fileType]['extension'];

        // ساخت مسیر فایل
        $filePath = "ReturnDocument/{$fileType}_{$this->contractId}.{$extension}";

        if (Storage::disk('myimage')->exists($filePath)) {
            Storage::disk('myimage')->delete($filePath);
        }

        // پاک کردن مقدار از دیتابیس
        $returnDocument = ReturnDocument::where('contract_id', $this->contractId)->first();
        if ($returnDocument) {
            $returnDocument->$dbField = null;
            $returnDocument->save();
        }

        // پاک‌سازی فایل در UI
        $this->existingFiles[$fileType] = null;

        session()->flash('message', ucfirst($fileType) . ' successfully removed.');

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
            session()->flash('error', 'The requested gallery section is not valid.');
            return;
        }

        $column = $section === 'inside' ? 'car_inside_photos' : 'car_outside_photos';

        $returnDocument = ReturnDocument::where('contract_id', $this->contractId)->first();
        if (! $returnDocument) {
            session()->flash('error', 'Return document not found.');
            return;
        }

        $gallery = is_array($returnDocument->$column)
            ? $returnDocument->$column
            : ($returnDocument->$column ? json_decode($returnDocument->$column, true) : []);

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

        $returnDocument->$column = $filteredGallery;
        $returnDocument->save();

        session()->flash('message', 'Photo removed successfully.');
        $this->mount($this->contractId);
    }


    public function changeStatusToPayment($contractId)
    {
        $contract = Contract::findOrFail($contractId);

        // اگر کنترکت قبلاً وضعیت payment دارد، کاری نکن
        if ($contract->current_status === 'payment') {
            session()->flash('message', 'Contract is already in payment status.');
            return;
        }

        DB::beginTransaction();
        try {

            $contract->changeStatus('returned', auth()->id());
            // سپس به payment
            $contract->changeStatus('payment', auth()->id());
            DB::commit();
            session()->flash('message', 'Status changed to Returned then Payment successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Error changing status: ' . $e->getMessage());
        }
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
            "ReturnDocument/{$section}/{$this->contractId}",
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
}
