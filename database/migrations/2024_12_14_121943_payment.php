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
        Schema::create('payments', function (Blueprint $table) {
            $table->id(); // شناسه پرداخت
            $table->foreignId('contract_id')->constrained('contracts')->onDelete('cascade'); // ارجاع به قرارداد مرتبط
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade'); // ارجاع به مشتری مرتبط
            $table->decimal('amount', 10, 2); // مبلغ پرداخت‌شده
            $table->enum('payment_type', ['rental_fee', 'fine'])->default('rental_fee'); // نوع پرداخت (هزینه اجاره یا جریمه)
            $table->date('payment_date'); // تاریخ پرداخت
            $table->timestamps(); // زمان‌های ایجاد و ویرایش
        });
        
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');

    }
};
