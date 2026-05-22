<?php

namespace App\Livewire\Pages\Panel\Expert\Car;

use App\Livewire\Concerns\LogsBusinessRead;
use Livewire\Component;

class CarDetail extends Component
{
    use LogsBusinessRead;

    public function mount(int $carId): void
    {
        $this->auditBusinessRead([
            'car_id' => $carId,
        ]);
    }

    public function render()
    {
        return view('livewire.pages.panel.expert.car.car-detail');
    }
}
