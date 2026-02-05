<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('contract_balance_transfers')) {
            return;
        }

        Schema::create('contract_balance_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_contract_id')->nullable()->constrained('contracts')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('to_contract_id')->nullable()->constrained('contracts')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('amount', 16, 2);
            $table->string('currency', 3)->default('AED');
            $table->string('reference')->nullable();
            $table->json('meta')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('transferred_at')->nullable();
            $table->timestamps();

            $table->index(['customer_id']);
            $table->index(['from_contract_id']);
            $table->index(['to_contract_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_balance_transfers');
    }
};
