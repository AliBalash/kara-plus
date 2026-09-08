<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('contract_amendments')) {
            return;
        }

        Schema::create('contract_amendments', function (Blueprint $table): void {
            $table->id();
            // Business history must prevent accidental hard deletion of its
            // aggregate root. Contracts are cancelled, never erased.
            $table->foreignId('contract_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('sequence_no');
            $table->string('type', 32);
            $table->string('status', 32)->default('draft');
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('requested_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->dateTime('effective_at')->nullable();
            $table->dateTime('old_return_at')->nullable();
            $table->dateTime('new_return_at')->nullable();
            $table->dateTime('extension_start_at')->nullable();
            $table->dateTime('extension_end_at')->nullable();
            $table->string('currency', 8)->default('AED');
            $table->string('pricing_policy', 64)->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->json('before_snapshot')->nullable();
            $table->json('after_snapshot')->nullable();
            $table->json('pricing_snapshot')->nullable();
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
            $table->uuid('idempotency_key')->unique();
            $table->timestamps();
            $table->unique(['contract_id', 'sequence_no'], 'contract_amendments_contract_sequence_unique');
            $table->index(['contract_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_amendments');
    }
};
