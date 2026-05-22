<?php

namespace App\Support\Audit;

class AuditContext
{
    private ?string $requestId = null;
    private ?string $sessionIdHash = null;
    private ?string $ip = null;
    private ?string $userAgent = null;
    private ?string $routeName = null;
    private ?string $method = null;
    private ?string $url = null;
    private ?int $statusCode = null;

    public function fromRequest(array $context): void
    {
        $this->requestId = $context['request_id'] ?? null;
        $this->sessionIdHash = $context['session_id_hash'] ?? null;
        $this->ip = $context['ip'] ?? null;
        $this->userAgent = $context['user_agent'] ?? null;
        $this->routeName = $context['route_name'] ?? null;
        $this->method = $context['method'] ?? null;
        $this->url = $context['url'] ?? null;
    }

    public function setStatusCode(?int $statusCode): void
    {
        $this->statusCode = $statusCode;
    }

    public function toArray(): array
    {
        return [
            'request_id' => $this->requestId,
            'session_id_hash' => $this->sessionIdHash,
            'ip' => $this->ip,
            'user_agent' => $this->userAgent,
            'route_name' => $this->routeName,
            'method' => $this->method,
            'url' => $this->url,
            'status_code' => $this->statusCode,
        ];
    }
}
