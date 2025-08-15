<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('contract_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('contracts')->onDelete('cascade');
            $table->string('title'); // عنوان هزینه (مثلاً: "صندلی کودک")
            $table->decimal('amount', 10, 2); // مبلغ این آیتم
            $table->text('type')->nullable(); // نوع آیتم
            $table->text('description')->nullable(); // توضیح اختیاری برای توضیح بیشتر
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_charges');
    }
};
