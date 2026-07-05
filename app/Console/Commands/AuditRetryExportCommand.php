<?php

namespace App\Console\Commands;

use App\Jobs\ExportAuditEventJob;
use App\Models\AuditEvent;
use App\Support\Audit\AuditExportFailure;
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
        $maxRetryAttempts = max(1, (int) config('audit.export.max_retry_attempts', 8));
        $retryCooldownMinutes = max(0, (int) config('audit.export.retry_cooldown_minutes', 30));
        $cooldownCutoff = now()->subMinutes($retryCooldownMinutes);

        $candidates = AuditEvent::query()
            ->whereIn('export_status', ['pending', 'failed'])
            ->orderBy('occurred_at')
            ->limit(max($limit * 10, 500))
            ->get(['id', 'export_status', 'export_attempts', 'last_export_attempt_at', 'export_last_error']);

        $events = $candidates
            ->filter(function (AuditEvent $event) use ($cooldownCutoff, $maxRetryAttempts) {
                if ((int) $event->export_attempts >= $maxRetryAttempts) {
                    return false;
                }

                if ($event->last_export_attempt_at !== null && $event->last_export_attempt_at->gt($cooldownCutoff)) {
                    return false;
                }

                if ($event->export_status === 'pending') {
                    return true;
                }

                return AuditExportFailure::isRetryableMessage($event->export_last_error);
            })
            ->take($limit);

        foreach ($events as $event) {
            ExportAuditEventJob::dispatch($event->id)->onQueue((string) config('audit.export.queue', 'default'));
        }

        $this->info("Queued {$events->count()} audit events for export retry.");

        return self::SUCCESS;
    }
}
