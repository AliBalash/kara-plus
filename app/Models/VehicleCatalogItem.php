<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class VehicleCatalogItem extends Model
{
    protected $fillable = [
        'code',
        'website_slug',
        'display_name',
        'brand',
        'model',
        'match_brand',
        'match_model',
        'manufacturing_year',
        'trim',
        'is_active',
    ];

    protected $casts = [
        'manufacturing_year' => 'integer',
        'is_active' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function appliesToCar(Car $car): bool
    {
        return $car->manufacturing_year === $this->manufacturing_year
            && $this->matchesCarModel($car);
    }

    public function matchesCarModel(Car $car): bool
    {
        return $this->normalise($car->carModel?->brand) === $this->normalise($this->match_brand)
            && $this->normalise($car->carModel?->model) === $this->normalise($this->match_model);
    }

    private function normalise(?string $value): string
    {
        return mb_strtolower(trim((string) $value));
    }
}
