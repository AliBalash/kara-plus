<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Services\Audit\AuditReportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditExportController extends Controller
{
    public function exportCsv(Request $request, AuditReportService $reportService): StreamedResponse
    {
        $this->authorizeSuperAdmin();

        $filters = $request->only([
            'search',
            'date_from',
            'date_to',
            'actor_user_id',
            'action_group',
            'action',
            'entity_type',
            'route_name',
            'status_code',
            'request_id',
            'contract_id',
            'customer_id',
            'payment_id',
        ]);

        $fileName = 'audit_center_' . now()->format('Ymd_His') . '.csv';

        $response = new StreamedResponse(function () use ($reportService, $filters) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Event UUID',
                'Occurred At',
                'Action',
                'User ID',
                'Route',
                'Method',
                'URL',
                'Status',
                'Entity Type',
                'Entity ID',
                'Request ID',
                'IP',
                'Export Status',
            ]);

            $reportService->query($filters)
                ->orderByDesc('occurred_at')
                ->chunk(1000, function ($events) use ($handle) {
                    foreach ($events as $event) {
                        fputcsv($handle, [
                            $event->event_uuid,
                            optional($event->occurred_at)->toDateTimeString(),
                            $event->action,
                            $event->actor_user_id,
                            $event->route_name,
                            $event->method,
                            $event->url,
                            $event->status_code,
                            $event->entity_type,
                            $event->entity_id,
                            $event->request_id,
                            $event->ip,
                            $event->export_status,
                        ]);
                    }
                });

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', "attachment; filename=\"{$fileName}\"");

        return $response;
    }

    private function authorizeSuperAdmin(): void
    {
        $user = auth()->user();

        abort_unless($user && $user->hasRole('super-admin'), 403);
    }
}
