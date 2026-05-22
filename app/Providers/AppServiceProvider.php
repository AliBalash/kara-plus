<?php

namespace App\Providers;

use App\Observers\AuditModelObserver;
use App\Services\Audit\AuditLogger;
use App\Services\Audit\AuditReportService;
use App\Services\Audit\Contracts\AuditExportContract;
use App\Services\Audit\Contracts\AuditQueryContract;
use App\Services\Audit\Contracts\AuditWriterContract;
use App\Services\Audit\ElasticsearchAuditExporter;
use App\Support\Audit\AuditContext;
use App\Support\Audit\AuditPayloadNormalizer;
use App\Support\Audit\AuditRedactor;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(AuditContext::class, fn () => new AuditContext());
        $this->app->singleton(AuditRedactor::class, fn () => new AuditRedactor());
        $this->app->singleton(AuditPayloadNormalizer::class, fn () => new AuditPayloadNormalizer());
        $this->app->bind(AuditWriterContract::class, AuditLogger::class);
        $this->app->bind(AuditQueryContract::class, AuditReportService::class);
        $this->app->bind(AuditExportContract::class, ElasticsearchAuditExporter::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('reservation-public', function (Request $request) {
            $ip = (string) $request->ip();

            return [
                Limit::perMinute(120)->by('reservation-public:' . $ip),
            ];
        });

        if ((bool) config('audit.capture.model_events', true)) {
            AuditModelObserver::registerForAllModels();
        }
    }
}
