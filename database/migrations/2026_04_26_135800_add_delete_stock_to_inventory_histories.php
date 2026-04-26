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
        Schema::table('inventory_histories', function (Blueprint $table) {
            $table->enum('action_type', ['ADD_STOCK', 'DEDUCT_STOCK', 'DELETE_STOCK'])->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_histories', function (Blueprint $table) {
            $table->enum('action_type', ['ADD_STOCK', 'DEDUCT_STOCK'])->change();
        });
    }
};
