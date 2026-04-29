<?php

namespace Tests\Feature\Reports;

use App\Http\Controllers\ReportExportController;
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
}
