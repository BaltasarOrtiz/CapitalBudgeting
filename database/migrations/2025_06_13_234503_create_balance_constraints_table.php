<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('balance_constraints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('optimization_id')->constrained()->onDelete('cascade');
            $table->integer('period');
            $table->decimal('min_balance', 15, 2);
            $table->timestamps();

            $table->unique(['optimization_id', 'period']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('balance_constraints');
    }
};
