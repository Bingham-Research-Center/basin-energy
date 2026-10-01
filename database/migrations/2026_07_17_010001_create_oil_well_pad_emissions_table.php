<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oil_well_pad_emissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('sample_number')->unique();
            $table->string('sample_type', 80)->index();

            $table->double('methane_g_hr')->nullable()->index();
            $table->double('carbon_dioxide_g_hr')->nullable();
            $table->double('tnmhc_g_hr')->nullable();
            $table->double('alkanes_g_hr')->nullable();
            $table->double('alkenes_alkyne_g_hr')->nullable();
            $table->double('aromatics_g_hr')->nullable();
            $table->double('alcohols_g_hr')->nullable();
            $table->double('carbonyls_g_hr')->nullable();
            $table->double('total_organic_compounds_g_hr')->nullable()->index();

            $table->text('notes')->nullable();
            $table->json('compound_emissions')->nullable();
            $table->string('source_file')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oil_well_pad_emissions');
    }
};
