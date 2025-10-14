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
        Schema::create('pickup_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contract_id');
            $table->unsignedBigInteger('user_id');
            $table->string('tars_contract')->nullable();
            $table->string('kardo_contract')->nullable();
            $table->string('factor_contract')->nullable();
            $table->string('car_dashboard')->nullable();
            $table->json('car_inside_photos')->nullable();
            $table->json('car_outside_photos')->nullable();
            $table->string('fuelLevel')->nullable();
            $table->string('mileage')->nullable();
            $table->text('note')->nullable();
            $table->text('driver_note')->nullable();

            $table->string('kardo_contract_number')->nullable()->after('kardo_contract');

            $table->timestamp('tars_approved_at')->nullable();
            $table->unsignedBigInteger('tars_approved_by')->nullable();
            $table->foreign('tars_approved_by')->references('id')->on('users')->onDelete('set null');

            $table->timestamp('kardo_approved_at')->nullable();
            $table->unsignedBigInteger('kardo_approved_by')->nullable();
            $table->foreign('kardo_approved_by')->references('id')->on('users')->onDelete('set null');

            $table->timestamps();

            $table->foreign('contract_id')->references('id')->on('contracts')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pickup_documents');
    }
};
