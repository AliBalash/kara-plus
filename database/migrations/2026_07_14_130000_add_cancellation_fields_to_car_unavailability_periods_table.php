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
            if (! Schema::hasColumn('car_unavailability_periods', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('updated_by')->index();
            }

            if (! Schema::hasColumn('car_unavailability_periods', 'cancelled_by')) {
                $table->foreignId('cancelled_by')->nullable()->after('cancelled_at')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('car_unavailability_periods', 'cancellation_note')) {
                $table->text('cancellation_note')->nullable()->after('cancelled_by');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('car_unavailability_periods')) {
            return;
        }

        Schema::table('car_unavailability_periods', function (Blueprint $table) {
            if (Schema::hasColumn('car_unavailability_periods', 'cancelled_by')) {
                $table->dropConstrainedForeignId('cancelled_by');
            }

            $dropColumns = array_values(array_filter([
                Schema::hasColumn('car_unavailability_periods', 'cancelled_at') ? 'cancelled_at' : null,
                Schema::hasColumn('car_unavailability_periods', 'cancellation_note') ? 'cancellation_note' : null,
            ]));

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
