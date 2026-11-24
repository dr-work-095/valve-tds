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
        Schema::create('company_settings', function (Blueprint $table) {
            $table->increments('id');
            $table->string('company_name');
            $table->text('address')->nullable();
            $table->string('tax_id_gst', 50)->nullable();
            $table->string('logo_path', 500)->nullable();
            $table->string('header_text')->nullable();
            $table->string('footer_text')->nullable();
            $table->json('default_notes')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_settings');
    }
};
