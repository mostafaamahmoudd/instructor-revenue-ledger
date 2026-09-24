<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained('subscriptions');
            $table->bigInteger('amount_minor');
            $table->bigInteger('platform_cut_minor');
            $table->char('currency', 3);
            $table->string('idempotency_key', 191)->unique();
            $table->string('provider_reference')->nullable();
            $table->string('status', 20)->default('confirmed');
            $table->timestamp('paid_at');
            $table->timestamp('created_at')->useCurrent();

            $table->index('subscription_id');
            $table->index('paid_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');
    }
};
