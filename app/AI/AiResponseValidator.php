<?php

namespace App\AI;

use Illuminate\Validation\ValidationException;

class AiResponseValidator
{
    public function validate(array $response, array $facts): array
    {
        foreach (['headline', 'summary'] as $field) {
            if (!isset($response[$field]) || !is_string($response[$field])) {
                throw ValidationException::withMessages([$field => 'Missing AI response field.']);
            }
        }
        $validIds = collect($facts)->pluck('fact_id')->filter()->flip();
        foreach (['critical_alerts', 'watchlist', 'positive_signals', 'data_quality_warnings', 'insufficient_data'] as $field) {
            $items = $response[$field] ?? [];
            if (!is_array($items)) throw ValidationException::withMessages([$field => 'Invalid AI list.']);
            $response[$field] = collect($items)->filter(function ($item) use ($validIds) {
                return is_array($item) && isset($item['fact_id']) && $validIds->has($item['fact_id']);
            })->map(fn ($item) => [
                'fact_id' => (string) $item['fact_id'],
                'title' => trim((string) ($item['title'] ?? 'Review item')),
                'reason' => trim((string) ($item['reason'] ?? 'Verified fact requires review.')),
                'check_now' => trim((string) ($item['check_now'] ?? 'Open evidence.')),
            ])->values()->all();
        }
        return $response;
    }
}
