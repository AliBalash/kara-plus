<?php

namespace Tests\Feature\Audit;

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditModelEventsTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_update_delete_model_events_are_logged_with_diffs(): void
    {
        $customer = Customer::create([
            'first_name' => 'Test',
            'last_name' => 'Customer',
            'national_code' => '1234567890',
            'email' => 'c@example.com',
            'phone' => '+989111111111',
            'messenger_phone' => '+989111111111',
            'address' => 'Address',
            'birth_date' => now()->subYears(20)->toDateString(),
            'passport_number' => 'P1234',
            'passport_expiry_date' => now()->addYear()->toDateString(),
            'nationality' => 'IR',
            'license_number' => 'L1234',
            'status' => 'active',
            'registration_date' => now()->toDateString(),
        ]);

        $customer->update(['first_name' => 'Updated']);
        $customer->delete();

        $this->assertDatabaseHas('audit_events', [
            'action' => 'model_created',
            'entity_type' => Customer::class,
            'entity_id' => (string) $customer->id,
        ]);

        $this->assertDatabaseHas('audit_events', [
            'action' => 'model_updated',
            'entity_type' => Customer::class,
            'entity_id' => (string) $customer->id,
        ]);

        $updateEvent = \App\Models\AuditEvent::where('action', 'model_updated')->latest('id')->first();
        $this->assertContains('first_name', $updateEvent->changed_fields ?? []);

        $this->assertDatabaseHas('audit_events', [
            'action' => 'model_deleted',
            'entity_type' => Customer::class,
            'entity_id' => (string) $customer->id,
        ]);
    }
}
