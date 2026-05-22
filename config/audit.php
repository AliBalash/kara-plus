<?php

return [
    'retention_days' => (int) env('AUDIT_RETENTION_DAYS', 30),

    'capture' => [
        'http_requests' => true,
        'livewire_calls' => true,
        'model_events' => true,
        'auth_events' => true,
        'business_reads' => true,
    ],

    'export' => [
        'enabled' => (bool) env('AUDIT_EXPORT_ENABLED', true),
        'queue' => env('AUDIT_EXPORT_QUEUE', 'default'),
        'batch_size' => (int) env('AUDIT_EXPORT_BATCH_SIZE', 200),
        'request_timeout_seconds' => (int) env('AUDIT_EXPORT_TIMEOUT_SECONDS', 10),
    ],

    'elasticsearch' => [
        'enabled' => (bool) env('ELASTICSEARCH_ENABLED', true),
        'base_url' => env('ELASTICSEARCH_BASE_URL', 'http://elasticsearch:9200'),
        'username' => env('ELASTICSEARCH_USERNAME', 'elastic'),
        'password' => env('ELASTICSEARCH_PASSWORD'),
        'api_key' => env('ELASTICSEARCH_API_KEY'),
        'verify_tls' => (bool) env('ELASTICSEARCH_VERIFY_TLS', false),
        'index_prefix' => env('ELASTICSEARCH_AUDIT_INDEX_PREFIX', 'kara-audit'),
        'ilm_policy' => env('ELASTICSEARCH_AUDIT_ILM_POLICY', 'kara-audit-1m'),
        'bootstrap_url' => env('KIBANA_BOOTSTRAP_URL', 'http://kibana:5601'),
        'dashboard_url' => env('KIBANA_DASHBOARD_URL', 'http://localhost:15601'),
    ],

    'redaction' => [
        'fields' => [
            'password',
            'password_confirmation',
            'remember_token',
            'token',
            'api_token',
            'access_token',
            'refresh_token',
            'authorization',
            'cookie',
            'x-csrf-token',
            'x-xsrf-token',
            'session',
            'session_id',
            'current_password',
            '_token',
        ],
        'mask' => '[REDACTED]',
    ],

    'business_read_routes' => [
        'rental-requests.details',
        'rental-requests.history',
        'rental-requests.edit',
        'rental-requests.payment',
        'rental-requests.pickup-document',
        'rental-requests.return-document',
        'customer.detail',
        'customer.history',
        'customer.debt',
        'payments.edit',
        'car.edit',
        'car.detail',
    ],
];
