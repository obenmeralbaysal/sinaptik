<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('external_subject_id')->nullable()->unique();
            $table->string('issuer')->nullable();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE users ALTER COLUMN password DROP NOT NULL');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("UPDATE users SET password = '' WHERE password IS NULL");
            DB::statement('ALTER TABLE users ALTER COLUMN password SET NOT NULL');
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['external_subject_id']);
            $table->dropColumn(['external_subject_id', 'issuer']);
        });
    }
};
