<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('valve_models', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category', 100);
            $table->string('design_standard');
            $table->string('testing_standard');
            $table->string('face_to_face_standard')->nullable();
            $table->string('end_connection_standard')->nullable();
            $table->string('schematic_image_path', 500)->nullable();
            $table->json('custom_attributes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'category']);
            $table->unique(['name', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('valve_models');
    }
};
