<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ai_feedback', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ai_insight_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('feature', 64)->index();
            $table->string('entity_type', 64)->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->boolean('helpful');
            $table->string('reason', 64)->nullable();
            $table->timestamps();
            $table->index(['feature', 'entity_type', 'entity_id'], 'ai_feedback_scope');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_feedback');
    }
};
