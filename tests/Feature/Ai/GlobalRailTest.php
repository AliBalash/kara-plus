<?php

namespace Tests\Feature\Ai;

use App\Livewire\Ai\GlobalRail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlobalRailTest extends TestCase
{
    use RefreshDatabase;

    public function test_rail_is_closed_until_the_user_opens_it(): void
    {
        $this->actingAs(User::factory()->create());
        $rail = app(GlobalRail::class);
        $rail->mount();
        $this->assertFalse($rail->open);
        $rail->toggle();
        $this->assertTrue($rail->open);
    }
}
