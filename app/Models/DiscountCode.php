<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiscountCode extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'phone', 'code', 'discount_percentage' , 'contacted' , 'registery_at'];

    public static function generateCode(): string
    {
        return strtoupper(bin2hex(random_bytes(4))); // 8-character unique code
    }
}
