<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiRun extends Model
{
    protected $fillable = ['request_id', 'user_id', 'feature', 'entity_type', 'entity_id', 'prompt_version', 'input_hash', 'provider', 'model', 'strategy', 'status', 'latency_ms', 'input_tokens', 'output_tokens', 'cached', 'error_class'];

    protected function casts(): array
    {
        return ['cached' => 'boolean'];
    }
}
