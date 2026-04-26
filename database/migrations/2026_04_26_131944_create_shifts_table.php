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
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('shift_id')->unique();
            $table->date('business_date');
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->string('status')->default('ACTIVE'); // ACTIVE or ENDED
            $table->decimal('opening_cash', 10, 2)->nullable();
            $table->decimal('closing_cash', 10, 2)->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
