<?php

namespace Tests\Feature\Ai;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiHealthCommandTest extends TestCase
{
    public function test_it_reports_a_healthy_gateway_without_sending_a_prompt(): void
    {
        config()->set('ai.enabled', true);
        config()->set('ai.ajil.base_url', 'http://ajil.test');
        Http::fake([
            'ajil.test/health' => Http::response(['providers' => ['groq' => true, 'gemini' => true]], 200),
            'ajil.test/v1/models/catalog/summary' => Http::response(['summary' => ['total' => 2], 'from_cache' => true], 200),
        ]);
        $this->artisan('ai:health')->expectsOutputToContain('Ajil is healthy')->assertSuccessful();
        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/chat/completions'));
    }
}
