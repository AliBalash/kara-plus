<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('leads') || Schema::hasColumn('leads', 'requested_manufacturing_year')) {
            return;
        }

        Schema::table('leads', function (Blueprint $table) {
            $table->unsignedSmallInteger('requested_manufacturing_year')
                ->nullable()
                ->after('requested_model_id');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('leads') || ! Schema::hasColumn('leads', 'requested_manufacturing_year')) {
            return;
        }

        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn('requested_manufacturing_year');
        });
    }
};
