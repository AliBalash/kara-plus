<?php

namespace App\Console\Commands;

use App\Models\Car;
use Illuminate\Console\Command;

class SyncCarOperationalStatusCommand extends Command
{
    protected $signature = 'cars:sync-operational-status {--chunk=200}';

    protected $description = 'Sync operational car statuses from manual state and reservation timelines.';

    public function handle(): int
    {
        if (! Car::hasStatusSupportColumn('manual_status')) {
            $this->warn('Skipping sync because manual car status columns are not available yet.');

            return self::SUCCESS;
        }

        $chunkSize = max((int) $this->option('chunk'), 1);
        $processed = 0;
        $updated = 0;

        Car::query()
            ->where(function ($builder) {
                $builder->whereNull('manual_status')
                    ->orWhere('manual_status', '!=', Car::MANUAL_STATUS_AVAILABLE)
                    ->orWhere('availability', false)
                    ->orWhereIn('status', [
                        Car::STATUS_RESERVED,
                        Car::STATUS_PRE_RESERVED,
                        Car::STATUS_UNAVAILABLE,
                        Car::LEGACY_STATUS_UNDER_MAINTENANCE,
                    ])
                    ->orWhereHas('contracts', function ($contractBuilder) {
                        $contractBuilder->whereIn('current_status', Car::reservingStatuses());
                    });
            })
            ->orderBy('id')
            ->chunkById($chunkSize, function ($cars) use (&$processed, &$updated) {
                foreach ($cars as $car) {
                    $processed++;

                    if ($car->syncOperationalState()) {
                        $updated++;
                    }
                }
            });

        $this->info("Processed {$processed} cars; updated {$updated}.");

        return self::SUCCESS;
    }
}
