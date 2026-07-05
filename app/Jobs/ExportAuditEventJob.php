<?php

namespace App\Jobs;

use App\Models\AuditEvent;
use App\Services\Audit\ElasticsearchAuditExporter;
use App\Support\Audit\AuditExportFailure;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExportAuditEventJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 5;

    public function __construct(public readonly int $auditEventId)
    {
    }

    public function handle(ElasticsearchAuditExporter $exporter): void
    {
        $event = AuditEvent::query()->find($this->auditEventId);
        if (! $event) {
            Log::warning('Audit event export skipped because event was not found.', [
                'audit_event_id' => $this->auditEventId,
            ]);
            return;
        }

        if ($event->export_status === 'exported') {
            Log::info('Audit event export skipped because event is already exported.', [
                'audit_event_id' => $event->id,
                'event_uuid' => $event->event_uuid,
            ]);
            return;
        }

        $event->forceFill([
            'export_attempts' => (int) $event->export_attempts + 1,
            'last_export_attempt_at' => now(),
        ])->save();

        try {
            $exporter->export($event);

            $event->forceFill([
                'export_status' => 'exported',
                'exported_at' => now(),
                'export_last_error' => null,
                'elastic_document_id' => $event->event_uuid,
            ])->save();
        } catch (\Throwable $exception) {
            $summary = AuditExportFailure::summarize($exception);

            Log::error('Audit event export failed.', [
                'audit_event_id' => $event->id,
                'event_uuid' => $event->event_uuid,
                'attempts' => (int) $event->export_attempts,
                'message' => $summary,
            ]);

            $event->forceFill([
                'export_status' => 'failed',
                'export_last_error' => $summary,
            ])->save();

            if (! AuditExportFailure::isRetryableThrowable($exception)) {
                return;
            }

            throw $exception;
        }
    }
}
