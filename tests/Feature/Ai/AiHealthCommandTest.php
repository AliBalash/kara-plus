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
            'ajil.test/v1/models/catalog*' => Http::response(['count' => 2, 'from_cache' => true, 'items' => [
                ['provider' => 'gemini', 'id' => 'gemini-3.8-flash'],
                ['provider' => 'gemini', 'id' => 'gemini-3.5-flash-lite'],
            ]], 200),
        ]);
        $this->artisan('ai:health')->expectsOutputToContain('Ajil is healthy')->assertSuccessful();
        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/chat/completions'));
    }

    public function test_it_does_not_treat_an_empty_catalog_with_failed_providers_as_healthy(): void
    {
        config()->set('ai.enabled', true);
        config()->set('ai.ajil.base_url', 'http://ajil.test');
        Http::fake([
            'ajil.test/health' => Http::response(['status' => 'ok', 'providers' => ['groq' => true, 'gemini' => true]], 200),
            'ajil.test/v1/models/catalog*' => Http::response(['count' => 0, 'items' => [], 'providers_status' => [
                ['provider' => 'gemini', 'ok' => false, 'status_code' => 502],
                ['provider' => 'groq', 'ok' => false, 'status_code' => 403],
            ]], 200),
        ]);

        $this->artisan('ai:health')
            ->expectsOutputToContain('gemini catalog is unavailable')
            ->expectsOutputToContain('groq catalog is unavailable')
            ->assertFailed();
    }
}
