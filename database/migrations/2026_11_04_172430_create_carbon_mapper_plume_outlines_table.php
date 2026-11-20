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
    Schema::create('carbon_mapper_plume_outlines', function (Blueprint $table) {
        $table->id();

        // Links each outline to its Carbon Mapper observation.
        $table->string('observation_id')->unique();

        // Permanently stores the complete GeoJSON outline.
        $table->longText('geojson');

        // Records when the outline was retrieved successfully.
        $table->timestamp('last_fetched_at')->nullable();

        $table->timestamps();
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carbon_mapper_plume_outlines');
    }
};
