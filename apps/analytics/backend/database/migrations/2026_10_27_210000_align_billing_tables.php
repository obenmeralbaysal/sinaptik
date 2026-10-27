<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->boolean('is_public')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('period')->default('monthly');
            $table->timestamp('period_start')->nullable();
            $table->string('gateway')->nullable();
            $table->boolean('cancel_at_period_end')->default(false);
            $table->foreignId('pending_plan_id')->nullable()->constrained('plans')->nullOnDelete();
            $table->string('pending_period')->nullable();
            $table->unsignedSmallInteger('dunning_attempts')->default(0);
            $table->timestamp('dunning_started_at')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->nullOnDelete();
            $table->string('event_id')->nullable()->unique();
            $table->string('kind')->nullable();
            $table->string('description')->nullable();
            $table->string('gateway_payment_id')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('paid_at')->nullable();
        });

        Schema::create('billing_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_id')->unique();
            $table->string('type');
            $table->json('payload');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
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
    }

    public function down(): void
    {
        Schema::dropIfExists('usage_counters');
        Schema::dropIfExists('billing_events');

        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('subscription_id');
            $table->dropUnique(['event_id']);
            $table->dropColumn(['event_id', 'kind', 'description', 'gateway_payment_id', 'meta', 'paid_at']);
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pending_plan_id');
            $table->dropColumn([
                'period',
                'period_start',
                'gateway',
                'cancel_at_period_end',
                'pending_period',
                'dunning_attempts',
                'dunning_started_at',
                'trial_ends_at',
            ]);
        });

        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['is_public', 'sort_order']);
        });
    }
};
