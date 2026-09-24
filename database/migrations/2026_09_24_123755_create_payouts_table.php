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
        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payout_batch_id')->constrained('payout_batches');
            $table->unsignedBigInteger('instructor_id');
            $table->enum('instructor_type', ['instructor'])->default('instructor');
            $table->string('period_key', 20);
            $table->bigInteger('amount_minor');
            $table->char('currency', 3);
            $table->string('status', 20)->default('pending');
            $table->char('provider_idempotency_key', 36)->unique();
            $table->string('provider_reference')->nullable();
            $table->smallInteger('attempts')->default(0);
            $table->boolean('requires_manual_review')->default(false);
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamps();

            $table->unique(['instructor_id', 'period_key', 'currency'], 'idx_payout_idempotency');
            $table->index(['status', 'last_checked_at'], 'idx_payout_status_check');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payouts');
    }
};
