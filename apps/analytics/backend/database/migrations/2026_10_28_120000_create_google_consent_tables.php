<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('provider');
            $table->string('text_version');
            $table->timestamp('granted_at');
            $table->timestamp('withdrawn_at')->nullable();

            $table->index(['workspace_id', 'provider', 'text_version']);
        });

        Schema::create('google_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('status');
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->string('text_version');
            $table->string('sync_status');
            $table->timestamp('disconnected_at')->nullable();
            $table->timestamps();

            $table->unique('workspace_id');
        });

        Schema::create('google_sync_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('google_connection_id')->constrained()->cascadeOnDelete();
            $table->string('status');
            $table->timestamps();

            $table->index(['google_connection_id', 'status']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('workspace_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['workspace_id', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('google_sync_jobs');
        Schema::dropIfExists('google_connections');
        Schema::dropIfExists('consents');
    }
};
