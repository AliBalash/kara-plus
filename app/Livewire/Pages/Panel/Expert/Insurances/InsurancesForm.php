<?php

namespace App\Livewire\Pages\Panel\Expert\Insurances;

use App\Models\Car;
use App\Models\Insurance;
use App\Livewire\Concerns\InteractsWithToasts;
use Livewire\Component;

class InsurancesForm extends Component
{
    use InteractsWithToasts;
    public $insuranceId;
    public $carId;
    public $car;
    public $expiryDate;
    public $validDays;
    public $status;
    public $insuranceCompany;

    public $cars; // For storing car list

    public function mount($insuranceId = null)
    {
        $this->cars = Car::all(); // Load all cars

        if ($insuranceId) {
            $this->loadInsuranceData($insuranceId);
        } else {
            $this->resetForm();
        }
    }

    private function loadInsuranceData($insuranceId)
    {
        $insurance = Insurance::findOrFail($insuranceId);

        $this->insuranceId = $insurance->id;
        $this->carId = $insurance->car->id;
        $this->car = $insurance->car;
        $this->expiryDate = $insurance->expiry_date;
        $this->validDays = $insurance->valid_days;
        $this->status = $insurance->status;
        $this->insuranceCompany = $insurance->insurance_company;
    }

    private function resetForm()
    {
        $this->insuranceId = null;
        $this->carId = null;
        $this->car = null;
        $this->expiryDate = null;
        $this->validDays = null;
        $this->status = null;
        $this->insuranceCompany = null;
    }

    public function updatedCarId($carId)
    {
        $this->car = Car::find($carId); // Load selected car data
    }

    public function save()
    {
        $this->validate([
            'carId' => 'required|integer|exists:cars,id',
            'expiryDate' => 'required|date',
            'validDays' => 'required|integer|min:0',
            'status' => 'required|string|in:pending,active,done',
            'insuranceCompany' => 'required|string|max:255',
        ]);

        // Check if the car already has insurance
        if (!$this->insuranceId && Insurance::where('car_id', $this->carId)->exists()) {
            $this->toast('error', 'This car already has an insurance policy.', false);
            return;
        }
        
        $insurance = $this->insuranceId
            ? Insurance::findOrFail($this->insuranceId)
            : new Insurance();

        $insurance->car_id = $this->carId;
        $insurance->expiry_date = $this->expiryDate;
        $insurance->valid_days = $this->validDays;
        $insurance->status = $this->status;
        $insurance->insurance_company = $this->insuranceCompany;

        $insurance->save();

        $this->toast('success', $this->insuranceId
            ? 'Insurance updated successfully!'
            : 'New insurance added successfully!', true);

        return redirect()->route('insurance.add');
    }

    public function render()
    {
        return view('livewire.pages.panel.expert.insurances.insurances-form', [
            'cars' => $this->cars,
        ]);
    }
}
