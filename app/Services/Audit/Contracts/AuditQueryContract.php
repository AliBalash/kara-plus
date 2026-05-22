<?php

namespace App\Services\Audit\Contracts;

use Illuminate\Database\Eloquent\Builder;

interface AuditQueryContract
{
    public function query(array $filters = []): Builder;

    public function summary(array $filters = []): array;
}
