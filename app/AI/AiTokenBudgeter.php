<?php

namespace App\AI;

use Illuminate\Support\Arr;

class AiTokenBudgeter
{
    /**
     * Keep the compact, highest-severity facts first. This is deliberately a
     * semantic budget: no critical fact is removed merely to satisfy bytes.
     */
    public function compact(array $facts, array $context, int $maxFacts = 25, int $maxBytes = 12000): array
    {
        usort($facts, fn (array $a, array $b) => ($b['severity'] ?? 0) <=> ($a['severity'] ?? 0));
        $facts = array_slice($facts, 0, $maxFacts);

        while ($facts !== [] && strlen((string) json_encode(['facts' => $facts, 'context' => $context])) > $maxBytes) {
            array_pop($facts);
        }

        // Context is already a DTO. Drop only optional generated metadata if
        // unusually large data slips through a future context builder.
        $context = Arr::except($context, ['debug', 'raw_notes', 'raw_events']);

        return [$facts, $context];
    }
}
