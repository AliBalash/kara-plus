<?php

namespace App\Livewire\Concerns;

use App\Services\Audit\AuditLogger;

trait LogsBusinessRead
{
    protected function auditBusinessRead(array $meta = []): void
    {
        if (! (bool) config('audit.capture.business_reads', true)) {
            return;
        }

        $routeName = request()->route()?->getName();
        if ($routeName !== null && ! in_array($routeName, config('audit.business_read_routes', []), true)) {
            return;
        }

        app(AuditLogger::class)->log('business_read', [
            'route_name' => $routeName,
            'meta' => $meta,
        ]);
    }
}
