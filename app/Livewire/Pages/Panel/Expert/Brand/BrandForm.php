<?php

namespace App\Livewire\Pages\Panel\Expert\Brand;

use App\Livewire\Concerns\InteractsWithToasts;
use App\Livewire\Concerns\RefreshesFileInputs;
use App\Models\CarModel;
use App\Services\Media\DeferredImageUploadService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\WithFileUploads;

class BrandForm extends Component
{
    use WithFileUploads;
    use InteractsWithToasts;
    use RefreshesFileInputs;

    public $brandId;
    public $brand;
    public $model;
    public $brandIcon;
    public $currentBrandIcon;
    public $additionalImage;
    protected DeferredImageUploadService $deferredUploader;

    public function boot(DeferredImageUploadService $deferredUploader): void
    {
        $this->deferredUploader = $deferredUploader;
    }

    public function mount($brandId = null): void
    {
        if ($brandId) {
            $this->loadCarModelData($brandId);
        }
    }

    private function loadCarModelData($brandId): void
    {
        $carModel = CarModel::findOrFail($brandId);

        $this->brandId = $brandId;
        $this->brand = $carModel->brand;
        $this->model = $carModel->model;
        $this->currentBrandIcon = $carModel->brand_icon;
    }

    public function save(): void
    {
        $this->validate([
            'brand' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'brandIcon' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'additionalImage' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5048',
        ]);

        $isEditing = (bool) $this->brandId;
        $carModel = $this->brandId ? CarModel::findOrFail($this->brandId) : new CarModel();
        $existingImageModel = $carModel->exists ? $carModel->image : null;
        $oldBrandIconPath = $carModel->brand_icon;
        $oldAdditionalImage = $existingImageModel?->file_name;
        $newBrandIconPath = null;
        $newAdditionalImagePath = null;

        try {
            $carModel->brand = $this->brand;
            $carModel->model = $this->model;

            if ($this->brandIcon) {
                $newBrandIconPath = $this->deferredUploader->store(
                    $this->brandIcon,
                    'brand-icons/' . Str::slug($this->brand . '-' . $this->model) . '-' . Str::uuid() . '.webp',
                    'myimage',
                    ['quality' => 50, 'max_width' => 512, 'max_height' => 512]
                );

                $carModel->brand_icon = $newBrandIconPath;
            }

            $carModel->save();

            if ($this->additionalImage) {
                $newAdditionalImagePath = $this->deferredUploader->store(
                    $this->additionalImage,
                    Str::slug($carModel->fullName() ?: ($this->brand . '-' . $this->model)) . '-' . Str::uuid() . '.webp',
                    'car_pics',
                    ['quality' => 55, 'max_width' => 1920, 'max_height' => 1080, 'optimize' => false]
                );

                $carModel->image()->updateOrCreate(
                    [
                        'imageable_id' => $carModel->id,
                        'imageable_type' => CarModel::class,
                    ],
                    [
                        'file_path' => 'car-pics/',
                        'file_name' => basename($newAdditionalImagePath),
                    ]
                );
            }
        } catch (\Throwable $exception) {
            if ($newBrandIconPath && Storage::disk('myimage')->exists($newBrandIconPath)) {
                Storage::disk('myimage')->delete($newBrandIconPath);
            }

            if ($newAdditionalImagePath && Storage::disk('car_pics')->exists(basename($newAdditionalImagePath))) {
                Storage::disk('car_pics')->delete(basename($newAdditionalImagePath));
            }

            $this->toast('error', 'Unable to save the car model: ' . $exception->getMessage(), false);

            return;
        }

        if ($newBrandIconPath && $oldBrandIconPath && $oldBrandIconPath !== $newBrandIconPath && Storage::disk('myimage')->exists($oldBrandIconPath)) {
            Storage::disk('myimage')->delete($oldBrandIconPath);
        }

        if (
            $newAdditionalImagePath
            && $oldAdditionalImage
            && $oldAdditionalImage !== basename($newAdditionalImagePath)
            && Storage::disk('car_pics')->exists($oldAdditionalImage)
        ) {
            Storage::disk('car_pics')->delete($oldAdditionalImage);
        }

        $this->brandId = $carModel->id;
        $this->currentBrandIcon = $carModel->brand_icon;
        $this->brandIcon = null;
        $this->additionalImage = null;
        $this->refreshFileInputs();

        $this->toast('success', $isEditing
            ? 'The car model has been successfully updated!'
            : 'A new car model has been successfully added!');
    }

    public function removeBrandIcon(): void
    {
        if (! $this->brandId) {
            return;
        }

        $carModel = CarModel::findOrFail($this->brandId);
        $path = $carModel->brand_icon;

        if (! $path) {
            return;
        }

        $carModel->update(['brand_icon' => null]);

        if (Storage::disk('myimage')->exists($path)) {
            Storage::disk('myimage')->delete($path);
        }

        $this->currentBrandIcon = null;
        $this->brandIcon = null;
        $this->refreshFileInputs();

        $this->toast('success', 'Brand icon removed successfully.');
    }

    public function removeAdditionalImage(): void
    {
        if (! $this->brandId) {
            return;
        }

        $carModel = CarModel::findOrFail($this->brandId);
        $image = $carModel->image;

        if (! $image) {
            return;
        }

        $fileName = $image->file_name;
        $image->delete();

        if ($fileName && Storage::disk('car_pics')->exists($fileName)) {
            Storage::disk('car_pics')->delete($fileName);
        }

        $this->additionalImage = null;
        $this->refreshFileInputs();

        $this->toast('success', 'Additional image removed successfully.');
    }

    public function render()
    {
        $additionalImages = $this->brandId
            ? CarModel::findOrFail($this->brandId)->image
            : null;

        return view('livewire.pages.panel.expert.brand.brand-form', compact('additionalImages'));
    }
}
