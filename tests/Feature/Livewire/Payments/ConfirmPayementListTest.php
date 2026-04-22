<?php

namespace Tests\Feature\Livewire\Payments;

use App\Livewire\Pages\Panel\Expert\Payments\ConfirmPayementList;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ConfirmPayementListTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('myimage');
    }

    public function test_delete_payment_removes_receipt_file_after_record_deletion(): void
    {
        $payment = Payment::factory()->create([
            'receipt' => 'payments/confirm-list-receipt.webp',
        ]);

        Storage::disk('myimage')->put('payments/confirm-list-receipt.webp', 'receipt');

        Livewire::test(ConfirmPayementList::class)
            ->call('deletePayment', $payment->id);

        $this->assertDatabaseMissing('payments', ['id' => $payment->id]);
        Storage::disk('myimage')->assertMissing('payments/confirm-list-receipt.webp');
    }
}
