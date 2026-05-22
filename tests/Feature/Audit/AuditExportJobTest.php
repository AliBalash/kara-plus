<?php

namespace Tests\Feature\Audit;

use App\Jobs\ExportAuditEventJob;
use App\Models\AuditEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AuditExportJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_job_is_idempotent_and_updates_status(): void
    {
        config()->set('audit.elasticsearch.enabled', true);
        config()->set('audit.elasticsearch.base_url', 'http://elasticsearch:9200');

        Http::fake([
            '*' => Http::response(['result' => 'created'], 200),
        ]);

        $event = AuditEvent::create([
            'event_uuid' => (string) \Illuminate\Support\Str::uuid(),
            'occurred_at' => now(),
            'action' => 'http_request',
            'export_status' => 'pending',
        ]);

        $job = new ExportAuditEventJob($event->id);
        $job->handle(app(\App\Services\Audit\ElasticsearchAuditExporter::class));
        $job->handle(app(\App\Services\Audit\ElasticsearchAuditExporter::class));

        $event->refresh();

        $this->assertSame('exported', $event->export_status);
        $this->assertNotNull($event->exported_at);
        $this->assertSame($event->event_uuid, $event->elastic_document_id);
        Http::assertSentCount(1);
    }

    public function test_export_payload_contains_normalized_contract_refs(): void
    {
        config()->set('audit.elasticsearch.enabled', true);
        config()->set('audit.elasticsearch.base_url', 'http://elasticsearch:9200');

        Http::fake([
            '*' => Http::response(['result' => 'created'], 200),
        ]);

        $event = AuditEvent::create([
            'event_uuid' => (string) \Illuminate\Support\Str::uuid(),
            'occurred_at' => now(),
            'action' => 'model_updated',
            'entity_type' => 'App\\Models\\Payment',
            'entity_id' => '18',
            'before' => ['contract_id' => 1770],
            'after' => ['contract_id' => 1770],
            'meta' => ['from_contract_id' => 1770, 'to_contract_id' => 1901],
            'export_status' => 'pending',
        ]);

        (new ExportAuditEventJob($event->id))->handle(app(\App\Services\Audit\ElasticsearchAuditExporter::class));

        Http::assertSent(function (\Illuminate\Http\Client\Request $request) {
            $payload = $request->data();

            return isset($payload['contract_refs'])
                && $payload['contract_refs'] === ['1770', '1901'];
        });
    }
}
