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
        Schema::create('selected_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('optimization_id')->constrained()->onDelete('cascade');

            // Datos de SelectedProjectsOutput.csv
            $table->string('project_name');
            $table->integer('start_period');
            $table->decimal('setup_cost', 15, 2);
            $table->decimal('total_reward', 15, 2);
            $table->decimal('npv_contribution', 15, 2);

            $table->timestamps();

            $table->index(['optimization_id', 'project_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('selected_projects');
    }
};
