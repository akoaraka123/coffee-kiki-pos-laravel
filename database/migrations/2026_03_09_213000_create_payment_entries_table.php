<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('payment_type', 20);
            $table->unsignedInteger('received_amount')->default(0);
            $table->timestamps();

            $table->index(['date', 'user_id', 'payment_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_entries');
    }
};
