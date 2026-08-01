<?php

namespace Tests\Feature\Livewire\Lead;

use App\Livewire\Pages\Panel\Expert\Lead\LeadList;
use App\Models\Car;
use App\Models\CarModel;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class LeadListTest extends TestCase
{
    use RefreshDatabase;

    public function test_save_creates_lead_without_customer_record(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $carModel = CarModel::factory()->create([
            'brand' => 'BMW',
            'model' => 'X5',
        ]);
        Car::factory()->available()->create(['car_model_id' => $carModel->id]);

        $this->actingAs($user);

        $component = app(LeadList::class);
        $component->mount();
        $component->first_name = 'Sara';
        $component->last_name = 'Ahmadi';
        $component->phone = '+971501111111';
        $component->source = 'whatsapp';
        $component->discovery_source = 'Google Ads';
        $component->selectedBrand = 'BMW';
        $component->selectedModelId = $carModel->id;
        $component->request_date = '2026-06-26';
        $component->priority = Lead::PRIORITY_HIGH;
        $component->status = Lead::STATUS_FOLLOW_UP;
        $component->save();

        $this->assertDatabaseHas('leads', [
            'first_name' => 'Sara',
            'last_name' => 'Ahmadi',
            'phone' => '+971501111111',
            'source' => 'whatsapp',
            'discovery_source' => 'Google Ads',
            'requested_brand' => 'BMW',
            'requested_model_id' => $carModel->id,
            'priority' => Lead::PRIORITY_HIGH,
            'status' => Lead::STATUS_FOLLOW_UP,
            'created_by' => $user->id,
            'customer_id' => null,
        ]);

        $lead = Lead::query()->firstOrFail();
        $this->assertSame('2026-06-26', $lead->request_date?->format('Y-m-d'));

        $this->assertDatabaseCount('customers', 0);
    }

    public function test_vehicle_options_only_include_reservable_cars_and_deduplicate_models(): void
    {
        $selectableModel = CarModel::factory()->create(['brand' => 'Hyundai', 'model' => 'ACCENT']);
        $duplicateModel = CarModel::factory()->create(['brand' => 'Hyundai', 'model' => 'ACCENT']);
        $unavailableModel = CarModel::factory()->create(['brand' => 'Hyundai', 'model' => 'ELANTRA']);
        CarModel::factory()->create(['brand' => 'Kia', 'model' => 'K5']);

        Car::factory()->available()->create([
            'car_model_id' => $selectableModel->id,
            'manufacturing_year' => 2024,
        ]);
        Car::factory()->available()->create([
            'car_model_id' => $duplicateModel->id,
            'manufacturing_year' => 2023,
        ]);
        Car::factory()->unavailable()->create(['car_model_id' => $unavailableModel->id]);

        $component = $this->leadList();
        $component->selectedBrand = 'Hyundai';
        $viewData = $component->render()->getData();

        $this->assertSame(['Hyundai'], $viewData['brands']->all());
        $this->assertCount(1, $viewData['models']);
        $this->assertSame('ACCENT', $viewData['models']->first()->model);
        $this->assertSame(2024, (int) $viewData['models']->first()->manufacturing_year);
        $this->assertNotContains('Kia', $viewData['brands']->all());
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

    public function test_filters_leads_from_a_request_date(): void
    {
        $before = $this->createLead(['request_date' => '2026-06-01']);
        $from = $this->createLead(['request_date' => '2026-06-15']);
        $after = $this->createLead(['request_date' => '2026-06-30']);

        $component = $this->leadList();
        $component->dateFrom = '2026-06-15';

        $this->assertSame([$after->id, $from->id], $this->renderedLeadIds($component));
        $this->assertNotContains($before->id, $this->renderedLeadIds($component));
    }

    public function test_filters_leads_to_a_request_date(): void
    {
        $before = $this->createLead(['request_date' => '2026-06-01']);
        $to = $this->createLead(['request_date' => '2026-06-15']);
        $after = $this->createLead(['request_date' => '2026-06-30']);

        $component = $this->leadList();
        $component->dateTo = '2026-06-15';

        $this->assertSame([$to->id, $before->id], $this->renderedLeadIds($component));
        $this->assertNotContains($after->id, $this->renderedLeadIds($component));
    }

    public function test_filters_leads_between_request_dates(): void
    {
        $before = $this->createLead(['request_date' => '2026-06-01']);
        $start = $this->createLead(['request_date' => '2026-06-10']);
        $end = $this->createLead(['request_date' => '2026-06-20']);
        $after = $this->createLead(['request_date' => '2026-06-30']);

        $component = $this->leadList();
        $component->dateFrom = '2026-06-10';
        $component->dateTo = '2026-06-20';

        $this->assertSame([$end->id, $start->id], $this->renderedLeadIds($component));
        $this->assertNotContains($before->id, $this->renderedLeadIds($component));
        $this->assertNotContains($after->id, $this->renderedLeadIds($component));
    }

    public function test_sorts_request_dates_ascending_and_descending(): void
    {
        $first = $this->createLead(['request_date' => '2026-06-01']);
        $second = $this->createLead(['request_date' => '2026-06-15']);
        $third = $this->createLead(['request_date' => '2026-06-30']);

        $component = $this->leadList();
        $component->sortField = 'request_date';
        $component->sortDirection = 'asc';
        $this->assertSame([$first->id, $second->id, $third->id], $this->renderedLeadIds($component));

        $component->sortDirection = 'desc';
        $this->assertSame([$third->id, $second->id, $first->id], $this->renderedLeadIds($component));
    }

    public function test_invalid_sort_fields_fall_back_safely(): void
    {
        $this->createLead(['request_date' => '2026-06-01']);

        $component = $this->leadList();
        $component->sortField = 'phone; drop table leads';
        $component->sortDirection = 'sideways';
        $this->renderedLeadIds($component);

        $this->assertSame('updated_at', $component->sortField);
        $this->assertSame('desc', $component->sortDirection);
        $this->assertDatabaseCount('leads', 1);
    }

    public function test_clearing_date_filters_restores_all_leads(): void
    {
        $first = $this->createLead(['request_date' => '2026-06-01']);
        $second = $this->createLead(['request_date' => '2026-06-30']);

        $component = $this->leadList();
        $component->dateFrom = '2026-06-15';
        $this->assertSame([$second->id], $this->renderedLeadIds($component));

        $component->clearDateFilters();
        $this->assertSame('', $component->dateFrom);
        $this->assertSame('', $component->dateTo);
        $this->assertSame([$second->id, $first->id], $this->renderedLeadIds($component));
    }

    public function test_date_filters_and_sorting_are_applied_before_pagination(): void
    {
        foreach (range(1, 12) as $day) {
            $this->createLead(['request_date' => sprintf('2026-06-%02d', $day)]);
        }

        Livewire::test(LeadList::class)
            ->set('dateFrom', '2026-06-02')
            ->set('sortField', 'request_date')
            ->set('sortDirection', 'asc')
            ->call('setPage', 2)
            ->assertViewHas('leads', function ($leads) {
                return $leads->total() === 11
                    && $leads->pluck('request_date')->map->format('Y-m-d')->all() === ['2026-06-12'];
            });
    }

    private function leadList(): LeadList
    {
        $component = app(LeadList::class);
        $component->mount();
        $component->sortField = 'request_date';
        $component->sortDirection = 'desc';

        return $component;
    }

    private function renderedLeadIds(LeadList $component): array
    {
        return $component->render()->getData()['leads']->pluck('id')->all();
    }

    private function createLead(array $attributes = []): Lead
    {
        static $sequence = 0;
        $sequence++;

        return Lead::create(array_merge([
            'first_name' => 'Lead '.$sequence,
            'phone' => '+97150000'.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT),
            'priority' => Lead::PRIORITY_NORMAL,
            'status' => Lead::STATUS_NEW,
        ], $attributes));
    }
}
