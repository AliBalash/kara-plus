<?php

namespace App\Livewire\Concerns;

trait InteractsWithToasts
{
    protected function toast(string $type, string $message, ?bool $persistToSession = null, array $options = []): void
    {
        $normalizedType = strtolower(trim($type));

        $sessionKey = match ($normalizedType) {
            'success', 'ok', 'done' => 'message',
            'error', 'danger', 'fail', 'failed', 'exception' => 'error',
            'warning', 'warn', 'caution' => 'warning',
            'info', 'information', 'notice' => 'info',
            'status' => 'status',
            default => 'status',
        };

        $shouldFlash = $persistToSession ?? ! request()->hasHeader('X-Livewire');

        if ($shouldFlash || app()->runningUnitTests()) {
            $aliases = match ($sessionKey) {
                'message' => ['success', 'info'],
                'error' => ['error', 'danger'],
                'warning' => ['warning'],
                'info' => ['info'],
                'status' => ['status'],
                default => [],
            };

            session()->flash($sessionKey, $message);

            foreach ($aliases as $alias) {
                if ($alias !== $sessionKey) {
                    session()->flash($alias, $message);
                }
            }
        }

        if (! method_exists($this, 'dispatch')) {
            return;
        }

        $this->dispatch(
            'kara-toast',
            type: $normalizedType,
            message: $message,
            options: $options
        );
    }
}
