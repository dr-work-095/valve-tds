<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('valve_dimensions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('valve_model_id')->constrained('valve_models')->cascadeOnDelete();
            $table->string('size_inch', 20);
            $table->string('size_dn', 20)->nullable();
            $table->string('pressure_class', 50);
            $table->string('end_connection', 50);
            $table->decimal('face_to_face_mm', 10, 2)->nullable();
            $table->decimal('end_to_end_mm', 10, 2)->nullable();
            $table->decimal('flange_od_mm', 10, 2)->nullable();
            $table->decimal('center_to_top_mm', 10, 2)->nullable();
            $table->decimal('weight_kg', 10, 2)->nullable();
            $table->json('extra_dimensions')->nullable();
            $table->timestamps();

            $table->unique(
                ['valve_model_id', 'size_inch', 'pressure_class', 'end_connection'],
                'valve_dimensions_unique_tuple',
            );
        });

        Schema::create('valve_bom_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('valve_model_id')->constrained('valve_models')->cascadeOnDelete();
            $table->unsignedInteger('part_number');
            $table->string('part_name', 150);
            $table->foreignId('standard_material_id')->constrained('materials')->restrictOnDelete();
            $table->json('optional_material_ids')->nullable();
            $table->timestamps();

            $table->unique(['valve_model_id', 'part_number'], 'valve_bom_templates_unique_part');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('valve_bom_templates');
        Schema::dropIfExists('valve_dimensions');
    }
};
