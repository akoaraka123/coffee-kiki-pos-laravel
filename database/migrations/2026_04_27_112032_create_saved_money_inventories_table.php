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
        Schema::create('saved_money_inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->timestamp('saved_at')->nullable();
            $table->decimal('total_sales', 10, 2)->default(0);
            $table->decimal('cash_total', 10, 2)->default(0);
            $table->decimal('gcash_total', 10, 2)->default(0);
            $table->decimal('total_verified', 10, 2)->default(0);
            $table->decimal('difference', 10, 2)->default(0);
            $table->string('status')->default('pending');
            $table->json('cash_breakdown')->nullable();
            $table->json('gcash_details')->nullable();
            $table->json('payment_entries')->nullable();
            $table->string('shift_id')->nullable();
            $table->date('business_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saved_money_inventories');
    }
};
