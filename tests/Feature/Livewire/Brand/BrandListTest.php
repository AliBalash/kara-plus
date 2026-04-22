<?php

namespace Tests\Feature\Livewire\Brand;

use App\Livewire\Pages\Panel\Expert\Brand\BrandList;
use App\Models\CarModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class BrandListTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('myimage');
        Storage::fake('car_pics');
    }

    public function test_delete_brand_removes_related_files_and_image_record(): void
    {
        $brand = CarModel::factory()->create([
            'brand_icon' => 'brand-icons/test-brand.webp',
        ]);

        $image = $brand->image()->create([
            'file_path' => 'car-pics/',
            'file_name' => 'brand-gallery.webp',
        ]);

        Storage::disk('myimage')->put('brand-icons/test-brand.webp', 'icon');
        Storage::disk('car_pics')->put('brand-gallery.webp', 'gallery-image');

        Livewire::test(BrandList::class)
            ->call('deleteBrand', $brand->id);

        $this->assertDatabaseMissing('car_models', ['id' => $brand->id]);
        $this->assertDatabaseMissing('images', ['id' => $image->id]);
        Storage::disk('myimage')->assertMissing('brand-icons/test-brand.webp');
        Storage::disk('car_pics')->assertMissing('brand-gallery.webp');
    }
}
