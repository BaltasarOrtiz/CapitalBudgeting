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
        Schema::create('period_cash_flows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('optimization_id')->constrained()->onDelete('cascade');

            // Datos de CashFlowResults.csv
            $table->integer('period');
            $table->decimal('cash_in', 15, 2);
            $table->decimal('cash_out', 15, 2);
            $table->decimal('net_cash_flow', 15, 2);

            $table->timestamps();

            $table->unique(['optimization_id', 'period']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('period_cash_flows');
    }
};
