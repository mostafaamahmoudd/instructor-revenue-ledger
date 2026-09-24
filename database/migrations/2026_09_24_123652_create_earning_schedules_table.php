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
        Schema::create('earning_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('allocation_id')->constrained('subscription_payment_allocations');
            $table->unsignedBigInteger('instructor_id');
            $table->date('earn_date');
            $table->bigInteger('amount_minor');
            $table->char('currency', 3);
            $table->timestamp('recognized_at')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->foreignId('voided_by_refund_id')->nullable()->constrained('subscription_refunds');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['allocation_id', 'earn_date'], 'idx_earn_unique');
            $table->index(['instructor_id', 'recognized_at', 'voided_at'], 'idx_earn_balance');
            $table->index(['earn_date', 'recognized_at'], 'idx_earn_recognition_sweep');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('earning_schedules');
    }
};
