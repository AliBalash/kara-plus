<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;

trait CreatesApplication
{
    /**
     * Boot the application for testing.
     */
    public function createApplication()
    {
        $this->forceTestingEnvironment();

        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    private function forceTestingEnvironment(): void
    {
        $values = [
            'APP_ENV' => 'testing',
            'APP_KEY' => 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=',
            'APP_DEBUG' => 'true',
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => ':memory:',
            'CACHE_STORE' => 'array',
            'QUEUE_CONNECTION' => 'sync',
            'SESSION_DRIVER' => 'array',
            'MAIL_MAILER' => 'array',
            'PULSE_ENABLED' => 'false',
            'TELESCOPE_ENABLED' => 'false',
        ];

        foreach ($values as $key => $value) {
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}
