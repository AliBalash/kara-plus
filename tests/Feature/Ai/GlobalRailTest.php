<?php

namespace Tests\Feature\Ai;

use App\AI\AiInsightService;
use App\Livewire\Ai\GlobalRail;
use App\Livewire\Ai\InsightCard;
use App\Models\Contract;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Livewire\Livewire;
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

    public function test_rail_template_has_exactly_one_root_element(): void
    {
        $this->actingAs(User::factory()->create());
        $rail = app(GlobalRail::class);
        $rail->mount();

        $html = trim(view('livewire.ai.global-rail', [
            'open' => $rail->open,
            'contextTitle' => $rail->contextTitle,
            'showSaveReminder' => $rail->showSaveReminder,
            'presets' => $rail->presets,
        ])->render());

        $this->assertStringStartsWith('<aside ', $html);
        $this->assertStringEndsWith('</aside>', $html);
        $this->assertStringContainsString('<style>', $html);
        $this->assertStringContainsString('Today', $html);
        $this->assertStringNotContainsString('<section id="kara-ai-context-panel"', $html);
    }

    public function test_insight_cards_are_only_mounted_after_opening_the_rail(): void
    {
        $this->actingAs(User::factory()->create());
        $this->mock(AiInsightService::class, function ($mock): void {
            $mock->shouldNotReceive('preview');
            $mock->shouldNotReceive('generate');
        });

        Livewire::test(GlobalRail::class)
            ->assertSet('open', false)
            ->assertDontSeeLivewire(InsightCard::class)
            ->call('toggle')
            ->assertSet('open', true)
            ->assertSeeLivewire(InsightCard::class)
            ->assertSeeHtml('wire:init="loadFacts"')
            ->call('toggle')
            ->assertSet('open', false)
            ->assertDontSeeLivewire(InsightCard::class);
    }

    public function test_rail_preserves_the_insights_for_pages_that_previously_had_inline_cards(): void
    {
        $this->actingAs(User::factory()->create());
        $contract = Contract::factory()->status('review_pending')->create([
            'intake_source' => Contract::INTAKE_SOURCE_WEBSITE,
        ]);

        $pages = [
            ['expert.dashboard', [], ['dashboard_operations', 'changes_since_login']],
            ['rental-requests.confirm-payment-list', [], ['payment_queue']],
            ['rental-requests.website-review', [], ['reservation_queue']],
            ['car.list', [], ['fleet_outlook']],
            ['rental-requests.payment', ['contractId' => $contract->id, 'customerId' => $contract->customer_id], ['contract_finance', 'contract_brief']],
            ['rental-requests.edit', ['contractId' => $contract->id], ['reservation_triage', 'contract_brief']],
        ];

        foreach ($pages as [$routeName, $parameters, $features]) {
            $request = Request::create(route($routeName, $parameters));
            $route = app('router')->getRoutes()->getByName($routeName);
            $route->bind($request);
            $request->setRouteResolver(fn () => $route);
            app()->instance('request', $request);

            $rail = app(GlobalRail::class);
            $rail->mount();

            $this->assertSame($features, array_column($rail->presets, 'feature'), $routeName);
            $this->assertFalse($rail->open);
            $this->assertSame($routeName === 'rental-requests.edit', $rail->showSaveReminder);
            foreach ($rail->presets as $preset) {
                $this->assertSame(isset($parameters['contractId']) ? $contract->id : null, $preset['entity_id'], $routeName);
            }
        }
    }
}
