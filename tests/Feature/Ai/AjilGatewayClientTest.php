<?php

namespace Tests\Feature\Ai;

use App\AI\AjilGatewayClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AjilGatewayClientTest extends TestCase
{
    public function test_client_sends_one_logical_fallback_chain_request(): void
    {
        config()->set('ai.ajil.base_url', 'http://ajil.test');
        config()->set('ai.ajil.token', 'test-token');
        config()->set('ai.models.default', [
            ['provider' => 'gemini', 'model' => 'gemini-3.5-flash-lite', 'priority' => 0],
            ['provider' => 'gemini', 'model' => 'gemini-3.8-flash', 'priority' => 1],
            ['provider' => 'groq', 'model' => 'openai/gpt-oss-20b', 'priority' => 2],
            ['provider' => 'groq', 'model' => 'qwen/qwen3.8-27b', 'priority' => 3],
        ]);
        Http::fake(['ajil.test/v1/chat/completions' => Http::response(['choices' => [['message' => ['content' => json_encode(['headline' => 'OK', 'summary' => 'OK'])]]]], 200)]);
        $result = app(AjilGatewayClient::class)->complete('dashboard_operations', [], []);
        $this->assertSame('OK', $result['response']['headline']);
        Http::assertSent(function (Request $request): bool {
            return $request->header('x-api-token')[0] === 'test-token'
                && $request['x_router']['strategy'] === 'fallback_chain'
                && count($request['model']) === 4
                && $request['model'][0]['model'] === 'gemini-3.5-flash-lite'
                && $request['model'][1]['model'] === 'gemini-3.8-flash';
        });
    }

    public function test_client_rejects_an_ajil_local_fallback(): void
    {
        config()->set('ai.ajil.base_url', 'http://ajil.test');
        Http::fake(['ajil.test/v1/chat/completions' => Http::response(['model' => 'local/fallback', 'choices' => [['message' => ['content' => '{}']]]], 200)]);
        $this->expectException(\RuntimeException::class);
        app(AjilGatewayClient::class)->complete('dashboard_operations', [], []);
    }

    public function test_client_accepts_a_fenced_json_object(): void
    {
        config()->set('ai.ajil.base_url', 'http://ajil.test');
        Http::fake(['ajil.test/v1/chat/completions' => Http::response([
            'model' => 'gemini-3.5-flash-lite',
            'choices' => [['message' => ['content' => "```json\n{\"headline\":\"Review today\",\"summary\":\"Open verified records.\"}\n```"]]],
        ], 200)]);

        $result = app(AjilGatewayClient::class)->complete('dashboard_operations', [], []);

        $this->assertSame('Review today', $result['response']['headline']);
    }

    public function test_parallel_race_is_explicitly_configurable(): void
    {
        config()->set('ai.ajil.base_url', 'http://ajil.test');
        config()->set('ai.routing_strategy', 'parallel_race');
        Http::fake(['ajil.test/v1/chat/completions' => Http::response(['choices' => [['message' => ['content' => json_encode(['headline' => 'OK', 'summary' => 'OK'])]]]], 200)]);
        app(AjilGatewayClient::class)->complete('dashboard_operations', [], []);
        Http::assertSent(fn (Request $request) => $request['x_router']['strategy'] === 'parallel_race');
    }
}
