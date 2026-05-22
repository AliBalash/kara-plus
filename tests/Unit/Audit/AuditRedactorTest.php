<?php

namespace Tests\Unit\Audit;

use App\Support\Audit\AuditRedactor;
use Tests\TestCase;

class AuditRedactorTest extends TestCase
{
    public function test_redacts_configured_secret_fields_recursively(): void
    {
        $redactor = new AuditRedactor();

        $input = [
            'password' => 'secret',
            'nested' => [
                'token' => 'abc',
                'safe' => 'ok',
            ],
        ];

        $result = $redactor->redact($input);

        $this->assertSame('[REDACTED]', $result['password']);
        $this->assertSame('[REDACTED]', $result['nested']['token']);
        $this->assertSame('ok', $result['nested']['safe']);
    }
}
