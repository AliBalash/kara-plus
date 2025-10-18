<?php

namespace App\Livewire\Pages\Panel\Expert\Brand;


use App\Models\CarModel;
use App\Services\Media\OptimizedUploadService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\WithFileUploads;

class BrandForm extends Component
{
    use WithFileUploads;

    public $brandId;
    public $brand;
    public $model;
    public $brandIcon;
    public $currentBrandIcon;
    public $additionalImage;
    protected OptimizedUploadService $imageUploader;

    public function boot(OptimizedUploadService $imageUploader): void
    {
        $this->imageUploader = $imageUploader;
    }

    public function mount($brandId = null)
    {
        if ($brandId) {
            $this->loadCarModelData($brandId);
        }


    }

    private function loadCarModelData($brandId)
    {
        $carModel = CarModel::findOrFail($brandId);

        $this->brandId = $brandId;
        $this->brand = $carModel->brand;
        $this->model = $carModel->model;
        $this->currentBrandIcon = $carModel->brand_icon;
    }
    public function save()
    {
        $this->validate([
            'brand' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'brandIcon' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'additionalImage' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5048',

        ]);

        $carModel = $this->brandId ? CarModel::findOrFail($this->brandId) : new CarModel();

        $carModel->brand = $this->brand;
        $carModel->model = $this->model;



        if ($this->brandIcon) {
            if ($carModel->brand_icon && Storage::disk('myimage')->exists($carModel->brand_icon)) {
                Storage::disk('myimage')->delete($carModel->brand_icon);
            }

            $path = $this->imageUploader->store(
                $this->brandIcon,
                'brand-icons/' . Str::slug($this->brand . '-' . $this->model) . '-' . time() . '.webp',
                'myimage',
                ['quality' => 50, 'max_width' => 512, 'max_height' => 512]
            );

            $carModel->brand_icon = $path;
        }
        $carModel->save();

        // Handle image upload
        if ($this->additionalImage) {
            $imageModel = $carModel->image;
            if ($imageModel && $imageModel->file_name) {
                Storage::disk('car_pics')->delete($imageModel->file_name);
            }

            $safeName = Str::slug($carModel->fullname() ?? ($this->brand . '-' . $this->model)) . '-' . time() . '.webp';
            $storedPath = $this->imageUploader->store(
                $this->additionalImage,
                $safeName,
                'car_pics',
                ['quality' => 55, 'max_width' => 1920, 'max_height' => 1080]
            );

            $fileName = basename($storedPath);

            if (! $imageModel) {
                $carModel->image()->create([
                    'file_path' => 'assets/car-pics/',
                    'file_name' => $fileName,
                ]);
            } else {
                $imageModel->update([
                    'file_name' => $fileName,
                ]);
            }
        }


        session()->flash('success', $this->brandId
            ? 'The car model has been successfully updated!'
            : 'A new car model has been successfully added!');

        // return redirect()->route('brand.add');
    }


    public function render()
    {
        $additionalImages = $this->brandId
            ? CarModel::findOrFail($this->brandId)->image
            : null;
        return view(
            'livewire.pages.panel.expert.brand.brand-form',
            compact('additionalImages')
        );
    }
}
