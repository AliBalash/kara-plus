<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $missing = array_values(array_filter([
            Schema::hasColumn('contracts', 'original_return_date') ? null : 'original_return_date',
            Schema::hasColumn('contracts', 'actual_pickup_at') ? null : 'actual_pickup_at',
            Schema::hasColumn('contracts', 'actual_return_at') ? null : 'actual_return_at',
        ]));

        if ($missing !== []) {
            Schema::table('contracts', function (Blueprint $table) use ($missing): void {
                if (in_array('original_return_date', $missing, true)) {
                    $table->dateTime('original_return_date')->nullable()->after('return_date');
                }
                if (in_array('actual_pickup_at', $missing, true)) {
                    $table->dateTime('actual_pickup_at')->nullable()->after('original_return_date');
                }
                if (in_array('actual_return_at', $missing, true)) {
                    $table->dateTime('actual_return_at')->nullable()->after('actual_pickup_at');
                }
            });
        }

        DB::table('contracts')
            ->whereNull('original_return_date')
            ->whereNotNull('return_date')
            ->orderBy('id')
            ->chunkById(500, function ($contracts): void {
                DB::table('contracts')
                    ->whereIn('id', $contracts->pluck('id'))
                    ->update(['original_return_date' => DB::raw('return_date')]);
            });
    }

    public function down(): void
    {
        $columns = array_values(array_filter([
            Schema::hasColumn('contracts', 'original_return_date') ? 'original_return_date' : null,
            Schema::hasColumn('contracts', 'actual_pickup_at') ? 'actual_pickup_at' : null,
            Schema::hasColumn('contracts', 'actual_return_at') ? 'actual_return_at' : null,
        ]));

        if ($columns !== []) {
            Schema::table('contracts', fn (Blueprint $table) => $table->dropColumn($columns));
        }
    }
};
