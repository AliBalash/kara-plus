<?php

namespace Tests\Feature\Audit;

use App\Models\AuditEvent;
use App\Models\User;
use App\Services\Audit\AuditReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuditDashboardInsightsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_insights_and_action_group_filtering_work(): void
    {
        $user = User::factory()->create();

        AuditEvent::query()->create([
            'event_uuid' => (string) Str::uuid(),
            'occurred_at' => now()->subMinutes(10),
            'actor_user_id' => $user->id,
            'action' => 'model_updated',
            'entity_type' => 'App\\Models\\Contract',
            'entity_id' => '1001',
            'export_status' => 'exported',
        ]);

        AuditEvent::query()->create([
            'event_uuid' => (string) Str::uuid(),
            'occurred_at' => now()->subMinutes(8),
            'actor_user_id' => $user->id,
            'action' => 'auth_login_failed',
            'status_code' => 401,
            'export_status' => 'failed',
        ]);

        $service = app(AuditReportService::class);

        $mutationEvents = $service->query([
            'action_group' => 'mutations',
            'actor_user_id' => (string) $user->id,
        ])->count();
        $this->assertSame(1, $mutationEvents);

        $insights = $service->dashboard([]);
        $this->assertArrayHasKey('top_users', $insights);
        $this->assertArrayHasKey('focus_entities', $insights);
        $this->assertArrayHasKey('risk', $insights);

        $this->assertSame($user->id, $insights['top_users'][0]['user_id']);
        $this->assertSame(1, $insights['focus_entities']['Contract']['updated']);
        $this->assertSame(1, $insights['risk']['failed_logins']);
    }
}
