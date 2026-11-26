<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('valve_models', function (Blueprint $table) {
            $table->string('flange_facing_finish')->nullable();
            $table->string('bonnet_cover_type')->nullable();
            $table->string('bore_port_configuration')->nullable();
            $table->string('flow_characteristic', 100)->nullable();
            $table->string('seat_leakage_class', 100)->nullable();
        });

        Schema::table('valve_bom_templates', function (Blueprint $table) {
            $table->string('component_group', 100)->nullable();
            $table->decimal('quantity_per_valve', 10, 3)->default(1);
            $table->string('unit', 20)->default('ea');
            $table->text('notes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('valve_bom_templates', function (Blueprint $table) {
            $table->dropColumn(['component_group', 'quantity_per_valve', 'unit', 'notes']);
        });

        Schema::table('valve_models', function (Blueprint $table) {
            $table->dropColumn([
                'flange_facing_finish',
                'bonnet_cover_type',
                'bore_port_configuration',
                'flow_characteristic',
                'seat_leakage_class',
            ]);
        });
    }
};
