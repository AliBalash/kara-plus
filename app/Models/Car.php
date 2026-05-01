<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Car extends Model
{
    use HasFactory;

    private const RENTABLE_STATUSES = ['available', 'pre_reserved'];

    /**
     * @var array<string, array<int, string>>
     */
    private static array $imageDirectoryCache = [];

    /**
     * ویژگی‌های قابل پر کردن (mass assignable).
     *
     * @var array
     */
    protected $fillable = [
        'car_model_id',
        'plate_number',
        'status',
        'availability',
        'mileage',
        'price_per_day_short',
        'price_per_day_mid',
        'price_per_day_long',
        'ldw_price_short',
        'ldw_price_mid',
        'ldw_price_long',
        'scdw_price_short',
        'scdw_price_mid',
        'scdw_price_long',
        'ldw_price',
        'scdw_price',
        'service_due_date',
        'damage_report',
        'manufacturing_year',
        'color',
        'notes',
        'chassis_number',
        'gps',
        'is_company_car',
        'ownership_type',
        'issue_date',
        'expiry_date',
        'passing_date',
        'passing_valid_for_days',
        'passing_status',
        'registration_valid_for_days',
        'registration_status',
    ];

    /**
     * تبدیل‌های مربوط به نوع داده‌ها.
     *
     * @var array
     */
    protected $casts = [
        'availability' => 'boolean',
        'mileage' => 'integer',
        'price_per_day_short' => 'decimal:2',
        'price_per_day_mid' => 'decimal:2',
        'price_per_day_long' => 'decimal:2',
        'ldw_price_short' => 'decimal:2',
        'ldw_price_mid' => 'decimal:2',
        'ldw_price_long' => 'decimal:2',
        'scdw_price_short' => 'decimal:2',
        'scdw_price_mid' => 'decimal:2',
        'scdw_price_long' => 'decimal:2',
        'price_per_day' => 'decimal:2',
        'insurance_expiry_date' => 'date',
        'service_due_date' => 'date',
        'manufacturing_year' => 'integer',
        'is_company_car' => 'boolean',
    ];

    private const OWNERSHIP_OPTIONS = [
        'company' => ['label' => 'Our Fleet', 'badge' => 'bg-label-primary'],
        'golden_key' => ['label' => 'Golden Key', 'badge' => 'bg-label-info'],
        'liverpool' => ['label' => 'Liverpool', 'badge' => 'bg-label-success'],
        'safe_drive' => ['label' => 'Safe Drive', 'badge' => 'bg-label-secondary'],
        'other' => ['label' => 'Other Fleet', 'badge' => 'bg-label-warning'],
    ];

    /**
     * متد برای بررسی وضعیت خودرو.
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        return in_array($this->status, self::RENTABLE_STATUSES, true) && $this->availability;
    }

    public function operationalStatus(): string
    {
        if ($this->status === 'sold') {
            return 'sold';
        }

        if ($this->status === 'under_maintenance') {
            return 'under_maintenance';
        }

        if ($this->status === 'reserved') {
            return 'reserved';
        }

        if (! $this->availability) {
            return 'unavailable';
        }

        if ($this->status === 'pre_reserved') {
            return 'pre_reserved';
        }

        if ($this->status === 'available') {
            return 'available';
        }

        return $this->status ?: 'unavailable';
    }

    public function operationalStatusLabel(): string
    {
        return match ($this->operationalStatus()) {
            'available' => 'Available',
            'pre_reserved' => 'Upcoming booking',
            'reserved' => 'Active booking',
            'under_maintenance' => 'Under maintenance',
            'sold' => 'Sold',
            'unavailable' => 'Unavailable',
            default => ucfirst((string) $this->operationalStatus()),
        };
    }

    public function operationalStatusBadgeClass(): string
    {
        return match ($this->operationalStatus()) {
            'available' => 'bg-success',
            'pre_reserved' => 'bg-info',
            'reserved' => 'bg-warning',
            'under_maintenance' => 'bg-danger',
            'sold' => 'bg-dark',
            'unavailable' => 'bg-secondary',
            default => 'bg-secondary',
        };
    }

    public function scopeByOperationalStatus($query, string $status)
    {
        return match ($status) {
            'available' => $query->where('status', 'available')->where('availability', true),
            'pre_reserved' => $query->where('status', 'pre_reserved')->where('availability', true),
            'reserved' => $query->where('status', 'reserved'),
            'under_maintenance' => $query->where('status', 'under_maintenance'),
            'sold' => $query->where('status', 'sold'),
            'unavailable' => $query->where(function ($builder) {
                $builder->where('availability', false)
                    ->whereIn('status', self::RENTABLE_STATUSES);
            }),
            default => $query->where('status', $status),
        };
    }

    /**
     * متد برای خودروهایی که نیاز به سرویس دارند.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNeedsService($query)
    {
        return $query->whereDate('service_due_date', '<=', now());
    }

    /**
     * متد برای بررسی وضعیت بیمه خودرو.
     *
     * @return bool
     */
    public function isInsuranceExpired(): bool
    {
        return $this->insurance_expiry_date && $this->insurance_expiry_date->isPast();
    }

    /**
     * متد برای نام کامل خودرو (مدل و پلاک).
     *
     * @return string
     */
    public function modelName(): string
    {
        return optional($this->carModel)->fullName() ?? 'Vehicle';
    }

    public function nameWithPlate(): string
    {
        $name = $this->modelName();

        return $this->plate_number ? sprintf('%s (%s)', $name, $this->plate_number) : $name;
    }

    public function fullName(): string
    {
        return $this->nameWithPlate();
    }

    public function ownershipLabel(): string
    {
        $ownershipType = $this->ownershipType();

        return static::OWNERSHIP_OPTIONS[$ownershipType]['label'] ?? 'Other Fleet';
    }

    public function ownershipBadgeClass(): string
    {
        $ownershipType = $this->ownershipType();

        return static::OWNERSHIP_OPTIONS[$ownershipType]['badge'] ?? 'bg-label-secondary';
    }

    public function ownershipType(): string
    {
        if ($this->ownership_type) {
            return $this->ownership_type;
        }

        return $this->is_company_car ? 'company' : 'other';
    }

    /**
     * رابطه با مدل CarModel.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function carModel()
    {
        return $this->belongsTo(CarModel::class, 'car_model_id');
    }

    public function image()
    {
        return $this->morphOne(Image::class, 'imageable');
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }

    public function scopeWithoutActiveReservations($query)
    {
        $now = Carbon::now();
        $statuses = static::reservingStatuses();

        return $query->whereDoesntHave('contracts', function ($builder) use ($now, $statuses) {
            $builder->whereIn('current_status', $statuses)
                ->whereNotNull('pickup_date')
                ->where('pickup_date', '<=', $now)
                ->where(function ($timeQuery) use ($now) {
                    $timeQuery->whereNull('return_date')->orWhere('return_date', '>=', $now);
                });
        });
    }

    public function upcomingReservation()
    {
        $statuses = static::reservingStatuses();

        return $this->hasOne(Contract::class)
            ->whereIn('current_status', $statuses)
            ->whereNotNull('pickup_date')
            ->where('pickup_date', '>', Carbon::now())
            ->orderBy('pickup_date');
    }

    public static function reservingStatuses(): array
    {
        return [
            'pending',
            'assigned',
            'under_review',
            'reserved',
            'delivery',
            'inspection',
            'agreement_inspection',
            'awaiting_return',
        ];
    }

    public function hasReservingContracts(?int $exceptContractId = null): bool
    {
        $query = $this->contracts()
            ->whereIn('current_status', static::reservingStatuses());

        if ($exceptContractId) {
            $query->where('id', '!=', $exceptContractId);
        }

        return $query->exists();
    }

    /**
     * متد برای دریافت خودروهای با وضعیت خاص.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    // تعریف رابطه با بیمه (یک به یک)
    public function insurance()
    {
        return $this->hasOne(Insurance::class); // یک خودرو یک بیمه دارد
    }

    public function currentContract()
    {
        return $this->hasOne(\App\Models\Contract::class)
            ->whereIn('current_status', static::reservingStatuses())
            ->orderBy('pickup_date');
    }

    public function options()
    {
        return $this->hasMany(CarOption::class);
    }

    public function primaryImageUrl(): string
    {
        if ($this->image) {
            $url = $this->resolveImageUrl($this->image);
            if ($url !== null) {
                return $url;
            }
        }

        if ($this->carModel?->image) {
            $url = $this->resolveImageUrl($this->carModel->image);
            if ($url !== null) {
                return $url;
            }
        }

        return $this->publicAssetUrl('assets/car-pics/car test.webp');
    }

    private function resolveImageUrl(Image $image): ?string
    {
        $fileName = trim((string) $image->file_name);
        if ($fileName === '') {
            return null;
        }

        $path = trim((string) $image->file_path);
        $path = ltrim($path, '/');

        if ($path === '') {
            return null;
        }

        if (! str_starts_with($path, 'assets/')) {
            $path = 'assets/' . $path;
        }

        $path = rtrim($path, '/');
        $relativePath = $path . '/' . $fileName;

        if (! is_file(public_path($relativePath))) {
            $matchedPath = $this->resolveClosestImagePath($path, $fileName);
            if ($matchedPath === null) {
                return null;
            }

            return $this->publicAssetUrl($matchedPath);
        }

        return $this->publicAssetUrl($relativePath);
    }

    private function resolveClosestImagePath(string $basePath, string $requestedFileName): ?string
    {
        $candidateFiles = $this->imageFilesInDirectory($basePath);
        if ($candidateFiles === []) {
            return null;
        }

        $targetKey = $this->normalizeImageKey(pathinfo($requestedFileName, PATHINFO_FILENAME));
        if ($targetKey === '') {
            return null;
        }

        $bestMatch = null;
        $bestScore = PHP_INT_MAX;

        foreach ($candidateFiles as $candidateFileName) {
            $candidateKey = $this->normalizeImageKey(pathinfo($candidateFileName, PATHINFO_FILENAME));
            if ($candidateKey === '') {
                continue;
            }

            if ($candidateKey === $targetKey) {
                return $basePath . '/' . $candidateFileName;
            }

            if (!str_contains($candidateKey, $targetKey) && !str_contains($targetKey, $candidateKey)) {
                continue;
            }

            $distance = levenshtein($targetKey, $candidateKey);
            $lengthPenalty = abs(strlen($candidateKey) - strlen($targetKey));
            $score = $distance + $lengthPenalty;

            if ($score < $bestScore) {
                $bestScore = $score;
                $bestMatch = $candidateFileName;
            }
        }

        if ($bestMatch === null) {
            return null;
        }

        return $basePath . '/' . $bestMatch;
    }

    /**
     * @return array<int, string>
     */
    private function imageFilesInDirectory(string $relativeDirectory): array
    {
        $normalizedDirectory = trim($relativeDirectory, '/');
        if (
            isset(self::$imageDirectoryCache[$normalizedDirectory])
            && self::$imageDirectoryCache[$normalizedDirectory] !== []
        ) {
            return self::$imageDirectoryCache[$normalizedDirectory];
        }

        $absoluteDirectory = public_path($normalizedDirectory);
        if (!is_dir($absoluteDirectory)) {
            self::$imageDirectoryCache[$normalizedDirectory] = [];

            return [];
        }

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'];
        $files = [];
        clearstatcache(true, $absoluteDirectory);

        foreach (scandir($absoluteDirectory) ?: [] as $entry) {
            $entry = trim((string) $entry);
            if ($entry === '' || $entry === '.' || $entry === '..') {
                continue;
            }

            $absolutePath = $absoluteDirectory . DIRECTORY_SEPARATOR . $entry;
            if (!is_file($absolutePath)) {
                continue;
            }

            $extension = strtolower((string) pathinfo($entry, PATHINFO_EXTENSION));
            if (!in_array($extension, $allowedExtensions, true)) {
                continue;
            }

            $files[] = $entry;
        }

        self::$imageDirectoryCache[$normalizedDirectory] = $files;

        return $files;
    }

    private function normalizeImageKey(string $value): string
    {
        return (string) preg_replace('/[^a-z0-9]+/i', '', strtolower(trim($value)));
    }

    private function publicAssetUrl(string $relativePath): string
    {
        $segments = array_values(array_filter(explode('/', ltrim($relativePath, '/')), static fn (string $segment): bool => $segment !== ''));
        $encodedPath = implode('/', array_map(static fn (string $segment): string => rawurlencode($segment), $segments));

        return asset($encodedPath);
    }
}
