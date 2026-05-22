<?php

namespace App\Console\Commands;

use App\Models\AuditEvent;
use Illuminate\Console\Command;

class AuditPruneCommand extends Command
{
    protected $signature = 'audit:prune {--days=}';

    protected $description = 'Prune canonical audit events older than configured retention';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?: config('audit.retention_days', 180));
        $cutoff = now()->subDays($days);

        $deleted = AuditEvent::query()->where('occurred_at', '<', $cutoff)->delete();

        $this->info("Pruned {$deleted} audit events older than {$cutoff->toDateTimeString()}.");

        return self::SUCCESS;
    }
}
