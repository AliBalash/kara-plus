<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('leads') || Schema::hasColumn('leads', 'discovery_source')) {
            return;
        }

        Schema::table('leads', function (Blueprint $table) {
            $table->string('discovery_source')->nullable()->after('source');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('leads') || ! Schema::hasColumn('leads', 'discovery_source')) {
            return;
        }

        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn('discovery_source');
        });
    }
};
