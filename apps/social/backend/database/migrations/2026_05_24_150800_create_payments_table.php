<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->integer('amount_cents');
            $table->string('currency', 3)->default('TRY');
            $table->string('provider')->default('manual'); // paytr|stripe|manual
            $table->string('status')->default('pending');  // pending|completed|failed|refunded
            $table->string('transaction_id')->nullable();
            $table->jsonb('raw_payload')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
