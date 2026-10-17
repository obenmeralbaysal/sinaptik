<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('locale', 8)->default('tr');
            $table->text('totp_secret')->nullable();
            $table->timestamp('totp_confirmed_at')->nullable();
            $table->boolean('is_super_admin')->default(false);
            $table->unsignedBigInteger('current_workspace_id')->nullable()->index();
        });

        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('price_monthly_minor')->default(0);
            $table->unsignedInteger('price_yearly_minor')->default(0);
            $table->string('currency', 3)->default('TRY');
            $table->json('limits');
            $table->json('features')->nullable();
            $table->boolean('is_public')->default(true);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('workspaces', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->foreignId('owner_id')->constrained('users');
            $table->string('timezone')->default('Europe/Istanbul');
            $table->string('locale', 8)->default('tr');
            $table->string('status')->default('active');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('read_only_at')->nullable();
            $table->timestamp('trial_lapsed_at')->nullable();
            $table->timestamp('deletion_scheduled_at')->nullable();
            $table->boolean('require_owner_2fa')->default(false);
            $table->boolean('approval_required')->default(false);
            $table->boolean('ads_auto_apply')->default(false);
            $table->boolean('ads_kill_switch')->default(false);
            $table->unsignedBigInteger('ads_daily_cap_minor')->default(50000);
            $table->unsignedBigInteger('ads_monthly_cap_minor')->default(1000000);
            $table->unsignedSmallInteger('ads_max_increase_percent')->default(20);
            $table->unsignedSmallInteger('ads_cooldown_hours')->default(24);
            $table->boolean('system_user_enabled')->default(false);
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('current_workspace_id')->references('id')->on('workspaces')->nullOnDelete();
        });

        Schema::create('workspace_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role');
            $table->boolean('ads_write')->default(false);
            $table->timestamps();
            $table->unique(['workspace_id', 'user_id']);
        });

        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->timestamps();
            $table->unique(['workspace_id', 'slug']);
        });

        Schema::create('invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->string('email');
            $table->string('role');
            $table->boolean('ads_write')->default(false);
            $table->string('token_hash', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained();
            $table->string('status');
            $table->string('period')->default('monthly');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('current_period_start')->nullable();
            $table->timestamp('current_period_end')->nullable();
            $table->foreignId('pending_plan_id')->nullable()->constrained('plans')->nullOnDelete();
            $table->string('pending_period')->nullable();
            $table->boolean('cancel_at_period_end')->default(false);
            $table->string('gateway')->nullable();
            $table->string('gateway_subscription_id')->nullable();
            $table->unsignedSmallInteger('dunning_attempts')->default(0);
            $table->timestamp('dunning_started_at')->nullable();
            $table->timestamps();
            $table->index(['workspace_id', 'status']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event_id')->nullable()->unique();
            $table->string('kind');
            $table->string('status');
            $table->unsignedInteger('amount_minor')->default(0);
            $table->string('currency', 3)->default('TRY');
            $table->string('description')->nullable();
            $table->string('gateway_payment_id')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('billing_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_id')->unique();
            $table->string('type');
            $table->json('payload');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('workspace_addons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->unsignedInteger('quantity')->default(1);
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->unique(['workspace_id', 'code']);
        });

        Schema::create('credit_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('currency_type');
            $table->integer('delta');
            $table->integer('balance_after');
            $table->string('reason');
            $table->string('reference')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['workspace_id', 'currency_type']);
        });

        Schema::create('usage_counters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('metric');
            $table->string('period', 7);
            $table->unsignedInteger('count')->default(0);
            $table->timestamps();
            $table->unique(['workspace_id', 'metric', 'period']);
        });

        Schema::create('social_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->string('platform');
            $table->string('external_id');
            $table->string('name');
            $table->string('username')->nullable();
            $table->string('parent_external_id')->nullable();
            $table->text('access_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->json('scopes')->nullable();
            $table->string('status')->default('connected');
            $table->timestamp('health_checked_at')->nullable();
            $table->text('health_error')->nullable();
            $table->json('meta')->nullable();
            $table->foreignId('connected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['workspace_id', 'platform', 'external_id']);
        });

        Schema::create('media_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->string('disk');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime')->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('social_account_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('draft');
            $table->string('post_type')->default('feed');
            $table->text('body')->nullable();
            $table->json('media_ids')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->string('external_id')->nullable();
            $table->text('failure_reason')->nullable();
            $table->unsignedSmallInteger('attempt_count')->default(0);
            $table->boolean('quota_counted')->default(false);
            $table->json('platform_options')->nullable();
            $table->timestamps();
            $table->index(['workspace_id', 'status', 'scheduled_at']);
        });

        Schema::create('post_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('pending');
            $table->text('comment')->nullable();
            $table->timestamps();
        });

        Schema::create('account_insights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('social_account_id')->constrained()->cascadeOnDelete();
            $table->date('metric_date');
            $table->json('metrics');
            $table->timestamps();
            $table->unique(['social_account_id', 'metric_date']);
        });

        Schema::create('post_insights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->json('metrics');
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });

        Schema::create('ad_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('social_account_id')->nullable()->constrained()->nullOnDelete();
            $table->string('external_id');
            $table->string('name');
            $table->string('currency', 8)->nullable();
            $table->string('status')->default('active');
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->unique(['workspace_id', 'external_id']);
        });

        Schema::create('ad_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ad_account_id')->constrained()->cascadeOnDelete();
            $table->string('external_id');
            $table->string('name');
            $table->string('status')->nullable();
            $table->string('objective')->nullable();
            $table->unsignedBigInteger('daily_budget_minor')->nullable();
            $table->unsignedBigInteger('lifetime_budget_minor')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->unique(['workspace_id', 'external_id']);
        });

        Schema::create('ad_sets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ad_campaign_id')->constrained()->cascadeOnDelete();
            $table->string('external_id');
            $table->string('name');
            $table->string('status')->nullable();
            $table->unsignedBigInteger('daily_budget_minor')->nullable();
            $table->unsignedBigInteger('lifetime_budget_minor')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->unique(['workspace_id', 'external_id']);
        });

        Schema::create('ads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ad_set_id')->constrained()->cascadeOnDelete();
            $table->string('external_id');
            $table->string('name');
            $table->string('status')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->unique(['workspace_id', 'external_id']);
        });

        Schema::create('ad_insights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('level');
            $table->unsignedBigInteger('subject_id');
            $table->date('metric_date');
            $table->json('metrics');
            $table->timestamps();
            $table->unique(['level', 'subject_id', 'metric_date']);
        });

        Schema::create('ad_proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ad_account_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('ad_campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('ad_set_id')->nullable()->constrained()->nullOnDelete();
            $table->string('kind');
            $table->string('status')->default('proposed');
            $table->json('payload');
            $table->json('guardrail')->nullable();
            $table->string('source')->default('user');
            $table->foreignId('proposed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('api_key_id')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('ad_change_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('proposal_id')->nullable()->constrained('ad_proposals')->nullOnDelete();
            $table->foreignId('ad_set_id')->nullable()->constrained()->nullOnDelete();
            $table->string('external_id')->nullable();
            $table->string('field');
            $table->text('previous_value')->nullable();
            $table->text('new_value')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->timestamp('reverted_at')->nullable();
            $table->string('actor_type')->nullable();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->timestamps();
        });

        Schema::create('automation_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->boolean('enabled')->default(true);
            $table->json('definition');
            $table->timestamp('last_ran_at')->nullable();
            $table->timestamps();
        });

        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('prefix', 16);
            $table->string('key_hash', 64)->unique();
            $table->json('scopes');
            $table->timestamp('last_used_at')->nullable();
            $table->string('last_used_ip', 45)->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('ad_proposals', function (Blueprint $table) {
            $table->foreign('api_key_id')->references('id')->on('api_keys')->nullOnDelete();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->nullable()->constrained()->nullOnDelete();
            $table->string('actor_type');
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('action');
            $table->string('tool')->nullable();
            $table->text('params_summary')->nullable();
            $table->string('result')->default('ok');
            $table->integer('cost_minor')->nullable();
            $table->integer('budget_delta_minor')->nullable();
            $table->string('ip', 45)->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['workspace_id', 'created_at']);
        });

        Schema::create('legal_pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug');
            $table->string('locale', 8)->default('tr');
            $table->string('title');
            $table->longText('body_markdown');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['slug', 'locale']);
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->json('value')->nullable();
            $table->timestamps();
        });

        Schema::create('oauth_clients', function (Blueprint $table) {
            $table->id();
            $table->string('client_id')->unique();
            $table->string('name');
            $table->json('redirect_uris');
            $table->foreignId('workspace_id')->nullable()->constrained()->nullOnDelete();
            $table->string('token_endpoint_auth_method')->default('none');
            $table->timestamps();
        });

        Schema::create('oauth_auth_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code_hash', 64)->unique();
            $table->string('client_id');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->json('scopes');
            $table->string('code_challenge');
            $table->string('code_challenge_method')->default('S256');
            $table->string('redirect_uri');
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });

        Schema::create('oauth_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('token_hash', 64)->unique();
            $table->string('refresh_token_hash', 64)->nullable()->unique();
            $table->string('client_id');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->json('scopes');
            $table->timestamp('expires_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });

        Schema::create('data_deletion_requests', function (Blueprint $table) {
            $table->id();
            $table->string('confirmation_code')->unique();
            $table->string('platform')->default('meta');
            $table->string('status')->default('pending');
            $table->string('external_user_id')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('data_deletion_requests');
        Schema::dropIfExists('oauth_access_tokens');
        Schema::dropIfExists('oauth_auth_codes');
        Schema::dropIfExists('oauth_clients');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('legal_pages');
        Schema::dropIfExists('audit_logs');
        Schema::table('ad_proposals', function (Blueprint $table) {
            $table->dropForeign(['api_key_id']);
        });
        Schema::dropIfExists('api_keys');
        Schema::dropIfExists('automation_rules');
        Schema::dropIfExists('ad_change_logs');
        Schema::dropIfExists('ad_proposals');
        Schema::dropIfExists('ad_insights');
        Schema::dropIfExists('ads');
        Schema::dropIfExists('ad_sets');
        Schema::dropIfExists('ad_campaigns');
        Schema::dropIfExists('ad_accounts');
        Schema::dropIfExists('post_insights');
        Schema::dropIfExists('account_insights');
        Schema::dropIfExists('post_approvals');
        Schema::dropIfExists('posts');
        Schema::dropIfExists('media_assets');
        Schema::dropIfExists('social_accounts');
        Schema::dropIfExists('usage_counters');
        Schema::dropIfExists('credit_ledger');
        Schema::dropIfExists('workspace_addons');
        Schema::dropIfExists('billing_events');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('invitations');
        Schema::dropIfExists('brands');
        Schema::dropIfExists('workspace_user');
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['current_workspace_id']);
            $table->dropColumn(['locale', 'totp_secret', 'totp_confirmed_at', 'is_super_admin', 'current_workspace_id']);
        });
        Schema::dropIfExists('workspaces');
        Schema::dropIfExists('plans');
    }
};
