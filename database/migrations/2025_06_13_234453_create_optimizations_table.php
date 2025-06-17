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
        Schema::create('optimizations', function (Blueprint $table) {
            $table->id();
            $table->text('description')->nullable();
            $table->enum('status', ['pending', 'running', 'completed', 'failed'])->default('pending');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('url_status')->nullable(); // URL del job en IBM Watson ML

            // Parámetros del modelo (parameters.csv)
            $table->integer('total_periods');           // T
            $table->decimal('discount_rate', 8, 6);     // Rate
            $table->decimal('initial_balance', 15, 2);  // InitBal
            $table->integer('nb_must_take_one')->default(0); // NbMustTakeOne

            // Archivos y logs
            $table->string('input_files_path', 500)->nullable();
            $table->string('output_files_path', 500)->nullable();
            $table->text('execution_log')->nullable();

            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('optimizations');
    }
};
