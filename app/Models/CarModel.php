<?php

namespace App\Models;

use App\Services\Media\OptimizedUploadService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CarModel extends Model
{
    use HasFactory;

    /**
     * ویژگی‌های قابل پر کردن (mass assignable).
     *
     * @var array
     */
    protected $fillable = [
        'brand',
        'model',
        'brand_icon',
        'is_featured'
    ];

    protected $casts = [
        'is_featured' => 'boolean',
    ];



    /*
     * متد برای ترکیب برند و مدل ماشین.
     *
     * @return string
     */
    public function fullName(): string
    {
        return $this->brand . ' ' . $this->model;
    }



    /**
     * رابطه با مدل Car (ماشین‌ها).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function cars()
    {
        return $this->hasMany(Car::class, 'car_model_id');
    }

    // رابطه چندشکلی برای تصاویر
    public function image()
    {
        return $this->morphOne(Image::class, 'imageable');
    }

    // دسترسی‌ برای آیکون برند
    public function getBrandIconUrlAttribute()
    {
        if (! $this->brand_icon) {
            return null;
        }

        $path = ltrim($this->brand_icon, '/');
        if (! Str::startsWith($path, 'brand-icons/')) {
            $path = 'brand-icons/' . ltrim($path, '/');
        }

        if (! Storage::disk('myimage')->exists($path)) {
            return null;
        }

        return asset('storage/' . $path);
    }

    public function updateBrandIcon(Request $request, $id)
    {
        $carModel = CarModel::findOrFail($id);

        if ($request->hasFile('brand_icon')) {
            $file = $request->file('brand_icon');

            if ($carModel->brand_icon && Storage::disk('myimage')->exists($carModel->brand_icon)) {
                Storage::disk('myimage')->delete($carModel->brand_icon);
            }

            $uploader = app(OptimizedUploadService::class);
            $slug = Str::slug(pathinfo($file->getClientOriginalName() ?? $carModel->fullName(), PATHINFO_FILENAME));
            $storedPath = $uploader->store(
                $file,
                'brand-icons/' . $slug . '-' . time() . '.webp',
                'myimage',
                ['quality' => 50, 'max_width' => 512, 'max_height' => 512]
            );

            $carModel->update(['brand_icon' => $storedPath]);
        }

        return response()->json(['message' => 'Brand icon updated successfully']);
    }

    public function addImages(Request $request, $id)
    {
        $carModel = CarModel::findOrFail($id);

        foreach ($request->file('images') as $file) {
            $uploader = app(OptimizedUploadService::class);
            $slug = Str::slug(pathinfo($file->getClientOriginalName() ?? $carModel->fullName(), PATHINFO_FILENAME));
            $storedPath = $uploader->store(
                $file,
                $slug . '-' . time() . '.webp',
                'car_pics',
                ['quality' => 55, 'max_width' => 1920, 'max_height' => 1080, 'optimize' => false]
            );

            $carModel->images()->create([
                'file_path' => 'assets/car-pics/',
                'file_name' => basename($storedPath),
            ]);
        }

        return response()->json(['message' => 'Images added successfully']);
    }
}
