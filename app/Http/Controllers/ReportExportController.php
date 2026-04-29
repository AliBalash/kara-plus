<?php

namespace App\Http\Controllers;

use App\Exports\Reports\ReportWorkbookExport;
use App\Services\Reports\OperationsReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportExportController extends Controller
{
    public function __construct(private readonly OperationsReportService $reports)
    {
    }

    public function customerRequests(Request $request): BinaryFileResponse
    {
        $payload = $this->reports->customerRequests($request->query());

        return $this->download(
            title: 'Customer Requests Report',
            filePrefix: 'customer_requests_report',
            dataSheetTitle: 'Customer Requests',
            payload: $payload
        );
    }

    public function customerBalances(Request $request): BinaryFileResponse
    {
        $payload = $this->reports->customerBalances($request->query());

        return $this->download(
            title: 'Customer Balance Report',
            filePrefix: 'customer_balance_report',
            dataSheetTitle: 'Customer Balances',
            payload: $payload
        );
    }

    public function fleetPerformance(Request $request): BinaryFileResponse
    {
        $payload = $this->reports->fleetPerformance($request->query());

        return $this->download(
            title: 'Fleet Performance Report',
            filePrefix: 'fleet_performance_report',
            dataSheetTitle: 'Fleet Performance',
            payload: $payload
        );
    }

    public function paymentCollections(Request $request): BinaryFileResponse
    {
        $payload = $this->reports->paymentCollections($request->query());

        return $this->download(
            title: 'Payment Collection Report',
            filePrefix: 'payment_collection_report',
            dataSheetTitle: 'Payment Collections',
            payload: $payload
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function download(string $title, string $filePrefix, string $dataSheetTitle, array $payload): BinaryFileResponse
    {
        $fileName = sprintf('%s_%s.xlsx', $filePrefix, Carbon::now()->format('Ymd_His'));

        return Excel::download(
            new ReportWorkbookExport(
                $title,
                $payload['summary_sections'] ?? [],
                $payload['filter_summary'] ?? [],
                $payload['export_headings'] ?? [],
                $payload['export_rows'] ?? [],
                $dataSheetTitle,
                $payload['extra_sheets'] ?? []
            ),
            $fileName
        );
    }
}
