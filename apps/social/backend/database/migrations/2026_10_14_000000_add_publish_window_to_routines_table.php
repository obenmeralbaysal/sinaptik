<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('routines', function (Blueprint $table) {
            $table->unsignedSmallInteger('frequency_days')->default(1);
            $table->string('window_start', 5)->default('09:00');
            $table->string('window_end', 5)->default('10:00');
            $table->string('timezone', 64)->default('Europe/Istanbul');
            $table->date('schedule_anchor')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('routines', function (Blueprint $table) {
            $table->dropColumn([
                'frequency_days',
                'window_start',
                'window_end',
                'timezone',
                'schedule_anchor',
            ]);
        });
    }
};
