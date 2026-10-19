<?php

namespace Tests\Feature\Reports;

use App\Exports\Reports\ReportWorkbookExport;
use App\Http\Controllers\ReportExportController;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ReportExportControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_customer_request_export_controller_triggers_an_excel_download(): void
    {
        Carbon::setTestNow('2025-06-10 11:30:00');
        Excel::fake();

        $request = Request::create('/expert/reports/customer-requests/export', 'GET', [
            'search' => 'Sara',
            'date_from' => '2025-02-01',
            'date_to' => '2025-02-28',
        ]);

        app(ReportExportController::class)->customerRequests($request);

        Excel::assertDownloaded('customer_requests_report_20250610_113000.xlsx');
    }

    public function test_first_time_customer_export_controller_triggers_an_excel_download(): void
    {
        Carbon::setTestNow('2025-06-10 11:30:00');
        Excel::fake();

        $request = Request::create('/expert/reports/first-time-customers/export', 'GET', [
            'date_field' => 'pickup_date',
            'date_from' => '2025-07-01',
            'date_to' => '2025-07-31',
        ]);

        app(ReportExportController::class)->firstTimeCustomers($request);

        Excel::assertDownloaded('first_time_customer_report_20250610_113000.xlsx');
    }

    public function test_lead_source_export_controller_triggers_an_excel_download(): void
    {
        Carbon::setTestNow('2025-06-10 11:30:00');
        Excel::fake();

        $request = Request::create('/expert/reports/lead-sources/export', 'GET', [
            'date_field' => 'request_date',
            'date_from' => '2026-07-01',
            'date_to' => '2026-07-31',
        ]);

        app(ReportExportController::class)->leadSources($request);

        Excel::assertDownloaded('lead_source_report_20250610_113000.xlsx');
    }

    public function test_payment_collection_download_contains_the_filtered_discount_reason_column(): void
    {
        Carbon::setTestNow('2026-04-10 11:30:00');
        Excel::fake();
        Payment::factory()->create([
            'payment_type' => 'discount', 'discount_reason' => 'extension_discount',
            'amount_in_aed' => 125, 'payment_date' => '2026-04-01',
        ]);
        Payment::factory()->create([
            'payment_type' => 'discount', 'discount_reason' => 'management_discount',
            'payment_date' => '2026-04-01',
        ]);
        $request = Request::create('/expert/reports/payment-collections/export', 'GET', [
            'discount_reason' => 'extension_discount', 'date_from' => '2026-04-01', 'date_to' => '2026-04-30',
        ]);
        app(ReportExportController::class)->paymentCollections($request);

        Excel::assertDownloaded('payment_collection_report_20260410_113000.xlsx', function (ReportWorkbookExport $export) {
            $data = $export->sheets()[1]->array();
            $this->assertCount(2, $data);
            $column = array_search('Discount Reason', $data[0], true);
            $this->assertIsInt($column);
            $this->assertSame('Extension Discount', $data[1][$column]);
            $this->assertCount(count($data[0]), $data[1]);

            return true;
        });
    }
}
