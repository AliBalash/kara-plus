<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class AiHealthCommand extends Command
{
    protected $signature = 'ai:health';

    protected $description = 'Check the private Ajil gateway without exposing credentials or sending prompts.';

    public function handle(): int
    {
        if (! config('ai.enabled')) {
            $this->warn('Kara AI is disabled. Set KARA_AI_ENABLED=true after Ajil is configured.');

            return self::FAILURE;
        }

        try {
            $health = Http::baseUrl(config('ai.ajil.base_url'))
                ->acceptJson()->timeout(config('ai.ajil.connect_timeout') + 2)
                ->get('/health');
        } catch (\Throwable $exception) {
            $this->error('Ajil is unreachable: '.class_basename($exception));

            return self::FAILURE;
        }

        if (! $health->successful()) {
            $this->error('Ajil health endpoint returned HTTP '.$health->status().'.');

            return self::FAILURE;
        }

        $providers = collect($health->json('providers', []))
            ->filter()
            ->keys()
            ->implode(', ');
        $this->info('Ajil is healthy. Enabled providers: '.($providers ?: 'none').'.');

        try {
            $models = Http::baseUrl(config('ai.ajil.base_url'))
                ->acceptJson()
                ->withHeaders(array_filter(['x-api-token' => config('ai.ajil.token')]))
                ->timeout(config('ai.catalog_timeout'))
                // This endpoint gathers providers concurrently and caches the
                // result in Ajil. /v1/models is sequential on a cold cache.
                ->get('/v1/models/catalog?capability=chat.completions&include_raw=false');
            if ($models->successful()) {
                $count = (int) $models->json('count', 0);
                $cache = $models->json('from_cache') ? 'cached' : 'fresh';
                $this->info("Ajil model catalog is reachable ({$count} entries; {$cache}).");
                if ($models->json('fallback_applied')) {
                    $this->warn('Ajil used its static catalog fallback; model availability is not verified live.');
                } else {
                    $available = collect($models->json('items', []))
                        ->map(fn (array $item) => ($item['provider'] ?? '').'/'.($item['id'] ?? ''))
                        ->all();
                    foreach (config('ai.models.default', []) as $candidate) {
                        $key = $candidate['provider'].'/'.$candidate['model'];
                        if (! in_array($key, $available, true)) {
                            $this->warn("Configured model is absent from the current Ajil catalog: {$key}.");
                        }
                    }
                }
            } else {
                $this->warn('Ajil is healthy, but model catalog returned HTTP '.$models->status().'.');
            }
        } catch (\Throwable $exception) {
            $this->warn('Ajil is healthy, but the model catalog was unavailable: '.class_basename($exception).'.');
        }

        return self::SUCCESS;
    }
}
