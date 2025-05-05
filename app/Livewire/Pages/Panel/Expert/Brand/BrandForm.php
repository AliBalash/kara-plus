<?php

namespace App\Livewire\Pages\Panel\Expert\Brand;


use App\Models\CarModel;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\WithFileUploads;

class BrandForm extends Component
{
    use WithFileUploads;

    public $brandId;
    public $brand;
    public $model;
    public $engineCapacity;
    public $fuelType;
    public $gearboxType;
    public $seatingCapacity;
    public $brandIcon;
    public $additionalImage;
    public $currentBrandIcon;

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
        $this->engineCapacity = $carModel->engine_capacity;
        $this->fuelType = $carModel->fuel_type;
        $this->gearboxType = $carModel->gearbox_type;
        $this->seatingCapacity = $carModel->seating_capacity;
        $this->currentBrandIcon = $carModel->brand_icon;
    }
    public function save()
    {
        $this->validate([
            'brand' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'engineCapacity' => 'required|numeric|min:0',
            'fuelType' => 'required|string',
            'gearboxType' => 'required|string',
            'seatingCapacity' => 'required|integer|min:1',
            'brandIcon' => 'nullable|image|mimes:jpg,jpeg,png|max:1024',
            'additionalImage' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $carModel = $this->brandId ? CarModel::findOrFail($this->brandId) : new CarModel();

        $carModel->brand = $this->brand;
        $carModel->model = $this->model;
        $carModel->engine_capacity = $this->engineCapacity;
        $carModel->fuel_type = $this->fuelType;
        $carModel->gearbox_type = $this->gearboxType;
        $carModel->seating_capacity = $this->seatingCapacity;



        // if ($this->brandIcon) {
        //     if ($carModel->brand_icon && Storage::exists('public/' . $carModel->brand_icon)) {
        //         Storage::delete('public/' . $carModel->brand_icon);
        //     }
        //     $path = $this->brandIcon->store('brand-icons', 'myimage');
        //     $carModel->brand_icon = $path;
        // }

        // if ($this->additionalImage) {
        //     $additionalImagePath = $this->additionalImage->store('additional-images', 'myimage');
        //     // Handle saving additional image
        // }

        // $carModel->save();

        session()->flash('success', $this->brandId 
        ? 'The car model has been successfully updated!' 
        : 'A new car model has been successfully added!');
    
        // return redirect()->route('brand.add');
    }


    public function render()
    {
        $additionalImages = $this->brandId
            ? CarModel::findOrFail($this->brandId)->images
            : null;

        return view(
            'livewire.pages.panel.expert.brand.brand-form',
            compact('additionalImages')
        );
    }
}
