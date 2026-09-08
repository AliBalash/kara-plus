<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Repair database dumps whose migrations ledger says the original
     * contracts migration ran, but whose table is missing this column.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('contracts', 'communication_channel')) {
            Schema::table('contracts', function (Blueprint $table) {
                $table->string('communication_channel')->nullable()->after('agent_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('contracts', 'communication_channel')) {
            Schema::table('contracts', function (Blueprint $table) {
                $table->dropColumn('communication_channel');
            });
        }
    }
};
