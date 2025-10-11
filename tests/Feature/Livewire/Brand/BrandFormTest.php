<?php

namespace Tests\Feature\Livewire\Brand;

use App\Livewire\Pages\Panel\Expert\Brand\BrandForm;
use App\Models\CarModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class BrandFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_save_creates_new_car_model(): void
    {
        Storage::fake('myimage');
        Storage::fake('car_pics');

        $component = Mockery::mock(BrandForm::class)->makePartial();
        $component->shouldAllowMockingProtectedMethods();

        $component->brand = 'Tesla';
        $component->model = 'Model 3';
        $component->brandIcon = null;
        $component->additionalImage = null;

        $component->shouldReceive('validate')->once()->andReturn([
            'brand' => 'Tesla',
            'model' => 'Model 3',
            'brandIcon' => null,
            'additionalImage' => null,
        ]);

        $component->save();

        $carModel = CarModel::where('brand', 'Tesla')->where('model', 'Model 3')->first();
        $this->assertNotNull($carModel);
        $this->assertEquals('A new car model has been successfully added!', session('success'));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
