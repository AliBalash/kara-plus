<?php

namespace App\Services\Audit\Contracts;

use App\Models\AuditEvent;

interface AuditExportContract
{
    public function export(AuditEvent $event): void;

    public function ensureIlmAndTemplate(): void;
}
