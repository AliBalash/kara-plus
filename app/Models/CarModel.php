<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

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
        return $this->brand_icon
            ? asset('storage/brand_icons/' . $this->brand_icon)
            : null;
    }

    public function updateBrandIcon(Request $request, $id)
    {
        $carModel = CarModel::findOrFail($id);

        if ($request->hasFile('brand_icon')) {
            $file = $request->file('brand_icon');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $file->storeAs('public/brand_icons', $fileName);

            $carModel->update(['brand_icon' => $fileName]);
        }

        return response()->json(['message' => 'Brand icon updated successfully']);
    }

    public function addImages(Request $request, $id)
    {
        $carModel = CarModel::findOrFail($id);

        foreach ($request->file('images') as $file) {
            $fileName = time() . '_' . $file->getClientOriginalName();
            $file->storeAs('public/images', $fileName);

            $carModel->images()->create([
                'file_path' => 'storage/images/',
                'file_name' => $fileName,
            ]);
        }

        return response()->json(['message' => 'Images added successfully']);
    }
}
