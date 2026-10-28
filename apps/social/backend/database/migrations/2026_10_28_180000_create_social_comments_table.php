<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('social_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('post_id')->nullable()->constrained()->nullOnDelete();
            $table->string('platform');
            $table->string('external_id');
            $table->string('parent_external_id')->nullable();
            $table->string('author_external_id')->nullable();
            $table->string('author_username')->nullable();
            $table->text('body')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamp('hidden_at')->nullable();
            $table->timestamp('purged_at')->nullable();
            $table->json('raw')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->unique(['social_account_id', 'external_id']);
            $table->index(['workspace_id', 'posted_at']);
            $table->index(['post_id', 'posted_at']);
        });

        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared(<<<'SQL'
            ALTER TABLE social_comments ENABLE ROW LEVEL SECURITY;
            ALTER TABLE social_comments FORCE ROW LEVEL SECURITY;
            DROP POLICY IF EXISTS tenant_isolation ON social_comments;
            CREATE POLICY tenant_isolation ON social_comments
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
        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared('DROP POLICY IF EXISTS tenant_isolation ON social_comments; ALTER TABLE social_comments NO FORCE ROW LEVEL SECURITY; ALTER TABLE social_comments DISABLE ROW LEVEL SECURITY;');
        }

        Schema::dropIfExists('social_comments');
    }
};
