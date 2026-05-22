<?php

namespace App\Services\Audit;

use App\Models\AuditEvent;
use App\Models\User;
use App\Services\Audit\Contracts\AuditQueryContract;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class AuditReportService implements AuditQueryContract
{
    public function query(array $filters = []): Builder
    {
        $query = AuditEvent::query()->with('actor');

        if (! empty($filters['date_from'])) {
            $query->where('occurred_at', '>=', $filters['date_from'] . ' 00:00:00');
        }

        if (! empty($filters['date_to'])) {
            $query->where('occurred_at', '<=', $filters['date_to'] . ' 23:59:59');
        }

        if (! empty($filters['actor_user_id'])) {
            $query->where('actor_user_id', (int) $filters['actor_user_id']);
        }

        if (! empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (! empty($filters['action_group'])) {
            $this->applyActionGroup($query, (string) $filters['action_group']);
        }

        if (! empty($filters['entity_type'])) {
            $query->where('entity_type', 'like', '%' . $filters['entity_type'] . '%');
        }

        if (! empty($filters['route_name'])) {
            $query->where('route_name', 'like', '%' . $filters['route_name'] . '%');
        }

        if (! empty($filters['status_code'])) {
            $query->where('status_code', (int) $filters['status_code']);
        }

        if (! empty($filters['request_id'])) {
            $query->where('request_id', $filters['request_id']);
        }

        if (! empty($filters['contract_id'])) {
            $contractId = (string) $filters['contract_id'];
            $query->where(function (Builder $builder) use ($contractId) {
                $builder->where(function (Builder $entity) use ($contractId) {
                    $entity->where('entity_type', 'App\\Models\\Contract')
                        ->where('entity_id', $contractId);
                })->orWhere('meta->contract_id', $contractId);
            });
        }

        if (! empty($filters['customer_id'])) {
            $customerId = (string) $filters['customer_id'];
            $query->where(function (Builder $builder) use ($customerId) {
                $builder->where(function (Builder $entity) use ($customerId) {
                    $entity->where('entity_type', 'App\\Models\\Customer')
                        ->where('entity_id', $customerId);
                })->orWhere('meta->customer_id', $customerId);
            });
        }

        if (! empty($filters['payment_id'])) {
            $paymentId = (string) $filters['payment_id'];
            $query->where(function (Builder $builder) use ($paymentId) {
                $builder->where(function (Builder $entity) use ($paymentId) {
                    $entity->where('entity_type', 'App\\Models\\Payment')
                        ->where('entity_id', $paymentId);
                })->orWhere('meta->payment_id', $paymentId);
            });
        }

        if (! empty($filters['search'])) {
            $search = '%' . trim((string) $filters['search']) . '%';
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('url', 'like', $search)
                    ->orWhere('route_name', 'like', $search)
                    ->orWhere('action', 'like', $search)
                    ->orWhere('entity_type', 'like', $search)
                    ->orWhere('entity_id', 'like', $search)
                    ->orWhere('ip', 'like', $search)
                    ->orWhere('request_id', 'like', $search);
            });
        }

        return $query;
    }

    public function summary(array $filters = []): array
    {
        $base = $this->query($filters);

        $total = (clone $base)->count();
        $failedExports = (clone $base)->where('export_status', 'failed')->count();
        $uniqueUsers = (clone $base)->whereNotNull('actor_user_id')->distinct('actor_user_id')->count('actor_user_id');
        $uniqueRequests = (clone $base)->whereNotNull('request_id')->distinct('request_id')->count('request_id');
        $actions = (clone $base)->selectRaw('action, COUNT(*) as total')
            ->groupBy('action')
            ->orderByDesc('total')
            ->limit(10)
            ->pluck('total', 'action')
            ->all();

        return [
            'total' => $total,
            'failed_exports' => $failedExports,
            'unique_users' => $uniqueUsers,
            'unique_requests' => $uniqueRequests,
            'actions' => $actions,
        ];
    }

    public function dashboard(array $filters = []): array
    {
        $base = $this->query($filters);

        $topUsersRows = (clone $base)
            ->whereNotNull('actor_user_id')
            ->selectRaw('actor_user_id, COUNT(*) as total_events, SUM(CASE WHEN action IN ("model_created","model_updated","model_deleted") THEN 1 ELSE 0 END) as mutation_events, MAX(occurred_at) as last_seen_at')
            ->groupBy('actor_user_id')
            ->orderByDesc('total_events')
            ->limit(8)
            ->get();

        $userMap = User::query()
            ->whereIn('id', $topUsersRows->pluck('actor_user_id')->all())
            ->get(['id', 'first_name', 'last_name'])
            ->keyBy('id');

        $topUsers = $topUsersRows->map(function ($row) use ($userMap) {
            $user = $userMap->get((int) $row->actor_user_id);
            $fullName = $user ? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) : null;

            return [
                'user_id' => (int) $row->actor_user_id,
                'name' => $fullName ?: ('User #' . $row->actor_user_id),
                'total_events' => (int) $row->total_events,
                'mutation_events' => (int) $row->mutation_events,
                'last_seen_at' => $row->last_seen_at,
            ];
        })->values()->all();

        $timelineRows = (clone $base)
            ->where('occurred_at', '>=', now()->subHours(23)->startOfHour())
            ->get(['occurred_at'])
            ->groupBy(function (AuditEvent $event) {
                return optional($event->occurred_at)->format('Y-m-d H:00:00');
            })
            ->map(fn ($events) => $events->count());

        $hourlyTimeline = [];
        $cursor = now()->subHours(23)->startOfHour();
        for ($i = 0; $i < 24; $i++) {
            $bucket = $cursor->copy()->addHours($i)->format('Y-m-d H:00:00');
            $hourlyTimeline[] = [
                'label' => Carbon::createFromFormat('Y-m-d H:i:s', $bucket)->format('H:00'),
                'bucket' => $bucket,
                'total_events' => (int) ($timelineRows->get($bucket) ?? 0),
            ];
        }

        $focusRows = (clone $base)
            ->whereIn('entity_type', [
                'App\\Models\\Contract',
                'App\\Models\\Car',
                'App\\Models\\Payment',
            ])
            ->whereIn('action', ['model_created', 'model_updated', 'model_deleted'])
            ->selectRaw('entity_type, COUNT(*) as total, SUM(CASE WHEN action = "model_created" THEN 1 ELSE 0 END) as created_count, SUM(CASE WHEN action = "model_updated" THEN 1 ELSE 0 END) as updated_count, SUM(CASE WHEN action = "model_deleted" THEN 1 ELSE 0 END) as deleted_count')
            ->groupBy('entity_type')
            ->get()
            ->keyBy('entity_type');

        $focusEntities = [
            'Contract' => $this->buildEntityFocusRow($focusRows->get('App\\Models\\Contract')),
            'Car' => $this->buildEntityFocusRow($focusRows->get('App\\Models\\Car')),
            'Payment' => $this->buildEntityFocusRow($focusRows->get('App\\Models\\Payment')),
        ];

        $topActions = (clone $base)
            ->selectRaw('action, COUNT(*) as total')
            ->groupBy('action')
            ->orderByDesc('total')
            ->limit(8)
            ->get()
            ->map(fn ($row) => ['action' => (string) $row->action, 'total' => (int) $row->total])
            ->values()
            ->all();

        $risk = [
            'failed_logins' => (clone $base)->where('action', 'auth_login_failed')->count(),
            'delete_actions' => (clone $base)->where('action', 'model_deleted')->count(),
            'http_errors' => (clone $base)->where('status_code', '>=', 400)->count(),
            'failed_exports' => (clone $base)->where('export_status', 'failed')->count(),
        ];

        return [
            'top_users' => $topUsers,
            'hourly_timeline' => $hourlyTimeline,
            'focus_entities' => $focusEntities,
            'top_actions' => $topActions,
            'risk' => $risk,
        ];
    }

    private function applyActionGroup(Builder $query, string $actionGroup): void
    {
        if ($actionGroup === 'mutations') {
            $query->whereIn('action', ['model_created', 'model_updated', 'model_deleted']);
            return;
        }

        if ($actionGroup === 'auth') {
            $query->whereIn('action', ['auth_login_success', 'auth_login_failed', 'auth_logout']);
            return;
        }

        if ($actionGroup === 'reads') {
            $query->where('action', 'business_read');
            return;
        }

        if ($actionGroup === 'requests') {
            $query->whereIn('action', ['http_request', 'livewire_call']);
            return;
        }

        if ($actionGroup === 'errors') {
            $query->where(function (Builder $builder) {
                $builder->where('status_code', '>=', 400)
                    ->orWhere('export_status', 'failed')
                    ->orWhere('action', 'auth_login_failed');
            });
        }
    }

    private function buildEntityFocusRow(mixed $row): array
    {
        return [
            'total' => (int) ($row->total ?? 0),
            'created' => (int) ($row->created_count ?? 0),
            'updated' => (int) ($row->updated_count ?? 0),
            'deleted' => (int) ($row->deleted_count ?? 0),
        ];
    }
}
