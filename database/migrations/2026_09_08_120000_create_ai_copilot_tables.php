<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ai_insights', function (Blueprint $table): void {
            $table->id();
            $table->string('scope', 64)->default('panel');
            $table->string('entity_type', 64)->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->string('feature', 64);
            $table->string('prompt_version', 64);
            $table->string('input_hash', 64);
            $table->json('response_json');
            $table->timestamp('generated_at');
            $table->timestamp('expires_at')->index();
            $table->timestamps();
            $table->index(['feature', 'entity_type', 'entity_id', 'input_hash'], 'ai_insight_lookup');
        });

        Schema::create('ai_runs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('request_id')->index();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('feature', 64)->index();
            $table->string('entity_type', 64)->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->string('prompt_version', 64);
            $table->string('input_hash', 64);
            $table->string('provider', 32)->nullable();
            $table->string('model', 128)->nullable();
            $table->string('strategy', 32)->default('fallback_chain');
            $table->string('status', 32);
            $table->unsignedInteger('latency_ms')->nullable();
            $table->unsignedInteger('input_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();
            $table->boolean('cached')->default(false);
            $table->string('error_class', 128)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_runs');
        Schema::dropIfExists('ai_insights');
    }
};
