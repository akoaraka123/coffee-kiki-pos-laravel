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
            $table->string('shift_id')->nullable()->after('user_id');
            $table->date('business_date')->nullable()->after('shift_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_entries', function (Blueprint $table) {
            $table->dropColumn(['shift_id', 'business_date']);
        });
    }
};
