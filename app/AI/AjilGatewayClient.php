<?php

namespace App\AI;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class AjilGatewayClient
{
    public function complete(string $feature, array $facts, array $context): array
    {
        $requestId = (string) Str::uuid();
        $response = Http::baseUrl(config('ai.ajil.base_url'))
            ->acceptJson()->asJson()
            ->withHeaders(array_filter(['x-api-token' => config('ai.ajil.token'), 'x-request-id' => $requestId, 'x-ai-feature' => $feature]))
            ->connectTimeout(config('ai.ajil.connect_timeout'))
            ->timeout(config('ai.ajil.timeout'))
            ->post('/v1/chat/completions', [
                'model' => config('ai.models.default'),
                'messages' => [
                    ['role' => 'system', 'content' => app(PromptRegistry::class)->system($feature)],
                    ['role' => 'user', 'content' => json_encode(['facts' => $facts, 'context' => $context], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)],
                ],
                'temperature' => 0.1,
                'response_format' => ['type' => 'json_object'],
                'x_router' => ['strategy' => config('ai.routing_strategy'), 'mode' => config('ai.routing_mode')],
            ]);
        if (!$response->successful()) throw new RuntimeException('Ajil request failed: '.$response->status());
        if (data_get($response->json(), 'model') === 'local/fallback') {
            throw new RuntimeException('Ajil has no usable upstream provider.');
        }
        $content = data_get($response->json(), 'choices.0.message.content');
        if (!is_string($content)) throw new RuntimeException('Ajil response has no message content.');
        $decoded = json_decode($content, true);
        if (!is_array($decoded)) throw new RuntimeException('Ajil returned malformed JSON.');
        return ['request_id' => $requestId, 'response' => $decoded, 'provider' => data_get($response->json(), 'provider'), 'model' => data_get($response->json(), 'model')];
    }
}
