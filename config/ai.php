<?php

return [
    'enabled' => env('KARA_AI_ENABLED', false),
    'cache_ttl' => (int) env('KARA_AI_CACHE_TTL', 600),
    // Ajil gathers provider catalogs concurrently and may need up to its
    // provider timeout on a cold cache. This is only used by ai:health, never
    // by page rendering or an end-user insight.
    'catalog_timeout' => (int) env('KARA_AI_CATALOG_TIMEOUT', 35),
    'max_facts' => (int) env('KARA_AI_MAX_FACTS', 25),
    'max_context_bytes' => (int) env('KARA_AI_MAX_CONTEXT_BYTES', 12000),
    'circuit' => [
        'failure_threshold' => (int) env('KARA_AI_FAILURE_THRESHOLD', 3),
        'cooldown' => (int) env('KARA_AI_CIRCUIT_COOLDOWN', 60),
    ],
    'routing_strategy' => env('KARA_AI_ROUTING_STRATEGY', 'fallback_chain'),
    'routing_mode' => env('KARA_AI_ROUTING_MODE', 'latency_first'),
    'router_timeout' => (int) env('KARA_AI_ROUTER_TIMEOUT', 18),
    'ajil' => [
        'base_url' => rtrim((string) env('AJIL_BASE_URL', 'http://ajil:8080'), '/'),
        'token' => env('AJIL_API_TOKEN'),
        'timeout' => (int) env('KARA_AI_TIMEOUT', 35),
        'connect_timeout' => (int) env('KARA_AI_CONNECT_TIMEOUT', 3),
    ],
    'features' => [
        'contract_brief' => env('KARA_AI_CONTRACT_BRIEF_ENABLED', true),
        'customer_brief' => env('KARA_AI_CUSTOMER_BRIEF_ENABLED', true),
        'vehicle_brief' => env('KARA_AI_VEHICLE_BRIEF_ENABLED', true),
        'dashboard_operations' => env('KARA_AI_DASHBOARD_BRIEF_ENABLED', true),
        'payment_queue' => env('KARA_AI_PAYMENT_BRIEF_ENABLED', true),
        'changes_since_login' => env('KARA_AI_CHANGES_ENABLED', true),
        'reservation_triage' => env('KARA_AI_RESERVATION_TRIAGE_ENABLED', true),
        'reservation_queue' => env('KARA_AI_RESERVATION_QUEUE_ENABLED', true),
        'contract_finance' => env('KARA_AI_CONTRACT_FINANCE_ENABLED', true),
        'fleet_outlook' => env('KARA_AI_FLEET_OUTLOOK_ENABLED', true),
    ],
    'models' => [
        'default' => [
            // Keep these defaults aligned with Ajil's live catalog. The
            // router owns retry, key rotation, cooldown and fallback.
            ['provider' => 'gemini', 'model' => env('KARA_AI_GEMINI_MODEL', 'gemini-3.8-flash'), 'priority' => 0],
            ['provider' => 'gemini', 'model' => env('KARA_AI_GEMINI_FALLBACK_MODEL', 'gemini-3.5-flash-lite'), 'priority' => 1],
            ['provider' => 'groq', 'model' => env('KARA_AI_GROQ_MODEL', 'openai/gpt-oss-20b'), 'priority' => 2],
            ['provider' => 'groq', 'model' => env('KARA_AI_GROQ_FALLBACK_MODEL', 'qwen/qwen3.8-27b'), 'priority' => 3],
        ],
    ],
];
