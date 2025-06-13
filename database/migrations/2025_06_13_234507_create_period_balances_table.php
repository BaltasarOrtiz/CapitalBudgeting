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
        Schema::create('period_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('optimization_id')->constrained()->onDelete('cascade');

            // Datos de BalanceResults.csv
            $table->integer('period');
            $table->decimal('balance', 15, 2);
            $table->decimal('discounted_balance', 15, 2);

            $table->timestamps();

            $table->unique(['optimization_id', 'period']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('period_balances');
    }
};
