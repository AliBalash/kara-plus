<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('car_unavailability_periods')) {
            return;
        }

        Schema::table('car_unavailability_periods', function (Blueprint $table) {
            if (! Schema::hasColumn('car_unavailability_periods', 'resolved_at')) {
                $table->timestamp('resolved_at')->nullable()->after('cancellation_note')->index();
            }

            if (! Schema::hasColumn('car_unavailability_periods', 'resolved_by')) {
                $table->foreignId('resolved_by')->nullable()->after('resolved_at')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('car_unavailability_periods', 'resolution_note')) {
                $table->text('resolution_note')->nullable()->after('resolved_by');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('car_unavailability_periods')) {
            return;
        }

        Schema::table('car_unavailability_periods', function (Blueprint $table) {
            if (Schema::hasColumn('car_unavailability_periods', 'resolved_by')) {
                $table->dropConstrainedForeignId('resolved_by');
            }

            $dropColumns = array_values(array_filter([
                Schema::hasColumn('car_unavailability_periods', 'resolved_at') ? 'resolved_at' : null,
                Schema::hasColumn('car_unavailability_periods', 'resolution_note') ? 'resolution_note' : null,
            ]));

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
