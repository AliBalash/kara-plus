<?php

namespace Tests\Feature\Ai;

use App\Livewire\Ai\InsightCard;
use App\Models\User;
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
}
