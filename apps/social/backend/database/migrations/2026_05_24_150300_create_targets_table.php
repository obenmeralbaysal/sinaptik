<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('social_account_id')->nullable()->constrained()->nullOnDelete();
            $table->string('platform')->default('x');
            $table->string('target_username');
            $table->string('ai_tone')->default('supportive');
            $table->text('custom_prompt')->nullable();
            $table->string('ai_model')->nullable();
            $table->integer('daily_limit')->default(10);
            $table->integer('reply_frequency_minutes')->default(60);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['organization_id', 'platform']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('targets');
    }
};
