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
        Schema::table('payment_entries', function (Blueprint $table) {
            $table->string('gcash_sender_name')->nullable()->after('order_id');
            $table->string('gcash_reference_number')->nullable()->after('gcash_sender_name');
            $table->string('gcash_sender_mobile')->nullable()->after('gcash_reference_number');
            $table->string('gcash_proof_image')->nullable()->after('gcash_sender_mobile');
            $table->timestamp('verified_at')->nullable()->after('gcash_proof_image');
            $table->unsignedBigInteger('verified_by')->nullable()->after('verified_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_entries', function (Blueprint $table) {
            $table->dropColumn([
                'gcash_sender_name',
                'gcash_reference_number',
                'gcash_sender_mobile',
                'gcash_proof_image',
                'verified_at',
                'verified_by',
            ]);
        });
    }
};
