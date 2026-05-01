<?php

namespace Tests\Feature\Livewire\Lead;

use App\Livewire\Pages\Panel\Expert\Lead\LeadList;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LeadListTest extends TestCase
{
    use RefreshDatabase;

    public function test_save_creates_lead_without_customer_record(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $this->actingAs($user);

        $component = app(LeadList::class);
        $component->mount();
        $component->first_name = 'Sara';
        $component->last_name = 'Ahmadi';
        $component->phone = '+971501111111';
        $component->source = 'WhatsApp';
        $component->discovery_source = 'Google Ads';
        $component->requested_vehicle = 'BMW X5';
        $component->priority = Lead::PRIORITY_HIGH;
        $component->status = Lead::STATUS_FOLLOW_UP;
        $component->save();

        $this->assertDatabaseHas('leads', [
            'first_name' => 'Sara',
            'last_name' => 'Ahmadi',
            'phone' => '+971501111111',
            'source' => 'WhatsApp',
            'discovery_source' => 'Google Ads',
            'requested_vehicle' => 'BMW X5',
            'priority' => Lead::PRIORITY_HIGH,
            'status' => Lead::STATUS_FOLLOW_UP,
            'created_by' => $user->id,
            'customer_id' => null,
        ]);

        $this->assertDatabaseCount('customers', 0);
    }

    public function test_convert_to_customer_creates_customer_and_marks_lead_converted(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $lead = Lead::create([
            'first_name' => 'Ali',
            'last_name' => 'Karimi',
            'phone' => '+971502222222',
            'messenger_phone' => '+971502222222',
            'email' => 'ali@example.com',
            'priority' => Lead::PRIORITY_NORMAL,
            'status' => Lead::STATUS_INTERESTED,
            'created_by' => $user->id,
        ]);

        $this->actingAs($user);

        $component = app(LeadList::class);
        $component->mount();
        $component->prepareConversion($lead->id);
        $component->convertToCustomer();

        $lead->refresh();

        $this->assertDatabaseHas('customers', [
            'first_name' => 'Ali',
            'last_name' => 'Karimi',
            'email' => 'ali@example.com',
            'phone' => '+971502222222',
            'messenger_phone' => '+971502222222',
            'status' => 'active',
        ]);

        $this->assertSame(Lead::STATUS_CONVERTED, $lead->status);
        $this->assertNotNull($lead->customer_id);
        $this->assertSame($user->id, $lead->converted_by);
        $this->assertNotNull($lead->converted_at);
    }

    public function test_render_does_not_fail_when_leads_table_is_missing(): void
    {
        User::factory()->create(['status' => 'active']);
        Schema::dropIfExists('leads');

        $component = app(LeadList::class);
        $component->mount();

        $view = $component->render();

        $this->assertFalse($view->getData()['databaseReady']);
        $this->assertSame(0, $view->getData()['summary']['total']);
    }

    public function test_save_returns_clear_validation_message_for_missing_phone(): void
    {
        $this->actingAs(User::factory()->create(['status' => 'active']));

        $component = app(LeadList::class);
        $component->mount();

        try {
            $component->save();
            $this->fail('Expected validation exception was not thrown.');
        } catch (ValidationException $exception) {
            $this->assertSame('Phone number is required.', $exception->validator->errors()->first('phone'));
        }
    }

    public function test_convert_returns_clear_validation_message_for_duplicate_customer_email(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        Customer::factory()->create(['email' => 'duplicate@example.com']);
        $lead = Lead::create([
            'first_name' => 'Reza',
            'last_name' => 'Moradi',
            'phone' => '+971503333333',
            'messenger_phone' => '+971503333333',
            'email' => 'duplicate@example.com',
            'priority' => Lead::PRIORITY_NORMAL,
            'status' => Lead::STATUS_INTERESTED,
            'created_by' => $user->id,
        ]);

        $this->actingAs($user);

        $component = app(LeadList::class);
        $component->mount();
        $component->prepareConversion($lead->id);

        try {
            $component->convertToCustomer();
            $this->fail('Expected validation exception was not thrown.');
        } catch (ValidationException $exception) {
            $this->assertSame('A customer with this email already exists.', $exception->validator->errors()->first('email'));
        }
    }
}
