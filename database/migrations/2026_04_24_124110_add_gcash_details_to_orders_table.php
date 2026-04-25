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
        Schema::table('orders', function (Blueprint $table) {
            $table->string('gcash_reference')->nullable()->after('payment_type');
            $table->string('gcash_sender_name')->nullable()->after('gcash_reference');
            $table->string('gcash_sender_mobile', 11)->nullable()->after('gcash_sender_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['gcash_reference', 'gcash_sender_name', 'gcash_sender_mobile']);
        });
    }
};
