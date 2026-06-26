<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('leads')) {
            return;
        }

        Schema::table('leads', function (Blueprint $table) {
            if (! Schema::hasColumn('leads', 'requested_brand')) {
                $table->string('requested_brand')->nullable()->after('requested_vehicle');
            }

            if (! Schema::hasColumn('leads', 'requested_model_id')) {
                $table->foreignId('requested_model_id')->nullable()->after('requested_brand')
                    ->constrained('car_models')->nullOnDelete();
            }

            if (! Schema::hasColumn('leads', 'request_date')) {
                $table->date('request_date')->nullable()->after('requested_model_id');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('leads')) {
            return;
        }

        Schema::table('leads', function (Blueprint $table) {
            if (Schema::hasColumn('leads', 'requested_model_id')) {
                $table->dropConstrainedForeignId('requested_model_id');
            }

            if (Schema::hasColumn('leads', 'request_date')) {
                $table->dropColumn('request_date');
            }

            if (Schema::hasColumn('leads', 'requested_brand')) {
                $table->dropColumn('requested_brand');
            }
        });
    }
};
