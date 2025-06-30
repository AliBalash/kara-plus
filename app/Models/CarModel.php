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
        'engine_capacity',
        'fuel_type',
        'gearbox_type',
        'seating_capacity',
    ];

    /**
     * تبدیل‌های مربوط به نوع داده‌ها.
     *
     * @var array
     */
    protected $casts = [
        'engine_capacity' => 'float',
        'seating_capacity' => 'integer',
    ];

    /**
     * متد برای بررسی نوع سوخت.
     *
     * @return bool
     */
    public function isElectric(): bool
    {
        return $this->fuel_type === 'electric';
    }

    /**
     * متد برای بررسی نوع گیربکس.
     *
     * @return bool
     */
    public function isAutomatic(): bool
    {
        return $this->gearbox_type === 'automatic';
    }

    /**
     * متد برای ترکیب برند و مدل ماشین.
     *
     * @return string
     */
    public function fullName(): string
    {
        return $this->brand . ' ' . $this->model;
    }

    /**
     * متد برای دریافت ماشین‌های با سوخت خاص.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $fuelType
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByFuelType($query, string $fuelType)
    {
        return $query->where('fuel_type', $fuelType);
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
