<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_entry_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_entry_id')->constrained('payment_entries')->cascadeOnDelete();
            $table->unsignedInteger('denomination');
            $table->unsignedInteger('quantity')->default(0);
            $table->timestamps();

            $table->unique(['payment_entry_id', 'denomination']);
            $table->index(['denomination']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_entry_items');
    }
};
