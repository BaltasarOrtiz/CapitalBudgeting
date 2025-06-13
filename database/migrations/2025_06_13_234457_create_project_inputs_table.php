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
        Schema::create('project_inputs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('optimization_id')->constrained()->onDelete('cascade');
            $table->string('project_name');
            $table->integer('period');
            $table->enum('type', ['cost', 'reward']);
            $table->decimal('amount', 15, 2);
            $table->timestamps();

            // Índices para performance
            $table->index(['optimization_id', 'type']);
            $table->index(['optimization_id', 'project_name']);
            $table->unique(['optimization_id', 'project_name', 'period', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_inputs');
    }
};
