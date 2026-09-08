<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiInsight extends Model
{
    protected $fillable = ['scope', 'entity_type', 'entity_id', 'feature', 'prompt_version', 'input_hash', 'response_json', 'generated_at', 'expires_at'];

    protected function casts(): array
    {
        return ['response_json' => 'array', 'generated_at' => 'datetime', 'expires_at' => 'datetime'];
    }
}
