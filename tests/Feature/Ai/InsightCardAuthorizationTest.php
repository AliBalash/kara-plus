<?php

namespace Tests\Feature\Ai;

use App\Livewire\Ai\InsightCard;
use App\Models\AiFeedback;
use App\Models\AiInsight;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InsightCardAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_anonymous_users_cannot_mount_an_ai_component(): void
    {
        $component = app(InsightCard::class);
        try {
            $component->mount('dashboard_operations');
            $this->fail('Anonymous component mount unexpectedly succeeded.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    public function test_authenticated_users_can_mount_known_feature(): void
    {
        $this->actingAs(User::factory()->create());
        $component = app(InsightCard::class);
        $component->mount('dashboard_operations');
        $this->assertSame('dashboard_operations', $component->feature);
    }

    public function test_driver_cannot_open_finance_or_reservation_ai_features(): void
    {
        Role::findOrCreate('driver', 'web');
        $driver = User::factory()->create();
        $driver->assignRole('driver');
        $this->actingAs($driver);

        foreach (['reservation_triage', 'reservation_queue', 'contract_finance', 'fleet_outlook'] as $feature) {
            try {
                app(InsightCard::class)->mount($feature, 1);
                $this->fail("Driver unexpectedly opened {$feature}.");
            } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
                $this->assertSame(403, $exception->getStatusCode());
            }
        }
    }

    public function test_feedback_is_stored_as_ai_quality_metadata_only(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $insight = AiInsight::create([
            'scope' => 'panel',
            'entity_type' => 'dashboard',
            'feature' => 'dashboard_operations',
            'prompt_version' => 'dashboard_operations:v1',
            'input_hash' => str_repeat('a', 64),
            'response_json' => ['headline' => 'Test', 'summary' => 'Test'],
            'generated_at' => now(),
            'expires_at' => now()->addMinute(),
        ]);

        $component = app(InsightCard::class);
        $component->mount('dashboard_operations');
        $component->state = 'ready';
        $component->insightId = $insight->id;
        $component->insightEntityType = 'dashboard';
        $component->feedback(true);
        $this->assertTrue($component->feedbackHelpful);

        $this->assertDatabaseHas('ai_feedback', [
            'ai_insight_id' => $insight->id,
            'user_id' => $user->id,
            'feature' => 'dashboard_operations',
            'helpful' => true,
        ]);
        $this->assertSame(1, AiFeedback::count());
    }
}
