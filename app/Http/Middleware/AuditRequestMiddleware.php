<?php

namespace App\Http\Middleware;

use App\Services\Audit\AuditLogger;
use App\Support\Audit\AuditContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AuditRequestMiddleware
{
    public function __construct(private readonly AuditContext $auditContext)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (! (bool) config('audit.capture.http_requests', true)) {
            return $next($request);
        }

        $requestId = (string) Str::uuid();
        $sessionId = $request->hasSession() ? $request->session()->getId() : null;

        $this->auditContext->fromRequest([
            'request_id' => $requestId,
            'session_id_hash' => $sessionId ? hash('sha256', $sessionId) : null,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'route_name' => $request->route()?->getName(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
        ]);

        $response = $next($request);

        $statusCode = $response->getStatusCode();
        $this->auditContext->setStatusCode($statusCode);

        $logger = app(AuditLogger::class);

        $logger->log('http_request', [
            'request_id' => $requestId,
            'status_code' => $statusCode,
            'meta' => [
                'query' => $request->query(),
                'input' => $request->except(['password', 'password_confirmation', '_token']),
            ],
        ]);

        if ((bool) config('audit.capture.livewire_calls', true)
            && $request->route()?->getName() === 'livewire.update'
            && $request->isMethod('POST')) {
            $logger->log('livewire_call', [
                'request_id' => $requestId,
                'status_code' => $statusCode,
                'meta' => [
                    'components' => $this->extractLivewireCalls($request->input('components', [])),
                ],
            ]);
        }

        return $response;
    }

    private function extractLivewireCalls(mixed $components): array
    {
        if (! is_array($components)) {
            return [];
        }

        $result = [];

        foreach ($components as $component) {
            if (! is_array($component)) {
                continue;
            }

            $snapshot = $component['snapshot'] ?? null;
            $snapshotDecoded = [];
            if (is_string($snapshot)) {
                $snapshotDecoded = json_decode($snapshot, true) ?: [];
            } elseif (is_array($snapshot)) {
                $snapshotDecoded = $snapshot;
            }

            $calls = [];
            foreach (($component['calls'] ?? []) as $call) {
                if (! is_array($call)) {
                    continue;
                }

                $calls[] = [
                    'method' => $call['method'] ?? null,
                    'params' => $call['params'] ?? [],
                ];
            }

            $result[] = [
                'name' => $snapshotDecoded['memo']['name'] ?? null,
                'id' => $snapshotDecoded['memo']['id'] ?? null,
                'path' => $snapshotDecoded['memo']['path'] ?? null,
                'calls' => $calls,
            ];
        }

        return $result;
    }
}
