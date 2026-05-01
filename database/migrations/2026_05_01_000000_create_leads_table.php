<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('phone');
            $table->string('messenger_phone')->nullable();
            $table->string('email')->nullable();
            $table->string('source')->nullable();
            $table->string('discovery_source')->nullable();
            $table->string('requested_vehicle')->nullable();
            $table->date('pickup_date')->nullable();
            $table->date('return_date')->nullable();
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->enum('status', ['new', 'follow_up', 'interested', 'not_interested', 'unreachable', 'converted'])->default('new');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('next_follow_up_at')->nullable();
            $table->dateTime('last_contacted_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('converted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('converted_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'priority']);
            $table->index('phone');
            $table->index('next_follow_up_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
