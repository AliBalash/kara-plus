<?php

namespace Tests\Feature\Reports;

use App\Livewire\Pages\Panel\Expert\Reports\CustomerBalanceReport;
use App\Livewire\Pages\Panel\Expert\Reports\CustomerRequestReport;
use App\Livewire\Pages\Panel\Expert\Reports\FleetPerformanceReport;
use App\Livewire\Pages\Panel\Expert\Reports\PaymentCollectionReport;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_request_report_view_renders(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $component = app(CustomerRequestReport::class);
        $component->mount();

        $html = $component->render()->render();

        $this->assertStringContainsString('Customer Request Intelligence', $html);
    }

    public function test_customer_balance_report_view_renders(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $component = app(CustomerBalanceReport::class);

        $html = $component->render()->render();

        $this->assertStringContainsString('Customer Balance Monitor', $html);
    }

    public function test_fleet_performance_report_view_renders(): void
    {
        Carbon::setTestNow('2025-06-10 12:00:00');

        $user = User::factory()->create();
        $this->actingAs($user);

        $component = app(FleetPerformanceReport::class);
        $component->mount();

        $html = $component->render()->render();

        $this->assertStringContainsString('Fleet Performance Window', $html);

        Carbon::setTestNow();
    }

    public function test_payment_collection_report_view_renders(): void
    {
        Carbon::setTestNow('2025-06-10 12:00:00');

        $user = User::factory()->create();
        $this->actingAs($user);

        $component = app(PaymentCollectionReport::class);
        $component->mount();

        $html = $component->render()->render();

        $this->assertStringContainsString('Payment Collection Control', $html);

        Carbon::setTestNow();
    }
}
