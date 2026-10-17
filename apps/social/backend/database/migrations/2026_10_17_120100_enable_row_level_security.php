<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $tables = [
        'social_accounts',
        'media_assets',
        'posts',
        'account_insights',
        'post_insights',
        'ad_accounts',
        'ad_campaigns',
        'ad_sets',
        'ads',
        'ad_insights',
        'ad_proposals',
        'ad_change_logs',
        'api_keys',
        'audit_logs',
    ];

    public function up(): void
    {
        $driver = DB::getDriverName();
        if ($driver !== 'pgsql') {
            return;
        }

        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION sinsocial_setting_id(setting text) RETURNS bigint
            LANGUAGE plpgsql STABLE AS $$
            DECLARE raw text;
            BEGIN
                raw := current_setting(setting, true);
                IF raw IS NULL OR raw !~ '^[0-9]+$' THEN
                    RETURN NULL;
                END IF;
                RETURN raw::bigint;
            END;
            $$;
        SQL);

        foreach ($this->tables as $table) {
            $extra = $table === 'media_assets'
                ? 'OR id = sinsocial_setting_id(\'app.media_access_id\')'
                : '';

            DB::unprepared(<<<SQL
                ALTER TABLE {$table} ENABLE ROW LEVEL SECURITY;
                ALTER TABLE {$table} FORCE ROW LEVEL SECURITY;
                DROP POLICY IF EXISTS tenant_isolation ON {$table};
                CREATE POLICY tenant_isolation ON {$table}
                    USING (
                        current_setting('app.bypass_rls', true) = 'on'
                        OR workspace_id = sinsocial_setting_id('app.workspace_id')
                        {$extra}
                    )
                    WITH CHECK (
                        current_setting('app.bypass_rls', true) = 'on'
                        OR workspace_id = sinsocial_setting_id('app.workspace_id')
                        {$extra}
                    );
            SQL);
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach ($this->tables as $table) {
            DB::unprepared("DROP POLICY IF EXISTS tenant_isolation ON {$table}; ALTER TABLE {$table} NO FORCE ROW LEVEL SECURITY; ALTER TABLE {$table} DISABLE ROW LEVEL SECURITY;");
        }
        DB::unprepared('DROP FUNCTION IF EXISTS sinsocial_setting_id(text);');
    }
};
