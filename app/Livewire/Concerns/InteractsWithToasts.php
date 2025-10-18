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

        if ($shouldFlash) {
            session()->flash($sessionKey, $message);
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
