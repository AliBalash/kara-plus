<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->string('manual_status')->nullable()->after('status');
            $table->string('manual_unavailability_reason')->nullable()->after('manual_status');
            $table->string('unavailability_reason')->nullable()->after('availability');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                "ALTER TABLE `cars` MODIFY COLUMN `status` ENUM('available', 'pre_reserved', 'reserved', 'unavailable', 'under_maintenance', 'sold') NOT NULL DEFAULT 'available'"
            );
        }

        DB::table('cars')
            ->where('status', 'sold')
            ->update([
                'manual_status' => 'sold',
                'manual_unavailability_reason' => null,
                'unavailability_reason' => null,
                'availability' => false,
            ]);

        DB::table('cars')
            ->where('status', 'under_maintenance')
            ->update([
                'status' => 'unavailable',
                'manual_status' => 'unavailable',
                'manual_unavailability_reason' => 'maintenance',
                'unavailability_reason' => 'maintenance',
                'availability' => false,
            ]);

        DB::table('cars')
            ->whereIn('status', ['available', 'pre_reserved'])
            ->where('availability', false)
            ->update([
                'status' => 'unavailable',
                'manual_status' => 'unavailable',
                'manual_unavailability_reason' => 'management_decision',
                'unavailability_reason' => 'management_decision',
            ]);

        DB::table('cars')
            ->whereNull('manual_status')
            ->update([
                'manual_status' => 'available',
                'manual_unavailability_reason' => null,
            ]);
    }

    public function down(): void
    {
        DB::table('cars')
            ->where('status', 'unavailable')
            ->where('unavailability_reason', 'maintenance')
            ->update([
                'status' => 'under_maintenance',
                'availability' => false,
            ]);

        DB::table('cars')
            ->where('status', 'unavailable')
            ->update([
                'status' => 'available',
                'availability' => false,
            ]);

        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                "ALTER TABLE `cars` MODIFY COLUMN `status` ENUM('available', 'pre_reserved', 'reserved', 'under_maintenance', 'sold') NOT NULL DEFAULT 'available'"
            );
        }

        Schema::table('cars', function (Blueprint $table) {
            $table->dropColumn([
                'manual_status',
                'manual_unavailability_reason',
                'unavailability_reason',
            ]);
        });
    }
};
