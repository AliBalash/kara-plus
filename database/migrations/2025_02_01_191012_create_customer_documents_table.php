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
        Schema::create('customer_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('contract_id'); // اضافه کردن فیلد contract_id
            $table->string('visa')->nullable();
            $table->string('passport')->nullable();
            $table->string('license')->nullable();
            $table->string('ticket')->nullable();
            $table->timestamps();

            // تعریف روابط با سایر جداول
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('contract_id')->references('id')->on('contracts')->onDelete('cascade'); // رابطه با جدول contracts
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_documents');
    }
};
