<?php

namespace App\Livewire\Pages\Panel\Expert\Brand;


use App\Models\CarModel;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\WithFileUploads;
use Illuminate\Support\Str;

class BrandForm extends Component
{
    use WithFileUploads;

    public $brandId;
    public $brand;
    public $model;
    public $brandIcon;
    public $currentBrandIcon;
    public $additionalImage;

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
            'brandIcon' => 'nullable|image|mimes:jpg,jpeg,png|max:1024',
            'additionalImage' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5048',

        ]);

        $carModel = $this->brandId ? CarModel::findOrFail($this->brandId) : new CarModel();

        $carModel->brand = $this->brand;
        $carModel->model = $this->model;



        if ($this->brandIcon) {
            if ($carModel->brand_icon && Storage::exists('public/' . $carModel->brand_icon)) {
                Storage::delete('public/' . $carModel->brand_icon);
            }
            $path = $this->brandIcon->store('brand-icons', 'myimage');
            $carModel->brand_icon = $path;
        }
        $carModel->save();

        // Handle image upload
        if ($this->additionalImage) {
            $imageModel = $carModel->image;
            // Delete old image if exists
            if ($imageModel && $imageModel->file_name) {
                Storage::disk('car_pics')->delete($imageModel->file_name);
            }

            $extension = $this->additionalImage->getClientOriginalExtension();
            $carName = $carModel->fullname();
            $safeName = Str::slug($carName) . '.' . $extension;

            Storage::disk('car_pics')->putFileAs('', $this->additionalImage, $safeName);

            // Update or create image
            if (!$imageModel) {
                $imageModel = $carModel->image()->create([
                    'file_path' => 'car-pics/',
                    'file_name' => $safeName,
                ]);
            } else {
                $imageModel->update([
                    'file_name' => $safeName
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
