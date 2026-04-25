<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('daily_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('selected_date');
            $table->decimal('total_sales', 10, 2)->default(0);
            $table->decimal('expected_cash', 10, 2)->default(0);
            $table->decimal('verified_cash', 10, 2)->default(0);
            $table->decimal('expected_gcash', 10, 2)->default(0);
            $table->decimal('verified_gcash', 10, 2)->default(0);
            $table->decimal('total_expected', 10, 2)->default(0);
            $table->decimal('total_verified', 10, 2)->default(0);
            $table->decimal('difference', 10, 2)->default(0);
            $table->string('status')->default('Balanced');
            $table->json('cash_denomination_breakdown')->nullable();
            $table->json('verified_gcash_transactions')->nullable();
            $table->integer('number_of_gcash_transactions')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'selected_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_reconciliations');
    }
};
