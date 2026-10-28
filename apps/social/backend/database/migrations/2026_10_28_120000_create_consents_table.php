<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
            $table->timestamps();
            $table->index(['workspace_id', 'provider', 'text_version']);
        });

        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared(<<<'SQL'
            ALTER TABLE consents ENABLE ROW LEVEL SECURITY;
            ALTER TABLE consents FORCE ROW LEVEL SECURITY;
            DROP POLICY IF EXISTS tenant_isolation ON consents;
            CREATE POLICY tenant_isolation ON consents
                USING (
                    current_setting('app.bypass_rls', true) = 'on'
                    OR workspace_id = sinsocial_setting_id('app.workspace_id')
                )
                WITH CHECK (
                    current_setting('app.bypass_rls', true) = 'on'
                    OR workspace_id = sinsocial_setting_id('app.workspace_id')
                );
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('consents');
    }
};
