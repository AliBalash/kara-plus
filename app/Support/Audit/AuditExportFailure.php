<?php

namespace App\Support\Audit;

use Throwable;

final class AuditExportFailure
{
    private const RETRYABLE_MARKERS = [
        'cluster_block_exception',
        'read-only-allow-delete',
        'flood-stage watermark',
        'too_many_requests',
        'connection refused',
        'connection reset',
        'could not resolve host',
        'operation timed out',
        'timed out',
        'timeout',
        'temporarily unavailable',
        'server error',
        'service unavailable',
        'bad gateway',
        'gateway timeout',
    ];

    public static function summarize(Throwable $exception): string
    {
        $message = trim(preg_replace('/\s+/', ' ', $exception->getMessage()) ?? $exception->getMessage());

        if ($message === '') {
            return class_basename($exception);
        }

        return mb_substr($message, 0, 1000);
    }

    public static function isRetryableThrowable(Throwable $exception): bool
    {
        return self::isRetryableMessage(self::summarize($exception));
    }

    public static function isRetryableMessage(?string $message): bool
    {
        if (! is_string($message) || trim($message) === '') {
            return false;
        }

        $normalized = strtolower($message);

        foreach (self::RETRYABLE_MARKERS as $marker) {
            if (str_contains($normalized, $marker)) {
                return true;
            }
        }

        if (preg_match('/\b(408|409|425|429|500|502|503|504)\b/', $normalized) === 1) {
            return true;
        }

        return false;
    }
}
