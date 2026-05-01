<?php

namespace App\Livewire\Pages\Panel\Expert\Lead;

use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class LeadList extends Component
{
    use WithPagination;
    use InteractsWithToasts;

    public string $search = '';
    public string $searchInput = '';
    public string $statusFilter = '';
    public string $priorityFilter = '';

    public ?int $editingId = null;
    public ?int $convertingId = null;

    public ?string $first_name = '';
    public ?string $last_name = '';
    public string $phone = '';
    public ?string $messenger_phone = '';
    public ?string $email = '';
    public ?string $source = '';
    public ?string $discovery_source = '';
    public ?string $requested_vehicle = '';
    public ?string $pickup_date = '';
    public ?string $return_date = '';
    public string $priority = Lead::PRIORITY_NORMAL;
    public string $status = Lead::STATUS_NEW;
    public $assigned_to = null;
    public ?string $next_follow_up_at = '';
    public ?string $last_contacted_at = '';
    public ?string $notes = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'priorityFilter' => ['except' => ''],
    ];

    public function mount(): void
    {
        $this->searchInput = $this->search;
    }

    public function updatedSearchInput(): void
    {
        $this->applySearch();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPriorityFilter(): void
    {
        $this->resetPage();
    }

    public function applySearch(): void
    {
        $this->search = trim($this->searchInput);
        $this->resetPage();
    }

    public function resetForm(): void
    {
        $this->reset([
            'editingId',
            'convertingId',
            'first_name',
            'last_name',
            'phone',
            'messenger_phone',
            'email',
            'source',
            'discovery_source',
            'requested_vehicle',
            'pickup_date',
            'return_date',
            'assigned_to',
            'next_follow_up_at',
            'last_contacted_at',
            'notes',
        ]);

        $this->priority = Lead::PRIORITY_NORMAL;
        $this->status = Lead::STATUS_NEW;
        $this->resetValidation();
    }

    public function edit(int $id): void
    {
        $lead = Lead::findOrFail($id);

        $this->editingId = $lead->id;
        $this->convertingId = null;
        $this->first_name = $lead->first_name;
        $this->last_name = $lead->last_name;
        $this->phone = $lead->phone;
        $this->messenger_phone = $lead->messenger_phone;
        $this->email = $lead->email;
        $this->source = $lead->source;
        $this->discovery_source = $lead->discovery_source;
        $this->requested_vehicle = $lead->requested_vehicle;
        $this->pickup_date = $lead->pickup_date?->format('Y-m-d') ?? '';
        $this->return_date = $lead->return_date?->format('Y-m-d') ?? '';
        $this->priority = $lead->priority;
        $this->status = $lead->status;
        $this->assigned_to = $lead->assigned_to;
        $this->next_follow_up_at = $lead->next_follow_up_at?->format('Y-m-d\TH:i') ?? '';
        $this->last_contacted_at = $lead->last_contacted_at?->format('Y-m-d\TH:i') ?? '';
        $this->notes = $lead->notes;
        $this->resetValidation();
    }

    public function prepareConversion(int $id): void
    {
        $this->edit($id);
        $this->convertingId = $id;
    }

    public function save(): void
    {
        if (! $this->leadsTableExists()) {
            $this->toast('error', 'Lead could not be saved right now. Please try again later.', false);
            return;
        }

        $this->trimFormFields();

        $validated = $this->validateLeadForm();
        $validated = $this->normalizeLeadData($validated);

        $lead = $this->editingId ? Lead::findOrFail($this->editingId) : null;

        if (($validated['status'] ?? null) === Lead::STATUS_CONVERTED && ! $lead?->isConverted()) {
            $this->toast('error', 'Use Create Customer to convert a lead.', false);
            return;
        }

        if ($lead) {
            if ($lead->isConverted()) {
                unset($validated['status']);
            }

            $lead->update($validated);
            $this->toast('success', 'Lead updated successfully.');
        } else {
            $validated['created_by'] = auth()->id();
            Lead::create($validated);
            $this->toast('success', 'Lead created successfully.');
        }

        $this->resetForm();
        $this->resetPage();
    }

    public function convertToCustomer(): void
    {
        if (! $this->leadsTableExists()) {
            $this->toast('error', 'Lead could not be converted right now. Please try again later.', false);
            return;
        }

        if (! $this->convertingId) {
            return;
        }

        $lead = Lead::findOrFail($this->convertingId);

        if ($lead->isConverted()) {
            $this->toast('warning', 'This lead is already converted.');
            $this->resetForm();
            return;
        }

        $this->trimFormFields();

        $validated = $this->validateConversionForm();

        DB::transaction(function () use ($lead, $validated) {
            $customer = Customer::create([
                'first_name' => trim($validated['first_name']),
                'last_name' => trim($validated['last_name']),
                'email' => trim($validated['email']),
                'phone' => trim($validated['phone']),
                'messenger_phone' => trim($validated['messenger_phone']),
                'gender' => 'other',
                'status' => 'active',
                'registration_date' => now()->toDateString(),
            ]);

            $lead->update([
                'first_name' => trim($validated['first_name']),
                'last_name' => trim($validated['last_name']),
                'email' => trim($validated['email']),
                'phone' => trim($validated['phone']),
                'messenger_phone' => trim($validated['messenger_phone']),
                'status' => Lead::STATUS_CONVERTED,
                'customer_id' => $customer->id,
                'converted_by' => auth()->id(),
                'converted_at' => now(),
            ]);
        });

        $this->toast('success', 'Lead converted to customer successfully.');
        $this->resetForm();
        $this->resetPage();
    }

    protected function leadRules(): array
    {
        return [
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['required', 'string', 'min:7', 'max:50', 'regex:/^[0-9+()\-\s]+$/'],
            'messenger_phone' => ['nullable', 'string', 'min:7', 'max:50', 'regex:/^[0-9+()\-\s]+$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'source' => ['nullable', 'string', 'max:100'],
            'discovery_source' => ['nullable', 'string', 'max:255'],
            'requested_vehicle' => ['nullable', 'string', 'max:255'],
            'pickup_date' => ['nullable', 'date'],
            'return_date' => ['nullable', 'date', 'after_or_equal:pickup_date'],
            'priority' => ['required', Rule::in(array_keys(Lead::priorities()))],
            'status' => ['required', Rule::in(array_keys(Lead::statuses()))],
            'assigned_to' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'next_follow_up_at' => ['nullable', 'date'],
            'last_contacted_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    protected function conversionRules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'min:7', 'max:50', 'regex:/^[0-9+()\-\s]+$/'],
            'messenger_phone' => ['required', 'string', 'min:7', 'max:50', 'regex:/^[0-9+()\-\s]+$/'],
            'email' => ['required', 'email', 'max:255', Rule::unique('customers', 'email')],
        ];
    }

    protected function validateLeadForm(): array
    {
        $validated = $this->validate(
            $this->leadRules(),
            $this->validationMessages(),
            $this->validationAttributes()
        );

        return $this->normalizePhoneFields($validated, false);
    }

    protected function validateConversionForm(): array
    {
        $validated = $this->validate(
            $this->conversionRules(),
            $this->validationMessages(),
            $this->validationAttributes()
        );

        return $this->normalizePhoneFields($validated, true);
    }

    protected function normalizePhoneFields(array $validated, bool $messengerRequired): array
    {
        $normalizedPhone = PhoneNumber::normalize($validated['phone'] ?? null);

        if (! $normalizedPhone) {
            $this->addError('phone', 'Enter a valid phone number.');
        } else {
            $validated['phone'] = $normalizedPhone;
            $this->phone = $normalizedPhone;
        }

        $messengerPhone = $validated['messenger_phone'] ?? null;

        if ($messengerPhone !== null && $messengerPhone !== '') {
            $normalizedMessengerPhone = PhoneNumber::normalize($messengerPhone);

            if (! $normalizedMessengerPhone) {
                $this->addError('messenger_phone', 'Enter a valid messenger phone number.');
            } else {
                $validated['messenger_phone'] = $normalizedMessengerPhone;
                $this->messenger_phone = $normalizedMessengerPhone;
            }
        } elseif ($messengerRequired) {
            $this->addError('messenger_phone', 'Messenger phone is required to create a customer.');
        }

        if ($this->getErrorBag()->has('phone') || $this->getErrorBag()->has('messenger_phone')) {
            $this->dispatch('kara-scroll-to-error', field: $this->getErrorBag()->has('phone') ? 'phone' : 'messenger_phone');
            throw \Illuminate\Validation\ValidationException::withMessages($this->getErrorBag()->toArray());
        }

        return $validated;
    }

    protected function validationMessages(): array
    {
        return [
            'first_name.required' => 'First name is required to create a customer.',
            'first_name.max' => 'First name cannot be longer than 255 characters.',
            'last_name.required' => 'Last name is required to create a customer.',
            'last_name.max' => 'Last name cannot be longer than 255 characters.',
            'phone.required' => 'Phone number is required.',
            'phone.min' => 'Phone number is too short.',
            'phone.max' => 'Phone number cannot be longer than 50 characters.',
            'phone.regex' => 'Phone number may only contain digits, spaces, +, -, and parentheses.',
            'messenger_phone.required' => 'Messenger phone is required to create a customer.',
            'messenger_phone.min' => 'Messenger phone number is too short.',
            'messenger_phone.max' => 'Messenger phone number cannot be longer than 50 characters.',
            'messenger_phone.regex' => 'Messenger phone may only contain digits, spaces, +, -, and parentheses.',
            'email.required' => 'Email is required to create a customer.',
            'email.email' => 'Enter a valid email address.',
            'email.max' => 'Email cannot be longer than 255 characters.',
            'email.unique' => 'A customer with this email already exists.',
            'source.max' => 'Contact channel cannot be longer than 100 characters.',
            'discovery_source.max' => 'How found us cannot be longer than 255 characters.',
            'requested_vehicle.max' => 'Requested vehicle cannot be longer than 255 characters.',
            'pickup_date.date' => 'Pickup date is not valid.',
            'return_date.date' => 'Return date is not valid.',
            'return_date.after_or_equal' => 'Return date cannot be before pickup date.',
            'priority.required' => 'Priority is required.',
            'priority.in' => 'Selected priority is not valid.',
            'status.required' => 'Status is required.',
            'status.in' => 'Selected status is not valid.',
            'assigned_to.integer' => 'Selected owner is not valid.',
            'assigned_to.exists' => 'Selected owner does not exist.',
            'next_follow_up_at.date' => 'Next follow-up date is not valid.',
            'last_contacted_at.date' => 'Last contact date is not valid.',
            'notes.max' => 'Notes cannot be longer than 5000 characters.',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'first_name' => 'first name',
            'last_name' => 'last name',
            'phone' => 'phone number',
            'messenger_phone' => 'messenger phone',
            'email' => 'email',
            'source' => 'contact channel',
            'discovery_source' => 'how found us',
            'requested_vehicle' => 'requested vehicle',
            'pickup_date' => 'pickup date',
            'return_date' => 'return date',
            'priority' => 'priority',
            'status' => 'status',
            'assigned_to' => 'owner',
            'next_follow_up_at' => 'next follow-up',
            'last_contacted_at' => 'last contact',
            'notes' => 'notes',
        ];
    }

    protected function normalizeLeadData(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = trim($value);
            }
        }

        foreach (['first_name', 'last_name', 'messenger_phone', 'email', 'source', 'discovery_source', 'requested_vehicle', 'pickup_date', 'return_date', 'next_follow_up_at', 'last_contacted_at', 'notes'] as $key) {
            if (($data[$key] ?? null) === '') {
                $data[$key] = null;
            }
        }

        if (($data['assigned_to'] ?? null) === '') {
            $data['assigned_to'] = null;
        }

        return $data;
    }

    protected function trimFormFields(): void
    {
        foreach ([
            'first_name',
            'last_name',
            'phone',
            'messenger_phone',
            'email',
            'source',
            'discovery_source',
            'requested_vehicle',
            'pickup_date',
            'return_date',
            'next_follow_up_at',
            'last_contacted_at',
            'notes',
        ] as $property) {
            if (is_string($this->{$property})) {
                $this->{$property} = trim($this->{$property});
            }
        }
    }

    protected function leadsTableExists(): bool
    {
        return Schema::hasTable('leads');
    }

    public function render()
    {
        if (! $this->leadsTableExists()) {
            return view('livewire.pages.panel.expert.lead.lead-list', [
                'leads' => new LengthAwarePaginator([], 0, 10),
                'summary' => [
                    'total' => 0,
                    'open' => 0,
                    'due' => 0,
                    'converted' => 0,
                ],
                'statuses' => Lead::statuses(),
                'priorities' => Lead::priorities(),
                'users' => User::query()->where('status', 'active')->orderBy('first_name')->orderBy('last_name')->get(),
                'databaseReady' => false,
            ]);
        }

        $search = trim($this->search);
        $likeSearch = '%' . $search . '%';

        $leads = Lead::query()
            ->with(['customer', 'assignedUser'])
            ->when($search !== '', function ($query) use ($likeSearch) {
                $query->where(function ($scoped) use ($likeSearch) {
                    $scoped->where('first_name', 'like', $likeSearch)
                        ->orWhere('last_name', 'like', $likeSearch)
                        ->orWhere('phone', 'like', $likeSearch)
                        ->orWhere('messenger_phone', 'like', $likeSearch)
                        ->orWhere('email', 'like', $likeSearch)
                        ->orWhere('source', 'like', $likeSearch)
                        ->orWhere('discovery_source', 'like', $likeSearch)
                        ->orWhere('requested_vehicle', 'like', $likeSearch)
                        ->orWhere('notes', 'like', $likeSearch);
                });
            })
            ->when($this->statusFilter !== '', fn ($query) => $query->where('status', $this->statusFilter))
            ->when($this->priorityFilter !== '', fn ($query) => $query->where('priority', $this->priorityFilter))
            ->latest('updated_at')
            ->paginate(10);

        $summary = [
            'total' => Lead::count(),
            'open' => Lead::where('status', '!=', Lead::STATUS_CONVERTED)->count(),
            'due' => Lead::where('status', '!=', Lead::STATUS_CONVERTED)
                ->whereNotNull('next_follow_up_at')
                ->where('next_follow_up_at', '<=', now())
                ->count(),
            'converted' => Lead::where('status', Lead::STATUS_CONVERTED)->count(),
        ];

        return view('livewire.pages.panel.expert.lead.lead-list', [
            'leads' => $leads,
            'summary' => $summary,
            'statuses' => Lead::statuses(),
            'priorities' => Lead::priorities(),
            'users' => User::query()->where('status', 'active')->orderBy('first_name')->orderBy('last_name')->get(),
            'databaseReady' => true,
        ]);
    }
}
