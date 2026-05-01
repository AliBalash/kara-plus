<?php

namespace Tests\Feature\Livewire\Customer;

use App\Livewire\Pages\Panel\Expert\Customer\CustomerDocumentUpload;
use App\Models\Car;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\CustomerDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CustomerDocumentUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('myimage');
    }

    public function test_remove_file_updates_document_record_and_deletes_stored_file(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $customer = Customer::factory()->create();
        $contract = Contract::factory()
            ->for($user)
            ->for($customer)
            ->for(Car::factory())
            ->create();

        Storage::disk('myimage')->put('CustomerDocument/passport-front.pdf', 'front-file');
        Storage::disk('myimage')->put('CustomerDocument/passport-back.pdf', 'back-file');

        $document = CustomerDocument::create([
            'customer_id' => $customer->id,
            'contract_id' => $contract->id,
            'passport' => [
                'front' => 'CustomerDocument/passport-front.pdf',
                'back' => 'CustomerDocument/passport-back.pdf',
            ],
            'hotel_name' => 'City Hotel',
            'hotel_address' => 'Dubai Marina',
        ]);

        $component = app(CustomerDocumentUpload::class);
        $component->mount($customer->id, $contract->id);
        $component->removeFile('passport', 'front');

        $this->assertSame(1, $component->fileInputVersion);

        $this->assertSame([
            'back' => 'CustomerDocument/passport-back.pdf',
        ], $document->fresh()->passport);

        Storage::disk('myimage')->assertMissing('CustomerDocument/passport-front.pdf');
        Storage::disk('myimage')->assertExists('CustomerDocument/passport-back.pdf');
    }
}
