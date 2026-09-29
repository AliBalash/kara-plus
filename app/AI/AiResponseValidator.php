<?php

namespace App\AI;

use Illuminate\Validation\ValidationException;

class AiResponseValidator
{
    public function validate(array $response, array $facts): array
    {
        $clean = [];
        foreach (['headline', 'summary'] as $field) {
            if (! isset($response[$field]) || ! is_string($response[$field]) || trim($response[$field]) === '') {
                throw ValidationException::withMessages([$field => 'Missing AI response field.']);
            }
            $clean[$field] = mb_substr(trim($response[$field]), 0, $field === 'headline' ? 120 : 700);
        }
        $validIds = collect($facts)->pluck('fact_id')->filter()->flip();
        foreach (['critical_alerts', 'watchlist', 'positive_signals', 'data_quality_warnings', 'insufficient_data'] as $field) {
            $items = $response[$field] ?? [];
            if (! is_array($items)) {
                throw ValidationException::withMessages([$field => 'Invalid AI list.']);
            }
            $clean[$field] = collect($items)->filter(function ($item) use ($validIds) {
                return is_array($item) && is_string($item['fact_id'] ?? null) && $validIds->has($item['fact_id']);
            })->map(fn ($item) => [
                'fact_id' => (string) $item['fact_id'],
                'title' => mb_substr(trim((string) ($item['title'] ?? 'Review item')), 0, 100),
                'reason' => mb_substr(trim((string) ($item['reason'] ?? 'Verified fact requires review.')), 0, 300),
                'check_now' => mb_substr(trim((string) ($item['check_now'] ?? 'Open evidence.')), 0, 100),
            ])->take(10)->values()->all();
        }

        return $clean;
    }
}
