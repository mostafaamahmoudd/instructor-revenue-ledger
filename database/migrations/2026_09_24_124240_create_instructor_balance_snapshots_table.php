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
        Schema::create('instructor_balance_snapshots', function (Blueprint $table) {
            $table->unsignedBigInteger('instructor_id');
            $table->char('currency', 3);
            $table->bigInteger('earned_total_minor');
            $table->bigInteger('paid_total_minor');
            $table->timestamp('computed_at');

            $table->primary(['instructor_id', 'currency']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('instructor_balance_snapshots');
    }
};
