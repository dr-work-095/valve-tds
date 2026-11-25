<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generated_datasheets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('created_by_user_id');
            $table->foreignId('valve_model_id')->constrained('valve_models')->restrictOnDelete();
            $table->string('document_number', 100);
            $table->string('revision', 10)->default('Rev 0');
            $table->unsignedBigInteger('parent_datasheet_id')->nullable();
            $table->string('client_name', 255);
            $table->string('project_reference', 255)->nullable();
            $table->json('selected_specs');
            $table->string('status', 20)->default('queued');
            $table->string('pdf_path', 500)->nullable();
            $table->timestamps();

            $table->foreign('parent_datasheet_id')
                ->references('id')
                ->on('generated_datasheets')
                ->nullOnDelete();
            $table->unique(['document_number', 'revision'], 'generated_datasheets_document_revision_unique');
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generated_datasheets');
    }
};
