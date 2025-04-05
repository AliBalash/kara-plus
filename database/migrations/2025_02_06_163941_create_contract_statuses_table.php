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
        Schema::create('contract_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->onDelete('cascade');
            $table->enum('status', [
                'pending', 
                'assigned', 
                'under_review', 
                'reserved', 
                'delivery', 
                'agreement_inspection', 
                'awaiting_return', 
                'returned', 
                'payment', 
                'complete', 
                'cancelled', 
                'rejected'
            ]);
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade'); // کاربری که تغییر داده
            $table->text('notes')->nullable();
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_statuses');
    }
};
