<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            if (! Schema::hasColumn('contracts', 'agent_id')) {
                $table->foreignId('agent_id')
                    ->nullable()
                    ->after('car_id')
                    ->constrained('agents')
                    ->nullOnDelete();
            }
        });

        if (Schema::hasColumn('contracts', 'agent_sale')) {
            $this->backfillAgentIdsFromNames();

            Schema::table('contracts', function (Blueprint $table) {
                $table->dropColumn('agent_sale');
            });
        }
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            if (! Schema::hasColumn('contracts', 'agent_sale')) {
                $table->string('agent_sale')->nullable()->after('car_id');
            }
        });

        $this->backfillAgentNamesFromIds();

        Schema::table('contracts', function (Blueprint $table) {
            if (Schema::hasColumn('contracts', 'agent_id')) {
                $table->dropForeign(['agent_id']);
                $table->dropColumn('agent_id');
            }
        });
    }

    private function backfillAgentIdsFromNames(): void
    {
        $existingAgents = DB::table('agents')->pluck('id', 'name');

        $contracts = DB::table('contracts')
            ->select('id', 'agent_sale')
            ->whereNotNull('agent_sale')
            ->get();

        foreach ($contracts as $contract) {
            $name = trim((string) $contract->agent_sale);

            if ($name === '') {
                continue;
            }

            $agentId = $existingAgents[$name] ?? null;

            if (! $agentId) {
                $agentId = DB::table('agents')->insertGetId([
                    'name' => $name,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $existingAgents[$name] = $agentId;
            }

            DB::table('contracts')
                ->where('id', $contract->id)
                ->update(['agent_id' => $agentId]);
        }
    }

    private function backfillAgentNamesFromIds(): void
    {
        if (! Schema::hasColumn('contracts', 'agent_id')) {
            return;
        }

        $agentNames = DB::table('agents')->pluck('name', 'id');

        $contracts = DB::table('contracts')
            ->select('id', 'agent_id')
            ->whereNotNull('agent_id')
            ->get();

        foreach ($contracts as $contract) {
            $name = $agentNames[$contract->agent_id] ?? null;

            if (! $name) {
                continue;
            }

            DB::table('contracts')
                ->where('id', $contract->id)
                ->update(['agent_sale' => $name]);
        }
    }
};
