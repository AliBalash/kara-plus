<?php

namespace Tests\Feature\Audit;

use App\Models\AuditEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuditPruneCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_prune_command_removes_old_events_only(): void
    {
        $old = AuditEvent::create([
            'event_uuid' => (string) Str::uuid(),
            'occurred_at' => now()->subDays(365),
            'action' => 'http_request',
            'export_status' => 'pending',
        ]);

        $recent = AuditEvent::create([
            'event_uuid' => (string) Str::uuid(),
            'occurred_at' => now()->subDays(10),
            'action' => 'http_request',
            'export_status' => 'pending',
        ]);

        $this->artisan('audit:prune --days=180')
            ->assertSuccessful();

        $this->assertDatabaseMissing('audit_events', ['id' => $old->id]);
        $this->assertDatabaseHas('audit_events', ['id' => $recent->id]);
    }
}
