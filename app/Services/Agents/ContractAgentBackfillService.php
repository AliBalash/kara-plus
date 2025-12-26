<?php

namespace App\Services\Agents;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class ContractAgentBackfillService
{
    public function run(): array
    {
        if (! Schema::hasTable('contracts')) {
            throw new RuntimeException('Contracts table does not exist.');
        }

        if (! Schema::hasColumn('contracts', 'agent_id')) {
            throw new RuntimeException('contracts.agent_id is missing. Run migrations first.');
        }

        if (! Schema::hasColumn('contracts', 'agent_sale')) {
            return [
                'updated_contracts' => 0,
                'created_agents' => 0,
                'message' => 'contracts.agent_sale column is missing; nothing to backfill.',
            ];
        }

        $existingAgents = DB::table('agents')->pluck('id', 'name');
        $contracts = DB::table('contracts')
            ->select('id', 'agent_sale')
            ->whereNotNull('agent_sale')
            ->get();

        $updatedContracts = 0;
        $createdAgents = 0;

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
                $createdAgents++;
            }

            DB::table('contracts')
                ->where('id', $contract->id)
                ->update(['agent_id' => $agentId]);

            $updatedContracts++;
        }

        return [
            'updated_contracts' => $updatedContracts,
            'created_agents' => $createdAgents,
        ];
    }
}
