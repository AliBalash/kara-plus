<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('agents')) {
            return;
        }

        Schema::table('agents', function (Blueprint $table) {
            if (! Schema::hasColumn('agents', 'direct_line')) {
                $table->string('direct_line', 50)->nullable()->after('name');
            }

            if (! Schema::hasColumn('agents', 'mobile')) {
                $table->string('mobile', 50)->nullable()->after('direct_line');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('agents')) {
            return;
        }

        Schema::table('agents', function (Blueprint $table) {
            if (Schema::hasColumn('agents', 'mobile')) {
                $table->dropColumn('mobile');
            }

            if (Schema::hasColumn('agents', 'direct_line')) {
                $table->dropColumn('direct_line');
            }
        });
    }
};
