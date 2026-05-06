<?php

namespace Tests\Unit;

use App\Models\Contract;
use PHPUnit\Framework\TestCase;

class ContractCommunicationChannelTest extends TestCase
{
    public function test_it_normalizes_legacy_invigo_values_to_invygo(): void
    {
        $contract = new Contract();
        $contract->communication_channel = 'invigo';

        $this->assertSame('invygo', $contract->communication_channel);
        $this->assertSame('Invygo', Contract::communicationChannelLabel('invigo'));
    }
}
