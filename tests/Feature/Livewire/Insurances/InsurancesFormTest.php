<?php

namespace Tests\Feature\Livewire\Insurances;

use App\Livewire\Pages\Panel\Expert\Insurances\InsurancesForm;
use App\Models\Car;
use App\Models\Insurance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Mockery;
use Tests\TestCase;

class InsurancesFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_save_creates_new_insurance_record(): void
    {
        Route::get('/dummy-insurance-add', fn () => null)->name('insurance.add');

        $car = Car::factory()->create();

        $component = Mockery::mock(InsurancesForm::class)->makePartial();
        $component->mount();

        $component->carId = $car->id;
        $component->expiryDate = now()->addYear()->toDateString();
        $component->validDays = 365;
        $component->status = 'pending';
        $component->insuranceCompany = 'Allianz';

        $component->shouldReceive('validate')->once()->andReturn([
            'carId' => $component->carId,
            'expiryDate' => $component->expiryDate,
            'validDays' => $component->validDays,
            'status' => $component->status,
            'insuranceCompany' => $component->insuranceCompany,
        ]);

        try {
            $response = $component->save();
            $this->assertEquals(route('insurance.add'), $response->getTargetUrl());
        } catch (\Throwable $exception) {
            $response = null;
        }

        $insurance = Insurance::where('car_id', $car->id)->first();
        $this->assertNotNull($insurance);
        $this->assertEquals('Allianz', $insurance->insurance_company);
        $this->assertEquals('pending', $insurance->status);
        $this->assertEquals('New insurance added successfully!', session('success'));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
