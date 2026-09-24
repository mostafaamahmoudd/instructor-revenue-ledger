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
        Schema::create('subscription_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_payment_id')->constrained('subscription_payments')->cascadeOnDelete();
            $table->unsignedBigInteger('instructor_id');
            $table->foreignId('course_id')->constrained('courses')->restrictOnDelete();

            $table->unique(['subscription_payment_id', 'instructor_id', 'course_id'], 'idx_enroll_unique');
            $table->index('instructor_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_enrollments');
    }
};
