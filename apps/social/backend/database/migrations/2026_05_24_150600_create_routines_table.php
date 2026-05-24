<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('routines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('social_account_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('persona')->nullable();
            $table->string('tone')->default('supportive');
            $table->string('language', 10)->default('en');
            $table->text('topic');
            $table->string('ai_model')->nullable();
            $table->string('schedule_cron')->nullable(); // e.g. "0 8 * * *"
            $table->integer('daily_count')->default(3);
            $table->boolean('generate_images')->default(false);
            $table->string('image_model')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('routines');
    }
};
