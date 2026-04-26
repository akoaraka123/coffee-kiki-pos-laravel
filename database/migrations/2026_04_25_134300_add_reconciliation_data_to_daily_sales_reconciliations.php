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
        Schema::table('daily_sales_reconciliations', function (Blueprint $table) {
            $table->json('reconciliation_data')->nullable()->after('reconciled_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_sales_reconciliations', function (Blueprint $table) {
            $table->dropColumn('reconciliation_data');
        });
    }
};
