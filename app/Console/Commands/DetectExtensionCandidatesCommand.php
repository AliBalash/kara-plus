<?php

namespace App\Console\Commands;

use App\Models\Contract;
use Carbon\Carbon;
use Illuminate\Console\Command;

class DetectExtensionCandidatesCommand extends Command
{
    protected $signature = 'contracts:detect-extension-candidates {--days=1 : Maximum gap between the first planned return and following pickup}';

    protected $description = 'Report possible legacy extension contracts without changing any data.';

    public function handle(): int
    {
        $gap = max(0, (int) $this->option('days'));
        $rows = [];

        foreach (Contract::query()->whereNotNull('return_date')->lazyById(500) as $first) {
            $returnAt = Carbon::parse($first->return_date);
            $candidate = Contract::query()
                ->where('customer_id', $first->customer_id)
                ->where('car_id', $first->car_id)
                ->where('id', '>', $first->id)
                ->whereNotIn('current_status', ['cancelled', 'rejected'])
                ->whereNotNull('pickup_date')
                ->whereBetween('pickup_date', [$returnAt->copy()->subDays($gap), $returnAt->copy()->addDays($gap)])
                ->orderBy('pickup_date')
                ->orderBy('id')
                ->first();

            if ($candidate) {
                $rows[] = [$first->id, $candidate->id, $first->customer_id, $first->car_id, $first->return_date, $candidate->pickup_date];
            }
        }

        usort($rows, fn (array $left, array $right): int => $left[0] <=> $right[0]);
        $this->table(['Original contract', 'Possible extension', 'Customer', 'Vehicle', 'Original return', 'Following pickup'], $rows);
        $this->info(count($rows).' candidate(s) found. This command does not merge or alter contracts.');

        return self::SUCCESS;
    }
}
