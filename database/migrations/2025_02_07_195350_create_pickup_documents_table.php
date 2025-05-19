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
            $table->string('car_inside_video')->nullable();
            $table->string('car_outside_video')->nullable();
            $table->string('fuelLevel')->nullable();
            $table->string('mileage')->nullable();
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
