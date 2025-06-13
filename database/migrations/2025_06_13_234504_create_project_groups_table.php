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
        Schema::create('project_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('optimization_id')->constrained()->onDelete('cascade');
            $table->integer('group_id');
            $table->string('project_name');
            $table->timestamps();

            $table->index(['optimization_id', 'group_id']);
            $table->unique(['optimization_id', 'group_id', 'project_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_groups');
    }
};
