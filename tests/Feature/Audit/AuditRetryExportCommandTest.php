<?php

namespace Tests\Feature\Audit;

use App\Jobs\ExportAuditEventJob;
use App\Models\AuditEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuditRetryExportCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_retry_export_command_only_requeues_retryable_items_within_limits(): void
    {
        Queue::fake();

        config()->set('audit.export.enabled', true);
        config()->set('audit.export.max_retry_attempts', 15);
        config()->set('audit.export.retry_cooldown_minutes', 30);

        $pendingEligible = AuditEvent::create([
            'event_uuid' => (string) Str::uuid(),
            'occurred_at' => now()->subMinutes(40),
            'action' => 'http_request',
            'export_status' => 'pending',
            'export_attempts' => 0,
            'last_export_attempt_at' => null,
        ]);

        $failedRetryable = AuditEvent::create([
            'event_uuid' => (string) Str::uuid(),
            'occurred_at' => now()->subMinutes(35),
            'action' => 'http_request',
            'export_status' => 'failed',
            'export_attempts' => 3,
            'last_export_attempt_at' => now()->subMinutes(31),
            'export_last_error' => 'Elasticsearch export failed: 429 cluster_block_exception',
        ]);

        AuditEvent::create([
            'event_uuid' => (string) Str::uuid(),
            'occurred_at' => now()->subMinutes(34),
            'action' => 'http_request',
            'export_status' => 'failed',
            'export_attempts' => 3,
            'last_export_attempt_at' => now()->subMinutes(31),
            'export_last_error' => 'Elasticsearch export failed: 400 document_parsing_exception',
        ]);

        AuditEvent::create([
            'event_uuid' => (string) Str::uuid(),
            'occurred_at' => now()->subMinutes(33),
            'action' => 'http_request',
            'export_status' => 'failed',
            'export_attempts' => 15,
            'last_export_attempt_at' => now()->subMinutes(31),
            'export_last_error' => 'Elasticsearch export failed: 429 cluster_block_exception',
        ]);

        AuditEvent::create([
            'event_uuid' => (string) Str::uuid(),
            'occurred_at' => now()->subMinutes(5),
            'action' => 'http_request',
            'export_status' => 'pending',
            'export_attempts' => 1,
            'last_export_attempt_at' => now()->subMinutes(5),
        ]);

        $this->artisan('audit:retry-export --limit=100')
            ->assertSuccessful();

        Queue::assertPushed(ExportAuditEventJob::class, function (ExportAuditEventJob $job) use ($pendingEligible, $failedRetryable) {
            return in_array($job->auditEventId, [$pendingEligible->id, $failedRetryable->id], true);
        });

        Queue::assertPushed(ExportAuditEventJob::class, 2);
    }
}
