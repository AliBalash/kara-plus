<?php

namespace Tests\Feature\Livewire\Agent;

use App\Livewire\Pages\Panel\Expert\Agent\AgentList;
use App\Models\Agent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentListTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_agent_with_contact_numbers(): void
    {
        $this->actingAs(User::factory()->create());

        $component = app(AgentList::class);
        $component->name = 'Downtown Sales';
        $component->direct_line = '04-1234567';
        $component->mobile = '0501234567';
        $component->is_active = true;
        $component->save();

        $this->assertDatabaseHas('agents', [
            'name' => 'Downtown Sales',
            'direct_line' => '04-1234567',
            'mobile' => '0501234567',
            'is_active' => true,
        ]);
    }

    public function test_edit_populates_agent_contact_numbers(): void
    {
        $this->actingAs(User::factory()->create());

        $agent = Agent::create([
            'name' => 'Marina Desk',
            'direct_line' => '04-7654321',
            'mobile' => '0527654321',
            'is_active' => true,
        ]);

        $component = app(AgentList::class);
        $component->edit($agent->id);

        $this->assertSame('Marina Desk', $component->name);
        $this->assertSame('04-7654321', $component->direct_line);
        $this->assertSame('0527654321', $component->mobile);
    }
}
