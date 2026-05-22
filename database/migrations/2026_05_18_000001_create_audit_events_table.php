<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('event_uuid')->unique();
            $table->timestamp('occurred_at')->index();

            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('actor_role_snapshot')->nullable();

            $table->string('ip', 64)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('route_name')->nullable();
            $table->string('method', 16)->nullable();
            $table->text('url')->nullable();
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->uuid('request_id')->nullable();
            $table->string('session_id_hash', 128)->nullable();

            $table->string('entity_type')->nullable();
            $table->string('entity_id')->nullable();
            $table->string('action', 64)->index();

            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->json('changed_fields')->nullable();
            $table->json('meta')->nullable();

            $table->enum('export_status', ['pending', 'exported', 'failed'])->default('pending')->index();
            $table->unsignedSmallInteger('export_attempts')->default(0);
            $table->timestamp('last_export_attempt_at')->nullable();
            $table->timestamp('exported_at')->nullable();
            $table->text('export_last_error')->nullable();
            $table->string('elastic_document_id')->nullable();

            $table->timestamps();

            $table->index(['actor_user_id', 'occurred_at']);
            $table->index(['route_name', 'action', 'occurred_at']);
            $table->index(['entity_type', 'entity_id', 'occurred_at']);
            $table->index(['request_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_events');
    }
};
