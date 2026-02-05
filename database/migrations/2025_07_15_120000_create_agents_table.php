<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('agents')) {
            Schema::create('agents', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        $agents = config('agents.sales_agents', []);

        if (! empty($agents) && Schema::hasTable('agents')) {
            $timestamp = now();
            $rows = collect($agents)
                ->filter(fn ($name) => is_string($name) && trim($name) !== '')
                ->map(fn ($name) => [
                    'name' => trim($name),
                    'is_active' => true,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ])
                ->values()
                ->all();

            DB::table('agents')->insertOrIgnore($rows);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('agents');
    }
};
