<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class CustomerDocument extends Model
{
    use HasFactory;

    protected static ?bool $supportsHotelFieldsCache = null;

    protected $fillable = [
        'customer_id',
        'contract_id',
        'visa',
        'passport',
        'license',
        'hotel_name',
        'hotel_address',
        'ticket',
    ];

    protected $casts = [
        'visa' => 'array',
        'passport' => 'array',
        'license' => 'array',
        'ticket' => 'array',
    ];

    public static function supportsHotelFields(): bool
    {
        if (static::$supportsHotelFieldsCache !== null) {
            return static::$supportsHotelFieldsCache;
        }

        $instance = new static();
        $table = $instance->getTable();

        static::$supportsHotelFieldsCache = Schema::hasTable($table)
            && Schema::hasColumn($table, 'hotel_name')
            && Schema::hasColumn($table, 'hotel_address');

        return static::$supportsHotelFieldsCache;
    }

    public function setAttribute($key, $value)
    {
        if (in_array($key, ['hotel_name', 'hotel_address'], true) && ! static::supportsHotelFields()) {
            return $this;
        }

        return parent::setAttribute($key, $value);
    }

    // Relationship with Customer model
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    // Relationship with Contract model
    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }
}
