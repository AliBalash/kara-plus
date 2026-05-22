<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use stdClass;

class AuditKibanaBootstrapCommand extends Command
{
    protected $signature = 'audit:kibana-bootstrap';

    protected $description = 'Create initial Kibana data view and Audit dashboard';

    public function handle(): int
    {
        $kibanaApiUrl = rtrim((string) config('audit.elasticsearch.bootstrap_url'), '/');
        if ($kibanaApiUrl === '') {
            $this->error('Kibana bootstrap URL is not configured.');

            return self::FAILURE;
        }

        $kibanaDashboardUrl = rtrim((string) config('audit.elasticsearch.dashboard_url'), '/');
        if ($kibanaDashboardUrl === '') {
            $kibanaDashboardUrl = $kibanaApiUrl;
        }

        $client = Http::timeout(20)
            ->acceptJson()
            ->asJson()
            ->withHeaders(['kbn-xsrf' => 'true']);

        $user = (string) (config('audit.elasticsearch.username') ?: 'elastic');
        $pass = (string) (config('audit.elasticsearch.password') ?: '');
        if ($pass !== '') {
            $client = $client->withBasicAuth($user, $pass);
        }

        $indexPrefix = (string) config('audit.elasticsearch.index_prefix', 'kara-audit');
        $dataViewId = 'kara-audit-data-view';

        $dataViewRes = $client->post("{$kibanaApiUrl}/api/data_views/data_view", [
            'data_view' => [
                'id' => $dataViewId,
                'name' => 'Kara Audit Events',
                'title' => $indexPrefix . '-*',
                'timeFieldName' => 'occurred_at',
            ],
            'override' => true,
        ]);

        if (! $dataViewRes->successful()) {
            $this->error('Failed creating data view: ' . $dataViewRes->status() . ' ' . $dataViewRes->body());

            return self::FAILURE;
        }

        $recentSearchRes = $client->post("{$kibanaApiUrl}/api/saved_objects/search/kara-audit-recent-events?overwrite=true", [
            'attributes' => [
                'title' => 'Recent Audit Events',
                'description' => 'Most recent audit events with actor, action, route, entity and status context.',
                'columns' => [
                    'occurred_at',
                    'contract_refs',
                    'action',
                    'actor_user_id',
                    'route_name',
                    'entity_type',
                    'entity_id',
                    'status_code',
                    'request_id',
                ],
                'sort' => [
                    ['occurred_at', 'desc'],
                ],
                'kibanaSavedObjectMeta' => [
                    'searchSourceJSON' => json_encode([
                        'query' => ['language' => 'kuery', 'query' => ''],
                        'filter' => [],
                        'indexRefName' => 'kibanaSavedObjectMeta.searchSourceJSON.index',
                    ], JSON_UNESCAPED_SLASHES),
                ],
            ],
            'references' => [
                [
                    'id' => $dataViewId,
                    'name' => 'kibanaSavedObjectMeta.searchSourceJSON.index',
                    'type' => 'index-pattern',
                ],
            ],
        ]);

        if (! $recentSearchRes->successful()) {
            $this->error('Failed creating recent events search: ' . $recentSearchRes->status() . ' ' . $recentSearchRes->body());

            return self::FAILURE;
        }

        $riskSearchRes = $client->post("{$kibanaApiUrl}/api/saved_objects/search/kara-audit-risk-events?overwrite=true", [
            'attributes' => [
                'title' => 'Risk / Error Events',
                'description' => 'Failed logins, delete mutations, and request errors (status >= 400).',
                'columns' => [
                    'occurred_at',
                    'contract_refs',
                    'action',
                    'actor_user_id',
                    'status_code',
                    'route_name',
                    'entity_type',
                    'entity_id',
                    'ip',
                ],
                'sort' => [
                    ['occurred_at', 'desc'],
                ],
                'kibanaSavedObjectMeta' => [
                    'searchSourceJSON' => json_encode([
                        'query' => [
                            'language' => 'kuery',
                            'query' => '(action: "auth_login_failed" or action: "model_deleted" or status_code >= 400)',
                        ],
                        'filter' => [],
                        'indexRefName' => 'kibanaSavedObjectMeta.searchSourceJSON.index',
                    ], JSON_UNESCAPED_SLASHES),
                ],
            ],
            'references' => [
                [
                    'id' => $dataViewId,
                    'name' => 'kibanaSavedObjectMeta.searchSourceJSON.index',
                    'type' => 'index-pattern',
                ],
            ],
        ]);

        if (! $riskSearchRes->successful()) {
            $this->error('Failed creating risk events search: ' . $riskSearchRes->status() . ' ' . $riskSearchRes->body());

            return self::FAILURE;
        }

        if (! $this->createContractSearches($client, $kibanaApiUrl, $dataViewId)) {
            return self::FAILURE;
        }

        $visualizationRes = $this->createVisualizations($client, $kibanaApiUrl, $dataViewId);
        if ($visualizationRes !== true) {
            return self::FAILURE;
        }

        $panels = [
            [
                'version' => '8.14.3',
                'type' => 'search',
                'gridData' => [
                    'x' => 0,
                    'y' => 0,
                    'w' => 24,
                    'h' => 12,
                    'i' => 'panel-0',
                ],
                'panelIndex' => 'panel-0',
                'panelRefName' => 'panel_0',
                'embeddableConfig' => new stdClass(),
            ],
            [
                'version' => '8.14.3',
                'type' => 'visualization',
                'gridData' => [
                    'x' => 24,
                    'y' => 0,
                    'w' => 24,
                    'h' => 12,
                    'i' => 'panel-2',
                ],
                'panelIndex' => 'panel-2',
                'panelRefName' => 'panel_2',
                'embeddableConfig' => new stdClass(),
            ],
            [
                'version' => '8.14.3',
                'type' => 'visualization',
                'gridData' => [
                    'x' => 0,
                    'y' => 12,
                    'w' => 16,
                    'h' => 10,
                    'i' => 'panel-3',
                ],
                'panelIndex' => 'panel-3',
                'panelRefName' => 'panel_3',
                'embeddableConfig' => new stdClass(),
            ],
            [
                'version' => '8.14.3',
                'type' => 'visualization',
                'gridData' => [
                    'x' => 16,
                    'y' => 12,
                    'w' => 16,
                    'h' => 10,
                    'i' => 'panel-4',
                ],
                'panelIndex' => 'panel-4',
                'panelRefName' => 'panel_4',
                'embeddableConfig' => new stdClass(),
            ],
            [
                'version' => '8.14.3',
                'type' => 'visualization',
                'gridData' => [
                    'x' => 32,
                    'y' => 12,
                    'w' => 16,
                    'h' => 10,
                    'i' => 'panel-5',
                ],
                'panelIndex' => 'panel-5',
                'panelRefName' => 'panel_5',
                'embeddableConfig' => new stdClass(),
            ],
            [
                'version' => '8.14.3',
                'type' => 'visualization',
                'gridData' => [
                    'x' => 0,
                    'y' => 22,
                    'w' => 24,
                    'h' => 10,
                    'i' => 'panel-6',
                ],
                'panelIndex' => 'panel-6',
                'panelRefName' => 'panel_6',
                'embeddableConfig' => new stdClass(),
            ],
            [
                'version' => '8.14.3',
                'type' => 'search',
                'gridData' => [
                    'x' => 24,
                    'y' => 22,
                    'w' => 24,
                    'h' => 10,
                    'i' => 'panel-1',
                ],
                'panelIndex' => 'panel-1',
                'panelRefName' => 'panel_1',
                'embeddableConfig' => new stdClass(),
            ],
        ];

        $dashboardRes = $client->post("{$kibanaApiUrl}/api/saved_objects/dashboard/kara-audit-overview?overwrite=true", [
            'attributes' => [
                'title' => 'Kara Audit Center Overview',
                'description' => 'Initial management dashboard for monitoring audit events, actor behavior, and operational changes.',
                'hits' => 0,
                'panelsJSON' => json_encode($panels, JSON_UNESCAPED_SLASHES),
                'optionsJSON' => '{"useMargins":true,"hidePanelTitles":false}',
                'version' => 1,
                'timeRestore' => true,
                'timeFrom' => 'now-30d',
                'timeTo' => 'now',
                'refreshInterval' => [
                    'pause' => false,
                    'value' => 15000,
                ],
                'kibanaSavedObjectMeta' => [
                    'searchSourceJSON' => json_encode([
                        'query' => ['language' => 'kuery', 'query' => ''],
                        'filter' => [],
                    ], JSON_UNESCAPED_SLASHES),
                ],
            ],
            'references' => [
                ['id' => 'kara-audit-recent-events', 'name' => 'panel_0', 'type' => 'search'],
                ['id' => 'kara-audit-events-over-time', 'name' => 'panel_2', 'type' => 'visualization'],
                ['id' => 'kara-audit-top-users', 'name' => 'panel_3', 'type' => 'visualization'],
                ['id' => 'kara-audit-action-types', 'name' => 'panel_4', 'type' => 'visualization'],
                ['id' => 'kara-audit-mutation-entities', 'name' => 'panel_5', 'type' => 'visualization'],
                ['id' => 'kara-audit-risk-over-time', 'name' => 'panel_6', 'type' => 'visualization'],
                ['id' => 'kara-audit-risk-events', 'name' => 'panel_1', 'type' => 'search'],
            ],
        ]);

        if (! $dashboardRes->successful()) {
            $this->error('Failed creating dashboard: ' . $dashboardRes->status() . ' ' . $dashboardRes->body());

            return self::FAILURE;
        }

        if (! $this->createContractDashboard($client, $kibanaApiUrl)) {
            return self::FAILURE;
        }

        $this->info('Kibana data view, searches, and initial dashboard panels created.');
        $this->line('Dashboard URL: ' . $kibanaDashboardUrl . '/app/dashboards#/view/kara-audit-overview');
        $this->line('Contract Dashboard URL: ' . $kibanaDashboardUrl . '/app/dashboards#/view/kara-audit-contract-investigation');
        $this->line('Contract filter (new logs): contract_refs: 1770');
        $this->line('Contract filter (all logs): ' . $this->contractLinkQuery('1770'));

        return self::SUCCESS;
    }

    private function createVisualizations($client, string $kibanaApiUrl, string $dataViewId): bool
    {
        $visualizations = [
            [
                'id' => 'kara-audit-events-over-time',
                'title' => 'Events Over Time',
                'type' => 'line',
                'aggs' => [
                    ['id' => '1', 'enabled' => true, 'type' => 'count', 'schema' => 'metric', 'params' => new stdClass()],
                    ['id' => '2', 'enabled' => true, 'type' => 'date_histogram', 'schema' => 'segment', 'params' => ['field' => 'occurred_at', 'timeRange' => ['from' => 'now-30d', 'to' => 'now'], 'useNormalizedEsInterval' => true, 'min_doc_count' => 1, 'drop_partials' => false, 'extended_bounds' => new stdClass(), 'interval' => 'auto']],
                ],
            ],
            [
                'id' => 'kara-audit-top-users',
                'title' => 'Top Users By Activity',
                'type' => 'horizontal_bar',
                'aggs' => [
                    ['id' => '1', 'enabled' => true, 'type' => 'count', 'schema' => 'metric', 'params' => new stdClass()],
                    ['id' => '2', 'enabled' => true, 'type' => 'terms', 'schema' => 'segment', 'params' => ['field' => 'actor_user_id', 'size' => 10, 'order' => 'desc', 'orderBy' => '1', 'otherBucket' => false, 'missingBucket' => true]],
                ],
            ],
            [
                'id' => 'kara-audit-action-types',
                'title' => 'Action Types Mix',
                'type' => 'pie',
                'aggs' => [
                    ['id' => '1', 'enabled' => true, 'type' => 'count', 'schema' => 'metric', 'params' => new stdClass()],
                    ['id' => '2', 'enabled' => true, 'type' => 'terms', 'schema' => 'segment', 'params' => ['field' => 'action.keyword', 'size' => 10, 'order' => 'desc', 'orderBy' => '1', 'otherBucket' => false, 'missingBucket' => false]],
                ],
            ],
            [
                'id' => 'kara-audit-mutation-entities',
                'title' => 'Mutation Focus (Contract/Car/Payment)',
                'type' => 'vertical_bar',
                'query' => 'action.keyword: ("model_created" or "model_updated" or "model_deleted") and entity_type.keyword: ("App\\\\Models\\\\Contract" or "App\\\\Models\\\\Car" or "App\\\\Models\\\\Payment")',
                'aggs' => [
                    ['id' => '1', 'enabled' => true, 'type' => 'count', 'schema' => 'metric', 'params' => new stdClass()],
                    ['id' => '2', 'enabled' => true, 'type' => 'terms', 'schema' => 'segment', 'params' => ['field' => 'entity_type.keyword', 'size' => 5, 'order' => 'desc', 'orderBy' => '1']],
                    ['id' => '3', 'enabled' => true, 'type' => 'terms', 'schema' => 'group', 'params' => ['field' => 'action.keyword', 'size' => 5, 'order' => 'desc', 'orderBy' => '1']],
                ],
            ],
            [
                'id' => 'kara-audit-risk-over-time',
                'title' => 'Risk Events Over Time',
                'type' => 'line',
                'query' => 'action.keyword: ("auth_login_failed" or "model_deleted") or status_code >= 400',
                'aggs' => [
                    ['id' => '1', 'enabled' => true, 'type' => 'count', 'schema' => 'metric', 'params' => new stdClass()],
                    ['id' => '2', 'enabled' => true, 'type' => 'date_histogram', 'schema' => 'segment', 'params' => ['field' => 'occurred_at', 'timeRange' => ['from' => 'now-30d', 'to' => 'now'], 'useNormalizedEsInterval' => true, 'min_doc_count' => 1, 'drop_partials' => false, 'extended_bounds' => new stdClass(), 'interval' => 'auto']],
                ],
            ],
        ];

        foreach ($visualizations as $vis) {
            $visState = [
                'title' => $vis['title'],
                'type' => $vis['type'],
                'aggs' => $vis['aggs'],
                'params' => [
                    'addLegend' => true,
                    'addTooltip' => true,
                    'legendPosition' => 'right',
                ],
            ];

            $searchSource = [
                'query' => [
                    'language' => 'kuery',
                    'query' => $vis['query'] ?? '',
                ],
                'filter' => [],
                'indexRefName' => 'kibanaSavedObjectMeta.searchSourceJSON.index',
            ];

            $payload = [
                'attributes' => [
                    'title' => $vis['title'],
                    'description' => '',
                    'visState' => json_encode($visState, JSON_UNESCAPED_SLASHES),
                    'uiStateJSON' => '{}',
                    'version' => 1,
                    'kibanaSavedObjectMeta' => [
                        'searchSourceJSON' => json_encode($searchSource, JSON_UNESCAPED_SLASHES),
                    ],
                ],
                'references' => [
                    [
                        'id' => $dataViewId,
                        'name' => 'kibanaSavedObjectMeta.searchSourceJSON.index',
                        'type' => 'index-pattern',
                    ],
                ],
            ];

            $response = $client->post("{$kibanaApiUrl}/api/saved_objects/visualization/{$vis['id']}?overwrite=true", $payload);

            if (! $response->successful()) {
                $this->error("Failed creating visualization {$vis['id']}: " . $response->status() . ' ' . $response->body());

                return false;
            }
        }

        return true;
    }

    private function createContractSearches($client, string $kibanaApiUrl, string $dataViewId): bool
    {
        $importantContractQuery = implode(' and ', [
            $this->contractLinkQuery(),
            '(('
                . 'action.keyword: "model_updated" and entity_type.keyword: "App\\\\Models\\\\Contract"'
                . ') or ('
                . 'action.keyword: "model_created" and entity_type.keyword: "App\\\\Models\\\\ContractStatus"'
                . ') or ('
                . 'entity_type.keyword: ("App\\\\Models\\\\Payment" or "App\\\\Models\\\\ContractBalanceTransfer" or "App\\\\Models\\\\PickupDocument" or "App\\\\Models\\\\ReturnDocument" or "App\\\\Models\\\\CustomerDocument")'
                . ') or ('
                . 'action.keyword: "business_read"'
                . ') or ('
                . 'status_code >= 400'
                . ') or ('
                . 'action.keyword: "model_deleted"'
                . '))',
        ]);

        $searches = [
            [
                'id' => 'kara-audit-contract-important-events',
                'title' => 'Contract Important Events',
                'description' => 'Important contract events. Add filter like contract_refs: 1770 in the dashboard query bar.',
                'query' => $importantContractQuery,
                'columns' => [
                    'occurred_at',
                    'contract_refs',
                    'action',
                    'actor_user_id',
                    'route_name',
                    'entity_type',
                    'entity_id',
                    'changed_fields',
                    'status_code',
                    'request_id',
                ],
            ],
            [
                'id' => 'kara-audit-contract-all-events',
                'title' => 'Contract All Linked Events',
                'description' => 'All events linked to one or more contracts (contract_refs field).',
                'query' => $this->contractLinkQuery(),
                'columns' => [
                    'occurred_at',
                    'contract_refs',
                    'action',
                    'actor_user_id',
                    'route_name',
                    'entity_type',
                    'entity_id',
                    'status_code',
                    'request_id',
                ],
            ],
        ];

        foreach ($searches as $search) {
            $searchRes = $client->post("{$kibanaApiUrl}/api/saved_objects/search/{$search['id']}?overwrite=true", [
                'attributes' => [
                    'title' => $search['title'],
                    'description' => $search['description'],
                    'columns' => $search['columns'],
                    'sort' => [
                        ['occurred_at', 'desc'],
                    ],
                    'kibanaSavedObjectMeta' => [
                        'searchSourceJSON' => json_encode([
                            'query' => [
                                'language' => 'kuery',
                                'query' => $search['query'],
                            ],
                            'filter' => [],
                            'indexRefName' => 'kibanaSavedObjectMeta.searchSourceJSON.index',
                        ], JSON_UNESCAPED_SLASHES),
                    ],
                ],
                'references' => [
                    [
                        'id' => $dataViewId,
                        'name' => 'kibanaSavedObjectMeta.searchSourceJSON.index',
                        'type' => 'index-pattern',
                    ],
                ],
            ]);

            if (! $searchRes->successful()) {
                $this->error('Failed creating contract search ' . $search['id'] . ': ' . $searchRes->status() . ' ' . $searchRes->body());

                return false;
            }
        }

        return true;
    }

    private function createContractDashboard($client, string $kibanaApiUrl): bool
    {
        $panels = [
            [
                'version' => '8.14.3',
                'type' => 'search',
                'gridData' => [
                    'x' => 0,
                    'y' => 0,
                    'w' => 48,
                    'h' => 16,
                    'i' => 'panel-0',
                ],
                'panelIndex' => 'panel-0',
                'panelRefName' => 'panel_0',
                'embeddableConfig' => new stdClass(),
            ],
            [
                'version' => '8.14.3',
                'type' => 'search',
                'gridData' => [
                    'x' => 0,
                    'y' => 16,
                    'w' => 48,
                    'h' => 16,
                    'i' => 'panel-1',
                ],
                'panelIndex' => 'panel-1',
                'panelRefName' => 'panel_1',
                'embeddableConfig' => new stdClass(),
            ],
        ];

        $dashboardRes = $client->post("{$kibanaApiUrl}/api/saved_objects/dashboard/kara-audit-contract-investigation?overwrite=true", [
            'attributes' => [
                'title' => 'Kara Contract Investigation',
                'description' => 'Contract-centric timeline. In query bar enter: contract_refs: 1770',
                'hits' => 0,
                'panelsJSON' => json_encode($panels, JSON_UNESCAPED_SLASHES),
                'optionsJSON' => '{"useMargins":true,"hidePanelTitles":false}',
                'version' => 1,
                'timeRestore' => true,
                'timeFrom' => 'now-30d',
                'timeTo' => 'now',
                'refreshInterval' => [
                    'pause' => false,
                    'value' => 15000,
                ],
                'kibanaSavedObjectMeta' => [
                    'searchSourceJSON' => json_encode([
                        'query' => ['language' => 'kuery', 'query' => ''],
                        'filter' => [],
                    ], JSON_UNESCAPED_SLASHES),
                ],
            ],
            'references' => [
                ['id' => 'kara-audit-contract-important-events', 'name' => 'panel_0', 'type' => 'search'],
                ['id' => 'kara-audit-contract-all-events', 'name' => 'panel_1', 'type' => 'search'],
            ],
        ]);

        if (! $dashboardRes->successful()) {
            $this->error('Failed creating contract dashboard: ' . $dashboardRes->status() . ' ' . $dashboardRes->body());

            return false;
        }

        return true;
    }

    private function contractLinkQuery(?string $contractId = null): string
    {
        $value = $contractId !== null ? $contractId : '*';

        $parts = [
            "contract_refs: {$value}",
            "(entity_type.keyword: \"App\\\\Models\\\\Contract\" and entity_id: {$value})",
            "meta.contract_id: {$value}",
            "before.contract_id: {$value}",
            "after.contract_id: {$value}",
            "meta.from_contract_id: {$value}",
            "meta.to_contract_id: {$value}",
            "before.from_contract_id: {$value}",
            "before.to_contract_id: {$value}",
            "after.from_contract_id: {$value}",
            "after.to_contract_id: {$value}",
        ];

        return '(' . implode(' or ', $parts) . ')';
    }
}
