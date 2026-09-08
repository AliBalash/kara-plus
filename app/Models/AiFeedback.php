<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiFeedback extends Model
{
    protected $fillable = [
        'ai_insight_id',
        'user_id',
        'feature',
        'entity_type',
        'entity_id',
        'helpful',
        'reason',
    ];

    protected function casts(): array
    {
        return ['helpful' => 'boolean'];
    }
}
