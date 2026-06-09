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
        Route::get('/dummy-insurance-form/{insuranceId?}', fn () => null)->name('insurance.form');

        $car = Car::factory()->create();

        $component = Mockery::mock(InsurancesForm::class)->makePartial();
        $component->mount();

        $component->carId = $car->id;
        $component->expiryDate = now()->addYear()->toDateString();
        $component->validDays = 365;
        $component->status = 'done';
        $component->passingDate = now()->subMonth()->toDateString();
        $component->passingValidDays = 180;
        $component->passingStatus = 'pending';

        $component->shouldReceive('validate')->once()->andReturn([
            'carId' => $component->carId,
            'expiryDate' => $component->expiryDate,
            'validDays' => $component->validDays,
            'status' => $component->status,
            'passingDate' => $component->passingDate,
            'passingValidDays' => $component->passingValidDays,
            'passingStatus' => $component->passingStatus,
        ]);

        try {
            $response = $component->save();
        } catch (\Throwable $exception) {
            $response = null;
        }

        $insurance = Insurance::where('car_id', $car->id)->first();
        $this->assertNotNull($insurance);
        $this->assertNull($insurance->insurance_company);
        $this->assertEquals('done', $insurance->status);
        $this->assertEquals(route('insurance.form', $insurance->id), $response->getTargetUrl());

        $car->refresh();
        $this->assertEquals($component->passingDate, $car->passing_date->toDateString());
        $this->assertEquals(180, $car->passing_valid_for_days);
        $this->assertEquals('pending', $car->passing_status);
        $this->assertEquals('New insurance added successfully!', session('success'));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
