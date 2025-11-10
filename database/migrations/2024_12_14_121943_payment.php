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
            $table->decimal('amount', 20, 2); // مبلغ پرداختی
            $table->decimal('rate', 12, 4)->nullable(); // نرخ تبدیل ارز نسبت به ریال
            $table->decimal('amount_in_aed', 20, 2)->nullable(); // مبلغ معادل به درهم
            $table->enum('currency', ['IRR', 'USD', 'EUR', 'AED'])->default('IRR');
            $table->enum('payment_type', ['rental_fee', 'security_deposit', 'salik', 'salik_4_aed', 'salik_6_aed', 'salik_other_revenue', 'fine', 'parking', 'damage', 'discount', 'payment_back', 'carwash', 'fuel'])->default('rental_fee'); // نوع پرداخت
            $table->enum('payment_method', ['cash', 'transfer', 'ticket'])->default('cash');
            $table->text('description')->nullable(); // توضیحات (در صورت نیاز)
            $table->date('payment_date'); // تاریخ پرداخت
            $table->boolean('is_refundable')->default(false); // آیا این پرداخت بازگشت‌پذیر است؟ (برای پیش‌پرداخت خلافی)
            $table->boolean('is_paid')->default(true); // وضعیت پرداخت            
            $table->string('receipt')->nullable()->after('rate');
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])
                ->default('pending')
                ->after('is_paid');

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
