<?php

return [
    'enabled' => env('KARA_AI_ENABLED', false),
    'cache_ttl' => (int) env('KARA_AI_CACHE_TTL', 600),
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
