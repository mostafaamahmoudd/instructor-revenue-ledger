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
        Schema::create('subscription_payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_payment_id')->constrained('subscription_payments')->restrictOnDelete();
            $table->unsignedBigInteger('instructor_id');
            $table->bigInteger('amount_minor');
            $table->char('currency', 3);
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['subscription_payment_id', 'instructor_id'], 'idx_alloc_payment_instructor');
            $table->index('instructor_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_payment_allocations');
    }
};
