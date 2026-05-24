<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('provider'); // x | instagram | facebook
            $table->string('provider_user_id')->nullable();
            $table->string('username')->nullable();
            $table->string('display_name')->nullable();
            $table->string('avatar_url')->nullable();
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('scopes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('ai_model')->nullable();
            $table->integer('reply_frequency_minutes')->default(10);
            $table->integer('daily_reply_limit')->default(10);
            $table->timestamp('last_reply_check_at')->nullable();
            $table->jsonb('settings')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'provider', 'provider_user_id']);
            $table->index(['organization_id', 'provider']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_accounts');
    }
};
