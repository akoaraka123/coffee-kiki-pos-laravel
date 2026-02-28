<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('total_amount', 12, 2)->default(0)->after('customer_name');
            $table->string('payment_type')->default('cash')->after('status');
            $table->decimal('cash_received', 12, 2)->nullable()->after('payment_type');
            $table->decimal('change_amount', 12, 2)->default(0)->after('cash_received');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['total_amount', 'payment_type', 'cash_received', 'change_amount']);
        });
    }
};
