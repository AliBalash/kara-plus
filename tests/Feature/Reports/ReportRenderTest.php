<?php

namespace Tests\Feature\Reports;

use App\Livewire\Pages\Panel\Expert\Reports\CustomerBalanceReport;
use App\Livewire\Pages\Panel\Expert\Reports\CustomerCommunicationChannelReport;
use App\Livewire\Pages\Panel\Expert\Reports\CustomerRequestReport;
use App\Livewire\Pages\Panel\Expert\Reports\FirstTimeCustomerReport;
use App\Livewire\Pages\Panel\Expert\Reports\FleetPerformanceReport;
use App\Livewire\Pages\Panel\Expert\Reports\LeadSourceReport;
use App\Livewire\Pages\Panel\Expert\Reports\PaymentCollectionReport;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
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

    public function test_first_time_customer_report_view_renders(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $component = app(FirstTimeCustomerReport::class);
        $component->mount();

        $html = $component->render()->render();

        $this->assertStringContainsString('First-Time Customer Acquisition', $html);
    }

    public function test_lead_source_report_view_renders(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $component = app(LeadSourceReport::class);

        $html = $component->render()->render();

        $this->assertStringContainsString('Lead Communication Channel', $html);
    }

    public function test_customer_communication_channel_report_view_renders(): void
    {
        $this->actingAs(User::factory()->create());

        $html = app(CustomerCommunicationChannelReport::class)->render()->render();

        $this->assertStringContainsString('Customer Communication Channel', $html);
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

    public function test_payment_collection_reason_filter_is_restored_exported_and_reset(): void
    {
        URL::forceRootUrl('http://localhost');
        $this->actingAs(User::factory()->create());
        Payment::factory()->create([
            'payment_type' => 'discount', 'discount_reason' => 'extension_discount', 'payment_date' => '2026-04-01',
        ]);
        Payment::factory()->create([
            'payment_type' => 'discount', 'discount_reason' => null, 'payment_date' => '2026-04-01',
        ]);

        $test = Livewire::withQueryParams([
            'discountReason' => 'extension_discount', 'dateFrom' => '2026-04-01', 'dateTo' => '2026-04-30',
        ])->test(PaymentCollectionReport::class)
            ->assertSet('discountReason', 'extension_discount')
            ->assertSee('Discount Reason')
            ->assertDontSee('Item Owner')
            ->assertViewHas('report', fn ($report) => $report['rows']->count() === 1);
        parse_str(parse_url($test->instance()->exportUrl(), PHP_URL_QUERY), $exportQuery);
        $this->assertSame('extension_discount', $exportQuery['discount_reason']);
        $test->call('setPage', 2)
            ->set('discountReason', 'all')
            ->assertSet('paginators.page', 1)
            ->assertSee('Reason not recorded')
            ->assertViewHas('report', fn ($report) => $report['rows']->count() === 2)
            ->call('clearFilters')
            ->assertSet('discountReason', 'all');
    }
}
