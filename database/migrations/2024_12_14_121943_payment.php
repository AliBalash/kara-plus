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
            $table->foreignId('contract_id')->nullable()->constrained('contracts')->onDelete('cascade'); // ارجاع به قرارداد (در صورت وجود)
            $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('cascade'); // ارجاع به مشتری
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade'); // ارجاع به جدول کاربران (کارشناس)

            $table->foreignId('car_id')->nullable()->constrained('cars')->onDelete('cascade'); // در صورت نیاز به ارتباط با خودرو
            $table->decimal('amount', 10, 2); // مبلغ پرداختی
            $table->decimal('rate', 12, 4)->nullable(); // نرخ تبدیل ارز نسبت به ریال
            $table->enum('currency', ['IRR', 'USD', 'AED'])->default('IRR');
            $table->enum('payment_type', ['rental_fee', 'prepaid_fine', 'toll', 'fine'])->default('rental_fee'); // نوع پرداخت
            $table->text('description')->nullable(); // توضیحات (در صورت نیاز)
            $table->date('payment_date'); // تاریخ پرداخت
            $table->boolean('is_refundable')->default(false); // آیا این پرداخت بازگشت‌پذیر است؟ (برای پیش‌پرداخت خلافی)
            $table->boolean('is_paid')->default(true); // وضعیت پرداخت            
            $table->string('receipt')->nullable()->after('rate');

            $table->timestamps();
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
