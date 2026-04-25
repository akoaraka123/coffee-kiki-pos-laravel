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
        Schema::create('cash_denomination_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->enum('entry_type', ['cash_count', 'gcash_verification'])->default('cash_count');
            
            // Cash denomination breakdown
            $table->integer('denomination_1000')->default(0);
            $table->integer('denomination_500')->default(0);
            $table->integer('denomination_200')->default(0);
            $table->integer('denomination_100')->default(0);
            $table->integer('denomination_50')->default(0);
            $table->integer('denomination_20')->default(0);
            $table->integer('denomination_10')->default(0);
            $table->integer('denomination_5')->default(0);
            $table->integer('denomination_1')->default(0);
            
            // Totals
            $table->decimal('total_cash_counted', 10, 2)->default(0);
            $table->decimal('expected_cash', 10, 2)->default(0);
            $table->decimal('expected_gcash', 10, 2)->default(0);
            $table->decimal('verified_gcash', 10, 2)->default(0);
            
            // Reconciliation
            $table->decimal('cash_difference', 10, 2)->default(0);
            $table->decimal('gcash_difference', 10, 2)->default(0);
            $table->decimal('total_difference', 10, 2)->default(0);
            $table->enum('status', ['balanced', 'short', 'over'])->default('balanced');
            
            // Additional details
            $table->json('gcash_verification_details')->nullable();
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            $table->index(['user_id', 'date']);
            $table->index('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_denomination_entries');
    }
};
