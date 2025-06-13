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
        Schema::create('optimization_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('optimization_id')->constrained()->onDelete('cascade');

            // Resultados principales (SolutionResults.csv)
            $table->decimal('npv', 15, 2);
            $table->decimal('final_balance', 15, 2);
            $table->decimal('initial_balance', 15, 2);
            $table->integer('total_periods');
            $table->integer('total_projects');
            $table->integer('projects_selected');
            $table->string('status');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('optimization_results');
    }
};
