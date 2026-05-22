<?php

namespace App\Console\Commands;

use App\Models\AuditEvent;
use Illuminate\Console\Command;

class AuditHealthCommand extends Command
{
    protected $signature = 'audit:health';

    protected $description = 'Show audit pipeline health metrics';

    public function handle(): int
    {
        $pending = AuditEvent::query()->where('export_status', 'pending')->count();
        $failed = AuditEvent::query()->where('export_status', 'failed')->count();
        $exported = AuditEvent::query()->where('export_status', 'exported')->count();

        $oldestPending = AuditEvent::query()
            ->where('export_status', 'pending')
            ->orderBy('occurred_at')
            ->value('occurred_at');

        $this->table(
            ['Metric', 'Value'],
            [
                ['Pending exports', $pending],
                ['Failed exports', $failed],
                ['Exported', $exported],
                ['Oldest pending', $oldestPending ?: '-'],
            ]
        );

        return self::SUCCESS;
    }
}
