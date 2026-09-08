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
                ->get('/v1/models');
            if ($models->successful()) {
                $count = count($models->json('data', $models->json('models', [])) ?: []);
                $this->info("Ajil model catalog is reachable ({$count} entries).");
            } else {
                $this->warn('Ajil is healthy, but model catalog returned HTTP '.$models->status().'.');
            }
        } catch (\Throwable $exception) {
            $this->warn('Ajil is healthy, but the model catalog was unavailable: '.class_basename($exception).'.');
        }

        return self::SUCCESS;
    }
}
