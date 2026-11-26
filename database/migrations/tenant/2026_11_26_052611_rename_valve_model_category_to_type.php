<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('valve_models', function (Blueprint $table) {
            $table->dropUnique('valve_models_name_category_unique');
            $table->dropIndex('valve_models_is_active_category_index');
        });

        Schema::table('valve_models', function (Blueprint $table) {
            $table->renameColumn('category', 'valve_type');
            $table->string('valve_category', 100)->nullable()->after('valve_type');
        });

        Schema::table('valve_models', function (Blueprint $table) {
            $table->index(['is_active', 'valve_category']);
            $table->unique(['name', 'valve_type']);
        });
    }

    public function down(): void
    {
        Schema::table('valve_models', function (Blueprint $table) {
            $table->dropUnique('valve_models_name_valve_type_unique');
            $table->dropIndex('valve_models_is_active_valve_category_index');
        });

        Schema::table('valve_models', function (Blueprint $table) {
            $table->renameColumn('valve_type', 'category');
            $table->dropColumn('valve_category');
        });

        Schema::table('valve_models', function (Blueprint $table) {
            $table->index(['is_active', 'category']);
            $table->unique(['name', 'category']);
        });
    }
};
