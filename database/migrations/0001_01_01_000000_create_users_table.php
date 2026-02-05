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
        Schema::create('users', function (Blueprint $table) {
            $table->id(); // شناسه اصلی
            $table->string('first_name'); // نام
            $table->string('last_name'); // نام خانوادگی
            $table->string('email')->unique(); // ایمیل (منحصر به‌فرد)
            $table->string('phone')->nullable(); // شماره تماس (اختیاری)
            $table->string('avatar')->nullable(); // تصویر پروفایل
            $table->string('password'); // رمز عبور
            $table->rememberToken(); // توکن یادآوری ورود
            $table->enum('status', ['active', 'inactive'])->default('active'); // وضعیت
            $table->timestamp('last_login')->nullable(); // آخرین ورود
            $table->string('national_code', 10)->nullable(); // کد ملی (اختیاری)
            $table->text('address')->nullable(); // آدرس (اختیاری)
            $table->timestamps(); // زمان‌های ایجاد و ویرایش
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
