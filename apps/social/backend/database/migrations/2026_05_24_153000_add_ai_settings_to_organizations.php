<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('ai_driver')->nullable()->after('settings');
            $table->text('ai_gemini_api_key')->nullable()->after('ai_driver');
            $table->text('ai_openai_api_key')->nullable()->after('ai_gemini_api_key');
            $table->string('ai_default_text_model')->nullable()->after('ai_openai_api_key');
            $table->string('ai_default_image_model')->nullable()->after('ai_default_text_model');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn([
                'ai_driver',
                'ai_gemini_api_key',
                'ai_openai_api_key',
                'ai_default_text_model',
                'ai_default_image_model',
            ]);
        });
    }
};
