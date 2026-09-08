<?php

return [
    'enabled' => env('KARA_AI_ENABLED', false),
    'cache_ttl' => (int) env('KARA_AI_CACHE_TTL', 600),
    'catalog_timeout' => (int) env('KARA_AI_CATALOG_TIMEOUT', 5),
    'max_facts' => (int) env('KARA_AI_MAX_FACTS', 25),
    'max_context_bytes' => (int) env('KARA_AI_MAX_CONTEXT_BYTES', 12000),
    'circuit' => [
        'failure_threshold' => (int) env('KARA_AI_FAILURE_THRESHOLD', 3),
        'cooldown' => (int) env('KARA_AI_CIRCUIT_COOLDOWN', 60),
    ],
    'routing_strategy' => env('KARA_AI_ROUTING_STRATEGY', 'fallback_chain'),
    'routing_mode' => env('KARA_AI_ROUTING_MODE', 'latency_first'),
    'ajil' => [
        'base_url' => rtrim((string) env('AJIL_BASE_URL', 'http://ajil:8080'), '/'),
        'token' => env('AJIL_API_TOKEN'),
        'timeout' => (int) env('KARA_AI_TIMEOUT', 20),
        'connect_timeout' => (int) env('KARA_AI_CONNECT_TIMEOUT', 3),
    ],
    'features' => [
        'contract_brief' => env('KARA_AI_CONTRACT_BRIEF_ENABLED', true),
        'dashboard_operations' => env('KARA_AI_DASHBOARD_BRIEF_ENABLED', true),
        'payment_queue' => env('KARA_AI_PAYMENT_BRIEF_ENABLED', true),
        'changes_since_login' => env('KARA_AI_CHANGES_ENABLED', true),
    ],
    'models' => [
        'default' => [
            ['provider' => 'groq', 'model' => env('KARA_AI_GROQ_MODEL', 'llama-3.3-70b-versatile'), 'priority' => 0],
            ['provider' => 'gemini', 'model' => env('KARA_AI_GEMINI_MODEL', 'gemini-2.5-flash'), 'priority' => 1],
        ],
    ],
];
