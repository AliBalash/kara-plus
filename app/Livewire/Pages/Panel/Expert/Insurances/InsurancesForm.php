<?php

namespace App\Livewire\Pages\Panel\Expert\Insurances;

use App\Models\Car;
use App\Models\Insurance;
use App\Livewire\Concerns\InteractsWithToasts;
use Carbon\Carbon;
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
    public $passingDate;
    public $passingValidDays;
    public $passingStatus;

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
        $insurance = Insurance::with('car')->findOrFail($insuranceId);
        $car = $insurance->car;

        $this->insuranceId = $insurance->id;
        $this->carId = $car?->id;
        $this->car = $car;
        $this->expiryDate = $insurance->expiry_date
            ? Carbon::parse($insurance->expiry_date)->toDateString()
            : null;
        $this->validDays = $insurance->valid_days;
        $this->status = $insurance->status ?: 'done';
        $this->passingDate = $car?->passing_date
            ? Carbon::parse($car->passing_date)->toDateString()
            : null;
        $this->passingValidDays = $car?->passing_valid_for_days;
        $this->passingStatus = $car?->passing_status ?: 'done';
    }

    private function resetForm()
    {
        $this->insuranceId = null;
        $this->carId = null;
        $this->car = null;
        $this->expiryDate = null;
        $this->validDays = null;
        $this->status = 'done';
        $this->passingDate = null;
        $this->passingValidDays = null;
        $this->passingStatus = 'done';
    }

    public function updatedCarId($carId)
    {
        $this->car = Car::find($carId); // Load selected car data

        $this->passingDate = $this->car?->passing_date
            ? Carbon::parse($this->car->passing_date)->toDateString()
            : null;
        $this->passingValidDays = $this->car?->passing_valid_for_days;
        $this->passingStatus = $this->car?->passing_status ?: 'done';
    }

    public function save()
    {
        $this->validate([
            'carId' => 'required|integer|exists:cars,id',
            'expiryDate' => 'required|date',
            'validDays' => 'nullable|integer|min:0',
            'status' => 'required|string|in:done,pending,failed',
            'passingDate' => 'nullable|date',
            'passingValidDays' => 'nullable|integer|min:0',
            'passingStatus' => 'nullable|string|in:done,pending,failed',
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
        $insurance->insurance_company = null;

        $insurance->save();

        $car = Car::findOrFail($this->carId);
        $car->passing_date = $this->passingDate ?: null;
        $car->passing_valid_for_days = $this->passingValidDays === '' ? null : $this->passingValidDays;
        $car->passing_status = $this->passingStatus ?: 'done';
        $car->save();

        $this->toast('success', $this->insuranceId
            ? 'Insurance updated successfully!'
            : 'New insurance added successfully!', true);

        return redirect()->route('insurance.form', $insurance->id);
    }

    public function render()
    {
        return view('livewire.pages.panel.expert.insurances.insurances-form', [
            'cars' => $this->cars,
        ]);
    }
}
