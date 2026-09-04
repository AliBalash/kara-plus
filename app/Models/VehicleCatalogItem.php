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
        return self::normalise($car->carModel?->brand) === self::normalise($this->match_brand)
            && self::normalise($car->carModel?->model) === self::normalise($this->match_model);
    }

    /**
     * The CRM is not consistent about casing and surrounding whitespace. Keep
     * the catalogue's family identity in one place so public and admin views
     * cannot drift from each other.
     */
    public static function normalise(?string $value): string
    {
        return mb_strtolower(trim((string) $value));
    }

    public function familyKey(): string
    {
        return self::familyKeyFor($this->match_brand, $this->match_model);
    }

    public static function familyKeyFor(?string $brand, ?string $model): string
    {
        return self::normalise($brand).'|'.self::normalise($model);
    }
}
