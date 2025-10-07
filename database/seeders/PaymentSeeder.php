<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Payment;
use App\Models\Contract;

class PaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Payment::create([
            'contract_id' => Contract::first()->id, // ارجاع به اولین قرارداد
            'amount' => 700.00,
            'payment_type' => 'rental_fee',
            'payment_date' => now(),
            'approval_status' => 'approved',
            'is_paid' => true,
        ]);
    }
}
