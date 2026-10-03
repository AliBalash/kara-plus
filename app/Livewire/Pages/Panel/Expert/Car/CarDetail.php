<?php

namespace App\Livewire\Pages\Panel\Expert\Car;

use App\Livewire\Concerns\LogsBusinessRead;
use App\Models\Car;
use Livewire\Component;

class CarDetail extends Component
{
    use LogsBusinessRead;

    public int $carId;

    public function mount(int $carId): void
    {
        Car::findOrFail($carId);
        $this->carId = $carId;

        $this->auditBusinessRead([
            'car_id' => $carId,
        ]);
    }

    public function render()
    {
        $relations = ['carModel.image', 'image', 'options', 'latestInsurance', 'currentContract.customer'];

        if (Car::supportsScheduledUnavailabilityPeriods()) {
            $relations[] = 'unavailabilityPeriods';
        }

        $car = Car::with($relations)->findOrFail($this->carId);

        return view('livewire.pages.panel.expert.car.car-detail', compact('car'));
    }
}
