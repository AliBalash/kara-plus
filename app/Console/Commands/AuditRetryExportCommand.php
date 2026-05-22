<?php

namespace App\Console\Commands;

use App\Jobs\ExportAuditEventJob;
use App\Models\AuditEvent;
use Illuminate\Console\Command;

class AuditRetryExportCommand extends Command
{
    protected $signature = 'audit:retry-export {--limit=1000}';

    protected $description = 'Retry failed or pending audit exports';

    public function handle(): int
    {
        if (! (bool) config('audit.export.enabled', true)) {
            $this->warn('Audit export is disabled.');

            return self::SUCCESS;
        }

        $limit = max(1, (int) $this->option('limit'));

        $events = AuditEvent::query()
            ->whereIn('export_status', ['pending', 'failed'])
            ->orderBy('occurred_at')
            ->limit($limit)
            ->get(['id']);

        foreach ($events as $event) {
            ExportAuditEventJob::dispatch($event->id)->onQueue((string) config('audit.export.queue', 'default'));
        }

        $this->info("Queued {$events->count()} audit events for export retry.");

        return self::SUCCESS;
    }
}
