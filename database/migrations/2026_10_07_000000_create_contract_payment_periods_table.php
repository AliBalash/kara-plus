<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_payment_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->string('title', 100)->nullable();
            $table->date('starts_on');
            $table->date('ends_on'); // Exclusive: this date belongs to the next period.
            $table->boolean('is_default')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('archived_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['contract_id', 'deleted_at', 'starts_on'], 'contract_payment_periods_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_payment_periods');
    }
};
